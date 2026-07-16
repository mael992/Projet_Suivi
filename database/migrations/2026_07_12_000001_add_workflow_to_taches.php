<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            // Employé de substitution choisi par le responsable
            $table->foreignId('substitut_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();
            // null = en attente de prise en charge ; 'responsable' | 'substitution'
            $table->string('prise_en_charge')->nullable()->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('substitut_id');
            $table->dropColumn('prise_en_charge');
        });
    }
};
