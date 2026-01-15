<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortalLoginCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $clientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre code de connexion au portail',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.portal-login-code',
            with: [
                'code' => $this->code,
                'clientName' => $this->clientName,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
