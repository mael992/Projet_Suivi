<?php

namespace Tests\Feature;

use App\Mail\AbonnementDernierJour;
use App\Mail\AbonnementExpire;
use App\Models\Mairie;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AbonnementEmailTest extends TestCase
{
    use RefreshDatabase;

    private function mairie(string $dateFin): Mairie
    {
        return Mairie::create([
            'nom'                 => 'Mairie ' . uniqid(),
            'email'               => uniqid() . '@mairie.fr',
            'date_fin_abonnement' => $dateFin,
        ]);
    }

    public function test_email_dernier_jour_envoye_le_jour_j(): void
    {
        Mail::fake();

        $mairie = $this->mairie(now()->toDateString());
        User::factory()->create([
            'mairie_id' => $mairie->id,
            'grade'     => Referentiel::GRADE_MAIRE,
            'email'     => 'maire@mairie.fr',
        ]);

        $this->artisan('mgds:notifier-abonnements')->assertSuccessful();

        Mail::assertSent(AbonnementDernierJour::class);
        Mail::assertNotSent(AbonnementExpire::class);
    }

    public function test_email_expire_envoye_le_lendemain(): void
    {
        Mail::fake();

        $mairie = $this->mairie(now()->subDay()->toDateString());
        User::factory()->create([
            'mairie_id' => $mairie->id,
            'grade'     => Referentiel::GRADE_DGS,
            'email'     => 'dgs@mairie.fr',
        ]);

        $this->artisan('mgds:notifier-abonnements')->assertSuccessful();

        Mail::assertSent(AbonnementExpire::class);
        Mail::assertNotSent(AbonnementDernierJour::class);
    }

    public function test_aucun_email_pour_les_autres_dates(): void
    {
        Mail::fake();

        $this->mairie(now()->addWeek()->toDateString());
        $this->mairie(now()->subWeek()->toDateString());

        $this->artisan('mgds:notifier-abonnements')->assertSuccessful();

        Mail::assertNothingSent();
    }
}
