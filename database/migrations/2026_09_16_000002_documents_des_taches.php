<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documents joints aux tâches (demande de la DGS) : au-delà de la photo,
 * on joint un PDF, un devis Word, un tableau… à la tâche elle-même, et on
 * peut en joindre aussi en réponse, au moment de la clôture.
 *
 * Deux listes JSON (même parti pris que ticket_messages.fichiers) : chaque
 * entrée garde le chemin sur le disque privé, le nom d'origine et la taille.
 * Le disque est privé parce que certaines tâches sont confidentielles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->json('fichiers')->nullable()->after('photo_apres');
            $table->json('fichiers_cloture')->nullable()->after('fichiers');
        });
    }

    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->dropColumn(['fichiers', 'fichiers_cloture']);
        });
    }
};
