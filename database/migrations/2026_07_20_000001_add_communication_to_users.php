<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Droit communication extérieur : catégories (services + « inconnu »)
            // dont l'utilisateur reçoit les messages « Contacter votre Mairie ».
            // null = valeur par défaut du grade (direction = tout, autres = rien).
            $table->json('communication')->nullable()->after('droit');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('communication');
        });
    }
};
