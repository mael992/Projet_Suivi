<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fond « vue aérienne de la ville » (image propre à chaque mairie)
        Schema::table('mairies', function (Blueprint $table) {
            $table->string('vue_aerienne')->nullable();
        });

        // Zones de marché posées sur la vue aérienne (place, rue, trottoir…)
        Schema::create('marche_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mairie_id')->constrained()->cascadeOnDelete();
            $table->string('nom');
            $table->string('type')->default('place'); // place, rue, trottoir, parking, autre
            $table->decimal('pos_x', 6, 3)->default(40);
            $table->decimal('pos_y', 6, 3)->default(40);
            $table->decimal('largeur_pct', 6, 3)->default(18);
            $table->decimal('hauteur_pct', 6, 3)->default(12);
            $table->decimal('rotation', 6, 2)->default(0);
            $table->string('couleur', 20)->default('#2e86de');
            // Configuration du marché de la zone (type, disposition, écart, obstacles…)
            $table->string('marche_type')->nullable();
            $table->decimal('longueur_m', 6, 1)->default(50);
            $table->decimal('largeur_m', 6, 1)->default(30);
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marche_zones');

        Schema::table('mairies', function (Blueprint $table) {
            $table->dropColumn('vue_aerienne');
        });
    }
};
