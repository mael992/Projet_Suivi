<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mairie_id')->constrained('mairies')->cascadeOnDelete();
            $table->string('reference');                          // ex: 12-0 (service - numéro)
            $table->unsignedTinyInteger('service');               // service chargé de réaliser la tâche
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();      // employé assigné
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();   // créateur
            $table->string('statut')->default('ouvert');          // ouvert | en_cours | fait
            $table->string('photo_avant')->nullable();            // photo de la tâche à faire
            $table->string('photo_apres')->nullable();            // photo de la tâche une fois finie
            $table->text('description_instruction')->nullable();  // description & remarques (d'instruction)
            $table->text('description_cloture')->nullable();      // description & remarques (de clôture)
            $table->date('date_butoir');                          // clôture prévue
            $table->dateTime('date_cloture')->nullable();         // automatique quand statut = fait
            $table->timestamps();

            $table->index(['mairie_id', 'service']);
            $table->index(['mairie_id', 'statut']);
            $table->unique(['mairie_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taches');
    }
};
