<?php

namespace App\Notifications;

use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use App\Models\Package;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PackageActivatedNotification extends Notification
{
    use Queueable, SkipsInvalidMailRecipients;

    public function __construct(
        public Subscription $subscription,
        public Package $package
    ) {
    }

    public function via(object $notifiable): array
    {
        // إرسال push و database أولاً حتى يصل الإشعار حتى لو فشل البريد
        return ['database', \App\Channels\FcmChannel::class, 'mail'];
    }

    public function toMail(object $notifiable)
    {
        $title = __('frontend.notifications.package_activated_title');
        $message = __('frontend.notifications.package_activated_message', [
            'package' => $this->package->name_ar ?? $this->package->name,
        ]);

        return (new MailMessage)
            ->subject($title)
            ->line($message)
            ->line(__('frontend.notifications.thank_you'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('frontend.notifications.package_activated_title'),
            'message' => __('frontend.notifications.package_activated_message', [
                'package' => $this->package->name_ar ?? $this->package->name,
            ]),
            'type' => 'package_activated',
            'subscription_id' => $this->subscription->id,
            'package_id' => $this->package->id,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $title = (string) __('frontend.notifications.package_activated_title');
        $body = (string) __('frontend.notifications.package_activated_message', [
            'package' => $this->package->name_ar ?? $this->package->name,
        ]);

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'package_activated',
                'subscription_id' => (string) $this->subscription->id,
                'package_id' => (string) $this->package->id,
                'title' => $title,
                'message' => $body,
            ],
        ];
    }
}
