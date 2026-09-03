<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Application Planning : feuille d'heures hebdomadaire de la mairie.
 *
 * Une ligne par agent et par semaine. Les créneaux de la semaine tiennent
 * dans une colonne JSON (même parti pris que MarcheZone::config) : sept
 * journées avec leurs plages horaires et leur éventuel jour de repos.
 *
 * Les absences ne sont PAS recopiées ici : elles sont relues depuis la table
 * `absences` à chaque affichage, pour qu'une absence de dernière minute ou
 * déclarée après coup apparaisse sans avoir à retoucher le planning.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plannings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mairie_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('annee');
            $table->unsignedTinyInteger('semaine');   // numéro ISO 1 → 53
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['mairie_id', 'annee', 'semaine']);
        });

        Schema::create('planning_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planning_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('jours')->nullable();                  // 1..7 => {repos, creneaux[[debut,fin]]}
            $table->unsignedSmallInteger('duree_contrat')->nullable(); // minutes dues sur la semaine
            $table->timestamp('signe_at')->nullable();          // signature de l'agent
            $table->timestamps();

            $table->unique(['planning_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_lignes');
        Schema::dropIfExists('plannings');
    }
};
