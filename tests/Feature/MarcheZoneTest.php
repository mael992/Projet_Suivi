<?php

namespace Tests\Feature;

use App\Models\Mairie;
use App\Models\MarcheZone;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarcheZoneTest extends TestCase
{
    use RefreshDatabase;

    private Mairie $mairie;
    private User $gestionnaire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mairie = Mairie::create([
            'nom'                 => 'Mairie Test',
            'email'               => 'test@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);

        $this->gestionnaire = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'grade'     => Referentiel::GRADE_DIR_CABINET,
        ]);
    }

    public function test_vue_aerienne_is_displayed(): void
    {
        $this->actingAs($this->gestionnaire)
            ->get('/marche/ville')
            ->assertOk()
            ->assertSee('Vue aérienne', false);
    }

    public function test_gestionnaire_can_create_zone(): void
    {
        $this->actingAs($this->gestionnaire)
            ->post('/marche/zones', [
                'nom'     => 'Place du marché',
                'type'    => 'place',
                'couleur' => '#2e86de',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('marche_zones', [
            'mairie_id' => $this->mairie->id,
            'nom'       => 'Place du marché',
            'type'      => 'place',
        ]);
    }

    public function test_zone_page_and_config_save(): void
    {
        $zone = $this->mairie->zonesMarche()->create(['nom' => 'Place', 'type' => 'place']);

        $this->actingAs($this->gestionnaire)
            ->get("/marche/zones/{$zone->id}")
            ->assertOk();

        $this->actingAs($this->gestionnaire)
            ->postJson("/marche/zones/{$zone->id}/config", [
                'marche_type'  => 'hebdomadaire',
                'longueur_m'   => 60,
                'largeur_m'    => 25,
                'disposition'  => 'double',
                'ecart'        => 1.5,
                'taille_stand' => 3,
                'obstacles'    => [
                    ['type' => 'arbre', 'x' => 10, 'y' => 5],
                    ['type' => 'fontaine', 'x' => 30, 'y' => 12],
                ],
            ])
            ->assertOk();

        $zone->refresh();
        $this->assertSame('hebdomadaire', $zone->marche_type);
        $this->assertSame('double', $zone->config['disposition']);
        $this->assertCount(2, $zone->config['obstacles']);
    }

    public function test_positions_bulk_update(): void
    {
        $zone = $this->mairie->zonesMarche()->create(['nom' => 'Rue A', 'type' => 'rue']);

        $this->actingAs($this->gestionnaire)
            ->postJson('/marche/zones/positions', [
                'zones' => [[
                    'id'          => $zone->id,
                    'pos_x'       => 12.5,
                    'pos_y'       => 30,
                    'largeur_pct' => 20,
                    'hauteur_pct' => 8,
                    'rotation'    => 45,
                ]],
            ])
            ->assertOk();

        $this->assertEquals(45, (float) $zone->fresh()->rotation);
    }

    public function test_employe_cannot_edit_zones(): void
    {
        $employe = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'grade'     => Referentiel::GRADE_EMPLOYE,
        ]);

        $this->actingAs($employe)
            ->post('/marche/zones', ['nom' => 'X', 'type' => 'place'])
            ->assertForbidden();
    }

    public function test_zone_of_another_mairie_is_forbidden(): void
    {
        $autre = Mairie::create([
            'nom'                 => 'Autre Mairie',
            'email'               => 'autre@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);
        $zone = $autre->zonesMarche()->create(['nom' => 'Place', 'type' => 'place']);

        $this->actingAs($this->gestionnaire)
            ->get("/marche/zones/{$zone->id}")
            ->assertForbidden();
    }
}
