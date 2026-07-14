<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserCustomNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $channelType = 'both' // push, email, both
    ) {
    }

    public function via(object $notifiable): array
    {
        switch ($this->channelType) {
            case 'push':
                return ['database', \App\Channels\FcmChannel::class];
            case 'email':
                return ['database', 'mail'];
            case 'both':
            default:
                return ['database', \App\Channels\FcmChannel::class, 'mail'];
        }
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        if ($channel !== 'mail') {
            return true;
        }

        $email = (string) ($notifiable->email ?? '');
        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Payload for Firebase Cloud Messaging (push to mobile).
     * FCM requires all data values to be strings.
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->message,
            'data' => [
                'type' => 'admin_notification',
                'title' => (string) $this->title,
                'message' => (string) $this->message,
            ],
        ];
    }
    
    public function toMail(object $notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject($this->title)
            ->markdown('vendor.notifications.admin-custom-email', [
                'messageText' => $this->message,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}

