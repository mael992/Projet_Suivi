<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NouveauMessageTicket extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public bool $pourCitoyen,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'MGDS — Nouveau message concernant votre demande (' . $this->ticket->sujet . ')',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.nouveau-message-ticket');
    }
}
