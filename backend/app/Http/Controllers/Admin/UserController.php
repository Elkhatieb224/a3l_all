<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BlockedUser;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Models\WalletTransaction;
use App\Notifications\PackageActivatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount('ads');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by verification
        if ($request->filled('verified')) {
            $query->where('is_verified', $request->verified === 'yes');
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Filter by account status - exclude deleted accounts by default
        if ($request->filled('account_status')) {
            $query->where('account_status', $request->account_status);
        } else {
            $query->where('account_status', '!=', 'deleted');
        }

        $users = $query->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function deletedAccounts(Request $request)
    {
        $query = User::where('account_status', 'deleted')->withCount('ads');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest('scheduled_deletion_at')->paginate(20);

        return view('admin.users.deleted', compact('users'));
    }

    public function show($id): View
    {
        $showAllSubscriptions = request('subscriptions') === 'all';
        $user = User::withCount(['ads', 'subscriptions', 'payments'])
            ->with([
                'ads' => function ($q) {
                    $q->latest()->take(10);
                },
                'activeSubscription' => fn ($q) => $q->with(['package', 'payment']),
                'subscriptions' => function ($q) use ($showAllSubscriptions) {
                    $q->with(['package', 'payment'])->latest();
                    if (!$showAllSubscriptions) {
                        $q->take(5);
                    }
                },
                'payments' => function ($q) {
                    $q->with('package')->latest()->take(5);
                },
            ])
            ->findOrFail($id);

        $walletBalances = WalletTransaction::where('user_id', $user->id)
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->map(fn ($t) => round((float) $t, 2))
            ->toArray();

        $packages = Package::where('is_active', true)->orderBy('order')->orderBy('id')->get();

        $availableCurrencies = ['SYP', 'TRY', 'USD', 'EUR'];

        return view('admin.users.show', compact('user', 'walletBalances', 'packages', 'showAllSubscriptions', 'availableCurrencies'));
    }

    public function addBalance(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|in:SYP,TRY,USD,EUR',
            'note' => 'nullable|string|max:500',
        ], [], [
            'amount' => __('admin.users.wallet_amount'),
            'currency' => __('admin.users.wallet_currency'),
        ]);

        $user = User::findOrFail($id);

        $description = __('admin.users.wallet_admin_credit_description', [
            'amount' => $request->amount,
            'currency' => $request->currency,
        ]);
        if ($request->filled('note')) {
            $description .= ' — ' . $request->note;
        }

        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => (float) $request->amount,
            'currency' => $request->currency,
            'type' => WalletTransaction::TYPE_ADMIN_ADJUSTMENT,
            'reference_type' => null,
            'reference_id' => null,
            'description' => $description,
        ]);

        ActivityLog::log('wallet_balance_added', $user, [
            'amount' => $request->amount,
            'currency' => $request->currency,
            'note' => $request->note,
        ]);

        return redirect()
            ->route('admin.users.show', $id)
            ->with('success', __('admin.users.wallet_balance_added_success'));
    }

    public function activatePackage(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        $user = User::findOrFail($id);
        $package = Package::where('is_active', true)->findOrFail($request->package_id);

        $currency = $package->currency ?? \App\Models\Setting::get('default_currency', 'SYP');

        // عند التفعيل من صفحة المستخدم /admin/users/{id}: لا خصم من المحفظة، ولا إضافة المبلغ في التقارير/الدفعات
        \DB::transaction(function () use ($user, $package, $currency) {
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'starts_at' => now(),
                'expires_at' => now()->addDays($package->duration_days),
                'status' => 'active',
                'ads_used' => 0,
            ]);

            $payment = Payment::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'subscription_id' => $subscription->id,
                'transaction_id' => 'ADM-' . strtoupper(\Illuminate\Support\Str::random(12)),
                'amount' => 0,
                'currency' => $currency,
                'payment_method' => 'admin_grant',
                'status' => 'completed',
                'payment_details' => [
                    'activated_from' => 'admin_user_page',
                ],
                'paid_at' => now(),
            ]);

            ActivityLog::log('package_activated_from_user_page', $user, [
                'subscription_id' => $subscription->id,
                'package_id' => $package->id,
                'payment_id' => $payment->id,
            ]);

            try {
                $user->notify(new PackageActivatedNotification($subscription, $package));
            } catch (\Throwable $e) {
                Log::warning('Package activation notification failed (user may still have been activated)', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'message' => $e->getMessage(),
                ]);
            }
        });

        $user->enforceAdsLimit();

        return redirect()
            ->route('admin.users.show', $id)
            ->with('success', __('admin.users.package_activated_success'));
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        ActivityLog::log('user_status_toggle', $user, [
            'is_active' => $user->is_active
        ]);

        return back()->with('success', 'تم تغيير حالة المستخدم بنجاح');
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6|confirmed',
                'phone' => 'nullable|string|max:20',
                'bio' => 'nullable|string|max:500',
                'location_country' => 'nullable|string|max:100',
                'location_city' => 'nullable|string|max:100',
                'is_verified' => 'nullable|boolean',
                'is_active' => 'nullable|boolean',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'location_country' => $validated['location_country'] ?? 'SY',
                'location_city' => $validated['location_city'] ?? null,
                'is_verified' => $request->input('is_verified', 0) == 1,
                'is_active' => $request->input('is_active', 1) == 1,
            ]);

            ActivityLog::log('user_created', $user);

            return redirect()->route('admin.users.index')
                ->with('success', 'تم إضافة المستخدم بنجاح');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء إضافة المستخدم: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
            'location_country' => 'nullable|string|max:100',
            'location_city' => 'nullable|string|max:100',
            'is_verified' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone ?? null,
            'bio' => $request->bio ?? null,
            'location_country' => $request->location_country ?? 'SY',
            'location_city' => $request->location_city ?? null,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $data['is_verified'] = $request->boolean('is_verified');
        $data['is_active'] = $request->boolean('is_active');

        $user->update($data);

        ActivityLog::log('user_updated', $user);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم تحديث بيانات المستخدم بنجاح');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        ActivityLog::log('user_deleted', $user);

        $user->delete();

        return back()->with('success', 'تم حذف المستخدم بنجاح');
    }

    public function verificationRequests(Request $request)
    {
        $query = VerificationRequest::with(['user', 'reviewer']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate(20);

        return view('admin.users.verification-requests', compact('requests'));
    }

    public function showVerificationRequest($id)
    {
        $request = VerificationRequest::with(['user', 'reviewer'])->findOrFail($id);

        return view('admin.users.verification-request-show', compact('request'));
    }

    public function approveVerification($id, Request $request)
    {
        $verificationRequest = VerificationRequest::with('user')->findOrFail($id);

        if ($verificationRequest->status !== 'pending') {
            return back()->with('error', 'هذا الطلب تمت معالجته بالفعل');
        }

        $verificationRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth('admin')->id(),
            'reviewed_at' => now(),
            'admin_notes' => $request->admin_notes,
        ]);

        // Verify the user and copy business data to profile
        $verificationRequest->user->update([
            'is_verified' => true,
            'business_name' => $verificationRequest->business_name,
            'business_type' => $verificationRequest->business_type,
            'business_owner' => $verificationRequest->responsible_person,
            'business_address' => $verificationRequest->business_address,
            'business_phone' => $verificationRequest->business_phone,
            'instagram_url' => $verificationRequest->instagram_url,
            'facebook_url' => $verificationRequest->facebook_url,
            'website_url' => $verificationRequest->website_url,
            'storefront_image_path' => $verificationRequest->storefront_image_path,
        ]);

        ActivityLog::log('verification_approved', $verificationRequest->user, [
            'request_id' => $verificationRequest->id,
        ]);

        $verificationRequest->user->notify(new \App\Notifications\VerificationApprovedNotification($verificationRequest));

        return back()->with('success', 'تم توثيق المستخدم بنجاح');
    }

    public function rejectVerification($id, Request $request)
    {
        $verificationRequest = VerificationRequest::with('user')->findOrFail($id);

        if ($verificationRequest->status !== 'pending') {
            return back()->with('error', 'هذا الطلب تمت معالجته بالفعل');
        }

        $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ], [
            'admin_notes.required' => 'يجب إضافة ملاحظات لرفض الطلب',
        ]);

        $verificationRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth('admin')->id(),
            'reviewed_at' => now(),
            'admin_notes' => $request->admin_notes,
        ]);

        ActivityLog::log('verification_rejected', $verificationRequest->user, [
            'request_id' => $verificationRequest->id,
        ]);

        $verificationRequest->user->notify(new \App\Notifications\VerificationRejectedNotification($verificationRequest));

        return back()->with('success', 'تم رفض طلب التوثيق');
    }

    public function blockUser(Request $request, $id)
    {
        $userToBlock = User::findOrFail($id);
        $reportId = $request->input('report_id');
        $report = $reportId ? Report::find($reportId) : null;

        // Block user for the reporter
        if ($report && $report->user_id) {
            BlockedUser::firstOrCreate([
                'user_id' => $report->user_id,
                'blocked_user_id' => $userToBlock->id,
            ]);

            ActivityLog::log('user_blocked', $userToBlock, [
                'blocked_by' => $report->user_id,
                'report_id' => $reportId,
            ]);
        }

        return back()->with('success', 'تم حظر المستخدم بنجاح');
    }

    public function export(Request $request)
    {
        $query = User::query();

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('verified')) {
            if ($request->verified === 'yes') {
                $query->where('is_verified', true);
            } elseif ($request->verified === 'no') {
                $query->where('is_verified', false);
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Exclude deleted accounts
        if (!$request->filled('account_status')) {
            $query->where('account_status', '!=', 'deleted');
        } elseif ($request->filled('account_status')) {
            $query->where('account_status', $request->account_status);
        }

        $users = $query->withCount('ads')->latest()->get();

        $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        // Add BOM for UTF-8 Excel compatibility
        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'ID',
                'الاسم',
                'البريد الإلكتروني',
                'رقم الهاتف',
                'البلد',
                'المدينة',
                'الاسم التجاري',
                'نوع العمل',
                'صاحب العمل',
                'عنوان العمل',
                'هاتف العمل',
                'الوصف',
                'Instagram',
                'Facebook',
                'الموقع الإلكتروني',
                'موثق',
                'نشط',
                'حالة الحساب',
                'تاريخ الإنشاء',
                'تاريخ آخر تسجيل دخول',
                'عدد الإعلانات'
            ], ',');

            // Data rows
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone ?? '',
                    $user->location_country ?? '',
                    $user->location_city ?? '',
                    $user->business_name ?? '',
                    $user->business_type ?? '',
                    $user->business_owner ?? '',
                    $user->business_address ?? '',
                    $user->business_phone ?? '',
                    $user->bio ?? '',
                    $user->instagram_url ?? '',
                    $user->facebook_url ?? '',
                    $user->website_url ?? '',
                    $user->is_verified ? 'نعم' : 'لا',
                    $user->is_active ? 'نشط' : 'غير نشط',
                    $user->account_status ?? 'active',
                    $user->created_at->format('Y-m-d H:i:s'),
                    $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : '',
                    $user->ads_count ?? 0,
                ], ',');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

