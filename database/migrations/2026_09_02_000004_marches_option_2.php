<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Navigation « option 2 » du Marché, proposée en parallèle de la vue
 * aérienne : un marché daté regroupe ses endroits (rue, place, trottoir…),
 * chaque endroit menant au plan 2D/3D existant.
 *
 * Rien n'est retiré : les zones sans marché restent gérées comme avant sur
 * la vue aérienne, le temps de choisir entre les deux présentations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mairie_id')->constrained()->cascadeOnDelete();
            $table->string('nom', 120);
            $table->date('date_deroulement')->nullable();
            $table->timestamps();

            $table->index(['mairie_id', 'date_deroulement']);
        });

        Schema::table('marche_zones', function (Blueprint $table) {
            $table->foreignId('marche_id')->nullable()->after('mairie_id')
                ->constrained('marches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('marche_zones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marche_id');
        });

        Schema::dropIfExists('marches');
    }
};
