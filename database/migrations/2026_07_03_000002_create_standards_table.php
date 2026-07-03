<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Numéros de standard affichés dans la fiche contact
        Schema::create('standards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mairie_id')->constrained('mairies')->cascadeOnDelete();
            $table->unsignedTinyInteger('service');
            $table->string('telephone_indicatif', 8)->default('+33');
            $table->string('telephone', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standards');
    }
};
