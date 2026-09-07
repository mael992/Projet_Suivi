<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le devis change de destinataire : ce n'est pas un devis d'abonnement
 * adressé à la mairie, mais une estimation qu'une mairie établit pour un
 * commerçant de son marché.
 *
 * `mairie_id` désigne donc désormais la mairie qui émet l'estimation, et
 * `commercant_id` le commerçant concerné (facultatif : on peut estimer pour
 * quelqu'un qui n'est pas encore au registre).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devis', function (Blueprint $table) {
            $table->foreignId('commercant_id')->nullable()->after('mairie_id')
                ->constrained('commercants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('devis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('commercant_id');
        });
    }
};
