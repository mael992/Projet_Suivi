<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mairies', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('email');
            $table->string('telephone_indicatif', 8)->default('+33');
            $table->string('telephone', 20)->nullable();
            $table->date('date_fin_abonnement')->nullable();
            $table->timestamps();
        });

        // Observateurs : reçoivent une copie de tous les mails de la mairie
        Schema::create('mairie_observateurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mairie_id')->constrained('mairies')->cascadeOnDelete();
            $table->string('nom')->nullable();
            $table->string('email');
            $table->timestamps();
            $table->unique(['mairie_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mairie_observateurs');
        Schema::dropIfExists('mairies');
    }
};
