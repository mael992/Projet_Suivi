<?php

namespace App\Mail;

use App\Models\Mairie;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbonnementExpire extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Mairie $mairie) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'MGDS — Fin de votre abonnement (' . $this->mairie->nom . ')',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.abonnement-expire');
    }
}
