<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAdImagesJob;
use App\Models\Ad;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;

class AdController extends Controller
{
    public function index(Request $request)
    {
        $query = Ad::with(['user', 'category']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('uid', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by account status - exclude deleted account ads by default
        if ($request->filled('account_status')) {
            $query->where('account_status', $request->account_status);
        } else {
            $query->where('account_status', '!=', 'deleted_account');
        }

        // Filter by pending changes
        if ($request->filled('has_pending_changes')) {
            $query->whereNotNull('pending_changes');
        }

        $ads = $query->latest()->paginate(20);

        $statusCounts = [
            'all' => Ad::count(),
            'pending' => Ad::where('status', 'pending')->count(),
            'active' => Ad::where('status', 'active')->count(),
            'rejected' => Ad::where('status', 'rejected')->count(),
            'expired' => Ad::where('status', 'expired')->count(),
            'with_pending_changes' => Ad::whereNotNull('pending_changes')->count(),
        ];

        return view('admin.ads.index', compact('ads', 'statusCounts'));
    }

    public function deletedAccountAds(Request $request)
    {
        $query = Ad::where('account_status', 'deleted_account')
            ->with(['user', 'category']);

        // Filter by user_id if provided
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('uid', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $ads = $query->latest()->paginate(20);

        return view('admin.ads.deleted-account-ads', compact('ads'));
    }

    public function create()
    {
        // Load categories with all subcategories recursively (like frontend)
        $categories = Category::active()
            ->with(['subcategories' => function($q) {
                $q->whereNull('parent_subcategory_id')
                  ->active()
                  ->with(['children' => function($childQuery) {
                      $childQuery->active()->ordered();
                  }])
                  ->orderBy('order')
                  ->select('id', 'category_id', 'parent_subcategory_id', 'name_ar', 'name_en', 'name_tr');
            }])
            ->ordered()
            ->get();

        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.ads.create', compact('categories', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:subcategories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|in:SYP,TRY,USD,EUR',
            'price_type' => 'nullable|in:fixed,negotiable,free',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'status' => 'required|in:pending,active,rejected',
            'is_featured' => 'boolean',
            'is_urgent' => 'boolean',
            'expires_at' => 'nullable|date',
        ]);

        $category = Category::findOrFail($request->category_id);
        $subcategory = Subcategory::findOrFail($request->subcategory_id);

        // Validate custom fields (aligned with frontend flow)
        $rules = [];
        $customFields = [];
        $categoryFields = collect($category->custom_fields ?? [])
            ->filter(function($field) {
                return $field['is_active'] ?? true;
            });

        foreach ($categoryFields as $index => $field) {
            $fieldId = $field['id'] ?? 'field_' . $index;
            $fieldType = $field['type'] ?? 'text';
            $isRequired = $field['required'] ?? false;

            $isNumberWithCurrency = $fieldType === 'number' && !empty($field['show_currency']);

            if ($isRequired) {
                if ($fieldType === 'location') {
                    $rules["custom_fields.$fieldId.lat"] = 'required|numeric|between:-90,90';
                    $rules["custom_fields.$fieldId.lng"] = 'required|numeric|between:-180,180';
                } elseif ($isNumberWithCurrency) {
                    $rules["custom_fields.$fieldId.value"] = 'required|numeric';
                } else {
                    $rules["custom_fields.$fieldId"] = 'required';
                }
            } else {
                if ($fieldType === 'location') {
                    $rules["custom_fields.$fieldId.lat"] = 'nullable|numeric|between:-90,90';
                    $rules["custom_fields.$fieldId.lng"] = 'nullable|numeric|between:-180,180';
                    $rules["custom_fields.$fieldId.address"] = 'nullable|string|max:255';
                } elseif ($isNumberWithCurrency) {
                    $rules["custom_fields.$fieldId.value"] = 'nullable|numeric';
                    $rules["custom_fields.$fieldId.currency"] = 'nullable|in:SYP,TRY,USD,EUR';
                } else {
                    $rules["custom_fields.$fieldId"] = 'nullable';
                }
            }

            if ($fieldType === 'number' && !$isNumberWithCurrency) {
                $rules["custom_fields.$fieldId"] .= '|numeric';
                if (isset($field['min'])) {
                    $rules["custom_fields.$fieldId"] .= '|min:' . $field['min'];
                }
                if (isset($field['max'])) {
                    $rules["custom_fields.$fieldId"] .= '|max:' . $field['max'];
                }
            }
            if ($isNumberWithCurrency) {
                if (isset($field['min'])) {
                    $rules["custom_fields.$fieldId.value"] .= '|min:' . $field['min'];
                }
                if (isset($field['max'])) {
                    $rules["custom_fields.$fieldId.value"] .= '|max:' . $field['max'];
                }
                $rules["custom_fields.$fieldId.currency"] = 'nullable|in:SYP,TRY,USD,EUR';
            }
        }

        $validatedCustomFields = $rules ? $request->validate($rules) : [];

        if (!empty($validatedCustomFields['custom_fields'])) {
            foreach ($validatedCustomFields['custom_fields'] as $fieldId => $value) {
                // Find field type from categoryFields
                $fieldType = 'text';
                foreach ($categoryFields as $field) {
                    $currentFieldId = $field['id'] ?? 'field_' . array_search($field, $categoryFields->toArray());
                    if ($currentFieldId == $fieldId) {
                        $fieldType = $field['type'] ?? 'text';
                        break;
                    }
                }

                if ($fieldType === 'location' && is_array($value)) {
                    $customFields[$fieldId] = [
                        'lat' => $value['lat'] ?? null,
                        'lng' => $value['lng'] ?? null,
                        'address' => $value['address'] ?? null,
                    ];
                } elseif (is_array($value) && array_key_exists('value', $value)) {
                    $customFields[$fieldId] = [
                        'value' => $value['value'] ?? null,
                        'currency' => !empty($value['currency']) ? $value['currency'] : \App\Models\Setting::get('default_currency', 'SYP'),
                    ];
                } else {
                    $customFields[$fieldId] = $value;
                }
            }
        }

        // Handle images upload (تحويل WebP بعد الاستجابة)
        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $images[] = store_ad_image_raw($image);
            }
        }

