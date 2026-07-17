<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Le droit « Tableau des suivis — lecture » disparaît du référentiel :
        // la lecture de ses propres tâches est un droit de base pour tous.
        DB::table('users')->where('droit', 'taches_lecture')->update(['droit' => null]);
    }

    public function down(): void
    {
        // Rien à restaurer
    }
};
