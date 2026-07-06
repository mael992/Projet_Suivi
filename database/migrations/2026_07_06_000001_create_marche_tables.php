<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Commerçants / exposants de la mairie
        Schema::create('commercants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mairie_id')->constrained('mairies')->cascadeOnDelete();
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('activite');                       // ex : fleuriste, vêtements…
            $table->string('telephone_indicatif', 8)->default('+33');
            $table->string('telephone', 20)->nullable();
            $table->string('email')->nullable();
            $table->decimal('longueur_defaut', 5, 2)->default(3); // longueur habituelle du stand (m)
            $table->timestamps();

            $table->index(['mairie_id', 'activite']);
        });

        // Plans de marché : un plan par date, modifiable
        Schema::create('marche_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mairie_id')->constrained('mairies')->cascadeOnDelete();
            $table->string('nom');                            // ex : Marché hebdomadaire
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['mairie_id', 'date']);
        });

        // Axes du plan (trottoirs, allées…) avec leur longueur en mètres
        Schema::create('marche_axes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marche_plan_id')->constrained('marche_plans')->cascadeOnDelete();
            $table->string('nom');                            // ex : Trottoir gauche rue de la Mairie
            $table->decimal('longueur', 6, 2);                // en mètres
            $table->timestamps();
        });

        // Emplacements : un commerçant placé sur un axe = une venue à la date du plan
        Schema::create('marche_emplacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marche_axe_id')->constrained('marche_axes')->cascadeOnDelete();
            $table->foreignId('commercant_id')->constrained('commercants')->cascadeOnDelete();
            $table->decimal('position', 6, 2);                // début du stand sur l'axe (m)
            $table->decimal('longueur', 5, 2);                // longueur occupée (m)
            $table->decimal('montant', 8, 2)->nullable();     // argent rapporté à la mairie
            $table->timestamps();

            $table->index('commercant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marche_emplacements');
        Schema::dropIfExists('marche_axes');
        Schema::dropIfExists('marche_plans');
        Schema::dropIfExists('commercants');
    }
};
