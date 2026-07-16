<?php

namespace App\Mail;

use App\Models\Rappel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RappelCalendrier extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Rappel $rappel) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔔 MGDS — Rappel de votre calendrier (' . $this->rappel->date_rappel->format('d/m/Y') . ')',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.rappel-calendrier');
    }
}
