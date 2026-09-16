<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réouverture d'une demande transférée : elle revient au « centre de tri ».
 *
 * Scénario : l'agent 1 reçoit la demande et la transfère à l'agent 2, qui la
 * clôture. Si l'habitant demande la réouverture, elle ne doit pas retourner
 * chez l'agent 2 — qui a peut-être clôturé sans regarder — mais chez l'agent 1,
 * qui juge et retransfère ou non.
 *
 * Pour que l'agent 1 sache ce qui s'est passé, on garde :
 *  - qui a cliqué sur « Clôturer » (cloture_user_id) ;
 *  - le transfert levé au moment de la réouverture (precedent_transfert).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('cloture_user_id')->nullable()->after('cloture_par')
                ->constrained('users')->nullOnDelete();
            $table->json('precedent_transfert')->nullable()->after('transfere_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cloture_user_id');
            $table->dropColumn('precedent_transfert');
        });
    }
};
