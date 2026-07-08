<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fond de plan (image de la place) propre à chaque marché
        Schema::table('marche_plans', function (Blueprint $table) {
            $table->string('image')->nullable()->after('notes');
        });

        // Emplacements posés librement en 2D sur le fond de plan
        // (les colonnes "axe" restent pour compatibilité, mais deviennent optionnelles)
        Schema::table('marche_emplacements', function (Blueprint $table) {
            $table->foreignId('marche_plan_id')->nullable()->after('marche_axe_id')
                ->constrained('marche_plans')->cascadeOnDelete();
            $table->foreignId('marche_axe_id')->nullable()->change();
            $table->foreignId('commercant_id')->nullable()->change();   // emplacement "libre" possible
            $table->decimal('position', 6, 2)->nullable()->change();
            $table->decimal('longueur', 5, 2)->nullable()->change();

            $table->string('label')->nullable();                  // n° d'emplacement (ex : 488, B12, CHALET 3…)
            $table->decimal('pos_x', 6, 3)->nullable();           // en % du fond de plan
            $table->decimal('pos_y', 6, 3)->nullable();
            $table->decimal('largeur_pct', 6, 3)->nullable();
            $table->decimal('hauteur_pct', 6, 3)->nullable();
            $table->string('couleur', 20)->default('#e6a23c');    // orange / bleu comme sur les plans papier
            $table->boolean('electricite')->default(false);       // ⚡
        });
    }

    public function down(): void
    {
        Schema::table('marche_emplacements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marche_plan_id');
            $table->dropColumn(['label', 'pos_x', 'pos_y', 'largeur_pct', 'hauteur_pct', 'couleur', 'electricite']);
        });

        Schema::table('marche_plans', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
