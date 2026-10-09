<?php

namespace Tests\Feature;

use App\Models\Mairie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menu principal : liens de contact selon que l'on est connecté ou non.
 */
class MenuNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_visiteur_voit_seulement_contacter_votre_mairie(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('contact.mairie'), false)
            ->assertDontSee('Contacter le Support technique');
    }

    public function test_agent_connecte_voit_mairie_entre_nouveautes_et_support(): void
    {
        $mairie = Mairie::create([
            'nom'                 => 'Mairie Test',
            'code_postal'         => '00000',
            'email'               => 'test@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);
        $agent = User::factory()->create([
            'mairie_id'        => $mairie->id,
            'grade'            => \App\Support\Referentiel::GRADE_EMPLOYE,
            'cgu_acceptees_at' => now(),
        ]);

        $this->actingAs($agent)->get('/')
            ->assertOk()
            ->assertSeeInOrder([
                route('nouveautes'),
                route('contact.mairie'),
                'Contacter votre Mairie',
                route('contact'),
                'Contacter le Support technique',
            ], false);

        // La page de la mairie reste ouverte à un agent connecté
        $this->actingAs($agent)->get(route('contact.mairie'))->assertOk();
    }
}
