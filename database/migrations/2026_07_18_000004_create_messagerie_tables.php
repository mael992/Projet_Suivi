<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tickets « Message externe » : demandes envoyées à une mairie
        // depuis la page publique « Contacter votre Mairie ».
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mairie_id')->constrained()->cascadeOnDelete();
            $table->string('reference');
            $table->string('type')->default('externe'); // externe | interne | support
            $table->unsignedTinyInteger('service')->nullable(); // null = « Je ne sais pas »
            $table->string('nom');
            $table->string('prenom');
            $table->string('telephone_indicatif', 8)->default('+33');
            $table->string('telephone', 20);
            $table->string('email');
            $table->string('sujet');
            $table->json('photos')->nullable();
            $table->string('statut')->default('ouvert'); // ouvert | transfere
            $table->timestamps();
        });

        // Fil de discussion d'un ticket (1er message = description initiale)
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            // null = message de la personne extérieure ; sinon agent de la mairie
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('corps');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
    }
};
