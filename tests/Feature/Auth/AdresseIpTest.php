<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Audit de sécurité : l'IP du visiteur ne doit plus pouvoir être falsifiée
 * via X-Forwarded-For. Sinon la limite de 5 essais de connexion se contourne
 * en changeant d'IP à chaque tentative (force brute illimitée).
 */
class AdresseIpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test-ip', fn () => request()->ip());
    }

    public function test_x_forwarded_for_n_est_plus_pris_en_compte(): void
    {
        $this->get('/_test-ip', ['X-Forwarded-For' => '203.0.113.7'])
            ->assertSee('127.0.0.1')
            ->assertDontSee('203.0.113.7');
    }

    public function test_l_ip_transmise_par_cloudflare_est_retenue(): void
    {
        $this->get('/_test-ip', ['CF-Connecting-IP' => '198.51.100.23'])
            ->assertSee('198.51.100.23');
    }

    public function test_une_ip_cloudflare_invalide_est_ignoree(): void
    {
        $this->get('/_test-ip', ['CF-Connecting-IP' => 'pas-une-ip'])
            ->assertSee('127.0.0.1');
    }

    public function test_changer_x_forwarded_for_ne_contourne_plus_la_limite_de_connexion(): void
    {
        $user = User::factory()->create();

        // 5 échecs, chacun annoncé depuis une IP différente
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/login', ['username' => $user->username, 'password' => 'mauvais'],
                ['X-Forwarded-For' => "203.0.113.{$i}"]);
        }

        // 6e essai avec le BON mot de passe et une nouvelle IP falsifiée :
        // le compteur n'a pas été réinitialisé, la connexion reste bloquée
        $this->post('/login', ['username' => $user->username, 'password' => 'password'],
            ['X-Forwarded-For' => '203.0.113.99'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }
}
