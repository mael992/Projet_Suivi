<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Sépare « visibilité sur tous les messages » (lecture) du « droit
 *    communication extérieur » (être destinataire d'un service).
 * 2. Références de tickets préfixées par la mairie : « 1-1 », « 2-1 »…
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('voit_tous_messages')->default(false)->after('communication');
        });

        // La direction garde la visibilité globale (comportement actuel)
        DB::table('users')->whereIn('grade', [1, 2, 3])->update(['voit_tous_messages' => true]);

        // …mais n'est plus destinataire par défaut : les services ne doivent
        // apparaître sur « Contacter votre Mairie » que s'ils sont cochés.
        DB::table('users')->whereIn('grade', [1, 2, 3])->whereNull('communication')
            ->update(['communication' => json_encode([])]);

        // Références des tickets existants : « n » → « {mairie}-n »
        if (Schema::hasTable('tickets')) {
            foreach (DB::table('tickets')->orderBy('id')->get(['id', 'mairie_id', 'reference']) as $t) {
                if (! str_contains($t->reference, '-')) {
                    DB::table('tickets')->where('id', $t->id)
                        ->update(['reference' => $t->mairie_id . '-' . $t->reference]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('voit_tous_messages');
        });
    }
};
