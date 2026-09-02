<?php

namespace Tests\Feature;

use App\Models\Mairie;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagerieTest extends TestCase
{
    use RefreshDatabase;

    private Mairie $mairie;

    /** Agent de $mairie qui réceptionne les demandes extérieures. */
    private User $facteur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mairie = Mairie::create([
            'nom'                 => 'Mairie Test',
            'code_postal'         => '00000',
            'email'               => 'test@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);

        // Sans personne autorisée à réceptionner, la mairie n'apparaît pas
        // sur la page publique : le parcours habitant en a donc toujours une.
        $this->facteur = $this->receptionniste($this->mairie);
    }

    /** Agent cochant « Réceptionner les messages extérieurs ». */
    private function receptionniste(Mairie $mairie): User
    {
        return User::factory()->create([
            'mairie_id'     => $mairie->id,
            'grade'         => \App\Support\Referentiel::GRADE_EMPLOYE,
            'communication' => ['inconnu'],
        ]);
    }

    public function test_formulaire_public_accessible(): void
    {
        $this->get('/contacter-mairie')->assertOk();
    }

    public function test_habitant_peut_envoyer_un_ticket(): void
    {
        $this->post('/contacter-mairie', [
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'nom'       => 'Dupont',
            'prenom'    => 'Marie',
            'telephone' => '0612345678',
            'email'     => 'marie@example.fr',
            'sujet'     => 'Voirie abîmée',
            'message'   => 'Un nid-de-poule rue du Centre.',
        ])->assertRedirect();

        $ticket = Ticket::first();
        // Référence « mairie-numéro » : chaque mairie repart à 1
        $this->assertSame($this->mairie->id . '-1', $ticket->reference);
        $this->assertSame('Voirie abîmée', $ticket->sujet);
        $this->assertCount(1, $ticket->messages);
    }

    public function test_numerotation_par_mairie_et_notification(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        // La direction voit tous les messages sans être destinataire
        $maire = User::factory()->create([
            'mairie_id'          => $this->mairie->id,
            'grade'              => \App\Support\Referentiel::GRADE_MAIRE,
            'email'              => 'maire@mairie.fr',
            'communication'      => [],
            'voit_tous_messages' => true,
        ]);

        $autre = Mairie::create([
            'nom'                 => 'Mairie Testroro',
            'code_postal'         => '26230',
            'email'               => 'roro@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);
        $this->receptionniste($autre);

        $envoyer = fn (Mairie $m, string $sujet) => $this->post('/contacter-mairie', [
            'mairie_id' => $m->id,
            'nom'       => 'Dupont', 'prenom' => 'Marie',
            'telephone' => '0612345678', 'email' => 'marie@example.fr',
            'sujet'     => $sujet, 'message' => 'Bonjour, ceci est un test.',
        ]);

        $envoyer($this->mairie, 'Premier')->assertRedirect();
        $envoyer($this->mairie, 'Deuxième')->assertRedirect();
        $envoyer($autre, 'Chez le voisin')->assertRedirect();

        // Numérotation indépendante par mairie
        $this->assertSame($this->mairie->id . '-1', Ticket::where('sujet', 'Premier')->first()->reference);
        $this->assertSame($this->mairie->id . '-2', Ticket::where('sujet', 'Deuxième')->first()->reference);
        $this->assertSame($autre->id . '-1', Ticket::where('sujet', 'Chez le voisin')->first()->reference);

        // L'agent qui réceptionne est prévenu par e-mail
        \Illuminate\Support\Facades\Mail::assertQueued(
            \App\Mail\NouveauMessageTicket::class,
            fn ($mail) => $mail->hasTo($this->facteur->email)
        );

        // Le maire voit les messages de sa mairie, pas ceux de la voisine
        $this->assertSame(2, Ticket::visiblesPar($maire)->count());
    }

    /**
     * Filet de sécurité : si plus personne n'est coché pour réceptionner
     * (case décochée après coup), les messages déjà arrivés remontent à la
     * direction plutôt que de se perdre.
     */
    public function test_sans_receptionniste_les_messages_remontent_a_la_direction(): void
    {
        $maire = User::factory()->create([
            'mairie_id'          => $this->mairie->id,
            'grade'              => \App\Support\Referentiel::GRADE_MAIRE,
            'email'              => 'maire@mairie.fr',
            'communication'      => [],
            'voit_tous_messages' => true,
        ]);

        $this->facteur->update(['communication' => []]);

        $destinataires = $this->mairie->fresh()->destinatairesCommunication(null);

        $this->assertSame([$maire->id], $destinataires->pluck('id')->all());
    }

    public function test_transfert_facteur_vers_service_et_personne(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        // Le « facteur » : reçoit les demandes générales
        $facteur = User::factory()->create([
            'mairie_id'     => $this->mairie->id,
            'grade'         => \App\Support\Referentiel::GRADE_EMPLOYE,
            'communication' => ['inconnu'],
            'email'         => 'facteur@mairie.fr',
        ]);
        // L'agent destinataire du transfert
        $agent = User::factory()->create([
            'mairie_id'     => $this->mairie->id,
            'service'       => 12,
            'grade'         => \App\Support\Referentiel::GRADE_EMPLOYE,
            'communication' => [],
            'email'         => 'agent@mairie.fr',
        ]);

        $ticket = Ticket::create([
            'mairie_id' => $this->mairie->id,
            'reference' => $this->mairie->id . '-1',
            'nom'       => 'Dupont', 'prenom' => 'Marie',
            'telephone' => '0612345678', 'email' => 'marie@example.fr',
            'sujet'     => 'Trou dans la route', 'statut' => Ticket::STATUT_RECEPTION,
        ]);

        // Avant transfert : l'agent ne voit rien, le facteur oui
        $this->assertFalse(Ticket::visiblesPar($agent)->whereKey($ticket->id)->exists());
        $this->assertTrue(Ticket::visiblesPar($facteur)->whereKey($ticket->id)->exists());

        // Transfert nominatif
        $this->actingAs($facteur)->post("/messagerie/tickets/{$ticket->id}/transferer", [
            'users' => [$agent->id],
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertSame([$agent->id], $ticket->idsTransfert());
        $this->assertSame($facteur->id, $ticket->transfere_par);

        // L'agent voit désormais la demande, le facteur la garde sous les yeux
        $this->assertTrue(Ticket::visiblesPar($agent->fresh())->whereKey($ticket->id)->exists());
        $this->assertTrue(Ticket::visiblesPar($facteur->fresh())->whereKey($ticket->id)->exists());

        // L'agent destinataire est prévenu par e-mail
        \Illuminate\Support\Facades\Mail::assertQueued(
            \App\Mail\NouveauMessageTicket::class,
            fn ($mail) => $mail->hasTo('agent@mairie.fr')
        );

        // Transfert vers un service
        $this->actingAs($facteur)->post("/messagerie/tickets/{$ticket->id}/transferer", [
            'services' => [12],
        ])->assertRedirect();

        $this->assertSame([12], $ticket->fresh()->servicesTransfert());
    }

    /**
     * Régression : le destinataire d'un transfert nominatif voyait la demande
     * mais recevait une erreur 403 en tentant de la clôturer ou d'y répondre,
     * parce qu'il n'était pas destinataire du service d'origine — service que
     * l'habitant ne choisit d'ailleurs plus.
     */
    public function test_destinataire_d_un_transfert_peut_repondre_et_cloturer(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $facteur = User::factory()->create([
            'mairie_id'     => $this->mairie->id,
            'grade'         => \App\Support\Referentiel::GRADE_EMPLOYE,
            'communication' => ['inconnu'],
        ]);
        // Ne réceptionne rien : il n'a la demande que par le transfert
        $agent = User::factory()->create([
            'mairie_id'     => $this->mairie->id,
            'service'       => 12,
            'grade'         => \App\Support\Referentiel::GRADE_EMPLOYE,
            'communication' => [],
        ]);

        $ticket = Ticket::create([
            'mairie_id' => $this->mairie->id,
            'reference' => $this->mairie->id . '-1',
            'service'   => null,
            'nom'       => 'Dupont', 'prenom' => 'Marie',
            'telephone' => '0612345678', 'email' => 'marie@example.fr',
            'sujet'     => 'Trou dans la route', 'statut' => Ticket::STATUT_RECEPTION,
        ]);

        // Avant transfert : ni visible, ni traitable
        $this->assertFalse($ticket->peutEtreGerePar($agent));
        $this->actingAs($agent)->post("/messagerie/tickets/{$ticket->id}/cloturer")->assertForbidden();

        $this->actingAs($facteur)->post("/messagerie/tickets/{$ticket->id}/transferer", [
            'users' => [$agent->id],
        ])->assertRedirect();

        // Après transfert : il répond et clôture sans erreur 403
        $agent->refresh();
        $this->assertTrue($ticket->fresh()->peutEtreGerePar($agent));

        $this->actingAs($agent)->post("/messagerie/tickets/{$ticket->id}/repondre", [
            'corps' => 'Nous intervenons cette semaine.',
        ])->assertRedirect();

        $this->actingAs($agent)->post("/messagerie/tickets/{$ticket->id}/cloturer")->assertRedirect();
        $this->assertSame(Ticket::STATUT_CLOTURE, $ticket->fresh()->statut);

        // Le « facteur » qui a transmis garde lui aussi la main
        $this->assertTrue($ticket->fresh()->peutEtreGerePar($facteur->fresh()));
    }

    public function test_transfert_vers_plusieurs_services_et_plusieurs_personnes(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $facteur = User::factory()->create([
            'mairie_id'     => $this->mairie->id,
            'grade'         => \App\Support\Referentiel::GRADE_EMPLOYE,
            'communication' => ['inconnu'],
        ]);
        $agentA = User::factory()->create([
            'mairie_id' => $this->mairie->id, 'service' => 12,
            'grade'     => \App\Support\Referentiel::GRADE_EMPLOYE, 'communication' => [],
            'email'     => 'a@mairie.fr',
        ]);
        $agentB = User::factory()->create([
            'mairie_id' => $this->mairie->id, 'service' => 13,
            'grade'     => \App\Support\Referentiel::GRADE_EMPLOYE, 'communication' => [],
            'email'     => 'b@mairie.fr',
        ]);

        $ticket = Ticket::create([
            'mairie_id' => $this->mairie->id,
            'reference' => $this->mairie->id . '-1',
            'nom'       => 'Dupont', 'prenom' => 'Marie',
            'telephone' => '0612345678', 'email' => 'marie@example.fr',
            'sujet'     => 'Fuite d\'eau', 'statut' => Ticket::STATUT_RECEPTION,
        ]);

        $this->actingAs($facteur)->post("/messagerie/tickets/{$ticket->id}/transferer", [
            'services' => [12, 13],
            'users'    => [$agentA->id, $agentB->id],
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertSame([12, 13], $ticket->servicesTransfert());
        $this->assertSame([$agentA->id, $agentB->id], $ticket->idsTransfert());

        // Les deux destinataires voient la demande et peuvent la traiter
        foreach ([$agentA, $agentB] as $agent) {
            $this->assertTrue(Ticket::visiblesPar($agent->fresh())->whereKey($ticket->id)->exists());
            $this->assertTrue($ticket->peutEtreGerePar($agent->fresh()));
        }

        // Chacun est prévenu une seule fois, malgré service + nominatif
        foreach (['a@mairie.fr', 'b@mairie.fr'] as $adresse) {
            \Illuminate\Support\Facades\Mail::assertQueued(
                \App\Mail\NouveauMessageTicket::class,
                fn ($mail) => $mail->hasTo($adresse)
            );
        }

        // Le dossier « Transféré » les regroupe, tous statuts confondus
        $this->assertSame(1, Ticket::compteursPour($facteur->fresh())[Ticket::DOSSIER_TRANSFERE]);
        $this->actingAs($facteur)->get('/messagerie?dossier=' . Ticket::DOSSIER_TRANSFERE)
            ->assertOk()
            ->assertSee($ticket->reference);
    }

    public function test_transfert_sans_destinataire_refuse(): void
    {
        $facteur = User::factory()->create([
            'mairie_id'     => $this->mairie->id,
            'grade'         => \App\Support\Referentiel::GRADE_EMPLOYE,
            'communication' => ['inconnu'],
        ]);

        $ticket = Ticket::create([
            'mairie_id' => $this->mairie->id,
            'reference' => $this->mairie->id . '-1',
            'nom'       => 'Dupont', 'prenom' => 'Marie',
            'telephone' => '0612345678', 'email' => 'marie@example.fr',
            'sujet'     => 'Sans cible', 'statut' => Ticket::STATUT_RECEPTION,
        ]);

        $this->actingAs($facteur)->post("/messagerie/tickets/{$ticket->id}/transferer", [])
            ->assertSessionHasErrors('transfert');

        $this->assertFalse($ticket->fresh()->estTransfere());
    }

    public function test_champs_vides_ou_un_caractere_refuses(): void
    {
        $this->post('/contacter-mairie', [
            'mairie_id' => $this->mairie->id,
            'nom'       => 'A',
            'prenom'    => '  ',
            'telephone' => '0612345678',
            'email'     => 'marie@example.fr',
            'sujet'     => 'x',
            'message'   => ' ',
        ])->assertSessionHasErrors(['nom', 'prenom', 'sujet', 'message']);
    }

    public function test_reprise_ticket_citoyen_verifie_ref_et_email(): void
    {
        $ticket = Ticket::create([
            'mairie_id' => $this->mairie->id,
            'reference' => '1',
            'nom'       => 'Dupont', 'prenom' => 'Marie',
            'telephone' => '0612345678', 'email' => 'marie@example.fr',
            'sujet'     => 'Voirie',
        ]);

        // Données erronées → refus
        $this->post('/mon-ticket', ['reference' => '1', 'email' => 'faux@example.fr'])
            ->assertSessionHasErrors('ticket');

        // Bon couple ref + email → redirection vers la conversation (page GET)
        $this->post('/mon-ticket', ['reference' => '1', 'email' => 'marie@example.fr'])
            ->assertRedirect(route('contact.ticket.voir', $ticket, absolute: false));

        // La conversation est consultable et rafraîchissable
        $this->get("/mon-ticket/{$ticket->id}")->assertOk()->assertSee('Voirie');
    }

    public function test_cycle_cloture_reouverture(): void
    {
        $agent = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'grade'     => \App\Support\Referentiel::GRADE_DIR_CABINET,
        ]);
        $ticket = Ticket::create([
            'mairie_id' => $this->mairie->id,
            'reference' => '1',
            'nom'       => 'Dupont', 'prenom' => 'Marie',
            'telephone' => '0612345678', 'email' => 'marie@example.fr',
            'sujet'     => 'Voirie', 'statut' => Ticket::STATUT_RECEPTION,
        ]);

        // La mairie répond → dossier « Réponse »
        $this->actingAs($agent)->post("/messagerie/tickets/{$ticket->id}/repondre", ['corps' => 'Bonjour']);
        $this->assertSame(Ticket::STATUT_REPONSE, $ticket->fresh()->statut);

        // La mairie clôture → dossier « Clôturé », écriture impossible
        $this->actingAs($agent)->post("/messagerie/tickets/{$ticket->id}/cloturer")->assertRedirect();
        $ticket->refresh();
        $this->assertSame(Ticket::STATUT_CLOTURE, $ticket->statut);
        $this->assertFalse($ticket->peutEcrire());
        $this->assertTrue($ticket->reouverturePossible());

        $this->actingAs($agent)->post("/messagerie/tickets/{$ticket->id}/repondre", ['corps' => 'Trop tard'])
            ->assertForbidden();

        // Le citoyen demande la réouverture
        $this->post('/mon-ticket', ['reference' => '1', 'email' => 'marie@example.fr'])->assertRedirect();
        $this->post("/mon-ticket/{$ticket->id}/reouverture", ['motif' => 'Le problème persiste.'])->assertRedirect();
        $this->assertSame(Ticket::STATUT_REOUVERTURE, $ticket->fresh()->statut);

        // La mairie refuse → retour à « Clôturé »
        $this->actingAs($agent)->post("/messagerie/tickets/{$ticket->id}/reouverture/refuser")->assertRedirect();
        $this->assertSame(Ticket::STATUT_CLOTURE, $ticket->fresh()->statut);

        // Nouvelle demande, acceptée cette fois → retour à « Réception »
        $this->post("/mon-ticket/{$ticket->id}/reouverture", ['motif' => 'Toujours pas résolu.']);
        $this->actingAs($agent)->post("/messagerie/tickets/{$ticket->id}/reouverture/accepter")->assertRedirect();
        $ticket->refresh();
        $this->assertSame(Ticket::STATUT_RECEPTION, $ticket->statut);
        $this->assertTrue($ticket->peutEcrire());
    }

    public function test_reouverture_impossible_apres_15_jours(): void
    {
        $ticket = Ticket::create([
            'mairie_id'   => $this->mairie->id,
            'reference'   => '1',
            'nom'         => 'Dupont', 'prenom' => 'Marie',
            'telephone'   => '0612345678', 'email' => 'marie@example.fr',
            'sujet'       => 'Voirie',
            'statut'      => Ticket::STATUT_CLOTURE,
            'cloture_at'  => now()->subDays(16),
            'cloture_par' => 'mairie',
        ]);

        $this->assertFalse($ticket->reouverturePossible());

        $this->post('/mon-ticket', ['reference' => '1', 'email' => 'marie@example.fr'])->assertRedirect();
        $this->post("/mon-ticket/{$ticket->id}/reouverture", ['motif' => 'Trop tard'])
            ->assertSessionHasErrors('reouverture');
    }

    public function test_agent_mairie_repond_admin_lecture_seule(): void
    {
        // Agent de la direction : reçoit tous les services par défaut
        $agent = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'grade'     => \App\Support\Referentiel::GRADE_DIR_CABINET,
        ]);
        $ticket = Ticket::create([
            'mairie_id' => $this->mairie->id,
            'reference' => '1',
            'nom'       => 'Dupont', 'prenom' => 'Marie',
            'telephone' => '0612345678', 'email' => 'm@example.fr',
            'sujet'     => 'Test',
        ]);

        $this->actingAs($agent)->get('/messagerie')->assertOk();

        $this->actingAs($agent)->post("/messagerie/tickets/{$ticket->id}/repondre", [
            'corps' => 'Bonjour, nous intervenons.',
        ])->assertRedirect();
        $this->assertCount(1, $ticket->fresh()->messages);

        // Admin : lecture seule, ne peut pas répondre
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post("/messagerie/tickets/{$ticket->id}/repondre", [
            'corps' => 'Interdit',
        ])->assertForbidden();
    }

    /**
     * Une mairie n'apparaît sur « Contacter votre Mairie » que si quelqu'un
     * peut recevoir la demande ET que son abonnement est valide.
     */
    public function test_mairie_listee_seulement_si_reception_et_abonnement_valides(): void
    {
        // Personne pour réceptionner
        $sansAgent = Mairie::create([
            'nom'                 => 'Mairie Sans Agent',
            'code_postal'         => '11111',
            'email'               => 'sansagent@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);

        // Quelqu'un réceptionne, mais l'abonnement est terminé
        $expiree = Mairie::create([
            'nom'                 => 'Mairie Expiree',
            'code_postal'         => '22222',
            'email'               => 'expiree@mairie.fr',
            'date_fin_abonnement' => now()->subDay()->toDateString(),
        ]);
        $this->receptionniste($expiree);

        $reponse = $this->get('/contacter-mairie');
        $reponse->assertOk()
            ->assertSee('Mairie Test')
            ->assertDontSee('Mairie Sans Agent')
            ->assertDontSee('Mairie Expiree');

        // Et un envoi forgé sur ces mairies est refusé
        foreach ([$sansAgent, $expiree] as $mairie) {
            $this->post('/contacter-mairie', [
                'mairie_id' => $mairie->id,
                'nom'       => 'Dupont', 'prenom' => 'Marie',
                'telephone' => '0612345678', 'email' => 'marie@example.fr',
                'sujet'     => 'Test', 'message' => 'Bonjour, ceci est un test.',
            ])->assertSessionHasErrors('mairie_id');
        }

        $this->assertSame(0, Ticket::count());
    }
}
