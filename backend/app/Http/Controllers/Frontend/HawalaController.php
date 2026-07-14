<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\HawalaTransferRequest;
use App\Models\WalletTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HawalaController extends Controller
{
   
    public function index(): View
    {
        $user = auth()->user();
        $balances = WalletTransaction::where('user_id', $user->id)
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        $transactions = WalletTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $transfers = HawalaTransferRequest::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('frontend.profile.hawala.index', compact('user', 'balances', 'transactions', 'transfers'));
    }

    /**
     * تقديم طلب حوالة جديد (نموذج).
     */
    public function create(): View
    {
        $user = auth()->user();
        return view('frontend.profile.hawala.create', compact('user'));
    }

    /**
     * حفظ طلب الحوالة.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|in:SYP,TRY,USD,EUR',
            'receipt_number' => 'required|string|max:255',
            'receipt_image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            'note' => 'nullable|string|max:1000',
        ], [], [
            'amount' => __('frontend.hawala.amount'),
            'currency' => __('frontend.hawala.currency'),
            'receipt_number' => __('frontend.hawala.receipt_number'),
            'receipt_image' => __('frontend.hawala.receipt_image'),
        ]);

        $path = $request->file('receipt_image')->store('hawala-receipts', 'public');

        HawalaTransferRequest::create([
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'currency' => $request->currency,
            'receipt_number' => $request->receipt_number,
            'receipt_image_path' => $path,
            'note' => $request->filled('note') ? $request->note : null,
            'status' => HawalaTransferRequest::STATUS_PENDING,
        ]);

        return redirect()
            ->route('profile.hawala.index')
            ->with('success', __('frontend.hawala.submit_success'));
    }
}
