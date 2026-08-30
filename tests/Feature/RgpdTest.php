<?php

namespace Tests\Feature;

use App\Models\Mairie;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RgpdTest extends TestCase
{
    use RefreshDatabase;

    private Mairie $mairie;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mairie = Mairie::create([
            'nom'                 => 'Mairie Test',
            'code_postal'         => '00000',
            'email'               => 'test@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);

        $agent = User::factory()->create(['mairie_id' => $this->mairie->id, 'service' => 12]);

        Tache::create([
            'mairie_id'   => $this->mairie->id,
            'reference'   => '12-1',
            'service'     => 12,
            'user_id'     => $agent->id,
            'statut'      => 'ouvert',
            'date_butoir' => now()->addWeek()->toDateString(),
        ]);

        $this->admin = User::factory()->admin()->create();
    }

    public function test_page_rgpd_reservee_aux_admins(): void
    {
        $this->actingAs($this->admin)->get('/admin/donnees')->assertOk();

        $agent = User::factory()->create(['mairie_id' => $this->mairie->id]);
        $this->actingAs($agent)->get('/admin/donnees')->assertForbidden();
    }

    public function test_export_zip_des_donnees(): void
    {
        $reponse = $this->actingAs($this->admin)->get("/admin/donnees/{$this->mairie->id}/export");

        $reponse->assertOk();
        // ZIP si l'extension PHP est disponible, sinon repli JSON
        $extension = class_exists(\ZipArchive::class) ? '.zip' : '.json';
        $this->assertStringContainsString($extension, $reponse->headers->get('Content-Disposition'));
    }

    public function test_destruction_exige_le_nom_exact_et_fournit_une_attestation(): void
    {
        // Mauvais nom → rien n'est supprimé
        $this->actingAs($this->admin)
            ->post("/admin/donnees/{$this->mairie->id}/detruire", ['confirmation' => 'Mairie Tes'])
            ->assertSessionHasErrors('confirmation');

        $this->assertDatabaseHas('mairies', ['id' => $this->mairie->id]);

        // Nom exact → destruction + attestation PDF
        $reponse = $this->actingAs($this->admin)
            ->post("/admin/donnees/{$this->mairie->id}/detruire", ['confirmation' => 'Mairie Test']);

        $reponse->assertOk();
        $this->assertSame('application/pdf', $reponse->headers->get('Content-Type'));

        $this->assertDatabaseMissing('mairies', ['id' => $this->mairie->id]);
        $this->assertDatabaseMissing('taches', ['mairie_id' => $this->mairie->id]);
        $this->assertDatabaseMissing('users', ['mairie_id' => $this->mairie->id]);
    }
}
