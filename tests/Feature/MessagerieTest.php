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
        $this->assertSame('1', $ticket->reference);
        $this->assertSame('Voirie abîmée', $ticket->sujet);
        $this->assertCount(1, $ticket->messages);
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

        // Bon couple ref + email → accès au ticket
        $this->post('/mon-ticket', ['reference' => '1', 'email' => 'marie@example.fr'])
            ->assertOk()
            ->assertSee('Voirie');
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
