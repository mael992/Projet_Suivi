<?php

namespace Tests\Feature;

use App\Models\Absence;
use App\Models\Mairie;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Module d'absences : plusieurs absences par agent, motif choisi dans une
 * liste, justificatif privé, et historique séparé des absences à venir.
 */
class AbsencesTest extends TestCase
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
            'service'   => 1,
            'grade'     => Referentiel::GRADE_DIR_CABINET,
            'droits'    => ['gestion_utilisateurs'],
        ]);
    }

    private function agent(): User
    {
        return User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'grade'     => Referentiel::GRADE_EMPLOYE,
        ]);
    }

    private function absence(User $agent, string $debut, string $fin, string $motif = 'vacances'): Absence
    {
        return Absence::create([
            'mairie_id'  => $this->mairie->id,
            'user_id'    => $agent->id,
            'motif'      => $motif,
            'date_debut' => $debut,
            'date_fin'   => $fin,
        ]);
    }

    public function test_la_page_separe_les_absences_a_venir_de_l_historique(): void
    {
        $agent = $this->agent();
        $this->absence($agent, now()->subDay()->toDateString(), now()->addWeek()->toDateString(), 'arret_maladie');
        $this->absence($agent, now()->subMonth()->toDateString(), now()->subMonth()->addDays(3)->toDateString(), 'formation');

        $reponse = $this->actingAs($this->gestionnaire())->get('/gestion/absences');

        $reponse->assertOk()
            ->assertSee('Arrêt maladie')
            ->assertSee('Formation')
            ->assertSee('Absences actuelles / à venir')
            ->assertSee('Historique &amp; Justificatifs', false);
    }

    public function test_un_agent_sans_droit_n_accede_pas_a_la_page(): void
    {
        $this->actingAs($this->agent())->get('/gestion/absences')->assertForbidden();
    }

    public function test_enregistrement_d_une_absence_avec_justificatif(): void
    {
        Storage::fake('local');
        $agent = $this->agent();

        $this->actingAs($this->gestionnaire())->post('/gestion/absences', [
            'user_id'      => $agent->id,
            'motif'        => 'arret_travail',
            'date_debut'   => now()->toDateString(),
            'date_fin'     => now()->addDays(5)->toDateString(),
            'justificatif' => UploadedFile::fake()->create('arret.pdf', 40, 'application/pdf'),
        ])->assertRedirect(route('gestion.absences.index'));

        $absence = Absence::firstOrFail();
        $this->assertSame($agent->id, $absence->user_id);
        $this->assertSame('arret_travail', $absence->motif);
        $this->assertNotNull($absence->justificatif);
        Storage::disk('local')->assertExists($absence->justificatif);

        // L'agent est vu comme absent aujourd'hui
        $this->assertTrue($agent->fresh()->estAbsent());
    }

    public function test_la_date_de_fin_ne_peut_pas_preceder_le_debut(): void
    {
        $agent = $this->agent();

        $this->actingAs($this->gestionnaire())->post('/gestion/absences', [
            'user_id'    => $agent->id,
            'motif'      => 'vacances',
            'date_debut' => now()->addWeek()->toDateString(),
            'date_fin'   => now()->toDateString(),
        ])->assertSessionHasErrors('date_fin');

        $this->assertSame(0, Absence::count());
    }

    public function test_impossible_de_declarer_l_absence_d_une_autre_mairie(): void
    {
        $autre = Mairie::create([
            'nom'                 => 'Mairie Voisine',
            'email'               => 'voisine@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);
        $etranger = User::factory()->create([
            'mairie_id' => $autre->id,
            'grade'     => Referentiel::GRADE_EMPLOYE,
        ]);

        $this->actingAs($this->gestionnaire())->post('/gestion/absences', [
            'user_id'    => $etranger->id,
            'motif'      => 'vacances',
            'date_debut' => now()->toDateString(),
            'date_fin'   => now()->addDay()->toDateString(),
        ])->assertForbidden();

        $this->assertSame(0, Absence::count());
    }

    public function test_le_justificatif_reste_prive_entre_mairies(): void
    {
        Storage::fake('local');
        $agent = $this->agent();

        $this->actingAs($this->gestionnaire())->post('/gestion/absences', [
            'user_id'      => $agent->id,
            'motif'        => 'arret_maladie',
            'date_debut'   => now()->toDateString(),
            'date_fin'     => now()->addDay()->toDateString(),
            'justificatif' => UploadedFile::fake()->create('arret.pdf', 20, 'application/pdf'),
        ]);

        $absence = Absence::firstOrFail();

        $this->actingAs($this->gestionnaire())
            ->get(route('gestion.absences.justificatif', $absence))->assertOk();

        $autre = Mairie::create([
            'nom'                 => 'Mairie Voisine',
            'email'               => 'voisine@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);
        $voisin = User::factory()->create([
            'mairie_id' => $autre->id,
            'grade'     => Referentiel::GRADE_DIR_CABINET,
            'droits'    => ['gestion_utilisateurs'],
        ]);

        $this->actingAs($voisin)
            ->get(route('gestion.absences.justificatif', $absence))->assertForbidden();
    }

    /**
     * Un justificatif arrive souvent après coup : l'absence doit rester
     * modifiable, y compris quand elle est déjà passée (historique).
     */
    public function test_une_absence_passee_reste_modifiable_et_accepte_un_justificatif(): void
    {
        Storage::fake('local');
        $agent   = $this->agent();
        $absence = $this->absence(
            $agent,
            now()->subMonth()->toDateString(),
            now()->subMonth()->addDays(2)->toDateString(),
            'signalee',
        );

        $this->actingAs($this->gestionnaire())->put(route('gestion.absences.update', $absence), [
            'motif'        => 'arret_travail',
            'date_debut'   => now()->subMonth()->toDateString(),
            'date_fin'     => now()->subMonth()->addDays(5)->toDateString(),
            'justificatif' => UploadedFile::fake()->create('arret.pdf', 30, 'application/pdf'),
        ])->assertRedirect(route('gestion.absences.index'));

        $absence->refresh();
        $this->assertSame('arret_travail', $absence->motif);
        $this->assertSame(now()->subMonth()->addDays(5)->toDateString(), $absence->date_fin->toDateString());
        $this->assertNotNull($absence->justificatif);
        Storage::disk('local')->assertExists($absence->justificatif);
    }

    public function test_le_justificatif_peut_etre_retire(): void
    {
        Storage::fake('local');
        $agent   = $this->agent();
        $absence = $this->absence($agent, now()->toDateString(), now()->addDay()->toDateString());
        $absence->update(['justificatif' => UploadedFile::fake()->create('a.pdf', 5)->store('justificatifs', 'local')]);

        $chemin = $absence->justificatif;

        $this->actingAs($this->gestionnaire())->put(route('gestion.absences.update', $absence), [
            'motif'                => $absence->motif,
            'date_debut'           => $absence->date_debut->toDateString(),
            'date_fin'             => $absence->date_fin->toDateString(),
            'retirer_justificatif' => '1',
        ])->assertRedirect();

        $this->assertNull($absence->fresh()->justificatif);
        Storage::disk('local')->assertMissing($chemin);
    }

    public function test_on_ne_modifie_pas_l_absence_d_une_autre_mairie(): void
    {
        $autre = Mairie::create([
            'nom'                 => 'Mairie Voisine',
            'email'               => 'voisine@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);
        $etranger = User::factory()->create(['mairie_id' => $autre->id, 'grade' => Referentiel::GRADE_EMPLOYE]);
        $absence  = Absence::create([
            'mairie_id'  => $autre->id,
            'user_id'    => $etranger->id,
            'motif'      => 'vacances',
            'date_debut' => now()->toDateString(),
            'date_fin'   => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($this->gestionnaire())->put(route('gestion.absences.update', $absence), [
            'motif'      => 'formation',
            'date_debut' => now()->toDateString(),
            'date_fin'   => now()->addDay()->toDateString(),
        ])->assertForbidden();

        $this->assertSame('vacances', $absence->fresh()->motif);
    }

    public function test_suppression_d_une_absence(): void
    {
        $agent    = $this->agent();
        $absence  = $this->absence($agent, now()->toDateString(), now()->addDay()->toDateString());

        $this->actingAs($this->gestionnaire())
            ->delete(route('gestion.absences.destroy', $absence))
            ->assertRedirect(route('gestion.absences.index'));

        $this->assertSame(0, Absence::count());
        $this->assertFalse($agent->fresh()->estAbsent());
    }

    public function test_plusieurs_absences_par_agent_et_absence_future_non_active(): void
    {
        $agent = $this->agent();
        $this->absence($agent, now()->addMonth()->toDateString(), now()->addMonth()->addWeek()->toDateString());

        $this->assertFalse($agent->fresh()->estAbsent());

        $this->absence($agent, now()->subDay()->toDateString(), now()->addDay()->toDateString(), 'rendez_vous');

        $agent = $agent->fresh();
        $this->assertTrue($agent->estAbsent());
        $this->assertSame('Rendez-vous', $agent->absenceEnCours()->motifLabel());
        $this->assertSame(2, $agent->absences()->count());
    }

    public function test_la_fiche_utilisateur_garde_le_binome_sans_case_absence(): void
    {
        $reponse = $this->actingAs($this->gestionnaire())->get('/gestion/utilisateurs/create');

        $reponse->assertOk()
            ->assertSee('name="binome_id"', false)
            ->assertDontSee('name="absent"', false)
            ->assertDontSee('name="absent_du"', false);
    }
}
