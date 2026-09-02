<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les absences quittent la fiche utilisateur pour devenir des enregistrements
 * à part entière : plusieurs absences par personne, un motif choisi dans une
 * liste, un justificatif possible et un historique consultable.
 *
 * Le binôme, lui, reste un réglage du compte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mairie_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('motif', 30);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('justificatif')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['mairie_id', 'date_debut']);
            $table->index(['user_id', 'date_debut']);
        });

        // Reprise de l'absence unique portée par la fiche utilisateur
        foreach (DB::table('users')->where('absent', true)->get() as $user) {
            if ($user->mairie_id === null) {
                continue;
            }

            DB::table('absences')->insert([
                'mairie_id'   => $user->mairie_id,
                'user_id'     => $user->id,
                'motif'       => 'signalee',
                'date_debut'  => $user->absent_du ?? now()->toDateString(),
                'date_fin'    => $user->absent_au ?? now()->toDateString(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['absent', 'absent_du', 'absent_au', 'absence_motif']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('absent')->default(false)->after('binome_id');
            $table->date('absent_du')->nullable()->after('absent');
            $table->date('absent_au')->nullable()->after('absent_du');
            $table->string('absence_motif', 100)->nullable()->after('absent_au');
        });

        // On ne peut restituer qu'une absence : la plus récente
        foreach (DB::table('absences')->orderBy('date_debut')->get() as $absence) {
            DB::table('users')->where('id', $absence->user_id)->update([
                'absent'        => true,
                'absent_du'     => $absence->date_debut,
                'absent_au'     => $absence->date_fin,
                'absence_motif' => $absence->motif,
            ]);
        }

        Schema::dropIfExists('absences');
    }
};
