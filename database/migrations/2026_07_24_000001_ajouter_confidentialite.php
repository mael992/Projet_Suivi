<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Confidentialité absolue : une tâche ou un message peut être réservé à des
 * personnes nommément choisies. Personne d'autre ne le voit — pas même la
 * direction (Maire, Directeur de Cabinet, DGS) ni les mini-admins.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->boolean('confidentiel')->default(false)->after('statut');
            $table->json('confidents')->nullable()->after('confidentiel'); // ids des personnes autorisées
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->boolean('confidentiel')->default(false)->after('statut');
            $table->json('confidents')->nullable()->after('confidentiel');
        });
    }

    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->dropColumn(['confidentiel', 'confidents']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['confidentiel', 'confidents']);
        });
    }
};
