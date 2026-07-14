<?php

namespace App\Notifications;

use App\Notifications\Concerns\SkipsInvalidMailRecipients;
use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportActionNotification extends Notification
{
    use Queueable, SkipsInvalidMailRecipients;

    public function __construct(
        public Report $report
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

        return (new MailMessage)
            ->subject($title)
            ->line($message)
            ->when($this->report->admin_notes, fn ($mail) => $mail->line(__('frontend.notifications.admin_notes') . ': ' . $this->report->admin_notes))
            ->action(__('frontend.notifications.view_report'), route('profile.reports.show', $this->report->id))
            ->line(__('frontend.notifications.thank_you'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'type' => 'report_action',
            'report_id' => $this->report->id,
            'status' => $this->report->status,
            'reports_url' => route('profile.reports.show', $this->report->id),
            'admin_notes' => $this->report->admin_notes,
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
                'type' => 'report_action',
                'report_id' => (string) $this->report->id,
                'status' => $this->report->status,
                'reports_url' => (string) route('profile.reports.show', $this->report->id),
                'title' => (string) $title,
                'message' => (string) $body,
            ],
        ];
    }

    private function getTitle(): string
    {
        return __('frontend.notifications.report_action_title');
    }

    private function getMessage(): string
    {
        $statusKey = "frontend.reports.status_{$this->report->status}";
        $statusText = __($statusKey);
        return __('frontend.notifications.report_action_message', [
            'status' => $statusText,
        ]);
    }
}
