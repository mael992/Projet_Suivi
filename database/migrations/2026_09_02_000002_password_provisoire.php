<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mot de passe provisoire en libre-service (bouton « Recevoir un mot de
 * passe provisoire » sur la page Mot de passe oublié).
 *
 * Il s'ajoute au mot de passe existant sans le remplacer : tant que la
 * personne n'a pas choisi un nouveau mot de passe, l'ancien continue de
 * fonctionner, et le provisoire cesse de fonctionner à son expiration.
 *
 * Distinct de `temp_password` (créé par la mairie à l'ouverture du compte,
 * conservé en clair pour le courrier d'identifiants) : celui-ci est haché
 * et n'est jamais réaffiché.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password_provisoire')->nullable()->after('temp_password_expires_at');
            $table->timestamp('password_provisoire_expires_at')->nullable()->after('password_provisoire');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password_provisoire', 'password_provisoire_expires_at']);
        });
    }
};
