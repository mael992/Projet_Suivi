<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fin de la cascade « de gauche à droite » sur les droits d'application.
 *
 * Chaque droit se coche désormais indépendamment ; seules deux implications
 * subsistent, et aucune ne franchit la frontière d'une application :
 *  - « Gestion des utilisateurs » ouvre tout ;
 *  - « modification » ouvre la « lecture » de la MÊME application.
 *
 * Le droit unique (colonne `droit`) devient donc une liste (`droits`).
 */
return new class extends Migration
{
    /** Ancien ordre hiérarchique, du plus fort au plus faible. */
    private const ORDRE = [
        'gestion_utilisateurs',
        'contacts_modification',
        'contacts_lecture',
        'marche_gestion',
        'taches_gestion',
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('droits')->nullable()->after('grade');
        });

        // Reprise : l'ancien droit donnait tous ceux situés à sa droite.
        // `null` (droit par défaut du statut) est conservé tel quel.
        foreach (DB::table('users')->whereNotNull('droit')->get(['id', 'droit']) as $user) {
            $rang = array_search($user->droit, self::ORDRE, true);

            DB::table('users')->where('id', $user->id)->update([
                'droits' => json_encode($rang === false ? [] : array_slice(self::ORDRE, $rang)),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('droit');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('droit')->nullable()->after('grade');
        });

        // On ne peut restituer qu'un seul droit : le plus fort de la liste.
        foreach (DB::table('users')->whereNotNull('droits')->get(['id', 'droits']) as $user) {
            $droits = json_decode($user->droits ?? '[]', true) ?: [];

            $plusFort = null;
            foreach (self::ORDRE as $droit) {
                if (in_array($droit, $droits, true)) {
                    $plusFort = $droit;
                    break;
                }
            }

            DB::table('users')->where('id', $user->id)->update([
                'droit' => $plusFort ?? 'aucun',
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('droits');
        });
    }
};
