<?php

namespace Tests\Feature;

use App\Models\Commercant;
use App\Models\Mairie;
use App\Models\MarcheCode;
use App\Models\MarcheDemande;
use App\Models\MarcheZone;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarcheExposantTest extends TestCase
{
    use RefreshDatabase;

    private Mairie $mairie;
    private User $gestionnaire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mairie = Mairie::create([
            'nom'                        => 'Mairie Test',
            'code_postal'                => '00000',
            'email'                      => 'test@mairie.fr',
            'date_fin_abonnement'        => now()->addYear()->toDateString(),
            'marche_inscription_ouverte' => true,
        ]);

        $this->gestionnaire = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'grade'     => Referentiel::GRADE_DIR_CABINET,
        ]);
    }

    public function test_commercant_peut_demander_a_rejoindre_le_marche(): void
    {
        $this->get('/marche-exposants')->assertOk()->assertSee('Mairie Test');

        $this->post('/marche-exposants', [
            'mairie_id' => $this->mairie->id,
            'prenom'    => 'Paul',
            'nom'       => 'Fromager',
            'activite'  => 'Fromagerie',
            'telephone' => '0612345678',
            'email'     => 'paul@example.fr',
            'message'   => 'Bonjour, je souhaite rejoindre votre marché.',
        ])->assertRedirect();

        $this->assertDatabaseHas('marche_demandes', [
            'mairie_id' => $this->mairie->id,
            'nom'       => 'Fromager',
            'statut'    => MarcheDemande::STATUT_ATTENTE,
        ]);
    }

    public function test_inscription_refusee_si_la_commune_na_pas_ouvert(): void
    {
        $this->mairie->update(['marche_inscription_ouverte' => false]);

        $this->post('/marche-exposants', [
            'mairie_id' => $this->mairie->id,
            'prenom'    => 'Paul', 'nom' => 'Fromager', 'activite' => 'Fromagerie',
            'telephone' => '0612345678', 'email' => 'paul@example.fr',
        ])->assertNotFound();
    }

    public function test_mairie_accepte_une_demande_et_cree_le_commercant(): void
    {
        $demande = MarcheDemande::create([
            'mairie_id' => $this->mairie->id,
            'prenom'    => 'Paul', 'nom' => 'Fromager', 'activite' => 'Fromagerie',
            'telephone' => '0612345678', 'email' => 'paul@example.fr',
        ]);

        $this->actingAs($this->gestionnaire)->get('/marche/demandes')->assertOk();

        $this->actingAs($this->gestionnaire)
            ->post("/marche/demandes/{$demande->id}/accepter")
            ->assertRedirect();

        $this->assertSame(MarcheDemande::STATUT_ACCEPTEE, $demande->fresh()->statut);
        $this->assertDatabaseHas('commercants', [
            'mairie_id' => $this->mairie->id,
            'email'     => 'paul@example.fr',
        ]);
    }

    public function test_code_plan_valable_jusquau_jour_du_marche(): void
    {
        $zone = MarcheZone::create([
            'mairie_id' => $this->mairie->id,
            'nom'       => 'Place du marché',
            'type'      => 'place',
        ]);

        // Génération par la mairie
        $this->actingAs($this->gestionnaire)->post('/marche/codes', [
            'marche_zone_id' => $zone->id,
            'valable_le'     => now()->addDays(2)->toDateString(),
        ])->assertRedirect();

        $code = MarcheCode::first();
        $this->assertTrue($code->estValide());

        // L'exposant consulte le plan avec son code
        $this->post('/marche-exposants/plan', ['code' => $code->code])
            ->assertOk()
            ->assertSee('Place du marché', false);

        // Code inconnu → refus
        $this->post('/marche-exposants/plan', ['code' => 'ZZZZZZ'])
            ->assertSessionHasErrors('code');

        // Après le jour du marché, le code ne fonctionne plus
        $code->update(['valable_le' => now()->subDay()->toDateString()]);
        $this->assertFalse($code->fresh()->estValide());
        $this->post('/marche-exposants/plan', ['code' => $code->code])
            ->assertSessionHasErrors('code');
    }
}
