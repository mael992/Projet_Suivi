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
            'grade'     => Referentiel::GRADE_DIR_CABINET,
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

    public function test_gestion_pages_are_displayed_for_maire(): void
    {
        $maire = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 1,
            'grade'     => Referentiel::GRADE_MAIRE,
        ]);

        $this->actingAs($maire)->get('/gestion/utilisateurs')->assertOk();
        $this->actingAs($maire)->get('/gestion/utilisateurs/create')->assertOk();
        $this->actingAs($maire)->get('/gestion/contacts')->assertOk();
        $this->actingAs($maire)->get('/gestion/avancement')->assertOk();
    }

    public function test_directeur_cabinet_a_tous_les_droits_par_defaut(): void
    {
        // Nouvelle règle : Maire / Dir. Cabinet / DGS = tous les droits cochés par défaut
        $dirCab = $this->responsable();

        $this->actingAs($dirCab)->get('/gestion/contacts')->assertOk();
        $this->actingAs($dirCab)->get('/gestion/utilisateurs')->assertOk();
    }

    public function test_droit_aucun_retire_tous_les_droits(): void
    {
        // Un chef à qui on retire explicitement tous les droits n'a plus accès
        $chef = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 1,
            'grade'     => Referentiel::GRADE_DIR_CABINET,
            'droit'     => Referentiel::DROIT_AUCUN,
        ]);

        $this->assertFalse($chef->aDroit('marche_gestion'));
        $this->assertFalse($chef->aDroit('contacts_lecture'));
        $this->actingAs($chef)->get('/gestion/utilisateurs')->assertForbidden();
        $this->actingAs($chef)->get('/marche/ville')->assertForbidden();
    }

    public function test_gestion_is_forbidden_for_employe(): void
    {
        $this->actingAs($this->employe())
            ->get('/gestion/utilisateurs')
            ->assertForbidden();

        $this->actingAs($this->employe())
            ->get('/gestion/contacts')
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
            'user_id'     => $responsable->id,
            'date_butoir' => now()->addWeek()->toDateString(),
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('taches', [
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'reference' => '12-1',
            'statut'    => 'ouvert',
        ]);

        // La deuxième tâche du même service prend le numéro suivant
        $this->actingAs($responsable)->post('/taches', [
            'service'     => 12,
            'user_id'     => $responsable->id,
            'date_butoir' => now()->addWeek()->toDateString(),
        ]);

        $this->assertDatabaseHas('taches', ['reference' => '12-2']);
    }

    public function test_responsable_obligatoire_a_la_creation(): void
    {
        $this->actingAs($this->responsable())->post('/taches', [
            'service'     => 12,
            'date_butoir' => now()->addWeek()->toDateString(),
        ])->assertSessionHasErrors('user_id');
    }

    public function test_employe_cannot_create_a_tache(): void
    {
        $this->actingAs($this->employe())
            ->post('/taches', [
                'service'     => 12,
                'date_butoir' => now()->addWeek()->toDateString(),
            ])->assertForbidden();
    }

    public function test_workflow_prise_en_charge_puis_cloture(): void
    {
        $responsable = $this->responsable();

        $tache = Tache::create([
            'mairie_id'   => $this->mairie->id,
            'reference'   => '12-0',
            'service'     => 12,
            'user_id'     => $responsable->id,
            'statut'      => 'ouvert',
            'date_butoir' => now()->addWeek()->toDateString(),
        ]);

        $this->assertTrue($tache->enAttentePriseEnCharge());

        // Prise en charge par le responsable
        $this->actingAs($responsable)->post("/taches/{$tache->id}/prise-en-charge", [
            'mode' => 'responsable',
        ])->assertRedirect();

        $tache->refresh();
        $this->assertSame('responsable', $tache->prise_en_charge);
        $this->assertSame('en_cours', $tache->statut);

        // Clôture : commentaire obligatoire
        $this->actingAs($responsable)->post("/taches/{$tache->id}/cloturer", [
            'description_cloture' => '   ',
        ])->assertSessionHasErrors('description_cloture');

        $this->actingAs($responsable)->post("/taches/{$tache->id}/cloturer", [
            'description_cloture' => 'Travail terminé.',
        ])->assertRedirect(route('dashboard', absolute: false));

        $tache->refresh();
        $this->assertSame('fait', $tache->statut);
        $this->assertNotNull($tache->date_cloture);
    }

    public function test_workflow_substitution_a_un_employe(): void
    {
        $responsable = $this->responsable();
        $employe     = $this->employe();

        $tache = Tache::create([
            'mairie_id'   => $this->mairie->id,
            'reference'   => '12-0',
            'service'     => 12,
            'user_id'     => $responsable->id,
            'statut'      => 'ouvert',
            'date_butoir' => now()->addWeek()->toDateString(),
        ]);

        $this->actingAs($responsable)->post("/taches/{$tache->id}/prise-en-charge", [
            'mode'         => 'substitution',
            'substitut_id' => $employe->id,
        ])->assertRedirect();

        $tache->refresh();
        $this->assertSame('substitution', $tache->prise_en_charge);
        $this->assertSame($employe->id, $tache->substitut_id);

        // Le substitut peut clôturer
        $this->actingAs($employe)->post("/taches/{$tache->id}/cloturer", [
            'description_cloture' => 'Fait par le substitut.',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame('fait', $tache->fresh()->statut);
    }

    public function test_rappel_echeance_envoye_le_jour_j(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $responsable = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'grade'     => Referentiel::GRADE_DIR_CABINET,
            'email'     => 'resp@mairie.fr',
        ]);

        // Échéance aujourd'hui, non terminée
        Tache::create([
            'mairie_id'   => $this->mairie->id,
            'reference'   => '12-1',
            'service'     => 12,
            'user_id'     => $responsable->id,
            'statut'      => 'en_cours',
            'date_butoir' => now()->toDateString(),
        ]);
        // Échéance aujourd'hui mais déjà faite → pas de rappel
        Tache::create([
            'mairie_id'   => $this->mairie->id,
            'reference'   => '12-2',
            'service'     => 12,
            'user_id'     => $responsable->id,
            'statut'      => 'fait',
            'date_butoir' => now()->toDateString(),
        ]);

        $this->artisan('mgds:rappeler-taches')->assertSuccessful();

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\TacheEcheance::class, 1);
    }

    public function test_le_responsable_peut_changer_le_substitut(): void
    {
        $responsable = $this->responsable();
        $employe1    = $this->employe();
        $employe2    = $this->employe();

        $tache = Tache::create([
            'mairie_id'       => $this->mairie->id,
            'reference'       => '12-0',
            'service'         => 12,
            'user_id'         => $responsable->id,
            'substitut_id'    => $employe1->id,
            'prise_en_charge' => 'substitution',
            'statut'          => 'en_cours',
            'date_butoir'     => now()->addWeek()->toDateString(),
        ]);

        // L'employé substitué ne peut pas changer la substitution
        $this->actingAs($employe1)->post("/taches/{$tache->id}/substitut", [
            'substitut_id' => $employe2->id,
        ])->assertForbidden();

        // Le responsable, oui
        $this->actingAs($responsable)->post("/taches/{$tache->id}/substitut", [
            'substitut_id' => $employe2->id,
        ])->assertRedirect();

        $this->assertSame($employe2->id, $tache->fresh()->substitut_id);
    }

    public function test_seul_le_createur_peut_modifier_ou_supprimer(): void
    {
        $createur    = $this->responsable();
        $responsable = $this->responsable();

        $tache = Tache::create([
            'mairie_id'   => $this->mairie->id,
            'reference'   => '12-0',
            'service'     => 12,
            'user_id'     => $responsable->id,
            'created_by'  => $createur->id,
            'statut'      => 'ouvert',
            'date_butoir' => now()->addWeek()->toDateString(),
        ]);

        // Le responsable affecté (non créateur) ne peut ni modifier ni supprimer
        $this->actingAs($responsable)->get("/taches/{$tache->id}/edit")->assertForbidden();
        $this->actingAs($responsable)->delete("/taches/{$tache->id}")->assertForbidden();

        // Le créateur, oui
        $this->actingAs($createur)->get("/taches/{$tache->id}/edit")->assertOk();
    }

    public function test_username_generation_handles_duplicates(): void
    {
        $this->assertSame('jean.dupont', User::genererUsername('Jean', 'Dupont'));

        User::factory()->create(['username' => 'jean.dupont']);
        $this->assertSame('jean.dupont1', User::genererUsername('Jean', 'Dupont'));
    }
}
