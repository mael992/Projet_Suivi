<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CGU : acceptation obligatoire à la première connexion.
 * Binôme : personne qui reprend le travail en cas d'absence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('cgu_acceptees_at')->nullable()->after('voit_tous_messages');

            // Binôme / remplaçant pendant une absence
            $table->foreignId('binome_id')->nullable()->after('cgu_acceptees_at')
                ->constrained('users')->nullOnDelete();
            $table->boolean('absent')->default(false)->after('binome_id');
            $table->date('absent_du')->nullable()->after('absent');
            $table->date('absent_au')->nullable()->after('absent_du');
            $table->string('absence_motif')->nullable()->after('absent_au');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('binome_id');
            $table->dropColumn(['cgu_acceptees_at', 'absent', 'absent_du', 'absent_au', 'absence_motif']);
        });
    }
};
