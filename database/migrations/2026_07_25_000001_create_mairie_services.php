<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Services personnalisés par mairie : chaque commune peut renommer,
 * désactiver ou ajouter ses propres services. Sans personnalisation,
 * le référentiel national par défaut s'applique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mairie_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mairie_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('numero'); // clé stable, sert dans les références (ex. 12-1)
            $table->string('nom');
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['mairie_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mairie_services');
    }
};
