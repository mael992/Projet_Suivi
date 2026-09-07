<?php

namespace Tests\Feature;

use App\Models\Mairie;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Ouverture d'un compte : le mot de passe provisoire est tiré au sort par
 * le système. La mairie n'en choisit aucun — elle ne peut donc pas en
 * réutiliser un d'un agent à l'autre.
 */
class CreationCompteTest extends TestCase
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

    private function gestionnaire(): User
    {
        return User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 1,
            'grade'     => Referentiel::GRADE_DIR_CABINET,
            'droits'    => ['gestion_utilisateurs'],
        ]);
    }

    private function creer(array $extra = []): User
    {
        $this->actingAs($this->gestionnaire())->post('/gestion/utilisateurs', array_merge([
            'prenom'  => 'Camille',
            'nom'     => 'Durand',
            'service' => 12,
            'grade'   => Referentiel::GRADE_EMPLOYE,
        ], $extra))->assertRedirect();

        return User::where('nom', 'Durand')->firstOrFail();
    }

    public function test_le_mot_de_passe_provisoire_est_genere_par_le_systeme(): void
    {
        $cree = $this->creer();

        $this->assertNotEmpty($cree->temp_password);
        $this->assertSame(12, strlen($cree->temp_password));
        $this->assertTrue($cree->must_change_password);
        $this->assertNotNull($cree->temp_password_expires_at);

        // Le provisoire stocké est bien celui du compte
        $this->assertTrue(Hash::check($cree->temp_password, $cree->password));
    }

    public function test_le_formulaire_n_accepte_plus_de_mot_de_passe_impose(): void
    {
        $cree = $this->creer(['password' => 'motdepasse-impose']);

        // La valeur envoyée est ignorée : le système garde le sien
        $this->assertFalse(Hash::check('motdepasse-impose', $cree->password));
        $this->assertNotSame('motdepasse-impose', $cree->temp_password);
    }

    public function test_deux_comptes_recoivent_deux_mots_de_passe_differents(): void
    {
        $premier = $this->creer();

        $this->actingAs($this->gestionnaire())->post('/gestion/utilisateurs', [
            'prenom'  => 'Alex',
            'nom'     => 'Bernard',
            'service' => 12,
            'grade'   => Referentiel::GRADE_EMPLOYE,
        ])->assertRedirect();

        $second = User::where('nom', 'Bernard')->firstOrFail();

        $this->assertNotSame($premier->temp_password, $second->temp_password);
    }

    public function test_le_provisoire_genere_permet_de_se_connecter_puis_impose_un_changement(): void
    {
        $cree = $this->creer();

        // La création laisse le gestionnaire connecté : /login lui est interdit
        $this->post('/logout');

        $this->post('/login', [
            'username' => $cree->username,
            'password' => $cree->temp_password,
        ])->assertRedirect();

        $this->assertAuthenticatedAs($cree);
        $this->get('/apps')->assertRedirect(route('password.force-change'));
    }

    public function test_la_case_de_reinitialisation_tire_un_nouveau_provisoire(): void
    {
        $cree    = $this->creer();
        $ancien  = $cree->temp_password;

        $this->actingAs($this->gestionnaire())->put("/gestion/utilisateurs/{$cree->id}", [
            'prenom'            => 'Camille',
            'nom'               => 'Durand',
            'service'           => 12,
            'grade'             => Referentiel::GRADE_EMPLOYE,
            'reinitialiser_mdp' => '1',
        ])->assertRedirect();

        $cree->refresh();
        $this->assertNotSame($ancien, $cree->temp_password);
        $this->assertTrue($cree->must_change_password);
        $this->assertTrue(Hash::check($cree->temp_password, $cree->password));
    }

    public function test_sans_la_case_le_mot_de_passe_reste_inchange(): void
    {
        $cree   = $this->creer();
        $ancien = $cree->password;

        $this->actingAs($this->gestionnaire())->put("/gestion/utilisateurs/{$cree->id}", [
            'prenom'  => 'Camille',
            'nom'     => 'Durand',
            'service' => 12,
            'grade'   => Referentiel::GRADE_EMPLOYE,
        ])->assertRedirect();

        $this->assertSame($ancien, $cree->fresh()->password);
    }

    /** L'administrateur, lui, choisit encore son mot de passe. */
    public function test_le_compte_administrateur_garde_un_mot_de_passe_choisi(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post('/users', [
            'prenom'   => 'Root',
            'nom'      => 'Admin',
            'role'     => 'admin',
            'password' => 'motdepasse-admin',
        ])->assertRedirect();

        $admin = User::where('nom', 'Admin')->where('role', 'admin')->firstOrFail();

        $this->assertTrue(Hash::check('motdepasse-admin', $admin->password));
        $this->assertNull($admin->temp_password);
        $this->assertFalse($admin->must_change_password);
    }
}
