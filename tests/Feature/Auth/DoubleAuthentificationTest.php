<?php

namespace Tests\Feature\Auth;

use App\Mail\CodeVerification;
use App\Models\AppareilConfiance;
use App\Models\Mairie;
use App\Models\User;
use App\Services\DoubleAuthentification as A2F;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Double authentification (A2F) : code à 6 chiffres par e-mail à la
 * connexion (sauf appareil de confiance : même navigateur, même IP, 30 jours)
 * et avant chaque modification importante, même depuis un appareil de confiance.
 */
class DoubleAuthentificationTest extends TestCase
{
    use RefreshDatabase;

    private Mairie $mairie;
    private User $agent;

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
            'service'   => 12,
            'grade'     => Referentiel::GRADE_EMPLOYE,
            'email'     => 'agent@example.fr',
        ]);
    }

    private function motDePasse(User $user, array $entetes = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/login', ['username' => $user->username, 'password' => 'password'], $entetes);
    }

    public function test_le_mot_de_passe_seul_n_ouvre_pas_la_session(): void
    {
        $this->motDePasse($this->agent)->assertRedirect(route('a2f.connexion'));

        $this->assertGuest();
        Mail::assertSent(CodeVerification::class, fn ($m) => $m->hasTo('agent@example.fr'));
        $this->get('/apps')->assertRedirect(route('login'));

        // Adresse masquée sur la page du code
        $this->get(route('a2f.connexion'))->assertOk()->assertSee('ag***@example.fr');

        // Mauvais code : toujours pas connecté
        $this->post('/verification-connexion', ['code' => '000000'])
            ->assertRedirect(route('a2f.connexion'))
            ->assertSessionHasErrors('code');
        $this->assertGuest();

        // Bon code : connecté
        $this->post('/verification-connexion', ['code' => $this->dernierCodeA2F()])
            ->assertRedirect(route('apps'));
        $this->assertAuthenticatedAs($this->agent);
    }

    public function test_le_code_ne_sert_qu_une_fois_et_bloque_apres_cinq_erreurs(): void
    {
        $this->motDePasse($this->agent);
        $code = $this->dernierCodeA2F();

        for ($i = 1; $i <= A2F::ESSAIS_MAX; $i++) {
            $this->post('/verification-connexion', ['code' => $code === '111111' ? '222222' : '111111']);
        }

        // Le bon code ne vaut plus rien : il faut recommencer depuis le mot de passe
        $this->post('/verification-connexion', ['code' => $code])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_le_code_expire_apres_dix_minutes(): void
    {
        $this->motDePasse($this->agent);
        $code = $this->dernierCodeA2F();

        $this->travel(A2F::MINUTES_CODE + 1)->minutes();

        $this->post('/verification-connexion', ['code' => $code])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_appareil_de_confiance_meme_navigateur_meme_ip(): void
    {
        $ip = ['CF-Connecting-IP' => '198.51.100.7'];

        $this->motDePasse($this->agent, $ip);
        $reponse = $this->withHeaders($ip)->post('/verification-connexion', ['code' => $this->dernierCodeA2F(), 'confiance' => '1']);
        $jeton   = $reponse->getCookie(A2F::COOKIE_APPAREIL)?->getValue();

        $this->assertNotEmpty($jeton);
        $this->assertSame(1, AppareilConfiance::where('user_id', $this->agent->id)->count());
        // Seule l'empreinte du jeton est stockée
        $this->assertNull(AppareilConfiance::where('jeton_hash', $jeton)->first());

        $this->post('/logout');
        $this->assertGuest();

        // Même navigateur, même IP : pas de code
        $envois = count(Mail::sent(CodeVerification::class));
        $this->withCookie(A2F::COOKIE_APPAREIL, $jeton);
        $this->motDePasse($this->agent, $ip)->assertRedirect(route('apps'));
        $this->assertAuthenticatedAs($this->agent);
        $this->assertCount($envois, Mail::sent(CodeVerification::class));
        $this->post('/logout');

        // Autre adresse IP : le code est redemandé
        $this->motDePasse($this->agent, ['CF-Connecting-IP' => '203.0.113.50'])->assertRedirect(route('a2f.connexion'));
        $this->assertGuest();

        // Autre compte sur le même appareil : le code est demandé aussi
        $collegue = User::factory()->create(['mairie_id' => $this->mairie->id, 'email' => 'collegue@example.fr']);
        $this->motDePasse($collegue, $ip)->assertRedirect(route('a2f.connexion'));
        $this->assertGuest();

        // Plus de 30 jours : le code est redemandé
        $this->travel(A2F::JOURS_CONFIANCE + 1)->days();
        $this->motDePasse($this->agent, $ip)->assertRedirect(route('a2f.connexion'));
        $this->assertGuest();
    }

    public function test_changer_d_email_demande_un_code_meme_sur_un_appareil_de_confiance(): void
    {
        AppareilConfiance::create([
            'user_id' => $this->agent->id, 'jeton_hash' => str_repeat('a', 64), 'ip' => '127.0.0.1',
            'navigateur_hash' => str_repeat('b', 64), 'expire_at' => now()->addDays(10),
        ]);

        $donnees = ['email' => 'nouvelle@example.fr', 'current_password' => 'password'];

        // Pas de code récent : rien n'est modifié, un code part à l'adresse actuelle
        $this->actingAs($this->agent)->patch('/profile', $donnees)->assertRedirect(route('a2f.action'));
        $this->assertSame('agent@example.fr', $this->agent->fresh()->email);
        Mail::assertSent(CodeVerification::class, fn ($m) => $m->but === A2F::BUT_ACTION);
        $this->assertSame(['agent@example.fr'], array_column(Mail::sent(CodeVerification::class)[0]->to, 'address'));

        // Bon code : retour au profil pour valider la modification
        $this->post('/verification-modification', ['code' => $this->dernierCodeA2F()])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('a2f_ok');

        $this->patch('/profile', $donnees)->assertRedirect(route('profile.edit'));
        $this->assertSame('nouvelle@example.fr', $this->agent->fresh()->email);

        // L'adresse a changé : plus aucun appareil de confiance
        $this->assertSame(0, AppareilConfiance::where('user_id', $this->agent->id)->count());

        // La confirmation ne dure que 10 minutes
        $this->travel(A2F::MINUTES_CONFIRMATION + 1)->minutes();
        $this->patch('/profile', ['email' => 'encore@example.fr', 'current_password' => 'password'])
            ->assertRedirect(route('a2f.action'));
    }

    public function test_mot_de_passe_et_suppression_du_compte_demandent_un_code(): void
    {
        $this->actingAs($this->agent)->put('/password', [
            'current_password'      => 'password',
            'password'              => 'nouveau-mot-de-passe-42',
            'password_confirmation' => 'nouveau-mot-de-passe-42',
        ])->assertRedirect(route('a2f.action'));
        $this->assertTrue(Hash::check('password', $this->agent->fresh()->password));

        $this->actingAs($this->agent)->delete('/profile', ['password' => 'password'])
            ->assertRedirect(route('a2f.action'));
        $this->assertNotNull($this->agent->fresh());
    }

    public function test_l_admin_qui_change_l_email_d_un_compte_saisit_un_code(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.fr']);

        $donnees = [
            'prenom'  => $this->agent->prenom,
            'nom'     => $this->agent->nom,
            'role'    => 'user',
            'mairie_id' => $this->mairie->id,
            'service' => $this->agent->service,
            'grade'   => $this->agent->grade,
            'email'   => 'recupere@example.fr',
        ];

        $this->actingAs($admin)->put("/users/{$this->agent->id}", $donnees)->assertRedirect(route('a2f.action'));
        $this->assertSame('agent@example.fr', $this->agent->fresh()->email);
        // Le code part à l'admin, pas au compte modifié
        Mail::assertSent(CodeVerification::class, fn ($m) => $m->hasTo('admin@example.fr'));

        $this->post('/verification-modification', ['code' => $this->dernierCodeA2F()])
            ->assertRedirect(route('users.edit', $this->agent));
        $this->put("/users/{$this->agent->id}", $donnees)->assertRedirect(route('users.index'));
        $this->assertSame('recupere@example.fr', $this->agent->fresh()->email);
    }

    public function test_le_responsable_qui_reinitialise_un_mot_de_passe_saisit_un_code(): void
    {
        $gestionnaire = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'grade'     => Referentiel::GRADE_DIR_CABINET,
            'droits'    => ['gestion_utilisateurs'],
            'email'     => 'responsable@example.fr',
        ]);
        $ancien = $this->agent->password;

        $donnees = [
            'prenom'            => $this->agent->prenom,
            'nom'               => $this->agent->nom,
            'service'           => $this->agent->service,
            'grade'             => $this->agent->grade,
            'email'             => $this->agent->email,
            'reinitialiser_mdp' => '1',
        ];

        $this->actingAs($gestionnaire)->put("/gestion/utilisateurs/{$this->agent->id}", $donnees)
            ->assertRedirect(route('a2f.action'));
        $this->assertSame($ancien, $this->agent->fresh()->password);

        // Sans changement d'e-mail ni de mot de passe : pas de code
        unset($donnees['reinitialiser_mdp']);
        $this->actingAs($gestionnaire)->put("/gestion/utilisateurs/{$this->agent->id}", $donnees)
            ->assertRedirect(route('gestion.utilisateurs.index'));
    }

    public function test_un_compte_sans_e_mail_ne_peut_pas_recevoir_de_code(): void
    {
        $sansEmail = User::factory()->create(['mairie_id' => $this->mairie->id, 'email' => null]);

        $this->motDePasse($sansEmail)->assertSessionHasErrors('username');
        $this->assertGuest();
    }
}
