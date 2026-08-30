<?php

namespace Tests\Feature;

use App\Models\Mairie;
use App\Models\MairieService;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicesMairieTest extends TestCase
{
    use RefreshDatabase;

    private Mairie $mairie;
    private User $maire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mairie = Mairie::create([
            'nom'                 => 'Mairie Test',
            'code_postal'         => '00000',
            'email'               => 'test@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);

        $this->maire = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 0,
            'grade'     => Referentiel::GRADE_MAIRE,
        ]);
    }

    public function test_sans_personnalisation_le_referentiel_par_defaut_sapplique(): void
    {
        $this->assertSame(Referentiel::SERVICES, $this->mairie->libellesServices());
    }

    public function test_mairie_peut_renommer_desactiver_et_ajouter_des_services(): void
    {
        $this->actingAs($this->maire)->get('/gestion/services')->assertOk();

        // Renomme le service 12, désactive le 13
        $this->actingAs($this->maire)->put('/gestion/services', [
            'services' => [
                12 => ['nom' => 'Services Techniques Municipaux', 'actif' => 1],
                13 => ['nom' => Referentiel::SERVICES[13], 'actif' => 0],
            ],
        ])->assertRedirect();

        $services = $this->mairie->fresh()->libellesServices();
        $this->assertSame('Services Techniques Municipaux', $services[12]);
        $this->assertArrayNotHasKey(13, $services);

        // Ajoute un service propre à la commune
        $this->actingAs($this->maire)->post('/gestion/services', ['nom' => 'Régie des eaux'])->assertRedirect();

        $ajoute = MairieService::where('mairie_id', $this->mairie->id)->where('nom', 'Régie des eaux')->first();
        $this->assertNotNull($ajoute);
        $this->assertGreaterThanOrEqual(20, $ajoute->numero);
        $this->assertSame('Régie des eaux', $this->mairie->fresh()->libellesServices()[$ajoute->numero]);
    }

    public function test_le_nom_personnalise_apparait_sur_les_utilisateurs(): void
    {
        MairieService::create([
            'mairie_id' => $this->mairie->id,
            'numero'    => 12,
            'nom'       => 'Ateliers municipaux',
            'actif'     => true,
        ]);

        $agent = User::factory()->create(['mairie_id' => $this->mairie->id, 'service' => 12]);

        $this->assertSame('Ateliers municipaux', $agent->fresh()->service_label);
    }

    public function test_un_employe_ne_peut_pas_modifier_les_services(): void
    {
        $employe = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'grade'     => Referentiel::GRADE_EMPLOYE,
        ]);

        $this->actingAs($employe)->get('/gestion/services')->assertForbidden();
    }
}
