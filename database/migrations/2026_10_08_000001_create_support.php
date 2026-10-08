<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support technique MGDS : demandes adressées à l'équipe MGDS (admins),
 * par un agent connecté ou par une personne sans compte.
 *
 * Confidentialité : seuls les admins les lisent, et leurs réponses sont
 * signées « Admin » — on ne garde pas quel admin a répondu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_demandes', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            // Jeton secret du lien de suivi
            $table->string('jeton', 64)->unique();
            // Agent connecté au moment de la demande (null = sans compte MGDS)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('avec_compte')->default(false);
            // Coordonnées : seulement pour une personne sans compte
            $table->string('nom', 100)->nullable();
            $table->string('prenom', 100)->nullable();
            $table->string('email')->nullable();
            // Réponses du questionnaire de l'assistant
            $table->string('concerne', 30);
            $table->text('precision')->nullable();
            $table->string('statut', 20)->default('reception');
            $table->timestamp('cloture_at')->nullable();
            $table->timestamps();
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_demande_id')->constrained('support_demandes')->cascadeOnDelete();
            // demandeur | assistant | admin
            $table->string('auteur', 20);
            $table->text('corps');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_demandes');
    }
};
