<?php

namespace App\Http\Controllers\Marche\Concerns;

use App\Models\Mairie;
use Illuminate\Http\Request;

/**
 * Contexte mairie de l'application Marché : les utilisateurs travaillent
 * sur leur propre mairie ; les admins choisissent la mairie via ?mairie=.
 */
trait ResolveMairie
{
    protected function mairieCourante(Request $request): Mairie
    {
        $user = $request->user();

        // Accès à l'application Marché réservé au droit « marche_gestion » (ou admin)
        abort_unless($user->isAdmin() || $user->aDroit('marche_gestion'), 403);

        if ($user->isAdmin()) {
            $mairie = $request->filled('mairie')
                ? Mairie::find($request->integer('mairie'))
                : Mairie::orderBy('nom')->first();

            abort_unless($mairie !== null, 404, 'Aucune mairie enregistrée.');

            return $mairie;
        }

        abort_unless($user->mairie !== null, 403);

        return $user->mairie;
    }

    protected function verifierEdition(): void
    {
        abort_unless(auth()->user()->aDroit('marche_gestion'), 403);
    }
}
