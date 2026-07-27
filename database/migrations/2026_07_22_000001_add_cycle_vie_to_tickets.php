<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // statut : reception | reponse | cloture | reouverture_demandee
            $table->timestamp('cloture_at')->nullable()->after('statut');
            $table->string('cloture_par')->nullable()->after('cloture_at');       // mairie | citoyen
            $table->timestamp('reouverture_demandee_at')->nullable()->after('cloture_par');
            $table->text('reouverture_motif')->nullable()->after('reouverture_demandee_at');
        });

        // Les tickets existants passent en « réception »
        DB::table('tickets')->whereIn('statut', ['ouvert', 'transfere'])->update(['statut' => 'reception']);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['cloture_at', 'cloture_par', 'reouverture_demandee_at', 'reouverture_motif']);
        });
    }
};
