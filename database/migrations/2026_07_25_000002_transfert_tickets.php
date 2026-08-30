<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transfert des messages externes : le destinataire initial (le « facteur »)
 * peut redistribuer une demande à un service ou à une personne précise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Service vers lequel le ticket a été transféré (null = pas de transfert)
            $table->unsignedTinyInteger('transfere_service')->nullable()->after('confidents');
            // Personne vers laquelle le ticket a été transféré
            $table->foreignId('transfere_user_id')->nullable()->after('transfere_service')
                ->constrained('users')->nullOnDelete();
            // Qui a transféré, et quand (le « facteur » garde la main)
            $table->foreignId('transfere_par')->nullable()->after('transfere_user_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('transfere_at')->nullable()->after('transfere_par');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transfere_user_id');
            $table->dropConstrainedForeignId('transfere_par');
            $table->dropColumn(['transfere_service', 'transfere_at']);
        });
    }
};
