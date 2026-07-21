<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mairies', function (Blueprint $table) {
            $table->string('code_postal', 5)->nullable()->after('nom');
            // Figurer dans la liste « Contacter votre Mairie » (page publique)
            $table->boolean('afficher_contact')->default(true)->after('code_postal');
        });

        // Mairies existantes : code postal provisoire 00000
        DB::table('mairies')->whereNull('code_postal')->update(['code_postal' => '00000']);
    }

    public function down(): void
    {
        Schema::table('mairies', function (Blueprint $table) {
            $table->dropColumn(['code_postal', 'afficher_contact']);
        });
    }
};
