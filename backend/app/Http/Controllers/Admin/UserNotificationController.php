<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\UserCustomNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserNotificationController extends Controller
{
    public function create()
    {
        $recentUsers = User::query()
            ->select(['id', 'name', 'email', 'is_verified', 'created_at'])
            ->latest()
            ->take(300)
            ->get();

        $selectableUsers = $recentUsers->map(function (User $user) {
            return [
                'id' => (int) $user->id,
                'name' => (string) ($user->name ?? ''),
                'email' => (string) ($user->email ?? ''),
                'is_verified' => (bool) $user->is_verified,
            ];
        })->values();

        return view('admin.notifications.create', compact('recentUsers', 'selectableUsers'));
    }

    public function store(Request $request)
    {
        Log::info('Admin notification store: request received', [
            'target_type' => $request->input('target_type'),
            'has_title' => $request->filled('title'),
        ]);

        try {
            $targetType = $request->input('target_type', 'all');
            if (!in_array($targetType, ['all', 'verified', 'unverified', 'date_range', 'selected'], true)) {
                $targetType = 'all';
            }
            $request->merge(['target_type' => $targetType]);

            $requestedChannelType = $request->input('channel_type', 'both');
            $channelType = in_array($requestedChannelType, ['push', 'email', 'both'], true) ? $requestedChannelType : 'both';

            $request->validate([
                'title' => 'required|string|max:190',
                'message' => 'required|string|max:5000',
                'target_type' => 'in:all,verified,unverified,date_range,selected',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'user_ids' => $targetType === 'selected' ? 'required|string|min:1' : 'nullable|string',
                'channel_type' => 'required|in:push,email,both',
            ], [
                'user_ids.required' => __('admin.notifications.user_ids_required'),
            ]);

            $query = User::query();

            switch ($targetType) {
                case 'verified':
                    $query->where('is_verified', true);
                    break;
                case 'unverified':
                    $query->where(function ($q) {
                        $q->where('is_verified', false)->orWhereNull('is_verified');
                    });
                    break;
                case 'date_range':
                    if ($request->filled('from_date')) {
                        $query->whereDate('created_at', '>=', $request->from_date);
                    }
                    if ($request->filled('to_date')) {
                        $query->whereDate('created_at', '<=', $request->to_date);
                    }
                    break;
                case 'selected':
                    $idsRaw = collect(explode(',', (string) $request->user_ids))
                        ->map(fn ($v) => trim($v))
                        ->filter();

                    $ids = $idsRaw->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (int) $v)->values();
                    $emails = $idsRaw->filter(fn ($v) => !is_numeric($v))->values();

                    if ($ids->isEmpty() && $emails->isEmpty()) {
                        return back()->withErrors(['user_ids' => __('admin.notifications.user_ids_required')]);
                    }

                    $query->where(function ($q) use ($ids, $emails) {
                        if ($ids->isNotEmpty()) {
                            $q->whereIn('id', $ids);
                        }
                        if ($emails->isNotEmpty()) {
                            $q->orWhereIn('email', $emails);
                        }
                    });
                    break;
                case 'all':
                default:
                    break;
            }

            $totalCount = (clone $query)->count();
            Log::info('Admin notification: target_type=' . $targetType . ', total_users=' . $totalCount);

            if ($totalCount === 0) {
                return back()->withErrors(['target_type' => __('admin.notifications.no_users_found')]);
            }

            $sentTo = 0;
            $notification = new UserCustomNotification(
                (string) $request->input('title'),
                (string) $request->input('message'),
                (string) $channelType
            );

            $query->chunk(500, function ($users) use (&$sentTo, $notification) {
                foreach ($users as $user) {
                    try {
                        $user->notify($notification);
                        $sentTo++;
                    } catch (\Throwable $e) {
                        Log::error('Admin notification send failed for user', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

            if ($sentTo === 0) {
                // There were users matching the filter, but no notification was actually sent
                // (likely due to delivery errors). Show a generic send error instead of
                // the misleading "no users found" message.
                return back()->withErrors(['title' => __('admin.notifications.send_error_generic')]);
            }

            return back()->with('success', __('admin.notifications.sent_success', ['count' => $sentTo]));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Admin notification store failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['title' => __('admin.notifications.send_error_generic')]);
        }
    }
}

