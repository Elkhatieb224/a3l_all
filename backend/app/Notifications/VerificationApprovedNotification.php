<?php

namespace App\Notifications;

use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationApprovedNotification extends Notification
{
    use Queueable, SkipsInvalidMailRecipients;

    public function __construct(
        public VerificationRequest $verificationRequest
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', \App\Channels\FcmChannel::class, 'mail'];
    }

    public function toMail(object $notifiable)
    {
        $title = __('frontend.notifications.verification_approved_title');
        $message = __('frontend.notifications.verification_approved_message');

        return (new MailMessage)
            ->subject($title)
            ->line($message)
            ->line(__('frontend.notifications.thank_you'));
    }

    public function toArray(object $notifiable): array
    {
        $title = __('frontend.notifications.verification_approved_title');
        $message = __('frontend.notifications.verification_approved_message');

        return [
            'title' => $title,
            'message' => $message,
            'type' => 'verification_approved',
            'verification_request_id' => $this->verificationRequest->id,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $title = (string) __('frontend.notifications.verification_approved_title');
        $message = (string) __('frontend.notifications.verification_approved_message');

        return [
            'title' => $title,
            'body' => $message,
            'data' => [
                'type' => 'verification_approved',
                'verification_request_id' => (string) $this->verificationRequest->id,
                'title' => $title,
                'message' => $message,
            ],
        ];
    }
}
