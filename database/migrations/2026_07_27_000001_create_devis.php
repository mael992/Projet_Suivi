<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Devis d'abonnement MGDS adressés aux mairies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mairie_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();

            // Destinataire (repris de la mairie, modifiable)
            $table->string('client_nom');
            $table->string('client_adresse')->nullable();
            $table->string('client_email')->nullable();

            $table->date('date_devis');
            $table->unsignedSmallInteger('validite_jours')->default(30);
            $table->string('lieu_execution')->nullable();
            $table->string('delai_execution')->nullable();
            $table->text('conditions')->nullable();
            $table->text('modalites_paiement')->nullable();

            $table->json('lignes');                       // désignation, quantité, prix unitaire HT
            $table->decimal('taux_tva', 5, 2)->default(20);
            $table->string('statut')->default('brouillon'); // brouillon | envoye | accepte | refuse
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};
