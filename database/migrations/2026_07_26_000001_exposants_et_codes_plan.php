<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marché :
 *  - demandes des commerçants souhaitant rejoindre le marché (inscription
 *    autonome, activable mairie par mairie) ;
 *  - codes d'accès au plan 2D, valables jusqu'au jour du marché.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Inscription autonome activée ou non, par mairie
        Schema::table('mairies', function (Blueprint $table) {
            $table->boolean('marche_inscription_ouverte')->default(false)->after('afficher_contact');
        });

        Schema::create('marche_demandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mairie_id')->constrained()->cascadeOnDelete();
            $table->string('prenom');
            $table->string('nom');
            $table->string('societe')->nullable();
            $table->string('activite');
            $table->string('telephone_indicatif', 8)->default('+33');
            $table->string('telephone', 20);
            $table->string('email');
            $table->decimal('longueur_souhaitee', 5, 1)->nullable();
            $table->text('message')->nullable();
            $table->string('statut')->default('en_attente'); // en_attente | acceptee | refusee
            $table->text('reponse')->nullable();
            $table->timestamps();
        });

        // Code d'accès au plan pour les exposants (un par zone et par date)
        Schema::create('marche_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marche_zone_id')->constrained()->cascadeOnDelete();
            $table->string('code', 8)->unique();
            $table->date('valable_le'); // jour du marché : au-delà, le code est caduc
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marche_codes');
        Schema::dropIfExists('marche_demandes');

        Schema::table('mairies', function (Blueprint $table) {
            $table->dropColumn('marche_inscription_ouverte');
        });
    }
};
