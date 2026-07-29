<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le statut par défaut des tickets était resté à « ouvert », valeur qui ne
 * correspond à aucun dossier du Centre de Messagerie : les nouveaux messages
 * n'apparaissaient donc nulle part côté mairie. Défaut corrigé en
 * « reception » et rattrapage des tickets concernés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('statut')->default('reception')->change();
        });

        DB::table('tickets')
            ->whereNotIn('statut', ['reception', 'reponse', 'cloture', 'reouverture_demandee'])
            ->update(['statut' => 'reception']);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('statut')->default('ouvert')->change();
        });
    }
};
