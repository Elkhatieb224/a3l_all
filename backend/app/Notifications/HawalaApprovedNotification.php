<?php

namespace App\Notifications;

use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use App\Models\HawalaTransferRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HawalaApprovedNotification extends Notification
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
        $title = __('frontend.notifications.hawala_approved_title');
        $amount = $this->transfer->admin_credited_amount;
        $currency = $this->transfer->admin_credited_currency ?? $this->transfer->currency;
        $message = __('frontend.notifications.hawala_approved_message', [
            'amount' => format_price($amount, 2, $currency),
        ]);

        return (new MailMessage)
            ->subject($title)
            ->line($message)
            ->line(__('frontend.notifications.thank_you'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('frontend.notifications.hawala_approved_title'),
            'message' => __('frontend.notifications.hawala_approved_message', [
                'amount' => format_price($this->transfer->admin_credited_amount, 2, $this->transfer->admin_credited_currency),
            ]),
            'type' => 'hawala_approved',
            'hawala_transfer_id' => $this->transfer->id,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $title = (string) __('frontend.notifications.hawala_approved_title');
        $body = (string) __('frontend.notifications.hawala_approved_message', [
            'amount' => format_price($this->transfer->admin_credited_amount, 2, $this->transfer->admin_credited_currency),
        ]);

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'hawala_approved',
                'hawala_transfer_id' => (string) $this->transfer->id,
                'title' => $title,
                'message' => $body,
            ],
        ];
    }
}
