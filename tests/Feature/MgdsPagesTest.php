<?php

namespace Tests\Feature;

use App\Models\Mairie;
use App\Models\Tache;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MgdsPagesTest extends TestCase
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

    private function responsable(): User
    {
        return User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'grade'     => Referentiel::GRADE_RESPONSABLE,
        ]);
    }

    private function employe(): User
    {
        return User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'grade'     => Referentiel::GRADE_EMPLOYE,
        ]);
    }

    public function test_dashboard_is_displayed_for_mairie_user(): void
    {
        $this->actingAs($this->employe())
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_gestion_pages_are_displayed_for_responsable(): void
    {
        $responsable = $this->responsable();

        $this->actingAs($responsable)->get('/gestion/utilisateurs')->assertOk();
        $this->actingAs($responsable)->get('/gestion/utilisateurs/create')->assertOk();
        $this->actingAs($responsable)->get('/gestion/contacts')->assertOk();
        $this->actingAs($responsable)->get('/gestion/avancement')->assertOk();
    }

    public function test_gestion_is_forbidden_for_employe(): void
    {
        $this->actingAs($this->employe())
            ->get('/gestion/utilisateurs')
            ->assertForbidden();
    }

    public function test_admin_pages_are_displayed_for_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/users')->assertOk();
        $this->actingAs($admin)->get('/users/create')->assertOk();
        $this->actingAs($admin)->get('/mairies')->assertOk();
        $this->actingAs($admin)->get('/mairies/create')->assertOk();
        $this->actingAs($admin)->get('/mairies/' . $this->mairie->id . '/edit')->assertOk();
        $this->actingAs($admin)->get('/admin/logs')->assertOk();
        $this->actingAs($admin)->get('/admin/messages')->assertOk();
    }

    public function test_admin_pages_are_forbidden_for_mairie_user(): void
    {
        $this->actingAs($this->responsable())->get('/users')->assertForbidden();
        $this->actingAs($this->responsable())->get('/mairies')->assertForbidden();
    }

    public function test_responsable_can_create_a_tache_with_auto_reference(): void
    {
        $responsable = $this->responsable();

        $this->actingAs($responsable)->get('/taches/create')->assertOk();

        $this->actingAs($responsable)->post('/taches', [
            'service'     => 12,
            'date_butoir' => now()->addWeek()->toDateString(),
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('taches', [
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'reference' => '12-0',
            'statut'    => 'ouvert',
        ]);

        // La deuxième tâche du même service prend le numéro suivant
        $this->actingAs($responsable)->post('/taches', [
            'service'     => 12,
            'date_butoir' => now()->addWeek()->toDateString(),
        ]);

        $this->assertDatabaseHas('taches', ['reference' => '12-1']);
    }

    public function test_employe_cannot_create_a_tache(): void
    {
        $this->actingAs($this->employe())
            ->post('/taches', [
                'service'     => 12,
                'date_butoir' => now()->addWeek()->toDateString(),
            ])->assertForbidden();
    }

    public function test_statut_fait_sets_date_cloture_automatically(): void
    {
        $employe = $this->employe();

        $tache = Tache::create([
            'mairie_id'   => $this->mairie->id,
            'reference'   => '12-0',
            'service'     => 12,
            'user_id'     => $employe->id,
            'statut'      => 'ouvert',
            'date_butoir' => now()->addWeek()->toDateString(),
        ]);

        $this->actingAs($employe)->put('/taches/' . $tache->id, [
            'statut' => 'fait',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertNotNull($tache->fresh()->date_cloture);
    }

    public function test_employe_cannot_reopen_a_tache_faite(): void
    {
        $employe = $this->employe();

        $tache = Tache::create([
            'mairie_id'    => $this->mairie->id,
            'reference'    => '12-0',
            'service'      => 12,
            'user_id'      => $employe->id,
            'statut'       => 'fait',
            'date_butoir'  => now()->addWeek()->toDateString(),
            'date_cloture' => now(),
        ]);

        $this->actingAs($employe)->put('/taches/' . $tache->id, [
            'statut' => 'ouvert',
        ])->assertSessionHasErrors('statut');

        $this->assertSame('fait', $tache->fresh()->statut);
    }

    public function test_username_generation_handles_duplicates(): void
    {
        $this->assertSame('jean.dupont', User::genererUsername('Jean', 'Dupont'));

        User::factory()->create(['username' => 'jean.dupont']);
        $this->assertSame('jean.dupont1', User::genererUsername('Jean', 'Dupont'));
    }
}
