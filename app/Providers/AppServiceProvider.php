<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Derrière Cloudflare (SSL Flexible), le serveur reçoit du HTTP :
        // on force le schéma HTTPS en production pour que les URLs, les
        // formulaires et les cookies de session soient corrects.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Politique de mot de passe unique (création, réinitialisation, 1re
        // connexion, profil…) : au moins 12 caractères, lettres et chiffres.
        // En production, on refuse en plus les mots de passe apparus dans des
        // fuites connues (vérif. k-anonyme chez haveibeenpwned, sans réseau en
        // test ; l'appel échoue « ouvert » si le service est indisponible).
        Password::defaults(function () {
            $regle = Password::min(12)->letters()->numbers();

            return $this->app->isProduction() ? $regle->uncompromised() : $regle;
        });
    }
}
