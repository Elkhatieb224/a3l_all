<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminTwoFactorMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public ?string $adminName,
        public string $purpose
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->purpose === \App\Models\AdminTwoFactorChallenge::TYPE_SETUP
            ? __('admin.two_factor.mail_subject_setup')
            : __('admin.two_factor.mail_subject_login');

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-two-factor',
            with: [
                'code' => $this->code,
                'adminName' => $this->adminName,
                'purpose' => $this->purpose,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
