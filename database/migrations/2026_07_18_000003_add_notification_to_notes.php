<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            // Rappel email optionnel pour une note (le jour J)
            $table->boolean('notifier')->default(false)->after('image');
            $table->date('date_notification')->nullable()->after('notifier');
            $table->boolean('notifiee')->default(false)->after('date_notification');
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn(['notifier', 'date_notification', 'notifiee']);
        });
    }
};
