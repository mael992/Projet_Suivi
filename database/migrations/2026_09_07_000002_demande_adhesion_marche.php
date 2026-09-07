<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demande d'adhésion au marché : elle devient une conversation.
 *
 * La demande existait déjà (marche_demandes), mais sans échange possible :
 * on acceptait ou on refusait, sans pouvoir discuter avec le candidat. On la
 * relie donc à un ticket, ce qui lui apporte d'un coup la conversation, le
 * suivi par référence + e-mail, la clôture et la réouverture.
 *
 * Le ticket porte le type « marche » : il n'atterrit pas dans les dossiers
 * des messages d'habitants mais dans sa propre boîte de réception, réservée
 * aux personnes qui ont le droit sur l'application Marché.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('marche_demande_id')->nullable()->after('mairie_id')
                ->constrained('marche_demandes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marche_demande_id');
        });
    }
};
