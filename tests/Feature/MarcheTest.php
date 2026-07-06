<?php

namespace Tests\Feature;

use App\Models\Commercant;
use App\Models\Mairie;
use App\Models\MarchePlan;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarcheTest extends TestCase
{
    use RefreshDatabase;

    private Mairie $mairie;
    private User $responsable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mairie = Mairie::create([
            'nom'                 => 'Mairie Test',
            'email'               => 'test@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);

        $this->responsable = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'grade'     => Referentiel::GRADE_RESPONSABLE,
        ]);
    }

    public function test_apps_hub_is_displayed(): void
    {
        $this->actingAs($this->responsable)->get('/apps')->assertOk();
    }

    public function test_marche_pages_are_displayed(): void
    {
        $this->actingAs($this->responsable)->get('/marche/plan')->assertOk();
        $this->actingAs($this->responsable)->get('/marche/registre')->assertOk();
        $this->actingAs($this->responsable)->get('/marche/commercants')->assertOk();
    }

    public function test_placement_computes_position_and_warns_on_overflow(): void
    {
        $commercant = Commercant::create([
            'mairie_id'       => $this->mairie->id,
            'nom'             => 'Durand',
            'prenom'          => 'Paul',
            'activite'        => 'Fleuriste',
            'longueur_defaut' => 4,
        ]);

        $plan = MarchePlan::create([
            'mairie_id' => $this->mairie->id,
            'nom'       => 'Marché hebdo',
            'date'      => now()->toDateString(),
        ]);

        $axe = $plan->axes()->create(['nom' => 'Trottoir gauche', 'longueur' => 10]);

        // Premier stand : placé au début, longueur par défaut (4 m) → reste 6 m
        $this->actingAs($this->responsable)
            ->post("/marche/axes/{$axe->id}/emplacements", ['commercant_id' => $commercant->id])
            ->assertRedirect();

        $this->assertDatabaseHas('marche_emplacements', [
            'marche_axe_id' => $axe->id,
            'position'      => 0,
            'longueur'      => 4,
        ]);

        // Deuxième stand de 8 m : placé à la suite (position 4) → dépasse → warning
        $this->actingAs($this->responsable)
            ->post("/marche/axes/{$axe->id}/emplacements", [
                'commercant_id' => $commercant->id,
                'longueur'      => 8,
            ])
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('marche_emplacements', [
            'marche_axe_id' => $axe->id,
            'position'      => 4,
            'longueur'      => 8,
        ]);
    }

    public function test_employe_cannot_edit_marche(): void
    {
        $employe = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'grade'     => Referentiel::GRADE_EMPLOYE,
        ]);

        $this->actingAs($employe)
            ->post('/marche/plans', ['nom' => 'Test', 'date' => now()->toDateString()])
            ->assertForbidden();
    }
}
