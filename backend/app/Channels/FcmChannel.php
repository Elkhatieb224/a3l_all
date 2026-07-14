<?php

namespace App\Channels;

use App\Services\FirebaseService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    /**
     * Send the given notification via Firebase Cloud Messaging (Push).
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notifiable, 'fcmTokens')) {
            return;
        }

        $tokens = $notifiable->fcmTokens()->pluck('token')->toArray();
        if (empty($tokens)) {
            Log::warning('FCM Push: لا يوجد token مسجّل لهذا المستخدم. التطبيق يجب أن يرسل التوكن عبر POST /api/v1/fcm-token بعد تسجيل الدخول.', ['user_id' => $notifiable->id ?? null]);
            return;
        }

        $credentialsPath = config('firebase.credentials');
        if (!$credentialsPath || !is_file($credentialsPath)) {
            Log::warning('FCM Push: ملف Firebase غير موجود. ضع service-account.json في storage/app/firebase/ أو Firebase_key/Firebase_key.json');
            return;
        }

        $message = $notification->toFcm($notifiable);
        $title = $message['title'] ?? '';
        $body = $message['body'] ?? '';
        $data = $message['data'] ?? [];

        try {
            $firebase = app(FirebaseService::class);
            $result = $firebase->sendToMultipleDevices($tokens, $title, $body, $data);
            if ($result['success'] > 0 || $result['failed'] > 0) {
                Log::info('FCM Push: تم الإرسال', [
                    'user_id' => $notifiable->id ?? null,
                    'success' => $result['success'],
                    'failed' => $result['failed'],
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('FCM Push: فشل الإرسال', [
                'notifiable_id' => $notifiable->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
