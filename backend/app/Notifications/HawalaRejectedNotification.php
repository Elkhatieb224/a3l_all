<?php

namespace App\Notifications;

use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use App\Models\HawalaTransferRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HawalaRejectedNotification extends Notification
{
    use Queueable, SkipsInvalidMailRecipients;

    public function __construct(
        public HawalaTransferRequest $transfer
    ) {
    }

    public function via(object $notifiable): array
    {
        // إرسال push و database أولاً حتى يصل الإشعار حتى لو فشل البريد
        return ['database', \App\Channels\FcmChannel::class, 'mail'];
    }

    public function toMail(object $notifiable)
    {
        $title = __('frontend.notifications.hawala_rejected_title');
        $message = __('frontend.notifications.hawala_rejected_message');
        if ($this->transfer->rejection_reason) {
            $message .= ' ' . $this->transfer->rejection_reason;
        }

        return (new MailMessage)
            ->subject($title)
            ->line($message)
            ->line(__('frontend.notifications.thank_you'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('frontend.notifications.hawala_rejected_title'),
            'message' => __('frontend.notifications.hawala_rejected_message'),
            'type' => 'hawala_rejected',
            'hawala_transfer_id' => $this->transfer->id,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $title = (string) __('frontend.notifications.hawala_rejected_title');
        $body = (string) __('frontend.notifications.hawala_rejected_message');

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'hawala_rejected',
                'hawala_transfer_id' => (string) $this->transfer->id,
                'title' => $title,
                'message' => $body,
            ],
        ];
    }
}
