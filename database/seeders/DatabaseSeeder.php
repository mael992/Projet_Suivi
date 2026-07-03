<?php

namespace Database\Seeders;

use App\Models\Mairie;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Compte administrateur (nous) ─────────────────────────
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'prenom'   => 'Admin',
                'nom'      => 'MGDS',
                'email'    => 'maelroinac8@gmail.com',
                'password' => Hash::make('Admin@MGDS2026'),
                'role'     => 'admin',
            ]
        );

        // ── Mairie de démonstration (local uniquement) ───────────
        if (app()->environment('local')) {
            $mairie = Mairie::firstOrCreate(
                ['nom' => 'Mairie de Démonstration'],
                [
                    'email'               => 'demo@mairie.fr',
                    'telephone_indicatif' => '+33',
                    'telephone'           => '4 75 00 00 00',
                    'date_fin_abonnement' => now()->addYear()->toDateString(),
                ]
            );

            $responsable = User::firstOrCreate(
                ['username' => 'romain.allien'],
                [
                    'prenom'    => 'Romain',
                    'nom'       => 'Allien',
                    'email'     => 'roro.informatique26@gmail.com',
                    'password'  => Hash::make('password'),
                    'role'      => 'user',
                    'mairie_id' => $mairie->id,
                    'service'   => 12, // Service Technique / CTM
                    'grade'     => Referentiel::GRADE_RESPONSABLE,
                    'reference' => '12-0',
                    'telephone_indicatif' => '+33',
                    'telephone' => '7 69 47 25 74',
                ]
            );

            User::firstOrCreate(
                ['username' => 'jean.dupont'],
                [
                    'prenom'    => 'Jean',
                    'nom'       => 'Dupont',
                    'password'  => Hash::make('password'),
                    'role'      => 'user',
                    'mairie_id' => $mairie->id,
                    'service'   => 12,
                    'grade'     => Referentiel::GRADE_EMPLOYE,
                    'reference' => '12-1',
                ]
            );
        }
    }
}