        $isFeatured = $request->has('is_featured');
        $isUrgent = $request->has('is_urgent');
        $user = \App\Models\User::find($request->user_id);

        // Admin can set featured/urgent; if user has subscription, increment their counters
        if ($user && ($isFeatured || $isUrgent)) {
            if ($isFeatured) {
                $user->consumeFeaturedQuota();
            }
            if ($isUrgent) {
                $user->consumeUrgentQuota();
            }
        }

        // Create the ad
        $ad = Ad::create([
            'user_id' => $request->user_id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'currency' => $request->currency ?? \App\Models\Setting::get('default_currency', 'SYP'),
            'price_type' => $request->price_type ?? 'fixed',
            'images' => $images,
            'custom_fields' => $customFields,
            'status' => $request->status,
            'is_featured' => $isFeatured,
            'is_urgent' => $isUrgent,
            'published_at' => $request->status === 'active' ? now() : null,
            'expires_at' => $request->expires_at ? now()->parse($request->expires_at) : now()->addMonths(1),
        ]);

        ActivityLog::log('ad_created', $ad, ['created_by_admin' => true]);

        if ($images !== []) {
            ProcessAdImagesJob::dispatch($ad->id, $images)->afterResponse();
        }

        return redirect()->route('admin.ads.show', $ad->uid)
            ->with('success', 'تم إضافة الإعلان بنجاح');
    }

    public function show($uid)
    {
        $ad = Ad::where('uid', $uid)->with(['user', 'category', 'subcategory.parent.parent.parent'])->firstOrFail();
        return view('admin.ads.show', compact('ad'));
    }

    public function approve($uid)
    {
        $ad = Ad::where('uid', $uid)->with('category')->firstOrFail();

        // If there are pending changes, apply them first
        $hasPendingChanges = !empty($ad->pending_changes);
        if ($hasPendingChanges) {
            $pendingChanges = $ad->pending_changes;
            $updateData = [
                'status' => 'active',
                'published_at' => now(),
                'rejection_reason' => null,
                'pending_changes' => null, // Clear pending changes
            ];

            // Apply pending changes
            if (isset($pendingChanges['title'])) {
                $updateData['title'] = $pendingChanges['title'];
            }
            if (isset($pendingChanges['description'])) {
                $updateData['description'] = $pendingChanges['description'];
            }
            if (isset($pendingChanges['price'])) {
                $priceVal = $pendingChanges['price'];
                if (is_array($priceVal) && array_key_exists('value', $priceVal)) {
                    $updateData['price'] = isset($priceVal['value']) && $priceVal['value'] !== '' && $priceVal['value'] !== null
                        ? (float) $priceVal['value'] : null;
                    if (!empty($priceVal['currency'])) {
                        $updateData['currency'] = $priceVal['currency'];
                    }
                } else {
                    $updateData['price'] = is_numeric($priceVal) ? (float) $priceVal : null;
                }
            }
            if (isset($pendingChanges['currency']) && !isset($updateData['currency'])) {
                $updateData['currency'] = $pendingChanges['currency'];
            }
            if (isset($pendingChanges['custom_fields'])) {
                // Merge with existing custom_fields so unchanged values are preserved
                $updateData['custom_fields'] = array_merge(
                    $ad->custom_fields ?? [],
                    $pendingChanges['custom_fields']
                );
            }
            if (isset($pendingChanges['images'])) {
                // Delete old images
                if ($ad->images) {
                    foreach ($ad->images as $oldImage) {
                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($oldImage)) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($oldImage);
                        }
                    }
                }
                $updateData['images'] = $pendingChanges['images'];
            }
            if (isset($pendingChanges['video'])) {
                $oldVideo = is_string($ad->video ?? null) ? trim((string) $ad->video) : '';
                if ($oldVideo !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldVideo)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldVideo);
                }
                $updateData['video'] = $pendingChanges['video'];
            }

            if (!isset($updateData['price'])) {
                $mergedCustom = array_merge($ad->custom_fields ?? [], $pendingChanges['custom_fields'] ?? []);
                foreach ($mergedCustom as $val) {
                    if (is_array($val) && isset($val['value'], $val['currency']) && ($val['value'] !== '' && $val['value'] !== null)) {
                        $updateData['price'] = is_numeric($val['value']) ? (float) $val['value'] : null;
                        $updateData['currency'] = $val['currency'] ?? $ad->currency;
                        break;
                    }
                }
            }

            // استخراج الموقع من الحقل المخصص (location) لملء أعمدة الإعلان
            $mergedCustom = $updateData['custom_fields'] ?? $ad->custom_fields ?? [];
            $category = $ad->category;
            if ($category && is_array($category->custom_fields ?? null)) {
                foreach ($category->custom_fields as $field) {
                    if (($field['type'] ?? '') === 'location') {
                        $fieldId = $field['id'] ?? null;
                        if ($fieldId !== null && isset($mergedCustom[$fieldId]) && is_array($mergedCustom[$fieldId])) {
                            $loc = $mergedCustom[$fieldId];
                            $lat = $loc['latitude'] ?? $loc['lat'] ?? null;
                            $lng = $loc['longitude'] ?? $loc['lng'] ?? null;
                            if ($lat !== null && $lng !== null && $lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng)) {
                                $updateData['latitude'] = (float) $lat;
                                $updateData['longitude'] = (float) $lng;
                                $updateData['location_address'] = $loc['address'] ?? $ad->location_address ?? '';
                                break;
                            }
                        }
                    }
                }
            }

            $ad->updateQuietly($updateData);
            ActivityLog::log('ad_changes_approved', $ad);
        } else {
            $ad->updateQuietly([
                'status' => 'active',
                'published_at' => now(),
                'rejection_reason' => null,
            ]);
            ActivityLog::log('ad_approved', $ad);
        }

        Ad::bumpApiListingCaches();

        // Send notification to ad owner when approved
        try {
            $ad->user->notify(new \App\Notifications\AdApprovedNotification($ad));
        } catch (\Exception $e) {
            \Log::error('Failed to send approval notification to ad owner: ' . $e->getMessage());
        }

        // Send notifications to users who have this ad in favorites when changes are approved
        if ($hasPendingChanges) {
            $favoritedUsers = \App\Models\Favorite::where('ad_id', $ad->id)
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter(function ($favoriteUser) use ($ad) {
                    // Don't notify the ad owner
                    return $favoriteUser && $favoriteUser->id !== $ad->user_id;
                });

            if ($favoritedUsers->isNotEmpty()) {
                try {
                    Notification::send(
                        $favoritedUsers,
                        new \App\Notifications\AdUpdatedNotification($ad)
                    );
                } catch (\Exception $e) {
                    // Log error but don't fail the approval process
                    \Log::error('Failed to send notification email: ' . $e->getMessage());
                }
            }
        }

        return back()->with('success', $hasPendingChanges ? 'تم قبول التعديلات بنجاح' : 'تم قبول الإعلان بنجاح');
    }

    public function reject(Request $request, $uid)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $ad = Ad::where('uid', $uid)->firstOrFail();

        // If there are pending changes, reject them (clear pending_changes)
        $hasPendingChanges = !empty($ad->pending_changes);
        if ($hasPendingChanges) {
            $pc = $ad->pending_changes;
            if (is_array($pc)) {
                if (isset($pc['images']) && is_array($pc['images'])) {
                    foreach ($pc['images'] as $p) {
                        if (is_string($p) && $p !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($p)) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($p);
                        }
                    }
                }
                if (! empty($pc['video']) && is_string($pc['video']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($pc['video'])) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($pc['video']);
                }
            }
            $ad->update([
                'pending_changes' => null,
                'rejection_reason' => $request->rejection_reason,
            ]);
            ActivityLog::log('ad_changes_rejected', $ad, [
                'reason' => $request->rejection_reason
            ]);

            try {
                $ad->user->notify(new \App\Notifications\AdChangesRejectedNotification($ad, $request->rejection_reason));
            } catch (\Exception $e) {
                \Log::error('Failed to send ad changes rejected notification: ' . $e->getMessage());
            }
        } else {
            $ad->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
            ]);
            ActivityLog::log('ad_rejected', $ad, [
                'reason' => $request->rejection_reason
            ]);

            // Send notification to ad owner when ad is rejected
            try {
                $ad->user->notify(new \App\Notifications\AdRejectedNotification($ad, $request->rejection_reason));
            } catch (\Exception $e) {
                \Log::error('Failed to send ad rejection notification: ' . $e->getMessage());
            }
        }

        return back()->with('success', $hasPendingChanges ? 'تم رفض التعديلات بنجاح' : 'تم رفض الإعلان بنجاح');
    }

    public function toggleFeatured($uid)
    {
        $ad = Ad::where('uid', $uid)->firstOrFail();
        $ad->update(['is_featured' => !$ad->is_featured]);

        ActivityLog::log('ad_featured_toggle', $ad);

        return back()->with('success', 'تم تغيير حالة التمييز بنجاح');
    }

    public function destroy($uid)
    {
        $ad = Ad::where('uid', $uid)->firstOrFail();

        ActivityLog::log('ad_deleted', $ad);

        $ad->delete();

        return back()->with('success', 'تم حذف الإعلان بنجاح');
    }
}

