<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HawalaTransferRequest;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\WalletTransaction;
use App\Notifications\HawalaApprovedNotification;
use App\Notifications\HawalaRejectedNotification;
use App\Notifications\PackageActivatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HawalaTransferController extends Controller
{
    public function index(Request $request): View
    {
        $query = HawalaTransferRequest::with(['user', 'package']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"));
            });
        }

        $transfers = $query->latest()->paginate(20);
        return view('admin.hawala-transfers.index', compact('transfers'));
    }

    public function show(int $id): View
    {
        $transfer = HawalaTransferRequest::with(['user', 'package', 'subscription'])
            ->findOrFail($id);
        $packages = Package::active()->ordered()->get();
        return view('admin.hawala-transfers.show', compact('transfer', 'packages'));
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'admin_credited_amount' => 'required|numeric|min:0',
            'admin_credited_currency' => 'required|string|in:SYP,TRY,USD,EUR',
        ], [], [
            'admin_credited_amount' => __('admin.hawala.credited_amount'),
            'admin_credited_currency' => __('admin.hawala.credited_currency'),
        ]);

        $transfer = HawalaTransferRequest::where('status', HawalaTransferRequest::STATUS_PENDING)->findOrFail($id);

        $transfer->update([
            'status' => HawalaTransferRequest::STATUS_APPROVED,
            'admin_credited_amount' => $request->admin_credited_amount,
            'admin_credited_currency' => $request->admin_credited_currency,
            'approved_at' => now(),
            'approved_by' => auth('admin')->id(),
        ]);

        WalletTransaction::create([
            'user_id' => $transfer->user_id,
            'amount' => $request->admin_credited_amount,
            'currency' => $request->admin_credited_currency,
            'type' => WalletTransaction::TYPE_HAWALA_CREDIT,
            'reference_type' => 'hawala_transfer',
            'reference_id' => $transfer->id,
            'description' => __('admin.hawala.credit_description', ['receipt' => $transfer->receipt_number]),
        ]);

        try {
            $transfer->user->notify(new HawalaApprovedNotification($transfer));
        } catch (\Throwable $e) {
            Log::warning('Hawala approved notification email failed', [
                'transfer_id' => $transfer->id,
                'user_id' => $transfer->user_id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.hawala-transfers.show', $id)
            ->with('success', __('admin.hawala.approved_success'));
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $transfer = HawalaTransferRequest::where('status', HawalaTransferRequest::STATUS_PENDING)->findOrFail($id);
        $transfer->update([
            'status' => HawalaTransferRequest::STATUS_REJECTED,
            'rejection_reason' => $request->rejection_reason,
            'approved_at' => now(),
            'approved_by' => auth('admin')->id(),
        ]);

        try {
            $transfer->user->notify(new HawalaRejectedNotification($transfer));
        } catch (\Throwable $e) {
            Log::warning('Hawala rejected notification email failed', [
                'transfer_id' => $transfer->id,
                'user_id' => $transfer->user_id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.hawala-transfers.show', $id)
            ->with('success', __('admin.hawala.rejected_success'));
    }

    public function activatePackage(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        $transfer = HawalaTransferRequest::where('status', HawalaTransferRequest::STATUS_APPROVED)->findOrFail($id);
        $user = $transfer->user;
        $package = Package::active()->findOrFail($request->package_id);

        $balance = WalletTransaction::where('user_id', $user->id)
            ->where('currency', $package->currency)
            ->sum('amount');

        if ((float) $balance < (float) $package->price) {
            return redirect()
                ->route('admin.hawala-transfers.show', $id)
                ->with('error', __('admin.hawala.insufficient_balance'));
        }

        \DB::transaction(function () use ($transfer, $user, $package) {
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
                'transaction_id' => 'HAW-' . strtoupper(Str::random(12)),
                'amount' => $package->price,
                'currency' => $package->currency,
                'payment_method' => 'bank_transfer',
                'status' => 'completed',
                'payment_details' => ['hawala_transfer_id' => $transfer->id],
                'paid_at' => now(),
            ]);

            WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => - (float) $package->price,
                'currency' => $package->currency,
                'type' => WalletTransaction::TYPE_PACKAGE_PURCHASE,
                'reference_type' => 'subscription',
                'reference_id' => $subscription->id,
                'description' => __('admin.hawala.package_purchase_description', ['package' => $package->name_ar ?? $package->name]),
            ]);

            $transfer->update([
                'package_id' => $package->id,
                'subscription_id' => $subscription->id,
            ]);

            try {
                $user->notify(new PackageActivatedNotification($subscription, $package));
            } catch (\Throwable $e) {
                Log::warning('Package activated notification (hawala): mail or other channel failed', [
                    'transfer_id' => $transfer->id,
                    'user_id' => $user->id,
                    'message' => $e->getMessage(),
                ]);
            }
        });

        $user->enforceAdsLimit();

        return redirect()
            ->route('admin.hawala-transfers.show', $id)
            ->with('success', __('admin.hawala.package_activated_success'));
    }
}
