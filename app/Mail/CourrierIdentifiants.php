<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourrierIdentifiants extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        protected string $pdfBinary,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'MGDS — Vos identifiants de connexion',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.courrier-identifiants',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinary, 'Courrier_MGDS_' . $this->user->username . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
