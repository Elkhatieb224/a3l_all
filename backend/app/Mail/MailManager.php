<?php

namespace App\Mail;

use Illuminate\Mail\MailManager as BaseMailManager;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class MailManager extends BaseMailManager
{
    /**
     * Configure the additional SMTP driver options (stream/SSL options for certificate verification).
     */
    protected function configureSmtpTransport(EsmtpTransport $transport, array $config): EsmtpTransport
    {
        $transport = parent::configureSmtpTransport($transport, $config);

        $stream = $transport->getStream();
        if ($stream instanceof SocketStream && ! empty($config['stream'])) {
            $stream->setStreamOptions($config['stream']);
        }

        return $transport;
    }
}
