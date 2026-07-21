<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dialogue_questions', function (Blueprint $table) {
            // Question clôturée par son auteur : verrouillée définitivement
            $table->timestamp('fermee_at')->nullable()->after('texte');
        });
    }

    public function down(): void
    {
        Schema::table('dialogue_questions', function (Blueprint $table) {
            $table->dropColumn('fermee_at');
        });
    }
};
