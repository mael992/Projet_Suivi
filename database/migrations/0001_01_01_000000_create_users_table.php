<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('prenom')->nullable();
            $table->string('nom')->nullable();
            $table->string('username')->unique();          // prenom.nom (+n si doublon)
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('temp_password')->nullable();
            $table->timestamp('temp_password_expires_at')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->string('role')->default('user');       // admin | user
            $table->foreignId('mairie_id')->nullable()->constrained('mairies')->cascadeOnDelete();
            $table->unsignedTinyInteger('service')->nullable();   // 1..13 (Referentiel::SERVICES)
            $table->unsignedTinyInteger('grade')->nullable();     // 1..5  (Referentiel::GRADES)
            $table->string('reference')->nullable();              // ex: 12-0
            $table->string('telephone_indicatif', 8)->default('+33');
            $table->string('telephone', 20)->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['mairie_id', 'service']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
