<?php

namespace App\Helpers;

use App\Services\FirebaseService;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Helper functions لإرسال إشعارات Firebase
 */
class FirebaseHelper
{
    /**
     * إرسال إشعار إلى مستخدم
     */
    public static function sendNotification(User $user, string $title, string $body, array $data = []): bool
    {
        try {
            $firebase = app(FirebaseService::class);
            $result = $firebase->sendToUser($user, $title, $body, $data);
            
            return $result['success'] > 0;
        } catch (\Exception $e) {
            Log::error('Firebase notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * إرسال إشعار عند رسالة جديدة
     */
    public static function sendMessageNotification(User $recipient, User $sender, string $message, int $conversationId): bool
    {
        return self::sendNotification(
            $recipient,
            __('notifications.new_message', ['name' => $sender->name]),
            $message,
            [
                'type' => 'message',
                'conversation_id' => $conversationId,
                'sender_id' => $sender->id,
                'sender_name' => $sender->name,
            ]
        );
    }

    /**
     * إرسال إشعار عند تحديث إعلان
     */
    public static function sendAdUpdateNotification(User $user, string $adTitle, string $adUid): bool
    {
        return self::sendNotification(
            $user,
            __('notifications.ad_updated'),
            __('notifications.ad_updated_message', ['title' => $adTitle]),
            [
                'type' => 'ad_updated',
                'ad_uid' => $adUid,
                'ad_url' => route('ads.show', $adUid),
            ]
        );
    }

    /**
     * إرسال إشعار عند تفاوض جديد
     */
    public static function sendNegotiationNotification(User $recipient, User $sender, string $adTitle, int $negotiationId): bool
    {
        return self::sendNotification(
            $recipient,
            __('notifications.new_negotiation'),
            __('notifications.new_negotiation_message', ['name' => $sender->name, 'ad' => $adTitle]),
            [
                'type' => 'negotiation',
                'negotiation_id' => $negotiationId,
                'sender_id' => $sender->id,
            ]
        );
    }
}
