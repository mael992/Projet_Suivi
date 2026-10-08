<?php

namespace App\Mail;

use App\Models\SupportDemande;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Support technique : nouveau message pour l'équipe MGDS (pourAdmin), ou
 * pour la personne qui a écrit (accusé de réception ou réponse de l'équipe).
 * Le contenu des messages n'est jamais recopié dans l'e-mail.
 */
class SupportNouveauMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportDemande $demande,
        public bool $pourAdmin,
        public bool $nouvelle = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->pourAdmin
                ? 'MGDS — Support : nouveau message (' . $this->demande->reference . ')'
                : ($this->nouvelle
                    ? 'MGDS — Votre demande au support (' . $this->demande->reference . ')'
                    : 'MGDS — Le support vous a répondu (' . $this->demande->reference . ')'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.support-message');
    }
}
