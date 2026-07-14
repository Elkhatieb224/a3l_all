<?php

namespace App\Notifications;

use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use App\Models\PackageRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PackageRequestRespondedNotification extends Notification
{
    use Queueable, SkipsInvalidMailRecipients;

    public function __construct(
        public PackageRequest $packageRequest
    ) {
    }

    public function via(object $notifiable): array
    {
        // إرسال push و database أولاً حتى يصل الإشعار حتى لو فشل البريد
        return ['database', \App\Channels\FcmChannel::class, 'mail'];
    }

    public function toMail(object $notifiable)
    {
        $title = $this->packageRequest->status === 'approved'
            ? __('frontend.notifications.package_request_approved_title')
            : __('frontend.notifications.package_request_rejected_title');
        $message = $this->getMessageText();

        return (new MailMessage)
            ->subject($title)
            ->line($message)
            ->when($this->packageRequest->admin_response, fn ($mail) => $mail->line(__('frontend.notifications.admin_response') . ': ' . $this->packageRequest->admin_response))
            ->line(__('frontend.notifications.thank_you'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->packageRequest->status === 'approved'
                ? __('frontend.notifications.package_request_approved_title')
                : __('frontend.notifications.package_request_rejected_title'),
            'message' => $this->getMessageText(),
            'admin_response' => $this->packageRequest->admin_response,
            'type' => 'package_request_responded',
            'package_request_id' => $this->packageRequest->id,
            'status' => $this->packageRequest->status,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $title = $this->packageRequest->status === 'approved'
            ? (string) __('frontend.notifications.package_request_approved_title')
            : (string) __('frontend.notifications.package_request_rejected_title');
        $body = $this->getMessageText();

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'package_request_responded',
                'package_request_id' => (string) $this->packageRequest->id,
                'status' => $this->packageRequest->status,
                'admin_response' => $this->packageRequest->admin_response ?? '',
                'title' => $title,
                'message' => $body,
            ],
        ];
    }

    private function getMessageText(): string
    {
        if ($this->packageRequest->status === 'approved') {
            return __('frontend.notifications.package_request_approved_message', [
                'package' => $this->packageRequest->package->name_ar ?? $this->packageRequest->package->name ?? '',
            ]);
        }
        return __('frontend.notifications.package_request_rejected_message');
    }
}
