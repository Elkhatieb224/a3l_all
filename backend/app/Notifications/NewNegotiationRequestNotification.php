<?php

namespace App\Notifications;

use App\Models\Negotiation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewNegotiationRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Negotiation $negotiation
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
        $buyerName = $this->negotiation->buyer->name ?? __('frontend.notifications.user');
        $adTitle = $this->negotiation->ad->title ?? '';
        $offeredPrice = format_price($this->negotiation->offered_price, 0, $this->negotiation->currency);

        return (new MailMessage)
            ->subject(__('frontend.notifications.negotiation_request_title'))
            ->markdown('vendor.notifications.ad-with-card', [
                'messageText' => __('frontend.notifications.negotiation_request_message', [
                        'name' => $buyerName,
                        'ad_title' => $adTitle,
                        'offered_price' => $offeredPrice,
                    ])
                    . ($this->negotiation->message ? ("\n\n" . __('frontend.negotiations.message') . ': ' . \Illuminate\Support\Str::limit($this->negotiation->message, 150)) : ''),
                'actionText' => __('frontend.notifications.view_negotiations'),
                'actionUrl' => route('negotiations.received'),
                'ad' => $this->negotiation->ad,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $buyerName = $this->negotiation->buyer->name ?? __('frontend.notifications.user');
        $offeredPrice = format_price($this->negotiation->offered_price, 0, $this->negotiation->currency);
        $message = __('frontend.notifications.negotiation_request_message', [
            'name' => $buyerName,
            'ad_title' => $this->negotiation->ad->title ?? '',
            'offered_price' => $offeredPrice,
        ]);

        return [
            'title' => __('frontend.notifications.negotiation_request_title'),
            'message' => $message,
            'type' => 'new_negotiation_request',
            'negotiation_id' => $this->negotiation->id,
            'ad_uid' => $this->negotiation->ad->uid ?? null,
            'negotiations_url' => route('negotiations.received'),
            'buyer_name' => $buyerName,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $buyerName = $this->negotiation->buyer->name ?? __('frontend.notifications.user');
        $offeredPrice = format_price($this->negotiation->offered_price, 0, $this->negotiation->currency);
        $body = __('frontend.notifications.negotiation_request_message', [
            'name' => $buyerName,
            'ad_title' => $this->negotiation->ad->title ?? '',
            'offered_price' => $offeredPrice,
        ]);
        $title = __('frontend.notifications.negotiation_request_title');

        return [
            'title' => (string) $title,
            'body' => (string) $body,
            'data' => [
                'type' => 'new_negotiation_request',
                'negotiation_id' => (string) $this->negotiation->id,
                'negotiations_url' => (string) route('negotiations.received'),
                'title' => (string) $title,
                'message' => (string) $body,
            ],
        ];
    }
}
