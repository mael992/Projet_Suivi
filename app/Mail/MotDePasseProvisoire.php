<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mot de passe provisoire demandé depuis « Mot de passe oublié ».
 * Le mot de passe en clair ne transite que par cet e-mail : il n'est
 * stocké que sous forme hachée.
 */
class MotDePasseProvisoire extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $motDePasse,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'MGDS — Votre mot de passe provisoire',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.mot-de-passe-provisoire',
            with: ['heures' => User::HEURES_PASSWORD_PROVISOIRE],
        );
    }
}
