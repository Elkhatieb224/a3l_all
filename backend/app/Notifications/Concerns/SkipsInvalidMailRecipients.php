<?php

namespace App\Notifications\Concerns;

trait SkipsInvalidMailRecipients
{
    public function shouldSend(object $notifiable, string $channel): bool
    {
        if ($channel !== 'mail') {
            return true;
        }

        $email = (string) ($notifiable->email ?? '');
        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
