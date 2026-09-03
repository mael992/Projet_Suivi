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

    public function test_la_destruction_efface_aussi_les_justificatifs_d_absence(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $agent = \App\Models\User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'grade'     => \App\Support\Referentiel::GRADE_EMPLOYE,
        ]);
        $chemin = \Illuminate\Http\UploadedFile::fake()
            ->create('arret.pdf', 10, 'application/pdf')
            ->store('justificatifs', 'local');

        \App\Models\Absence::create([
            'mairie_id'    => $this->mairie->id,
            'user_id'      => $agent->id,
            'motif'        => 'arret_maladie',
            'date_debut'   => now()->toDateString(),
            'date_fin'     => now()->addDay()->toDateString(),
            'justificatif' => $chemin,
        ]);

        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($chemin);

        $this->actingAs(\App\Models\User::factory()->admin()->create())
            ->post("/admin/donnees/{$this->mairie->id}/detruire", ['confirmation' => $this->mairie->nom])
            ->assertOk();

        \Illuminate\Support\Facades\Storage::disk('local')->assertMissing($chemin);
        $this->assertSame(0, \App\Models\Absence::count());
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
