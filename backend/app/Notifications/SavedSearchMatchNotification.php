<?php

namespace App\Notifications;

use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use App\Models\Ad;
use App\Models\SavedSearch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SavedSearchMatchNotification extends Notification
{
    use Queueable, SkipsInvalidMailRecipients;

    public function __construct(
        public SavedSearch $savedSearch,
        public Ad $ad
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', \App\Channels\FcmChannel::class, 'mail'];
    }

    public function toMail(object $notifiable)
    {
        $title = __('frontend.saved_searches.notification_title');
        $message = __('frontend.saved_searches.notification_message', [
            'search' => $this->savedSearch->name ?: ($this->savedSearch->filters['search'] ?? '#'.$this->savedSearch->id),
            'ad' => $this->ad->title,
        ]);

        return (new MailMessage)
            ->subject($title)
            ->markdown('vendor.notifications.ad-with-card', [
                'messageText' => $message,
                'actionText' => __('frontend.notifications.view_ad'),
                'actionUrl' => route('ads.show', $this->ad->uid),
                'ad' => $this->ad,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $title = __('frontend.saved_searches.notification_title');
        $message = __('frontend.saved_searches.notification_message', [
            'search' => $this->savedSearch->name ?: ($this->savedSearch->filters['search'] ?? '#'.$this->savedSearch->id),
            'ad' => $this->ad->title,
        ]);

        return [
            'title' => $title,
            'message' => $message,
            'type' => 'saved_search_match',
            'saved_search_id' => $this->savedSearch->id,
            'ad_uid' => $this->ad->uid,
            'click_url' => route('ads.show', $this->ad->uid),
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $title = __('frontend.saved_searches.notification_title');
        $body = __('frontend.saved_searches.notification_message', [
            'search' => $this->savedSearch->name ?: ($this->savedSearch->filters['search'] ?? '#'.$this->savedSearch->id),
            'ad' => $this->ad->title,
        ]);

        return [
            'title' => (string) $title,
            'body' => (string) $body,
            'data' => [
                'type' => 'saved_search_match',
                'saved_search_id' => (string) $this->savedSearch->id,
                'ad_uid' => (string) $this->ad->uid,
                'title' => (string) $title,
                'message' => (string) $body,
            ],
        ];
    }
}

