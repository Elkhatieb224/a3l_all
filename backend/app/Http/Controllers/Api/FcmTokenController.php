<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Validator;

class FcmTokenController extends Controller
{
    /**
     * حفظ أو تحديث FCM token للمستخدم
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|max:500',
            'device_type' => 'nullable|string|in:android,ios,web',
            'device_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $tokenHash = hash('sha256', $request->token);

        $fcmToken = FcmToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'token_hash' => $tokenHash,
            ],
            [
                'token' => $request->token,
                'device_type' => $request->device_type,
                'device_id' => $request->device_id,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'FCM token saved successfully',
            'data' => $fcmToken
        ], 201);
    }

    /**
     * حذف FCM token
     */
    public function destroy(Request $request, $tokenId = null)
    {
        $user = $request->user();

        if ($tokenId) {
            // حذف token محدد
            $fcmToken = FcmToken::where('user_id', $user->id)
                ->where('id', $tokenId)
                ->first();

            if (!$fcmToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not found'
                ], 404);
            }

            $fcmToken->delete();
        } else {
            $token = $request->input('token');
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token is required'
                ], 422);
            }

            $tokenHash = hash('sha256', $token);
            FcmToken::where('user_id', $user->id)
                ->where('token_hash', $tokenHash)
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'FCM token deleted successfully'
        ]);
    }
}
