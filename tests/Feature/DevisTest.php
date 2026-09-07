<?php

namespace Tests\Feature;

use App\Models\Commercant;
use App\Models\Devis;
use App\Models\Mairie;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les estimations ont quitté l'espace admin : ce ne sont plus des devis
 * d'abonnement pour les mairies, mais des chiffrages qu'une mairie établit
 * pour les commerçants de son marché.
 */
class DevisTest extends TestCase
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

    private function gestionnaireMarche(): User
    {
        return User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'grade'     => Referentiel::GRADE_EMPLOYE,
            'droits'    => ['marche_gestion'],
        ]);
    }

    private function commercant(): Commercant
    {
        return Commercant::create([
            'mairie_id' => $this->mairie->id,
            'nom'       => 'Martin',
            'prenom'    => 'Luc',
            'activite'  => 'Primeur',
            'email'     => 'luc.martin@example.fr',
        ]);
    }

    public function test_creation_estimation_et_calculs(): void
    {
        $user       = $this->gestionnaireMarche();
        $commercant = $this->commercant();

        $this->actingAs($user)->get('/marche/devis')->assertOk()->assertSee('Luc Martin');

        $this->actingAs($user)->post('/marche/devis', [
            'commercant_id'  => $commercant->id,
            'client_nom'     => 'Luc Martin',
            'date_devis'     => now()->toDateString(),
            'validite_jours' => 30,
            'taux_tva'       => 20,
            'lignes'         => [
                ['designation' => 'Emplacement 6 m (année)', 'quantite' => 1, 'prix_unitaire' => 1200],
                ['designation' => 'Branchement électrique', 'quantite' => 2, 'prix_unitaire' => 400],
                ['designation' => '', 'quantite' => 0, 'prix_unitaire' => 0], // ligne vide ignorée
            ],
        ])->assertRedirect();

        $devis = Devis::first();
        $this->assertStringStartsWith('DEV-' . now()->format('Y') . '-', $devis->reference);
        $this->assertSame($this->mairie->id, $devis->mairie_id);
        $this->assertSame($commercant->id, $devis->commercant_id);
        $this->assertCount(2, $devis->lignes);
        $this->assertSame(2000.0, $devis->totalHt());
        $this->assertSame(400.0, $devis->montantTva());
        $this->assertSame(2400.0, $devis->totalTtc());
        $this->assertSame(now()->addDays(30)->toDateString(), $devis->valableJusquau()->toDateString());
    }

    public function test_estimation_possible_pour_un_client_hors_registre(): void
    {
        $user = $this->gestionnaireMarche();

        $this->actingAs($user)->post('/marche/devis', [
            'client_nom'     => 'Camion pizza de passage',
            'date_devis'     => now()->toDateString(),
            'validite_jours' => 15,
            'taux_tva'       => 20,
            'lignes'         => [['designation' => 'Emplacement jour', 'quantite' => 1, 'prix_unitaire' => 25]],
        ])->assertRedirect();

        $this->assertNull(Devis::first()->commercant_id);
    }

    public function test_telechargement_pdf_de_l_estimation(): void
    {
        $user  = $this->gestionnaireMarche();
        $devis = Devis::create([
            'mairie_id'      => $this->mairie->id,
            'reference'      => Devis::genererReference(),
            'client_nom'     => 'Luc Martin',
            'date_devis'     => now(),
            'validite_jours' => 30,
            'taux_tva'       => 20,
            'lignes'         => [['designation' => 'Emplacement', 'quantite' => 1, 'prix_unitaire' => 1000]],
        ]);

        $reponse = $this->actingAs($user)->get("/marche/devis/{$devis->id}/pdf");

        $reponse->assertOk();
        $this->assertSame('application/pdf', $reponse->headers->get('Content-Type'));
    }

    public function test_les_estimations_sont_reservees_au_droit_marche(): void
    {
        $sansDroit = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'grade'     => Referentiel::GRADE_EMPLOYE,
            'droits'    => [],
        ]);

        $this->actingAs($sansDroit)->get('/marche/devis')->assertForbidden();
    }

    public function test_une_estimation_d_une_autre_mairie_est_inaccessible(): void
    {
        $autre = Mairie::create([
            'nom'                 => 'Mairie Voisine',
            'email'               => 'voisine@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);
        $devisVoisin = Devis::create([
            'mairie_id'      => $autre->id,
            'reference'      => Devis::genererReference(),
            'client_nom'     => 'Voisin',
            'date_devis'     => now(),
            'validite_jours' => 30,
            'taux_tva'       => 20,
            'lignes'         => [['designation' => 'Emplacement', 'quantite' => 1, 'prix_unitaire' => 10]],
        ]);

        $this->actingAs($this->gestionnaireMarche())
            ->get("/marche/devis/{$devisVoisin->id}/pdf")->assertForbidden();
    }

    /** L'onglet a bien quitté l'espace admin. */
    public function test_l_ancienne_page_admin_n_existe_plus(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/devis')->assertNotFound();
    }
}
