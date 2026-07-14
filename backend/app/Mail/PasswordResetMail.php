<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $userName;

    public function __construct($code, $userName = null)
    {
        $this->code = $code;
        $this->userName = $userName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('frontend.auth.password_reset_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: [
                'code' => $this->code,
                'userName' => $this->userName,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
