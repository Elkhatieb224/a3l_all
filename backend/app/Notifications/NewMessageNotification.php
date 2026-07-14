<?php

namespace App\Notifications;

use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable, SkipsInvalidMailRecipients;

    public function __construct(
        public Message $message,
        public User $sender,
        public Conversation $conversation
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', \App\Channels\FcmChannel::class, 'mail'];
    }

    public function toMail(object $notifiable)
    {
        $preview = \Illuminate\Support\Str::limit($this->message->message ?? __('frontend.notifications.attachment'), 180);
        $title = __('frontend.notifications.new_message_title', ['name' => $this->sender->name]);
        $messageText = $preview;

        $conversationUrl = route('messages.show', $this->conversation->id);

        $ad = $this->conversation->ad()->select(['id', 'uid', 'title', 'images', 'price', 'currency'])->first();
        if ($ad) {
            return (new MailMessage)
                ->subject($title)
                ->markdown('vendor.notifications.ad-with-card', [
                    'messageText' => $messageText,
                    'actionText' => __('frontend.negotiations.view_conversation'),
                    'actionUrl' => $conversationUrl,
                    'ad' => $ad,
                ]);
        }

        return (new MailMessage)
            ->subject($title)
            ->line($messageText)
            ->action(__('frontend.negotiations.view_conversation'), $conversationUrl);
    }

    public function toArray(object $notifiable): array
    {
        $preview = \Illuminate\Support\Str::limit($this->message->message ?? __('frontend.notifications.attachment'), 80);
        $title = __('frontend.notifications.new_message_title', ['name' => $this->sender->name]);
        return [
            'title' => $title,
            'message' => $preview,
            'type' => 'new_message',
            'conversation_id' => $this->conversation->id,
            'messages_url' => route('messages.show', $this->conversation->id),
            'sender_name' => $this->sender->name,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $preview = \Illuminate\Support\Str::limit($this->message->message ?? __('frontend.notifications.attachment'), 80);
        $title = __('frontend.notifications.new_message_title', ['name' => $this->sender->name]);

        return [
            'title' => (string) $title,
            'body' => (string) $preview,
            'data' => [
                'type' => 'new_message',
                'conversation_id' => (string) $this->conversation->id,
                'messages_url' => (string) route('messages.show', $this->conversation->id),
                'title' => (string) $title,
                'message' => (string) $preview,
            ],
        ];
    }
}
