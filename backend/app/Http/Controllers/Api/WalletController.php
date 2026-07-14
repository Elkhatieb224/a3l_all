<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    /**
     * رصيد المحفظة حسب العملة (مجموع الحركات لكل عملة).
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $balances = WalletTransaction::query()
            ->where('user_id', $user->id)
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->map(fn ($total) => round((float) $total, 2))
            ->toArray();

        $list = [];
        foreach ($balances as $currency => $amount) {
            $list[] = ['currency' => $currency, 'amount' => $amount];
        }
        if (empty($list)) {
            $list[] = ['currency' => 'SYP', 'amount' => 0];
        }

        return response()->json([
            'success' => true,
            'data' => ['balances' => $list],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * سجل حركات المحفظة (مع pagination).
     */
    public function transactions(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 50);
        $transactions = WalletTransaction::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $items = $transactions->getCollection()->map(function ($t) {
            return [
                'id' => $t->id,
                'amount' => (float) $t->amount,
                'currency' => $t->currency,
                'type' => $t->type,
                'description' => $t->description,
                'reference_type' => $t->reference_type,
                'created_at' => $t->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $items,
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
