<?php

namespace Tests\Feature;

use App\Models\Mairie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AideTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateur(): User
    {
        $mairie = Mairie::create([
            'nom'                 => 'Mairie Test',
            'code_postal'         => '00000',
            'email'               => 'test@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);

        return User::factory()->create(['mairie_id' => $mairie->id]);
    }

    public function test_guide_accessible_et_contient_les_sections(): void
    {
        $this->actingAs($this->utilisateur())
            ->get('/besoin-aide')
            ->assertOk()
            ->assertSee('Besoin d')
            ->assertSee('Tableau des suivis (tâches)', false)
            ->assertSee('Centre de Messagerie', false)
            ->assertSee('E-mails que vous pouvez recevoir', false);
    }

    public function test_guide_telechargeable_en_pdf(): void
    {
        $reponse = $this->actingAs($this->utilisateur())->get('/besoin-aide/pdf');

        $reponse->assertOk();
        $this->assertSame('application/pdf', $reponse->headers->get('Content-Type'));
        $this->assertStringContainsString('MGDS_Besoin_d_aide_', $reponse->headers->get('Content-Disposition'));
    }

    public function test_guide_reserve_aux_connectes(): void
    {
        $this->get('/besoin-aide')->assertRedirect('/login');
    }
}
