<?php

namespace Tests;

use App\Http\Middleware\ProtectionFormulairePublic;
use App\Mail\CodeVerification;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Testing\Fakes\MailFake;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /**
     * Champs anti-robots d'un formulaire public affiché depuis une minute
     * (voir ProtectionFormulairePublic).
     */
    protected function jetonFormulaire(): array
    {
        return [ProtectionFormulairePublic::CHAMP_HEURE => encrypt(now()->subMinute()->timestamp)];
    }

    /**
     * Connexion complète par le formulaire : identifiant + mot de passe,
     * puis le code de double authentification reçu par e-mail.
     * Renvoie la réponse du mot de passe s'il n'y a pas eu de code à saisir.
     */
    protected function seConnecter(array $identifiants, array $verification = []): TestResponse
    {
        $this->simulerEmails();

        $reponse = $this->post('/login', $identifiants);
        if (! $reponse->isRedirect(route('a2f.connexion'))) {
            return $reponse;
        }

        return $this->post('/verification-connexion', ['code' => $this->dernierCodeA2F()] + $verification);
    }

    /** Dernier code de double authentification envoyé par e-mail. */
    protected function dernierCodeA2F(): string
    {
        $code = null;
        Mail::assertSent(CodeVerification::class, function (CodeVerification $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return $code;
    }

    /** Code de double authentification déjà saisi dans la session (modification importante). */
    protected function avecCodeA2F(): static
    {
        return $this->withSession(['a2f_confirme_at' => now()->timestamp]);
    }

    /** Intercepte les e-mails, sans effacer ceux qu'un test intercepte déjà. */
    protected function simulerEmails(): void
    {
        if (! Mail::getFacadeRoot() instanceof MailFake) {
            Mail::fake();
        }
    }
}
