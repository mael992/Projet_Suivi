<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Double authentification (A2F) : appareils auxquels une personne a choisi
 * de faire confiance (« Je fais confiance à cet appareil »). Le navigateur
 * garde un jeton secret ; on ne stocke ici que son empreinte, avec l'adresse
 * IP et le navigateur du moment : si l'un d'eux change, le code est redemandé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appareils_confiance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Empreinte SHA-256 du jeton gardé par le navigateur
            $table->string('jeton_hash', 64)->unique();
            $table->string('ip', 45);
            // Empreinte SHA-256 du navigateur (User-Agent)
            $table->string('navigateur_hash', 64);
            $table->timestamp('expire_at');
            $table->timestamps();

            $table->index(['user_id', 'expire_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appareils_confiance');
    }
};
