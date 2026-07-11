<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Droit d'application le plus fort possédé (hiérarchie Referentiel::DROITS)
            $table->string('droit')->nullable()->after('grade');
            // Fonction libre affichée sur la fiche contact (employés)
            $table->string('fonction')->nullable()->after('droit');
        });

        // Anciens grades : 4 = Secrétaire (supprimé) et 5 = Employé → nouveau 4 = Employé
        DB::table('users')->whereIn('grade', [4, 5])->update(['grade' => 4]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['droit', 'fonction']);
        });
    }
};
