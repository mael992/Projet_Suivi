<?php

namespace App\Mail;

use App\Services\DoubleAuthentification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Double authentification : code à 6 chiffres. Envoyé tout de suite (pas de
 * file d'attente) : la personne attend le code devant l'écran. Le code n'est
 * stocké que sous forme d'empreinte.
 */
class CodeVerification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $but,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'MGDS — Votre code de vérification : ' . $this->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.code-verification',
            with: [
                'motif'   => match ($this->but) {
                    DoubleAuthentification::BUT_CONNEXION => 'pour vous connecter à MGDS',
                    DoubleAuthentification::BUT_TICKET    => 'pour consulter votre demande à la mairie',
                    default                               => 'pour confirmer une modification importante de votre compte',
                },
                'minutes' => DoubleAuthentification::MINUTES_CODE,
            ],
        );
    }
}
