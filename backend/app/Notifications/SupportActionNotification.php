<?php

namespace App\Notifications;

use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use App\Models\SupportMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportActionNotification extends Notification
{
    use Queueable, SkipsInvalidMailRecipients;

    public function __construct(
        public SupportMessage $supportMessage,
        public string $actionType = 'response' // 'response' | 'status'
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', \App\Channels\FcmChannel::class, 'mail'];
    }

    public function toMail(object $notifiable)
    {
        $title = $this->getTitle();
        $message = $this->getMessage();

        $mail = (new MailMessage)
            ->subject($title)
            ->line($message);

        if ($this->actionType === 'response' && $this->supportMessage->admin_response) {
            $mail->line(__('frontend.notifications.admin_response') . ': ' . \Illuminate\Support\Str::limit($this->supportMessage->admin_response, 200));
        }

        return $mail
            ->action(__('frontend.notifications.view_support_message'), route('profile.support-messages.show', $this->supportMessage->id))
            ->line(__('frontend.notifications.thank_you'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'type' => 'support_action',
            'support_message_id' => $this->supportMessage->id,
            'action_type' => $this->actionType,
            'support_url' => route('profile.support-messages.show', $this->supportMessage->id),
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $title = $this->getTitle();
        $body = $this->getMessage();

        return [
            'title' => (string) $title,
            'body' => (string) $body,
            'data' => [
                'type' => 'support_action',
                'support_message_id' => (string) $this->supportMessage->id,
                'support_url' => (string) route('profile.support-messages.show', $this->supportMessage->id),
                'title' => (string) $title,
                'message' => (string) $body,
            ],
        ];
    }

    private function getTitle(): string
    {
        if ($this->actionType === 'response') {
            return __('frontend.notifications.support_response_title');
        }
        return __('frontend.notifications.support_status_title');
    }

    private function getMessage(): string
    {
        if ($this->actionType === 'response') {
            return __('frontend.notifications.support_response_message', [
                'subject' => \Illuminate\Support\Str::limit($this->supportMessage->subject, 50),
            ]);
        }
        $statusKey = "frontend.help.status_{$this->supportMessage->status}";
        $statusText = __($statusKey);
        if ($statusText === $statusKey) {
            $statusText = ucfirst(str_replace('_', ' ', $this->supportMessage->status));
        }
        return __('frontend.notifications.support_status_message', [
            'subject' => \Illuminate\Support\Str::limit($this->supportMessage->subject, 50),
            'status' => $statusText,
        ]);
    }
}
