<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pense-bête / Calendrier : rappels datés avec email le jour J
        Schema::create('rappels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date_rappel');
            $table->text('texte')->nullable();
            $table->string('fichier')->nullable(); // photo ou document déposé
            $table->boolean('envoye')->default(false);
            $table->timestamps();
        });

        // Pense-bête / Notes : style boîte mail, avec sous-dossiers
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('dossier')->nullable();
            $table->string('titre');
            $table->text('contenu')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
        Schema::dropIfExists('rappels');
    }
};
