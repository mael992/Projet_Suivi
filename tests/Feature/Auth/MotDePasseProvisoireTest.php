<?php

namespace Tests\Feature\Auth;

use App\Mail\MotDePasseProvisoire;
use App\Models\Mairie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Bouton « Recevoir un mot de passe provisoire » : le mot de passe habituel
 * n'est jamais remplacé. S'il n'est pas utilisé, le provisoire expire seul.
 */
class MotDePasseProvisoireTest extends TestCase
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

    private function agent(): User
    {
        return User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'email'     => 'agent@mairie.fr',
            'password'  => Hash::make('ancien-mot-de-passe'),
        ]);
    }

    public function test_le_bouton_est_propose_sur_la_page_mot_de_passe_oublie(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee(__('messages.auth_temp_password_btn'));
    }

    public function test_l_envoi_ne_touche_pas_au_mot_de_passe_existant(): void
    {
        Mail::fake();
        $agent = $this->agent();
        $hashAvant = $agent->password;

        $this->post('/forgot-password/provisoire', ['email' => 'agent@mairie.fr'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertQueued(MotDePasseProvisoire::class, fn ($mail) => $mail->hasTo('agent@mairie.fr'));

        $agent->refresh();
        $this->assertSame($hashAvant, $agent->password);
        $this->assertTrue($agent->passwordProvisoireActif());

        // L'ancien mot de passe fonctionne toujours
        $this->post('/login', ['username' => $agent->username, 'password' => 'ancien-mot-de-passe'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($agent);
    }

    public function test_adresse_inconnue_repond_pareil_sans_rien_creer(): void
    {
        Mail::fake();

        $this->post('/forgot-password/provisoire', ['email' => 'personne@example.fr'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertNothingQueued();
    }

    public function test_connexion_avec_le_provisoire_impose_un_nouveau_mot_de_passe(): void
    {
        $agent = $this->agent();
        $clair = $agent->genererPasswordProvisoire();

        $this->post('/login', ['username' => $agent->username, 'password' => $clair])
            ->assertRedirect();
        $this->assertAuthenticatedAs($agent);

        // Toute navigation renvoie vers le changement de mot de passe
        $this->get('/apps')->assertRedirect(route('password.force-change'));

        // Usage unique : le provisoire est déjà consommé
        $this->assertFalse($agent->fresh()->passwordProvisoireActif());
    }

    public function test_provisoire_expire_ne_fonctionne_plus(): void
    {
        $agent = $this->agent();
        $clair = $agent->genererPasswordProvisoire();

        $this->travel(User::HEURES_PASSWORD_PROVISOIRE + 1)->hours();

        $this->post('/login', ['username' => $agent->username, 'password' => $clair])
            ->assertSessionHasErrors('username');
        $this->assertGuest();

        // Et le mot de passe habituel n'a pas bougé
        $this->post('/login', ['username' => $agent->username, 'password' => 'ancien-mot-de-passe'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($agent);
    }

    public function test_provisoire_ignore_puis_abandonne_laisse_le_compte_intact(): void
    {
        $agent = $this->agent();
        $clair = $agent->genererPasswordProvisoire();

        // Connexion avec le provisoire, puis départ sans choisir de mot de passe
        $this->post('/login', ['username' => $agent->username, 'password' => $clair]);
        $this->post('/logout');

        $agent->refresh();
        $this->assertFalse($agent->must_change_password);
        $this->assertTrue(Hash::check('ancien-mot-de-passe', $agent->password));

        // La connexion habituelle se fait sans écran imposé
        $this->post('/login', ['username' => $agent->username, 'password' => 'ancien-mot-de-passe']);
        $this->get('/apps')->assertOk();
    }

    public function test_le_changement_leve_l_obligation(): void
    {
        $agent = $this->agent();
        $clair = $agent->genererPasswordProvisoire();

        $this->post('/login', ['username' => $agent->username, 'password' => $clair]);

        $this->post('/password/change-required', [
            'password'              => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertRedirect();

        $agent->refresh();
        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $agent->password));

        $this->get('/apps')->assertOk();
    }
}
