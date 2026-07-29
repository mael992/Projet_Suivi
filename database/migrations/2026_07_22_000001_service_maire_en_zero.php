<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le service « M. / Mme le Maire » passe du numéro 14 au numéro 0
 * pour apparaître en tête de liste partout (références « 0-1 », etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('service', 14)->update(['service' => 0]);
        DB::table('taches')->where('service', 14)->update(['service' => 0]);
        DB::table('standards')->where('service', 14)->update(['service' => 0]);

        if (Schema::hasTable('tickets')) {
            DB::table('tickets')->where('service', 14)->update(['service' => 0]);
        }

        // Références générées avec l'ancien numéro (14-1 → 0-1)
        foreach (['users', 'taches'] as $table) {
            foreach (DB::table($table)->where('reference', 'like', '14-%')->get(['id', 'reference']) as $ligne) {
                DB::table($table)->where('id', $ligne->id)
                    ->update(['reference' => '0-' . substr($ligne->reference, 3)]);
            }
        }

        // Droit communication extérieur : la catégorie "14" devient "0"
        foreach (DB::table('users')->whereNotNull('communication')->get(['id', 'communication']) as $u) {
            $cats = json_decode($u->communication, true) ?: [];
            $maj  = array_values(array_map(fn ($c) => $c === '14' ? '0' : $c, $cats));

            if ($maj !== $cats) {
                DB::table('users')->where('id', $u->id)->update(['communication' => json_encode($maj)]);
            }
        }
    }

    public function down(): void
    {
        DB::table('users')->where('service', 0)->update(['service' => 14]);
        DB::table('taches')->where('service', 0)->update(['service' => 14]);
        DB::table('standards')->where('service', 0)->update(['service' => 14]);

        if (Schema::hasTable('tickets')) {
            DB::table('tickets')->where('service', 0)->update(['service' => 14]);
        }
    }
};
