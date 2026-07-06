<?php

namespace Tests\Feature\Auth;

use App\Models\Mairie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function mairie(array $attributes = []): Mairie
    {
        return Mairie::create(array_merge([
            'nom'                 => 'Mairie Test',
            'email'               => 'test@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ], $attributes));
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_with_username_and_mairie(): void
    {
        $mairie = $this->mairie();
        $user   = User::factory()->create(['mairie_id' => $mairie->id]);

        $response = $this->post('/login', [
            'mairie_id' => $mairie->id,
            'username'  => $user->username,
            'password'  => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('apps', absolute: false));
    }

    public function test_users_can_not_authenticate_without_mairie(): void
    {
        $mairie = $this->mairie();
        $user   = User::factory()->create(['mairie_id' => $mairie->id]);

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_wrong_mairie(): void
    {
        $mairie = $this->mairie();
        $autre  = $this->mairie(['nom' => 'Autre Mairie', 'email' => 'autre@mairie.fr']);
        $user   = User::factory()->create(['mairie_id' => $mairie->id]);

        $this->post('/login', [
            'mairie_id' => $autre->id,
            'username'  => $user->username,
            'password'  => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_admins_can_authenticate_without_mairie(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post('/login', [
            'username' => $admin->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('apps', absolute: false));
    }

    public function test_users_can_not_authenticate_when_abonnement_expired(): void
    {
        $mairie = $this->mairie(['date_fin_abonnement' => now()->subDay()->toDateString()]);
        $user   = User::factory()->create(['mairie_id' => $mairie->id]);

        $this->post('/login', [
            'mairie_id' => $mairie->id,
            'username'  => $user->username,
            'password'  => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $mairie = $this->mairie();
        $user   = User::factory()->create(['mairie_id' => $mairie->id]);

        $this->post('/login', [
            'mairie_id' => $mairie->id,
            'username'  => $user->username,
            'password'  => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
