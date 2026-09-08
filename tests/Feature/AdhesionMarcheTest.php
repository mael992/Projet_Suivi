<?php

namespace Tests\Feature;

use App\Models\Commercant;
use App\Models\Mairie;
use App\Models\MarcheDemande;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Demande d'adhésion au marché : un onglet sur « Contacter votre Mairie »,
 * une boîte de réception réservée à l'application Marché, et la possibilité
 * de discuter avant d'accepter ou de refuser.
 */
class AdhesionMarcheTest extends TestCase
{
    use RefreshDatabase;

    private Mairie $mairie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mairie = Mairie::create([
            'nom'                        => 'Mairie Test',
            'code_postal'                => '00000',
            'email'                      => 'test@mairie.fr',
            'date_fin_abonnement'        => now()->addYear()->toDateString(),
            'marche_inscription_ouverte' => true,
        ]);
    }

    private function gestionnaireMarche(): User
    {
        return User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'grade'     => Referentiel::GRADE_EMPLOYE,
            'droits'    => ['marche_gestion'],
            'email'     => 'marche@mairie.fr',
        ]);
    }

    private function demander(array $extra = []): Ticket
    {
        $this->post('/contacter-mairie/adhesion-marche', array_merge([
            'mairie_id'          => $this->mairie->id,
            'prenom'             => 'Luc',
            'nom'                => 'Martin',
            'activite'           => 'Primeur',
            'telephone'          => '0612345678',
            'email'              => 'luc.martin@example.fr',
            'longueur_souhaitee' => 6,
            'message'            => 'Je vends des fruits et légumes de saison.',
        ], $extra))->assertRedirect();

        return Ticket::where('type', Ticket::TYPE_MARCHE)->latest('id')->firstOrFail();
    }

    public function test_l_onglet_apparait_quand_les_inscriptions_sont_ouvertes(): void
    {
        // Il faut au moins une mairie joignable pour que la page s'affiche
        User::factory()->create([
            'mairie_id'     => $this->mairie->id,
            'communication' => ['inconnu'],
        ]);

        $this->get('/contacter-mairie')->assertOk()->assertSee('Demande adhésion marché');

        $this->mairie->update(['marche_inscription_ouverte' => false]);
        $this->get('/contacter-mairie')->assertOk()->assertDontSee('Demande adhésion marché');
    }

    public function test_la_demande_cree_une_conversation(): void
    {
        Mail::fake();
        $gestionnaire = $this->gestionnaireMarche();

        $ticket = $this->demander();

        $this->assertSame(Ticket::TYPE_MARCHE, $ticket->type);
        $this->assertSame(Ticket::STATUT_RECEPTION, $ticket->statut);
        $this->assertNotNull($ticket->marche_demande_id);
        $this->assertSame(MarcheDemande::STATUT_ATTENTE, $ticket->demandeMarche->statut);

        // Le premier message reprend la demande
        $this->assertStringContainsString('Primeur', $ticket->messages->first()->corps);
        $this->assertStringContainsString('6 m', $ticket->messages->first()->corps);

        // Le gestionnaire du marché est prévenu
        Mail::assertQueued(\App\Mail\NouveauMessageTicket::class,
            fn ($mail) => $mail->hasTo($gestionnaire->email));
    }

    public function test_inscription_refusee_si_la_commune_n_ouvre_pas_son_marche(): void
    {
        $this->mairie->update(['marche_inscription_ouverte' => false]);

        $this->post('/contacter-mairie/adhesion-marche', [
            'mairie_id' => $this->mairie->id,
            'prenom'    => 'Luc', 'nom' => 'Martin',
            'activite'  => 'Primeur',
            'telephone' => '0612345678',
            'email'     => 'luc.martin@example.fr',
        ])->assertSessionHasErrors('mairie_id');

        $this->assertSame(0, MarcheDemande::count());
    }

    public function test_la_boite_est_reservee_au_droit_marche(): void
    {
        Mail::fake();
        $this->demander();

        $sansDroit = User::factory()->create([
            'mairie_id'     => $this->mairie->id,
            'grade'         => Referentiel::GRADE_EMPLOYE,
            'droits'        => [],
            'communication' => ['inconnu'],
        ]);

        $this->actingAs($sansDroit)
            ->get('/messagerie?dossier=' . Ticket::DOSSIER_ADHESION)
            ->assertForbidden();

        $this->actingAs($this->gestionnaireMarche())
            ->get('/messagerie?dossier=' . Ticket::DOSSIER_ADHESION)
            ->assertOk()
            ->assertSee('Primeur');
    }

    /** Les demandes d'adhésion ne polluent pas la Réception des messages d'habitants. */
    public function test_la_demande_ne_figure_pas_dans_la_reception_habituelle(): void
    {
        Mail::fake();
        $this->demander();

        $facteur = User::factory()->create([
            'mairie_id'     => $this->mairie->id,
            'grade'         => Referentiel::GRADE_EMPLOYE,
            'droits'        => ['marche_gestion'],
            'communication' => ['inconnu'],
        ]);

        $this->actingAs($facteur)->get('/messagerie?dossier=' . Ticket::STATUT_RECEPTION)
            ->assertOk()
            ->assertDontSee('Primeur');
    }

    /** La demande se signale par une pastille bleue sur le hub des applications. */
    public function test_la_demande_se_signale_sur_la_liste_des_applications(): void
    {
        Mail::fake();
        $gestionnaire = $this->gestionnaireMarche();

        $this->assertSame(0, Ticket::adhesionsEnAttentePour($gestionnaire));

        $ticket = $this->demander();

        $this->assertSame(1, Ticket::adhesionsEnAttentePour($gestionnaire->fresh()));

        $this->actingAs($gestionnaire)->get('/apps')
            ->assertOk()
            ->assertSee('dossier=' . Ticket::DOSSIER_ADHESION, false)
            ->assertSee('bulle-notif', false);

        // Une fois traitée, la pastille retombe
        $this->actingAs($gestionnaire)->post("/messagerie/tickets/{$ticket->id}/adhesion/accepter");
        $this->assertSame(0, Ticket::adhesionsEnAttentePour($gestionnaire->fresh()));
    }

    /** Sans le droit Marché, aucune pastille : la boîte ne le concerne pas. */
    public function test_pas_de_pastille_sans_le_droit_marche(): void
    {
        Mail::fake();
        $this->demander();

        $sansDroit = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'grade'     => Referentiel::GRADE_EMPLOYE,
            'droits'    => [],
        ]);

        $this->assertSame(0, Ticket::adhesionsEnAttentePour($sansDroit));
    }

    public function test_accepter_inscrit_au_registre_et_cloture(): void
    {
        Mail::fake();
        $gestionnaire = $this->gestionnaireMarche();
        $ticket       = $this->demander();

        $this->actingAs($gestionnaire)
            ->post("/messagerie/tickets/{$ticket->id}/adhesion/accepter")
            ->assertRedirect();

        $commercant = Commercant::where('email', 'luc.martin@example.fr')->first();
        $this->assertNotNull($commercant, 'Le commerçant doit rejoindre le registre');
        $this->assertSame('Primeur', $commercant->activite);
        $this->assertSame(6.0, (float) $commercant->longueur_defaut);

        $ticket->refresh();
        $this->assertSame(Ticket::STATUT_CLOTURE, $ticket->statut);
        $this->assertSame(MarcheDemande::STATUT_ACCEPTEE, $ticket->demandeMarche->statut);
    }

    public function test_refuser_conserve_le_motif_et_n_inscrit_personne(): void
    {
        Mail::fake();
        $ticket = $this->demander();

        $this->actingAs($this->gestionnaireMarche())
            ->post("/messagerie/tickets/{$ticket->id}/adhesion/refuser", [
                'reponse' => 'Plus de place cette saison.',
            ])->assertRedirect();

        $ticket->refresh();
        $this->assertSame(MarcheDemande::STATUT_REFUSEE, $ticket->demandeMarche->statut);
        $this->assertSame('Plus de place cette saison.', $ticket->demandeMarche->reponse);
        $this->assertSame(Ticket::STATUT_CLOTURE, $ticket->statut);
        $this->assertSame(0, Commercant::count());

        // Le motif est tracé dans la conversation
        $this->assertStringContainsString('Plus de place', $ticket->messages->last()->corps);
    }

    public function test_un_agent_sans_droit_marche_ne_peut_pas_decider(): void
    {
        Mail::fake();
        $ticket = $this->demander();

        $sansDroit = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'grade'     => Referentiel::GRADE_EMPLOYE,
            'droits'    => [],
        ]);

        $this->actingAs($sansDroit)
            ->post("/messagerie/tickets/{$ticket->id}/adhesion/accepter")
            ->assertForbidden();

        $this->assertSame(0, Commercant::count());
    }

    public function test_la_mairie_peut_discuter_avant_de_decider(): void
    {
        Mail::fake();
        $ticket = $this->demander();

        $this->actingAs($this->gestionnaireMarche())
            ->post("/messagerie/tickets/{$ticket->id}/repondre", [
                'corps' => 'Quelle est la longueur exacte de votre banc ?',
            ])->assertRedirect();

        $ticket->refresh();
        $this->assertCount(2, $ticket->messages);
        $this->assertSame(Ticket::STATUT_REPONSE, $ticket->statut);
        $this->assertSame(MarcheDemande::STATUT_ATTENTE, $ticket->demandeMarche->statut);
    }
}
