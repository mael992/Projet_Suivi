<?php

namespace Tests\Feature;

use App\Models\Mairie;
use App\Models\Marche;
use App\Models\MarcheZone;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Navigation « option 2 » du Marché : marchés datés → endroits → plan.
 * Elle cohabite avec la vue aérienne, qui doit rester intacte.
 */
class MarcheListeTest extends TestCase
{
    use RefreshDatabase;

    private Mairie $mairie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mairie = Mairie::create([
            'nom'                 => 'Mairie Test',
            'email'               => 'test@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);
    }

    private function gestionnaire(): User
    {
        return User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'grade'     => Referentiel::GRADE_EMPLOYE,
            'droits'    => ['marche_gestion'],
        ]);
    }

    public function test_les_deux_options_restent_accessibles(): void
    {
        $user = $this->gestionnaire();

        $this->actingAs($user)->get('/marche/ville')->assertOk()->assertSee('option 2');
        $this->actingAs($user)->get('/marche/liste')->assertOk()->assertSee('option 1');
    }

    public function test_sans_droit_marche_l_option_2_est_refusee(): void
    {
        $sansDroit = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'grade'     => Referentiel::GRADE_EMPLOYE,
            'droits'    => [],
        ]);

        $this->actingAs($sansDroit)->get('/marche/liste')->assertForbidden();
    }

    public function test_creation_d_un_marche_puis_d_un_endroit(): void
    {
        $user = $this->gestionnaire();

        $this->actingAs($user)->post('/marche/liste', [
            'nom'              => 'Marché du samedi',
            'date_deroulement' => now()->addWeek()->toDateString(),
        ])->assertRedirect();

        $marche = Marche::firstOrFail();
        $this->assertSame($this->mairie->id, $marche->mairie_id);

        $this->actingAs($user)->post("/marche/liste/{$marche->id}/endroits", [
            'nom'  => 'Place de la Mairie',
            'type' => 'place',
        ])->assertRedirect();

        $endroit = MarcheZone::firstOrFail();
        $this->assertSame($marche->id, $endroit->marche_id);
        $this->assertSame($this->mairie->id, $endroit->mairie_id);

        $this->actingAs($user)->get("/marche/liste/{$marche->id}")
            ->assertOk()
            ->assertSee('Place de la Mairie')
            ->assertSee('Marché du samedi');
    }

    public function test_supprimer_un_marche_conserve_ses_endroits(): void
    {
        $user   = $this->gestionnaire();
        $marche = Marche::create(['mairie_id' => $this->mairie->id, 'nom' => 'Brocante']);
        $zone   = MarcheZone::create([
            'mairie_id' => $this->mairie->id,
            'marche_id' => $marche->id,
            'nom'       => 'Rue Centrale',
            'type'      => 'rue',
        ]);

        $this->actingAs($user)->delete("/marche/liste/{$marche->id}")->assertRedirect();

        $this->assertSame(0, Marche::count());
        $this->assertNotNull($zone->fresh(), 'La zone ne doit pas être supprimée avec le marché');
        $this->assertNull($zone->fresh()->marche_id);
    }

    public function test_retirer_un_endroit_sans_toucher_a_son_plan(): void
    {
        $user   = $this->gestionnaire();
        $marche = Marche::create(['mairie_id' => $this->mairie->id, 'nom' => 'Marché de Noël']);
        $zone   = MarcheZone::create([
            'mairie_id'   => $this->mairie->id,
            'marche_id'   => $marche->id,
            'nom'         => 'Place du Marché',
            'type'        => 'place',
            'marche_type' => 'noel',
        ]);

        $this->actingAs($user)->delete("/marche/liste/{$marche->id}/endroits/{$zone->id}")->assertRedirect();

        $zone->refresh();
        $this->assertNull($zone->marche_id);
        $this->assertSame('noel', $zone->marche_type);
    }

    public function test_un_marche_d_une_autre_mairie_est_inaccessible(): void
    {
        $autre = Mairie::create([
            'nom'                 => 'Mairie Voisine',
            'email'               => 'voisine@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);
        $marcheVoisin = Marche::create(['mairie_id' => $autre->id, 'nom' => 'Marché voisin']);

        $this->actingAs($this->gestionnaire())
            ->get("/marche/liste/{$marcheVoisin->id}")
            ->assertForbidden();
    }
}
