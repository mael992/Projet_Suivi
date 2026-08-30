<?php

namespace Tests\Feature;

use App\Models\Devis;
use App\Models\Mairie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevisTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mairie::create([
            'nom'                 => 'Mairie Test',
            'code_postal'         => '00000',
            'email'               => 'test@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);

        $this->admin = User::factory()->admin()->create();
    }

    public function test_creation_devis_et_calculs(): void
    {
        $this->actingAs($this->admin)->get('/admin/devis')->assertOk();

        $this->actingAs($this->admin)->post('/admin/devis', [
            'client_nom'     => 'Mairie Test',
            'date_devis'     => now()->toDateString(),
            'validite_jours' => 30,
            'taux_tva'       => 20,
            'lignes'         => [
                ['designation' => 'Abonnement annuel MGDS', 'quantite' => 1, 'prix_unitaire' => 1200],
                ['designation' => 'Formation sur site (journée)', 'quantite' => 2, 'prix_unitaire' => 400],
                ['designation' => '', 'quantite' => 0, 'prix_unitaire' => 0], // ligne vide ignorée
            ],
        ])->assertRedirect();

        $devis = Devis::first();
        $this->assertStringStartsWith('DEV-' . now()->format('Y') . '-', $devis->reference);
        $this->assertCount(2, $devis->lignes);
        $this->assertSame(2000.0, $devis->totalHt());
        $this->assertSame(400.0, $devis->montantTva());
        $this->assertSame(2400.0, $devis->totalTtc());
        $this->assertSame(now()->addDays(30)->toDateString(), $devis->valableJusquau()->toDateString());
    }

    public function test_telechargement_pdf_du_devis(): void
    {
        $devis = Devis::create([
            'reference'      => Devis::genererReference(),
            'client_nom'     => 'Mairie Test',
            'date_devis'     => now(),
            'validite_jours' => 30,
            'taux_tva'       => 20,
            'lignes'         => [['designation' => 'Abonnement', 'quantite' => 1, 'prix_unitaire' => 1000]],
        ]);

        $reponse = $this->actingAs($this->admin)->get("/admin/devis/{$devis->id}/pdf");

        $reponse->assertOk();
        $this->assertSame('application/pdf', $reponse->headers->get('Content-Type'));
    }

    public function test_devis_reserve_aux_admins(): void
    {
        $mairie = Mairie::first();
        $agent  = User::factory()->create(['mairie_id' => $mairie->id]);

        $this->actingAs($agent)->get('/admin/devis')->assertForbidden();
    }
}
