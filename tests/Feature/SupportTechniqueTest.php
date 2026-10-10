<?php

namespace Tests\Feature;

use App\Mail\SupportNouveauMessage;
use App\Models\Mairie;
use App\Models\SupportDemande;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Support technique MGDS (page Contact) : assistant à étapes, puis demande
 * transmise à l'équipe MGDS, qui répond signée « Admin ».
 */
class SupportTechniqueTest extends TestCase
{
    use RefreshDatabase;

    private Mairie $mairie;
    private User $agent;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->mairie = Mairie::create([
            'nom'                 => 'Mairie Test',
            'code_postal'         => '00000',
            'email'               => 'test@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);

        $this->agent = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'grade'     => \App\Support\Referentiel::GRADE_EMPLOYE,
        ]);

        $this->admin = User::factory()->admin()->create();
    }

    /** Demande envoyée par une personne sans compte. */
    private function demandePublique(array $extra = []): SupportDemande
    {
        $this->post('/contact', $this->jetonFormulaire() + array_merge([
            'concerne' => 'connexion',
            'nom'      => 'Durand', 'prenom' => 'Paul',
            'email'    => 'paul@example.fr',
            'message'  => 'Je n\'arrive pas à me connecter depuis hier.',
        ], $extra))->assertSessionHasNoErrors();

        return SupportDemande::latest('id')->firstOrFail();
    }

    public function test_page_contact_affiche_l_assistant_aux_visiteurs_et_aux_agents(): void
    {
        $this->get('/contact')->assertOk()
            ->assertSee('Je ne trouve pas ma mairie')
            ->assertSee('sans compte MGDS');

        $this->actingAs($this->agent)->get('/contact')->assertOk()
            ->assertSee('Un collègue de la même mairie')
            ->assertDontSee('Je ne trouve pas ma mairie');
    }

    public function test_personne_sans_compte_ouvre_une_demande_et_la_suit_par_son_lien(): void
    {
        $demande = $this->demandePublique();

        $this->assertFalse($demande->avec_compte);
        $this->assertNull($demande->user_id);
        $this->assertSame('S-' . $demande->id, $demande->reference);
        $this->assertSame(SupportDemande::STATUT_RECEPTION, $demande->statut);

        // L'assistant transmet les réponses déjà données, puis le message
        $messages = $demande->messages;
        $this->assertSame(SupportMessage::AUTEUR_ASSISTANT, $messages[0]->auteur);
        $this->assertStringContainsString('Sans compte MGDS', $messages[0]->corps);
        $this->assertSame(SupportMessage::AUTEUR_DEMANDEUR, $messages[1]->auteur);

        // Lien de suivi envoyé à la personne, équipe prévenue
        Mail::assertQueued(SupportNouveauMessage::class, fn ($m) => $m->hasTo('paul@example.fr') && ! $m->pourAdmin);
        Mail::assertQueued(SupportNouveauMessage::class, fn ($m) => $m->hasTo($this->admin->email) && $m->pourAdmin);

        $this->get('/support/' . $demande->jeton)->assertOk()->assertSee('depuis hier');
        $this->get('/support/' . str_repeat('x', 64))->assertNotFound();
    }

    public function test_coordonnees_obligatoires_sans_compte(): void
    {
        $this->post('/contact', $this->jetonFormulaire() + [
            'concerne' => 'autre',
            'message'  => 'Un problème quelconque.',
        ])->assertSessionHasErrors(['nom', 'prenom', 'email']);

        $this->assertSame(0, SupportDemande::count());
    }

    public function test_precision_demandee_selon_le_choix(): void
    {
        $this->actingAs($this->agent)->post('/contact', $this->jetonFormulaire() + [
            'concerne' => 'collegue',
            'message'  => 'Ma collègue ne voit plus ses tâches.',
        ])->assertSessionHasErrors('precision');

        $this->actingAs($this->agent)->post('/contact', $this->jetonFormulaire() + [
            'concerne'  => 'collegue',
            'precision' => 'Marie Dupont',
            'message'   => 'Ma collègue ne voit plus ses tâches.',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Marie Dupont', SupportDemande::first()->precision);
    }

    public function test_un_visiteur_ne_peut_pas_choisir_une_option_reservee_aux_agents(): void
    {
        $this->post('/contact', $this->jetonFormulaire() + [
            'concerne' => 'compte',
            'nom'      => 'Durand', 'prenom' => 'Paul', 'email' => 'paul@example.fr',
            'message'  => 'Un problème quelconque.',
        ])->assertSessionHasErrors('concerne');
    }

    public function test_agent_connecte_la_demande_est_rattachee_a_son_compte(): void
    {
        $reponse = $this->actingAs($this->agent)->post('/contact', $this->jetonFormulaire() + [
            'concerne' => 'compte',
            // Coordonnées ignorées : on prend le compte
            'nom'      => 'Autre', 'email' => 'autre@example.fr',
            'message'  => 'Je ne vois pas l\'application Marché.',
        ])->assertSessionHasNoErrors();

        $demande = SupportDemande::firstOrFail();
        $this->assertTrue($demande->avec_compte);
        $this->assertSame($this->agent->id, $demande->user_id);
        $this->assertNull($demande->email);
        $this->assertStringContainsString($this->agent->username, $demande->messages[0]->corps);

        // Redirigé sur la conversation, dans Centre de messagerie / Message Support
        $lien = $demande->lienMessagerie();
        $reponse->assertRedirect($lien);
        $this->actingAs($this->agent)->get($lien)->assertOk()
            ->assertSee($demande->reference)
            ->assertSee('application Marché');

        // La page Contact ne sert qu'à créer la demande
        $this->actingAs($this->agent)->get('/contact')->assertDontSee($demande->reference);

        // Le lien seul ne suffit pas : il faut être ce compte
        auth()->logout();
        $this->get('/support/' . $demande->jeton)->assertNotFound();
        $autre = User::factory()->create(['mairie_id' => $this->mairie->id]);
        $this->actingAs($autre)->get('/support/' . $demande->jeton)->assertNotFound();
        $this->actingAs($this->agent)->get('/support/' . $demande->jeton)->assertRedirect($lien);

        // Compte supprimé : le lien ne s'ouvre plus pour personne
        $this->agent->delete();
        auth()->logout();
        $this->get('/support/' . $demande->jeton)->assertNotFound();
        $this->actingAs($this->admin)->get($lien)->assertOk()->assertSee('Compte supprimé');
    }

    public function test_l_agent_repond_et_cloture_depuis_la_messagerie(): void
    {
        $this->actingAs($this->agent)->post('/contact', $this->jetonFormulaire() + [
            'concerne' => 'compte',
            'message'  => 'Je ne vois pas l\'application Marché.',
        ])->assertSessionHasNoErrors();
        $demande = SupportDemande::firstOrFail();
        $lien    = $demande->lienMessagerie();

        $this->actingAs($this->agent)->post('/support/' . $demande->jeton . '/repondre', ['corps' => 'Toujours rien ce matin.'])
            ->assertRedirect($lien);
        $this->actingAs($this->agent)->post('/support/' . $demande->jeton . '/cloturer')->assertRedirect($lien);
        $this->assertTrue($demande->fresh()->estCloture());

        // L'e-mail de l'agent mène à la messagerie, pas au lien secret
        $mail = new SupportNouveauMessage($demande->fresh(), pourAdmin: false, nouvelle: true);
        $mail->assertSeeInHtml('onglet=support', false);
        $mail->assertDontSeeInHtml($demande->jeton, false);
    }

    public function test_un_agent_ne_voit_que_ses_propres_demandes_au_support(): void
    {
        $this->actingAs($this->agent)->post('/contact', $this->jetonFormulaire() + [
            'concerne' => 'compte',
            'message'  => 'Message tres confidentiel de l\'agent.',
        ])->assertSessionHasNoErrors();
        $demande = SupportDemande::firstOrFail();
        auth()->logout();
        $publique = $this->demandePublique();

        $collegue = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'grade'     => \App\Support\Referentiel::GRADE_MAIRE,
        ]);

        // Ni la demande d'un collègue, ni celle d'une personne sans compte
        foreach ([$demande, $publique] as $d) {
            $this->actingAs($collegue)->get('/messagerie?onglet=support&demande=' . $d->id)->assertOk()
                ->assertDontSee($d->reference . ' —')
                ->assertDontSee('tres confidentiel')
                ->assertDontSee('paul@example.fr');
        }

        $this->actingAs($this->agent)->get('/messagerie?onglet=support')->assertOk()
            ->assertSee($demande->reference . ' —')
            ->assertDontSee($publique->reference . ' —');
    }

    public function test_seuls_les_admins_voient_les_demandes(): void
    {
        $demande = $this->demandePublique();

        $responsable = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'grade'     => \App\Support\Referentiel::GRADE_MAIRE,
        ]);

        $this->actingAs($responsable)->get('/admin/messages')->assertForbidden();
        $this->actingAs($responsable)->get('/admin/messages/' . $demande->id)->assertForbidden();

        // L'onglet de Paramètres administratifs renvoie vers Centre de messagerie / Message Support
        $this->actingAs($this->admin)->get('/admin/messages')->assertRedirect(route('messagerie.index', ['onglet' => 'support']));
        $this->actingAs($this->admin)->get('/admin/messages/' . $demande->id)->assertRedirect($demande->lienMessagerie());
        $this->actingAs($this->admin)->get('/users')->assertDontSee(route('admin.messages.index'));

        $this->actingAs($this->admin)->get('/messagerie?onglet=support')->assertOk()->assertSee($demande->reference);
        $this->actingAs($this->admin)->get($demande->lienMessagerie())->assertOk()
            ->assertSee('paul@example.fr')
            ->assertSee('Sans compte MGDS')
            ->assertSee(route('admin.messages.repondre', $demande), false);
    }

    public function test_reponse_de_l_admin_signee_admin_sans_son_nom(): void
    {
        $demande = $this->demandePublique();

        $this->actingAs($this->admin)
            ->post('/admin/messages/' . $demande->id . '/repondre', ['corps' => 'Utilisez le lien « Mot de passe oublié ».'])
            ->assertSessionHasNoErrors();

        $demande->refresh();
        $this->assertSame(SupportDemande::STATUT_REPONSE, $demande->statut);
        Mail::assertQueued(SupportNouveauMessage::class, fn ($m) => $m->hasTo('paul@example.fr') && ! $m->nouvelle);

        auth()->logout();
        $this->get('/support/' . $demande->jeton)->assertOk()
            ->assertSee('Admin')
            ->assertSee('Mot de passe oublié')
            ->assertDontSee($this->admin->username)
            ->assertDontSee($this->admin->nom);
    }

    public function test_la_personne_repond_puis_cloture(): void
    {
        $demande = $this->demandePublique();
        $demande->ajouterMessage(SupportMessage::AUTEUR_ADMIN, 'Pouvez-vous préciser ?');

        $this->post('/support/' . $demande->jeton . '/repondre', ['corps' => 'Oui, voici le détail.'])
            ->assertRedirect('/support/' . $demande->jeton);
        $this->assertSame(SupportDemande::STATUT_RECEPTION, $demande->fresh()->statut);

        $this->post('/support/' . $demande->jeton . '/cloturer')->assertRedirect();
        $this->assertTrue($demande->fresh()->estCloture());

        // Plus d'écriture une fois clôturée, ni d'un côté ni de l'autre
        $this->post('/support/' . $demande->jeton . '/repondre', ['corps' => 'Encore une chose.'])->assertForbidden();
        $this->actingAs($this->admin)
            ->post('/admin/messages/' . $demande->id . '/repondre', ['corps' => 'Trop tard.'])
            ->assertForbidden();
    }

    public function test_anti_robots_sur_le_formulaire_public(): void
    {
        $this->get('/contact')->assertSee('name="champ_controle"', false);

        $this->post('/contact', [
            'concerne' => 'autre',
            'nom'      => 'Durand', 'prenom' => 'Paul', 'email' => 'paul@example.fr',
            'message'  => 'Un problème quelconque.',
        ])->assertSessionHasErrors('anti_robot');

        $this->assertSame(0, SupportDemande::count());
    }
}
