<?php

namespace Tests\Feature;

use App\Models\Mairie;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Droits d'application : chaque case est indépendante. La seule cascade
 * restante est « Gestion des utilisateurs ouvre tout » et « modification
 * ouvre la lecture de la MÊME application ».
 */
class DroitsApplicationTest extends TestCase
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

    private function agent(array $droits, int $grade = Referentiel::GRADE_EMPLOYE): User
    {
        return User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'grade'     => $grade,
            'droits'    => $droits,
        ]);
    }

    public function test_ecriture_contacts_ouvre_la_lecture_mais_rien_d_autre(): void
    {
        $agent = $this->agent(['contacts_modification']);

        $this->assertTrue($agent->aDroit('contacts_modification'));
        $this->assertTrue($agent->aDroit('contacts_lecture'));

        // Aucune fuite vers les autres applications
        $this->assertFalse($agent->aDroit('marche_gestion'));
        $this->assertFalse($agent->aDroit('taches_gestion'));
        $this->assertFalse($agent->peutGererTaches());
        $this->assertFalse($agent->peutGererMairie());
    }

    public function test_lecture_contacts_n_ouvre_pas_l_ecriture(): void
    {
        $agent = $this->agent(['contacts_lecture']);

        $this->assertTrue($agent->aDroit('contacts_lecture'));
        $this->assertFalse($agent->aDroit('contacts_modification'));
    }

    public function test_marche_n_ouvre_pas_le_tableau_des_suivis(): void
    {
        $agent = $this->agent(['marche_gestion']);

        $this->assertTrue($agent->aDroit('marche_gestion'));
        $this->assertFalse($agent->aDroit('taches_gestion'));
        $this->assertFalse($agent->aDroit('contacts_lecture'));
    }

    public function test_le_tableau_des_suivis_n_ouvre_pas_les_contacts(): void
    {
        $agent = $this->agent(['taches_gestion']);

        $this->assertTrue($agent->peutGererTaches());
        $this->assertFalse($agent->aDroit('contacts_lecture'));
        $this->assertFalse($agent->aDroit('marche_gestion'));
    }

    public function test_gestion_des_utilisateurs_ouvre_tout(): void
    {
        $agent = $this->agent(['gestion_utilisateurs'], Referentiel::GRADE_DIR_CABINET);

        foreach (array_keys(Referentiel::DROITS) as $droit) {
            $this->assertTrue($agent->aDroit($droit), "Le droit {$droit} devrait être accordé");
        }
    }

    public function test_droits_combines_sans_cascade_entre_applications(): void
    {
        $agent = $this->agent(['contacts_lecture', 'taches_gestion']);

        $this->assertSame(['contacts_lecture', 'taches_gestion'], $agent->droitsActuels());
        $this->assertFalse($agent->aDroit('contacts_modification'));
        $this->assertFalse($agent->aDroit('marche_gestion'));
    }

    public function test_aucune_case_cochee_ne_donne_aucun_droit(): void
    {
        // Même pour un grade dont le défaut serait « accès complet »
        $chef = $this->agent([], Referentiel::GRADE_DIR_CABINET);

        $this->assertSame([], $chef->droitsActuels());
        $this->assertFalse($chef->peutGererMairie());
        $this->actingAs($chef)->get('/gestion/utilisateurs')->assertForbidden();
    }

    public function test_le_formulaire_enregistre_les_cases_cochees(): void
    {
        $gestionnaire = $this->agent(['gestion_utilisateurs'], Referentiel::GRADE_DIR_CABINET);

        // Le formulaire s'affiche avec une case par application
        $this->actingAs($gestionnaire)->get('/gestion/utilisateurs/create')
            ->assertOk()
            ->assertSee('name="droits[]"', false)
            ->assertSee('value="taches_gestion"', false);

        $this->actingAs($gestionnaire)->post('/gestion/utilisateurs', [
            'prenom'   => 'Camille',
            'nom'      => 'Durand',
            'service'  => 12,
            'grade'    => Referentiel::GRADE_EMPLOYE,
            'droits'   => ['contacts_modification', 'marche_gestion'],
            'password' => 'motdepasse123',
        ])->assertRedirect();

        $cree = User::where('nom', 'Durand')->firstOrFail();

        $this->assertSame(['contacts_modification', 'marche_gestion'], $cree->droitsCoches());
        // La lecture des contacts est accordée sans avoir été envoyée
        $this->assertTrue($cree->aDroit('contacts_lecture'));
        $this->assertFalse($cree->aDroit('taches_gestion'));
    }

    public function test_le_formulaire_refuse_un_droit_inconnu(): void
    {
        $gestionnaire = $this->agent(['gestion_utilisateurs'], Referentiel::GRADE_DIR_CABINET);

        $this->actingAs($gestionnaire)->post('/gestion/utilisateurs', [
            'prenom'   => 'Camille',
            'nom'      => 'Durand',
            'service'  => 12,
            'grade'    => Referentiel::GRADE_EMPLOYE,
            'droits'   => ['administrateur_supreme'],
            'password' => 'motdepasse123',
        ])->assertSessionHasErrors('droits.0');
    }
}
