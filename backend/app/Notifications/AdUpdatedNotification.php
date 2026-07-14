<?php

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ad $ad
    ) {
    }

    public function via(object $notifiable): array
    {
        // قاعدة: نخزن في قاعدة البيانات + نرسل بريد + Push (إن وُجد FCM token)
        return ['database', \App\Channels\FcmChannel::class, 'mail'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        if ($channel !== 'mail') {
            return true;
        }

        $email = (string) ($notifiable->email ?? '');
        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public function toMail(object $notifiable)
    {
        $title = __('frontend.notifications.ad_updated_title');
        $message = trans('frontend.notifications.ad_updated_message', [
            'ad_title' => $this->ad->title,
        ]);
        
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject($title)
            ->markdown('vendor.notifications.ad-with-card', [
                'messageText' => $message,
                'actionText' => __('frontend.notifications.view_ad'),
                'actionUrl' => route('ads.show', $this->ad->uid),
                'ad' => $this->ad,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        // Get the translated message with the ad title replaced
        $title = __('frontend.notifications.ad_updated_title');
        $message = trans('frontend.notifications.ad_updated_message', [
            'ad_title' => $this->ad->title,
        ]);
        
        return [
            'title' => $title,
            'message' => $message,
            'ad_uid' => $this->ad->uid,
            'ad_url' => route('ads.show', $this->ad->uid),
            'type' => 'ad_updated',
        ];
    }

    /**
     * بيانات إشعار الـ Push (Firebase Cloud Messaging).
     * يجب أن تكون كل القيم نصوصاً.
     */
    public function toFcm(object $notifiable): array
    {
        $title = __('frontend.notifications.ad_updated_title');
        $message = trans('frontend.notifications.ad_updated_message', [
            'ad_title' => $this->ad->title,
        ]);

        return [
            'title' => (string) $title,
            'body' => (string) $message,
            'data' => [
                'type' => 'ad_updated',
                'ad_uid' => (string) $this->ad->uid,
                'ad_url' => (string) route('ads.show', $this->ad->uid),
                'title' => (string) $title,
                'message' => (string) $message,
            ],
        ];
    }
}

