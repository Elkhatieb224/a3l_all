<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HawalaTransferRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HawalaTransferController extends Controller
{
    /**
     * تقديم طلب حوالة (مبلغ، عملة، رقم إيصال، صورة إيصال).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|in:SYP,TRY,USD,EUR',
            'receipt_number' => 'required|string|max:255',
            'receipt_image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            'note' => 'nullable|string|max:1000',
        ], [
            'amount.required' => 'المبلغ مطلوب',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً',
            'currency.in' => 'العملة غير مدعومة',
            'receipt_number.required' => 'رقم الإيصال مطلوب',
            'receipt_image.required' => 'صورة الإيصال مطلوبة',
            'receipt_image.image' => 'الملف يجب أن يكون صورة',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $file = $request->file('receipt_image');
        $path = $file->store('hawala-receipts', 'public');

        $transfer = HawalaTransferRequest::create([
            'user_id' => Auth::id(),
            'amount' => $request->amount,
            'currency' => $request->currency,
            'receipt_number' => $request->receipt_number,
            'receipt_image_path' => $path,
            'note' => $request->filled('note') ? $request->note : null,
            'status' => HawalaTransferRequest::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب الحوالة بنجاح. سيتم مراجعته من الإدارة.',
            'data' => [
                'id' => $transfer->id,
                'status' => $transfer->status,
            ],
        ], 201, [], JSON_UNESCAPED_UNICODE);
    }
}
