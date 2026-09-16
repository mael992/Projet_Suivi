<?php

namespace Tests\Feature;

use App\Models\Mairie;
use App\Models\Tache;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CguBinomeTest extends TestCase
{
    use RefreshDatabase;

    private Mairie $mairie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mairie = Mairie::create([
            'nom'                 => 'Mairie Test',
            'code_postal'         => '00000',
            'email'               => 'test@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);
    }

    public function test_cgu_obligatoires_avant_acces(): void
    {
        $user = User::factory()->create([
            'mairie_id'        => $this->mairie->id,
            'cgu_acceptees_at' => null,
        ]);

        // Toute page redirige vers les CGU tant qu'elles ne sont pas acceptées
        $this->actingAs($user)->get('/apps')->assertRedirect(route('cgu'));
        $this->actingAs($user)->get('/cgu')->assertOk();

        // Case non cochée → refus
        $this->actingAs($user)->post('/cgu', [])->assertSessionHasErrors('cgu');

        // Case cochée → accès rétabli
        $this->actingAs($user)->post('/cgu', ['cgu' => '1'])->assertRedirect(route('apps'));
        $this->assertNotNull($user->fresh()->cgu_acceptees_at);
        $this->actingAs($user->fresh())->get('/apps')->assertOk();
    }

    public function test_changement_de_mot_de_passe_impose_passe_avant_les_cgu(): void
    {
        // Cas réel : mot de passe provisoire ET CGU non acceptées.
        // Sans priorité, les deux middlewares se renvoyaient la balle.
        $user = User::factory()->create([
            'mairie_id'                => $this->mairie->id,
            'cgu_acceptees_at'         => null,
            'must_change_password'     => true,
            'temp_password_expires_at' => now()->addDay(),
        ]);

        // La page de changement de mot de passe s'affiche (pas de redirection vers les CGU)
        $this->actingAs($user)->get('/password/change-required')->assertOk();

        // Toute autre page mène bien au changement de mot de passe, pas aux CGU
        $this->actingAs($user)->get('/apps')->assertRedirect(route('password.force-change'));

        // Une fois le mot de passe changé, les CGU reprennent la main
        $user->update(['must_change_password' => false]);
        $this->actingAs($user->fresh())->get('/apps')->assertRedirect(route('cgu'));
    }

    public function test_binome_reprend_les_taches_pendant_absence(): void
    {
        $titulaire = User::factory()->create([
            'mairie_id'        => $this->mairie->id,
            'service'          => 12,
            'grade'            => Referentiel::GRADE_EMPLOYE,
            'cgu_acceptees_at' => now(),
        ]);
        $binome = User::factory()->create([
            'mairie_id'        => $this->mairie->id,
            'service'          => 12,
            'grade'            => Referentiel::GRADE_EMPLOYE,
            'cgu_acceptees_at' => now(),
        ]);

        $tache = Tache::create([
            'mairie_id'       => $this->mairie->id,
            'reference'       => '12-1',
            'service'         => 12,
            'user_id'         => $titulaire->id,
            'prise_en_charge' => 'responsable',
            'statut'          => 'en_cours',
            'date_butoir'     => now()->addWeek()->toDateString(),
        ]);

        // Sans absence déclarée, le binôme ne voit rien
        $titulaire->update(['binome_id' => $binome->id]);
        $this->assertFalse(Tache::visiblesPar($binome->fresh())->whereKey($tache->id)->exists());

        // Absence en cours → le binôme voit la tâche et peut la clôturer
        \App\Models\Absence::create([
            'mairie_id'  => $this->mairie->id,
            'user_id'    => $titulaire->id,
            'motif'      => 'vacances',
            'date_debut' => now()->subDay()->toDateString(),
            'date_fin'   => now()->addWeek()->toDateString(),
        ]);

        $binome->refresh();
        $this->assertTrue(Tache::visiblesPar($binome)->whereKey($tache->id)->exists());
        $this->assertTrue($tache->peutEtreClotureePar($binome));

        $this->actingAs($binome)->post("/taches/{$tache->id}/cloturer", [
            'description_cloture' => 'Traité par le binôme pendant l\'absence.',
        ])->assertRedirect();

        $this->assertSame('fait', $tache->fresh()->statut);
    }

    /**
     * Scénario signalé : test.test (Directeur de Cabinet, gestion des
     * utilisateurs) est absent sans avoir distribué son travail. Son binôme
     * test.test1, simple employé, voyait les tâches mais restait bloqué.
     */
    private function scenarioAbsence(): array
    {
        $chef = User::factory()->create([
            'mairie_id'        => $this->mairie->id,
            'service'          => 1,
            'grade'            => Referentiel::GRADE_DIR_CABINET,
            'droits'           => ['gestion_utilisateurs'],
            'cgu_acceptees_at' => now(),
        ]);
        $binome = User::factory()->create([
            'mairie_id'        => $this->mairie->id,
            'service'          => 1,
            'grade'            => Referentiel::GRADE_EMPLOYE,
            'droits'           => [],
            'cgu_acceptees_at' => now(),
        ]);
        $chef->update(['binome_id' => $binome->id]);

        $tache = Tache::create([
            'mairie_id'   => $this->mairie->id,
            'reference'   => '1-1',
            'service'     => 1,
            'user_id'     => $chef->id,
            'created_by'  => $chef->id,
            'statut'      => 'ouvert',
            'date_butoir' => now()->addWeek()->toDateString(),
        ]);

        return [$chef, $binome, $tache];
    }

    private function absenter(User $user): \App\Models\Absence
    {
        return \App\Models\Absence::create([
            'mairie_id'  => $this->mairie->id,
            'user_id'    => $user->id,
            'motif'      => 'vacances',
            'date_debut' => now()->subDay()->toDateString(),
            'date_fin'   => now()->addWeek()->toDateString(),
        ]);
    }

    public function test_le_binome_herite_des_droits_pendant_l_absence_seulement(): void
    {
        [$chef, $binome] = $this->scenarioAbsence();

        // Avant l'absence : aucun droit, pas d'accès à la gestion des utilisateurs
        $this->assertFalse($binome->aDroit('gestion_utilisateurs'));
        $this->actingAs($binome)->get('/gestion/utilisateurs')->assertForbidden();

        // Pendant : il reprend tout, gestion des utilisateurs comprise
        $absence = $this->absenter($chef);
        $binome  = $binome->fresh();

        $this->assertTrue($binome->aDroit('gestion_utilisateurs'));
        $this->assertTrue($binome->aDroit('taches_gestion'));
        $this->assertContains('gestion_utilisateurs', $binome->droitsHerites());
        $this->actingAs($binome)->get('/gestion/utilisateurs')->assertOk();

        // Après : plus rien, pas après non plus
        $absence->update(['date_fin' => now()->subDay()->toDateString(), 'date_debut' => now()->subWeek()->toDateString()]);
        $binome = $binome->fresh();

        $this->assertFalse($binome->aDroit('gestion_utilisateurs'));
        $this->assertSame([], $binome->droitsHerites());
        $this->actingAs($binome)->get('/gestion/utilisateurs')->assertForbidden();
    }

    public function test_le_binome_peut_attribuer_le_travail_du_responsable_absent(): void
    {
        [$chef, $binome, $tache] = $this->scenarioAbsence();
        $employe = User::factory()->create([
            'mairie_id'        => $this->mairie->id,
            'service'          => 1,
            'grade'            => Referentiel::GRADE_EMPLOYE,
            'cgu_acceptees_at' => now(),
        ]);

        // Sans absence : il ne peut pas prendre la main
        $this->actingAs($binome)->post("/taches/{$tache->id}/prise-en-charge", [
            'mode'         => 'substitution',
            'substitut_id' => $employe->id,
        ])->assertForbidden();

        $this->absenter($chef);
        $binome = $binome->fresh();

        $this->assertTrue($tache->estResponsablePour($binome));

        // Pendant l'absence : il distribue le travail à un employé
        $this->actingAs($binome)->post("/taches/{$tache->id}/prise-en-charge", [
            'mode'         => 'substitution',
            'substitut_id' => $employe->id,
        ])->assertRedirect();

        $tache->refresh();
        $this->assertSame('substitution', $tache->prise_en_charge);
        $this->assertSame($employe->id, $tache->substitut_id);

        // Et peut modifier la tâche que le chef avait créée
        $this->assertTrue($tache->peutEtreGereePar($binome));
    }

    public function test_pas_d_heritage_en_chaine(): void
    {
        [$chef, $binome] = $this->scenarioAbsence();

        // Le binôme a lui-même un binôme ; les deux premiers sont absents
        $troisieme = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'grade'     => Referentiel::GRADE_EMPLOYE,
            'droits'    => [],
        ]);
        $binome->update(['binome_id' => $troisieme->id]);

        $this->absenter($chef);
        $this->absenter($binome);

        // Le troisième reprend les droits PROPRES du binôme (aucun), pas ceux
        // que le binôme héritait du chef
        $this->assertFalse($troisieme->fresh()->aDroit('gestion_utilisateurs'));
    }
}
