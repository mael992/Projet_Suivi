<?php

namespace App\Mail;

use App\Models\Tache;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TacheCloturee extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tache $tache,
        public ?User $destinataire,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'MGDS — Tâche terminée (' . $this->tache->reference . ')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tache-cloturee',
        );
    }
}
