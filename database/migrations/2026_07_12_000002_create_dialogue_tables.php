<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Boîte de dialogue : entraide entre mairies, partagée entre toutes
        Schema::create('dialogue_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('section'); // tableau-suivis, marche, fiche-contact, administration
            $table->text('texte');
            $table->timestamps();
        });

        Schema::create('dialogue_reponses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dialogue_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('texte');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialogue_reponses');
        Schema::dropIfExists('dialogue_questions');
    }
};
