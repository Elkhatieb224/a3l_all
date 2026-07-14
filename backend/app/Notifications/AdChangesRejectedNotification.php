<?php

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdChangesRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ad $ad,
        public string $rejectionReason
    ) {
    }

    public function via(object $notifiable): array
    {
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
        $title = __('frontend.notifications.ad_changes_rejected_title');
        $message = trans('frontend.notifications.ad_changes_rejected_message', [
            'ad_title' => $this->ad->title,
            'reason' => $this->rejectionReason,
        ]);

        return (new MailMessage)
            ->subject($title)
            ->markdown('vendor.notifications.ad-with-card', [
                'messageText' => $message,
                'actionText' => __('frontend.notifications.view_ad'),
                'actionUrl' => route('profile.ads.show', $this->ad->uid),
                'ad' => $this->ad,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $title = __('frontend.notifications.ad_changes_rejected_title');
        $message = trans('frontend.notifications.ad_changes_rejected_message', [
            'ad_title' => $this->ad->title,
            'reason' => $this->rejectionReason,
        ]);

        return [
            'title' => $title,
            'message' => $message,
            'ad_uid' => $this->ad->uid,
            'ad_url' => route('profile.ads.show', $this->ad->uid),
            'type' => 'ad_changes_rejected',
            'rejection_reason' => $this->rejectionReason,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $title = __('frontend.notifications.ad_changes_rejected_title');
        $message = trans('frontend.notifications.ad_changes_rejected_message', [
            'ad_title' => $this->ad->title,
            'reason' => $this->rejectionReason,
        ]);

        return [
            'title' => (string) $title,
            'body' => (string) $message,
            'data' => [
                'type' => 'ad_changes_rejected',
                'ad_uid' => (string) $this->ad->uid,
                'ad_url' => (string) route('profile.ads.show', $this->ad->uid),
                'title' => (string) $title,
                'message' => (string) $message,
                'rejection_reason' => (string) $this->rejectionReason,
            ],
        ];
    }
}
