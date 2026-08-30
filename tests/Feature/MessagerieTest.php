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

    protected function setUp(): void
    {
        parent::setUp();

        $this->mairie = Mairie::create([
            'nom'                 => 'Mairie Test',
            'code_postal'         => '00000',
            'afficher_contact'    => true,
            'email'               => 'test@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
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

        // Une personne qui voit tous les messages mais n'a aucun service coché :
        // elle doit quand même être prévenue (filet de sécurité)
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
            'afficher_contact'    => true,
            'email'               => 'roro@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);

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

        // Le maire (visibilité globale) est prévenu même sans service coché
        \Illuminate\Support\Facades\Mail::assertQueued(
            \App\Mail\NouveauMessageTicket::class,
            fn ($mail) => $mail->hasTo('maire@mairie.fr')
        );

        // Il voit les messages de sa mairie, pas ceux de la voisine
        $this->assertSame(2, Ticket::visiblesPar($maire)->count());
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
            'cible'   => 'personne',
            'user_id' => $agent->id,
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertSame($agent->id, $ticket->transfere_user_id);
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
            'cible'   => 'service',
            'service' => 12,
        ])->assertRedirect();

        $this->assertSame(12, $ticket->fresh()->transfere_service);
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
}
