<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Package;
use App\Models\PackageRequest;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\WalletTransaction;
use App\Notifications\PackageActivatedNotification;
use App\Notifications\PackageRequestRespondedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PackageRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = PackageRequest::with(['user', 'package']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"));
            });
        }

        $requests = $query->latest()->paginate(20);
        $statusCounts = [
            'all' => PackageRequest::count(),
            'pending' => PackageRequest::pending()->count(),
            'approved' => PackageRequest::where('status', 'approved')->count(),
            'rejected' => PackageRequest::where('status', 'rejected')->count(),
        ];

        return view('admin.package-requests.index', compact('requests', 'statusCounts'));
    }

    public function show(int $id): View
    {
        $packageRequest = PackageRequest::with(['user', 'package', 'responder', 'subscription'])
            ->findOrFail($id);

        $walletBalances = [];
        if ($packageRequest->user) {
            $walletBalances = WalletTransaction::where('user_id', $packageRequest->user_id)
                ->selectRaw('currency, SUM(amount) as total')
                ->groupBy('currency')
                ->pluck('total', 'currency')
                ->map(fn ($t) => round((float) $t, 2))
                ->toArray();
        }

        return view('admin.package-requests.show', compact('packageRequest', 'walletBalances'));
    }

    public function approve(int $id): RedirectResponse
    {
        $packageRequest = PackageRequest::where('status', PackageRequest::STATUS_PENDING)
            ->with('user', 'package')
            ->findOrFail($id);

        $user = $packageRequest->user;
        $package = $packageRequest->package;

        $balance = (float) WalletTransaction::where('user_id', $user->id)
            ->where('currency', $package->currency)
            ->sum('amount');

        if ($balance < (float) $package->price) {
            return redirect()
                ->route('admin.package-requests.show', $id)
                ->with('error', __('admin.package_requests.insufficient_balance'));
        }

        \DB::transaction(function () use ($packageRequest, $user, $package) {
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'starts_at' => now(),
                'expires_at' => now()->addDays($package->duration_days),
                'status' => 'active',
                'ads_used' => 0,
            ]);

            Payment::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'subscription_id' => $subscription->id,
                'transaction_id' => 'PKG-' . strtoupper(\Illuminate\Support\Str::random(12)),
                'amount' => $package->price,
                'currency' => $package->currency,
                'payment_method' => 'wallet',
                'status' => 'completed',
                'payment_details' => [
                    'package_request_id' => $packageRequest->id,
                ],
                'paid_at' => now(),
            ]);

            WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => -(float) $package->price,
                'currency' => $package->currency,
                'type' => WalletTransaction::TYPE_PACKAGE_PURCHASE,
                'reference_type' => 'subscription',
                'reference_id' => $subscription->id,
                'description' => __('admin.hawala.package_purchase_description', [
                    'package' => $package->name_ar ?? $package->name_en ?? $package->name,
                ]),
            ]);

            $packageRequest->update([
                'status' => PackageRequest::STATUS_APPROVED,
                'admin_response' => $packageRequest->admin_response,
                'responded_at' => now(),
                'responded_by' => auth('admin')->id(),
                'subscription_id' => $subscription->id,
            ]);

            ActivityLog::log('package_request_approved', $packageRequest, [
                'subscription_id' => $subscription->id,
                'package_id' => $package->id,
                'amount' => $package->price,
                'currency' => $package->currency,
            ]);

            try {
                $user->notify(new PackageRequestRespondedNotification($packageRequest->fresh()));
                $user->notify(new PackageActivatedNotification($subscription, $package));
            } catch (\Throwable $e) {
                Log::warning('Package request approval: notification send failed (approval completed).', [
                    'package_request_id' => $packageRequest->id,
                    'user_id' => $user->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        });

        $user->enforceAdsLimit();

        return redirect()
            ->route('admin.package-requests.show', $id)
            ->with('success', __('admin.package_requests.approved_success'));
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'admin_response' => 'required|string|max:1000',
        ], [], [
            'admin_response' => __('admin.package_requests.admin_response'),
        ]);

        $packageRequest = PackageRequest::where('status', PackageRequest::STATUS_PENDING)
            ->with('user')
            ->findOrFail($id);

        $packageRequest->update([
            'status' => PackageRequest::STATUS_REJECTED,
            'admin_response' => $request->admin_response,
            'responded_at' => now(),
            'responded_by' => auth('admin')->id(),
        ]);

        try {
            $packageRequest->user->notify(new PackageRequestRespondedNotification($packageRequest->fresh()));
        } catch (\Throwable $e) {
            Log::warning('Package request rejected: notification send failed.', [
                'package_request_id' => $packageRequest->id,
                'user_id' => $packageRequest->user_id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.package-requests.show', $id)
            ->with('success', __('admin.package_requests.rejected_success'));
    }
}
