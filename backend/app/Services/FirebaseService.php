<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Exception\MessagingException;
use Illuminate\Support\Facades\Log;
use App\Models\FcmToken;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $credentialsPath = config('firebase.credentials');

        if (!$credentialsPath || !is_file($credentialsPath)) {
            throw new \RuntimeException(
                'Firebase credentials file not found. Set FIREBASE_CREDENTIALS in .env or add service-account.json to storage/app/firebase/ or Firebase_key/Firebase_key.json'
            );
        }

        $factory = (new Factory)->withServiceAccount($credentialsPath);
        $this->messaging = $factory->createMessaging();
    }

    /**
     * إرسال إشعار إلى جهاز واحد
     *
     * @param string $token FCM token
     * @param string $title عنوان الإشعار
     * @param string $body نص الإشعار
     * @param array $data بيانات إضافية
     * @return bool
     */
    public function sendToDevice(string $token, string $title, string $body, array $data = []): bool
    {
        try {
            // FCM: data payload accepts only string values
            $dataStrings = [];
            foreach ($data as $key => $value) {
                $dataStrings[(string) $key] = (string) $value;
            }

            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData($dataStrings)
                ->withAndroidConfig(
                    AndroidConfig::fromArray([
                        'priority' => 'high',
                        'notification' => [
                            'sound' => config('firebase.fcm.default_sound', 'default'),
                            'channel_id' => config('firebase.fcm.default_channel_id', 'default'),
                        ],
                    ])
                )
                ->withApnsConfig(
                    ApnsConfig::fromArray([
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1,
                            ],
                        ],
                    ])
                );

            $this->messaging->send($message);
            
            Log::info('FCM notification sent successfully', [
                'token' => substr($token, 0, 20) . '...',
                'title' => $title,
            ]);
            
            return true;
        } catch (MessagingException $e) {
            $message = $e->getMessage();

            // إذا كان التوكن غير صالح / غير موجود، نحذفه من قاعدة البيانات ونسجّل تحذيراً فقط
            if (str_contains($message, 'invalid') || str_contains($message, 'not found')) {
                FcmToken::where('token', $token)->delete();

                Log::warning('FCM token removed because it is invalid or not found', [
                    'token' => substr($token, 0, 20) . '...',
                    'error' => $message,
                ]);

                return false;
            }

            // أخطاء أخرى في FCM نحتفظ بها كـ error
            Log::error('FCM notification failed', [
                'error' => $message,
                'token' => substr($token, 0, 20) . '...',
            ]);

            throw $e;
        }
    }

    /**
     * إرسال إشعار إلى عدة أجهزة
     *
     * @param array $tokens مصفوفة من FCM tokens
     * @param string $title عنوان الإشعار
     * @param string $body نص الإشعار
     * @param array $data بيانات إضافية
     * @return array ['success' => count, 'failed' => count]
     */
    public function sendToMultipleDevices(array $tokens, string $title, string $body, array $data = []): array
    {
        $results = ['success' => 0, 'failed' => 0];
        
        foreach ($tokens as $token) {
            try {
                if ($this->sendToDevice($token, $title, $body, $data)) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('FCM batch send failed for token', [
                    'token' => substr($token, 0, 20) . '...',
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $results;
    }

    /**
     * إرسال إشعار إلى مستخدم (جميع أجهزته)
     *
     * @param \App\Models\User $user
     * @param string $title عنوان الإشعار
     * @param string $body نص الإشعار
     * @param array $data بيانات إضافية
     * @return array
     */
    public function sendToUser($user, string $title, string $body, array $data = []): array
    {
        $tokens = $user->fcmTokens()->pluck('token')->toArray();
        
        if (empty($tokens)) {
            Log::info('No FCM tokens found for user', ['user_id' => $user->id]);
            return ['success' => 0, 'failed' => 0];
        }
        
        return $this->sendToMultipleDevices($tokens, $title, $body, $data);
    }
}
