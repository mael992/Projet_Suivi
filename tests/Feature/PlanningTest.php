<?php

namespace Tests\Feature;

use App\Models\Absence;
use App\Models\Mairie;
use App\Models\Planning;
use App\Models\PlanningLigne;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Application Planning : grille des heures côté mairie, signature côté agent,
 * et report automatique des absences déclarées dans #56.
 */
class PlanningTest extends TestCase
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

    private function chef(): User
    {
        return User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 1,
            'grade'     => Referentiel::GRADE_DIR_CABINET,
            'droits'    => ['planning_gestion'],
        ]);
    }

    private function agent(): User
    {
        return User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'grade'     => Referentiel::GRADE_EMPLOYE,
            'droits'    => [],
        ]);
    }

    /** Semaine dont on maîtrise les dates, pour poser des absences précises. */
    private function planning(): Planning
    {
        $lundi = now()->startOfWeek();

        return Planning::create([
            'mairie_id' => $this->mairie->id,
            'annee'     => (int) $lundi->isoWeekYear,
            'semaine'   => (int) $lundi->isoWeek,
        ]);
    }

    public function test_creation_d_une_semaine_avec_une_ligne_par_agent(): void
    {
        $chef  = $this->chef();
        $agent = $this->agent();

        $this->actingAs($chef)->post('/planning', ['annee' => 2026, 'semaine' => 37])
            ->assertRedirect();

        $planning = Planning::firstOrFail();
        $this->assertSame(2026, $planning->annee);
        $this->assertSame(2, $planning->lignes()->count());
        $this->assertEqualsCanonicalizing(
            [$chef->id, $agent->id],
            $planning->lignes()->pluck('user_id')->all()
        );
    }

    public function test_la_meme_semaine_n_est_pas_creee_deux_fois(): void
    {
        $chef = $this->chef();

        $this->actingAs($chef)->post('/planning', ['annee' => 2026, 'semaine' => 37]);
        $this->actingAs($chef)->post('/planning', ['annee' => 2026, 'semaine' => 37]);

        $this->assertSame(1, Planning::count());
    }

    public function test_sans_droit_planning_l_agent_ne_peut_pas_creer_ni_modifier(): void
    {
        $planning = $this->planning();

        $this->actingAs($this->agent())->post('/planning', ['annee' => 2026, 'semaine' => 5])
            ->assertForbidden();

        $this->actingAs($this->agent())->put("/planning/{$planning->id}", ['lignes' => []])
            ->assertForbidden();
    }

    public function test_saisie_des_creneaux_et_calcul_des_totaux(): void
    {
        $chef     = $this->chef();
        $planning = $this->planning();
        $ligne    = PlanningLigne::create(['planning_id' => $planning->id, 'user_id' => $chef->id]);

        $this->actingAs($chef)->put("/planning/{$planning->id}", [
            'lignes' => [
                $ligne->id => [
                    // La durée se saisit en heures, en chiffres seulement
                    'duree_contrat' => '35',
                    'jours' => [
                        1 => ['creneaux' => [['08:00', '12:00'], ['14:00', '17:00']]],
                        2 => ['creneaux' => [['09:00', '12:30'], ['', '']]],
                        3 => ['repos' => '1', 'creneaux' => [['08:00', '12:00'], ['', '']]],
                    ],
                ],
            ],
        ])->assertRedirect();

        $ligne->refresh();
        $dates = $planning->dates();

        $this->assertSame(420, $ligne->minutesJour(1, $dates[1]));   // 4h + 3h
        $this->assertSame(210, $ligne->minutesJour(2, $dates[2]));   // 3h30
        $this->assertSame(0,   $ligne->minutesJour(3, $dates[3]));   // repos
        $this->assertSame(630, $ligne->totalMinutes($dates));
        $this->assertSame(2100, $ligne->duree_contrat);              // 35h
        $this->assertSame(630 - 2100, $ligne->variationMinutes($dates));
        $this->assertSame('10h30', PlanningLigne::formatMinutes(630));
        $this->assertSame('-24h30', PlanningLigne::formatMinutes(630 - 2100));
    }

    /** La durée accepte les demi-heures, toujours en chiffres. */
    public function test_la_duree_se_saisit_en_heures_decimales(): void
    {
        $chef     = $this->chef();
        $planning = $this->planning();
        $ligne    = PlanningLigne::create(['planning_id' => $planning->id, 'user_id' => $chef->id]);

        $this->actingAs($chef)->put("/planning/{$planning->id}", [
            'lignes' => [$ligne->id => ['duree_contrat' => '35.5', 'jours' => []]],
        ])->assertRedirect();

        $ligne->refresh();
        $this->assertSame(2130, $ligne->duree_contrat);      // 35 h 30
        $this->assertSame('35.5', $ligne->dureeContratSaisie());

        // Un « 35:00 » à l'ancienne est refusé
        $this->actingAs($chef)->put("/planning/{$planning->id}", [
            'lignes' => [$ligne->id => ['duree_contrat' => '35:00', 'jours' => []]],
        ])->assertSessionHasErrors('lignes.' . $ligne->id . '.duree_contrat');
    }

    /** Le retard se déduit des heures faites et se cumule sur la semaine. */
    public function test_le_retard_diminue_les_heures_du_jour(): void
    {
        $chef     = $this->chef();
        $planning = $this->planning();
        $ligne    = PlanningLigne::create(['planning_id' => $planning->id, 'user_id' => $chef->id]);

        $this->actingAs($chef)->put("/planning/{$planning->id}", [
            'lignes' => [
                $ligne->id => [
                    'duree_contrat' => '7',
                    'jours' => [
                        1 => ['creneaux' => [['08:00', '12:00']], 'retard' => 30],
                        2 => ['creneaux' => [['08:00', '12:00']], 'retard' => 0],
                    ],
                ],
            ],
        ])->assertRedirect();

        $ligne = PlanningLigne::with('user.absences')->find($ligne->id);
        $dates = $planning->dates();

        $this->assertSame(210, $ligne->minutesJour(1, $dates[1]));   // 4 h moins 30 min
        $this->assertSame(240, $ligne->minutesJour(2, $dates[2]));
        $this->assertSame(30,  $ligne->retardMinutes($dates));
        $this->assertSame(450, $ligne->totalMinutes($dates));
    }

    /** Le filtre par service prépare (et imprime) une équipe à la fois. */
    public function test_le_filtre_par_service_restreint_la_grille_et_le_pdf(): void
    {
        $chef     = $this->chef();                    // service 1
        $agent    = $this->agent();                   // service 12
        $planning = $this->planning();
        PlanningLigne::create(['planning_id' => $planning->id, 'user_id' => $chef->id]);
        PlanningLigne::create(['planning_id' => $planning->id, 'user_id' => $agent->id]);

        $this->actingAs($chef)->get("/planning/{$planning->id}?service=12")
            ->assertOk()
            ->assertSee($agent->full_name)
            ->assertDontSee($chef->full_name);

        $this->actingAs($chef)->get("/planning/{$planning->id}?service=tout")
            ->assertOk()
            ->assertSee($agent->full_name)
            ->assertSee($chef->full_name);

        $pdf = $this->actingAs($chef)->get("/planning/{$planning->id}/pdf?service=12");
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
    }

    public function test_une_absence_neutralise_la_journee_sans_toucher_au_planning(): void
    {
        $chef     = $this->chef();
        $planning = $this->planning();
        $ligne    = PlanningLigne::create([
            'planning_id' => $planning->id,
            'user_id'     => $chef->id,
            'jours'       => [
                '1' => ['repos' => false, 'creneaux' => [['08:00', '12:00']]],
                '2' => ['repos' => false, 'creneaux' => [['08:00', '12:00']]],
            ],
        ]);

        $dates = $planning->dates();
        $this->assertSame(480, $ligne->fresh()->load('user.absences')->totalMinutes($dates));

        // Absence déclarée après coup sur le lundi seulement
        Absence::create([
            'mairie_id'  => $this->mairie->id,
            'user_id'    => $chef->id,
            'motif'      => 'arret_maladie',
            'date_debut' => $dates[1]->toDateString(),
            'date_fin'   => $dates[1]->toDateString(),
        ]);

        $ligne = PlanningLigne::with('user.absences')->find($ligne->id);

        $this->assertSame(0, $ligne->minutesJour(1, $dates[1]));
        $this->assertSame(240, $ligne->minutesJour(2, $dates[2]));
        $this->assertSame(240, $ligne->totalMinutes($dates));

        // Les créneaux saisis sont conservés : seule la journée ne compte plus
        $this->assertSame([['08:00', '12:00']], $ligne->journee(1)['creneaux']);

        // Et le motif s'affiche dans la grille
        $this->actingAs($chef)->get("/planning/{$planning->id}")
            ->assertOk()
            ->assertSee('Arrêt maladie');
    }

    public function test_l_agent_signe_sa_semaine_et_une_modification_annule_la_signature(): void
    {
        $chef     = $this->chef();
        $agent    = $this->agent();
        $planning = $this->planning();
        $ligne    = PlanningLigne::create(['planning_id' => $planning->id, 'user_id' => $agent->id]);

        $this->actingAs($agent)->post("/planning/{$planning->id}/signer")->assertRedirect();
        $this->assertTrue($ligne->fresh()->estSigne());

        $this->actingAs($chef)->put("/planning/{$planning->id}", [
            'lignes' => [$ligne->id => ['jours' => [1 => ['creneaux' => [['08:00', '12:00']]]]]],
        ])->assertRedirect();

        $this->assertFalse($ligne->fresh()->estSigne());
    }

    public function test_on_ne_signe_pas_a_la_place_d_un_autre(): void
    {
        $planning = $this->planning();
        PlanningLigne::create(['planning_id' => $planning->id, 'user_id' => $this->agent()->id]);

        // Un agent sans ligne dans ce planning ne peut pas signer
        $this->actingAs($this->agent())->post("/planning/{$planning->id}/signer")->assertForbidden();
    }

    public function test_l_agent_ne_voit_que_sa_propre_ligne(): void
    {
        $chef     = $this->chef();
        $agent    = $this->agent();
        $planning = $this->planning();
        PlanningLigne::create(['planning_id' => $planning->id, 'user_id' => $chef->id]);
        PlanningLigne::create(['planning_id' => $planning->id, 'user_id' => $agent->id]);

        $this->actingAs($agent)->get("/planning/{$planning->id}")
            ->assertOk()
            ->assertSee($agent->full_name)
            ->assertDontSee($chef->full_name);

        // Le chef voit toute la mairie
        $this->actingAs($chef)->get("/planning/{$planning->id}")
            ->assertOk()
            ->assertSee($agent->full_name)
            ->assertSee($chef->full_name);
    }

    public function test_un_planning_d_une_autre_mairie_est_inaccessible(): void
    {
        $autre = Mairie::create([
            'nom'                 => 'Mairie Voisine',
            'email'               => 'voisine@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);
        $planningVoisin = Planning::create(['mairie_id' => $autre->id, 'annee' => 2026, 'semaine' => 12]);

        $this->actingAs($this->chef())->get("/planning/{$planningVoisin->id}")->assertForbidden();
    }

    public function test_export_pdf_reserve_a_la_gestion(): void
    {
        $chef     = $this->chef();
        $planning = $this->planning();
        PlanningLigne::create(['planning_id' => $planning->id, 'user_id' => $chef->id]);

        $reponse = $this->actingAs($chef)->get("/planning/{$planning->id}/pdf");
        $reponse->assertOk();
        $this->assertSame('application/pdf', $reponse->headers->get('content-type'));

        $this->actingAs($this->agent())->get("/planning/{$planning->id}/pdf")->assertForbidden();
    }

    public function test_le_droit_gestion_des_utilisateurs_ouvre_le_planning(): void
    {
        $patron = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'grade'     => Referentiel::GRADE_MAIRE,
            'droits'    => ['gestion_utilisateurs'],
        ]);

        $this->assertTrue($patron->aDroit('planning_gestion'));
        $this->actingAs($patron)->post('/planning', ['annee' => 2026, 'semaine' => 8])->assertRedirect();
    }
}
