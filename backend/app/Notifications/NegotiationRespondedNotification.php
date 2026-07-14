<?php

namespace App\Notifications;

use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use App\Models\Negotiation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NegotiationRespondedNotification extends Notification
{
    use Queueable, SkipsInvalidMailRecipients;

    public function __construct(
        public Negotiation $negotiation
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', \App\Channels\FcmChannel::class, 'mail'];
    }

    public function toMail(object $notifiable)
    {
        $adTitle = $this->negotiation->ad->title ?? '';
        $sellerName = $this->negotiation->seller->name ?? __('frontend.notifications.user');

        if ($this->negotiation->status === 'accepted') {
            $subject = __('frontend.notifications.negotiation_accepted_title');
            $mail = (new MailMessage)
                ->subject($subject)
                ->line(__('frontend.notifications.negotiation_accepted_message', [
                    'ad_title' => $adTitle,
                    'seller' => $sellerName,
                ]));
            if ($this->negotiation->conversation_id) {
                $mail->action(__('frontend.negotiations.view_conversation'), route('messages.show', $this->negotiation->conversation_id));
            } else {
                $mail->action(__('frontend.notifications.view_negotiations'), route('negotiations.sent'));
            }
        } else {
            $subject = __('frontend.notifications.negotiation_rejected_title');
            $mail = (new MailMessage)
                ->subject($subject)
                ->line(__('frontend.notifications.negotiation_rejected_message', [
                    'ad_title' => $adTitle,
                ]))
                ->when($this->negotiation->rejection_reason, fn ($m) => $m->line(__('frontend.negotiations.rejection_reason') . ': ' . \Illuminate\Support\Str::limit($this->negotiation->rejection_reason, 200)))
                ->action(__('frontend.notifications.view_negotiations_sent'), route('negotiations.sent'));
        }

        return $mail->line(__('frontend.notifications.thank_you'));
    }

    public function toArray(object $notifiable): array
    {
        $adTitle = $this->negotiation->ad->title ?? '';
        $sellerName = $this->negotiation->seller->name ?? __('frontend.notifications.user');

        if ($this->negotiation->status === 'accepted') {
            $title = __('frontend.notifications.negotiation_accepted_title');
            $message = __('frontend.notifications.negotiation_accepted_message', [
                'ad_title' => $adTitle,
                'seller' => $sellerName,
            ]);
            $url = $this->negotiation->conversation_id
                ? route('messages.show', $this->negotiation->conversation_id)
                : route('negotiations.sent');
        } else {
            $title = __('frontend.notifications.negotiation_rejected_title');
            $message = __('frontend.notifications.negotiation_rejected_message', ['ad_title' => $adTitle]);
            $url = route('negotiations.sent');
        }

        return [
            'title' => $title,
            'message' => $message,
            'type' => 'negotiation_responded',
            'negotiation_id' => $this->negotiation->id,
            'status' => $this->negotiation->status,
            'conversation_id' => $this->negotiation->conversation_id,
            'click_url' => $url,
            'ad_title' => $adTitle,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $adTitle = $this->negotiation->ad->title ?? '';
        $sellerName = $this->negotiation->seller->name ?? __('frontend.notifications.user');

        if ($this->negotiation->status === 'accepted') {
            $title = __('frontend.notifications.negotiation_accepted_title');
            $body = __('frontend.notifications.negotiation_accepted_message', [
                'ad_title' => $adTitle,
                'seller' => $sellerName,
            ]);
            $url = $this->negotiation->conversation_id
                ? route('messages.show', $this->negotiation->conversation_id)
                : route('negotiations.sent');
        } else {
            $title = __('frontend.notifications.negotiation_rejected_title');
            $body = __('frontend.notifications.negotiation_rejected_message', ['ad_title' => $adTitle]);
            $url = route('negotiations.sent');
        }

        return [
            'title' => (string) $title,
            'body' => (string) $body,
            'data' => [
                'type' => 'negotiation_responded',
                'negotiation_id' => (string) $this->negotiation->id,
                'status' => $this->negotiation->status,
                'conversation_id' => $this->negotiation->conversation_id ? (string) $this->negotiation->conversation_id : '',
                'click_url' => (string) $url,
                'title' => (string) $title,
                'message' => (string) $body,
            ],
        ];
    }
}
