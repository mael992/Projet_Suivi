<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Transfert multiple : une demande peut désormais être transférée à
 *    PLUSIEURS services et/ou à PLUSIEURS personnes à la fois. Les deux
 *    colonnes uniques deviennent des listes, sans perdre les transferts
 *    déjà enregistrés.
 * 2. La page publique « Contacter votre Mairie » liste toutes les mairies :
 *    l'interrupteur géré par l'administrateur n'a plus d'objet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->json('transfere_services')->nullable()->after('confidents');
            $table->json('transfere_users')->nullable()->after('transfere_services');
        });

        // Reprise des transferts existants : une valeur unique devient une liste
        foreach (DB::table('tickets')
            ->whereNotNull('transfere_service')
            ->orWhereNotNull('transfere_user_id')
            ->get(['id', 'transfere_service', 'transfere_user_id']) as $ticket) {
            DB::table('tickets')->where('id', $ticket->id)->update([
                'transfere_services' => $ticket->transfere_service !== null
                    ? json_encode([(int) $ticket->transfere_service]) : null,
                'transfere_users'    => $ticket->transfere_user_id !== null
                    ? json_encode([(int) $ticket->transfere_user_id]) : null,
            ]);
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transfere_user_id');
            $table->dropColumn('transfere_service');
        });

        Schema::table('mairies', function (Blueprint $table) {
            $table->dropColumn('afficher_contact');
        });
    }

    public function down(): void
    {
        Schema::table('mairies', function (Blueprint $table) {
            $table->boolean('afficher_contact')->default(true)->after('code_postal');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedTinyInteger('transfere_service')->nullable()->after('confidents');
            $table->foreignId('transfere_user_id')->nullable()->after('transfere_service')
                ->constrained('users')->nullOnDelete();
        });

        // On ne peut restituer qu'un seul destinataire : le premier de la liste
        foreach (DB::table('tickets')
            ->whereNotNull('transfere_services')
            ->orWhereNotNull('transfere_users')
            ->get(['id', 'transfere_services', 'transfere_users']) as $ticket) {
            $services = json_decode($ticket->transfere_services ?? '[]', true) ?: [];
            $users    = json_decode($ticket->transfere_users ?? '[]', true) ?: [];

            DB::table('tickets')->where('id', $ticket->id)->update([
                'transfere_service' => $services[0] ?? null,
                'transfere_user_id' => $users[0] ?? null,
            ]);
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['transfere_services', 'transfere_users']);
        });
    }
};
