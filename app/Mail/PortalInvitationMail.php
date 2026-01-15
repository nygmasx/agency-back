<?php

namespace App\Mail;

use App\Models\Client;
use App\Models\ClientCollaborator;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortalInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ClientCollaborator $collaborator,
        public Client $client,
        public ?string $portalUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invitation au portail client - {$this->client->name}",
        );
    }

    public function content(): Content
    {
        $loginUrl = $this->portalUrl ?? config('app.frontend_url') . '/portal/login';

        return new Content(
            view: 'emails.portal-invitation',
            with: [
                'collaboratorName' => $this->collaborator->name,
                'clientName' => $this->client->name,
                'loginUrl' => $loginUrl,
                'accessType' => $this->collaborator->access_type,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
