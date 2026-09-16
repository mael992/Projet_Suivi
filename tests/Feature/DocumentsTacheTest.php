<?php

namespace Tests\Feature;

use App\Models\Mairie;
use App\Models\Tache;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Documents joints aux tâches (demande de la DGS) : PDF, Word, tableurs…
 * à la création comme en réponse, stockés hors du disque public parce que
 * certaines tâches sont confidentielles.
 */
class DocumentsTacheTest extends TestCase
{
    use RefreshDatabase;

    private Mairie $mairie;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->mairie = Mairie::create([
            'nom'                 => 'Mairie Test',
            'email'               => 'test@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);
    }

    private function dgs(): User
    {
        return User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 2,
            'grade'     => Referentiel::GRADE_DGS,
            'droits'    => ['gestion_utilisateurs'],
        ]);
    }

    private function agent(): User
    {
        return User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 12,
            'grade'     => Referentiel::GRADE_EMPLOYE,
        ]);
    }

    private function creerAvecDocuments(User $createur, User $responsable, array $fichiers, array $extra = []): Tache
    {
        $this->actingAs($createur)->post('/taches', array_merge([
            'service'     => 12,
            'user_id'     => $responsable->id,
            'date_butoir' => now()->addWeek()->toDateString(),
            'fichiers'    => $fichiers,
        ], $extra))->assertRedirect();

        return Tache::latest('id')->firstOrFail();
    }

    public function test_on_joint_des_pdf_et_des_word_a_la_creation(): void
    {
        $tache = $this->creerAvecDocuments($this->dgs(), $this->agent(), [
            UploadedFile::fake()->create('cahier-des-charges.pdf', 200, 'application/pdf'),
            UploadedFile::fake()->create('devis.docx', 80, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ]);

        $this->assertCount(2, $tache->fichiers);
        $this->assertSame('cahier-des-charges.pdf', $tache->fichiers[0]['nom']);
        Storage::disk('local')->assertExists($tache->fichiers[0]['chemin']);
        Storage::disk('local')->assertExists($tache->fichiers[1]['chemin']);
    }

    public function test_un_format_non_accepte_est_refuse(): void
    {
        $this->actingAs($this->dgs())->post('/taches', [
            'service'     => 12,
            'user_id'     => $this->agent()->id,
            'date_butoir' => now()->addWeek()->toDateString(),
            'fichiers'    => [UploadedFile::fake()->create('script.exe', 10)],
        ])->assertSessionHasErrors('fichiers.0');

        $this->assertSame(0, Tache::count());
    }

    public function test_pas_plus_de_cinq_documents(): void
    {
        $six = array_map(fn ($i) => UploadedFile::fake()->create("doc{$i}.pdf", 10, 'application/pdf'), range(1, 6));

        $this->actingAs($this->dgs())->post('/taches', [
            'service'     => 12,
            'user_id'     => $this->agent()->id,
            'date_butoir' => now()->addWeek()->toDateString(),
            'fichiers'    => $six,
        ])->assertSessionHasErrors('fichiers');
    }

    /** Au-delà de 20 Mo cumulés, l'envoi dépasserait la limite du serveur. */
    public function test_la_taille_totale_est_plafonnee(): void
    {
        $lourds = array_map(
            fn ($i) => UploadedFile::fake()->create("plan{$i}.pdf", 9000, 'application/pdf'),
            range(1, 3)
        );

        $this->actingAs($this->dgs())->post('/taches', [
            'service'     => 12,
            'user_id'     => $this->agent()->id,
            'date_butoir' => now()->addWeek()->toDateString(),
            'fichiers'    => $lourds,          // 3 × 9 Mo = 27 Mo
        ])->assertSessionHasErrors('fichiers');

        $this->assertSame(0, Tache::count());
    }

    public function test_on_repond_avec_des_documents_a_la_cloture(): void
    {
        $responsable = $this->agent();
        $tache       = $this->creerAvecDocuments($this->dgs(), $responsable, []);
        $tache->update(['prise_en_charge' => 'responsable', 'statut' => 'en_cours']);

        $this->actingAs($responsable)->post("/taches/{$tache->id}/cloturer", [
            'description_cloture' => 'Travaux réalisés, compte rendu joint.',
            'fichiers_cloture'    => [UploadedFile::fake()->create('compte-rendu.pdf', 120, 'application/pdf')],
        ])->assertRedirect();

        $tache->refresh();
        $this->assertSame('fait', $tache->statut);
        $this->assertCount(1, $tache->fichiers_cloture);
        Storage::disk('local')->assertExists($tache->fichiers_cloture[0]['chemin']);
    }

    public function test_le_document_se_telecharge_depuis_la_tache(): void
    {
        $dgs   = $this->dgs();
        $tache = $this->creerAvecDocuments($dgs, $this->agent(), [
            UploadedFile::fake()->create('plan.pdf', 50, 'application/pdf'),
        ]);

        $this->actingAs($dgs)->get(route('taches.show', $tache))
            ->assertOk()
            ->assertSee('plan.pdf');

        $this->actingAs($dgs)
            ->get(route('taches.document', ['tache' => $tache->id, 'liste' => 'fichiers', 'index' => 0]))
            ->assertOk()
            ->assertDownload('plan.pdf');
    }

    /** Le document suit la confidentialité de la tâche. */
    public function test_le_document_d_une_tache_confidentielle_reste_prive(): void
    {
        $dgs     = $this->dgs();
        $tache   = $this->creerAvecDocuments($dgs, $this->agent(), [
            UploadedFile::fake()->create('rapport-sensible.pdf', 50, 'application/pdf'),
        ], ['confidentiel' => '1']);

        // Même le Maire n'y accède pas s'il n'a pas été désigné
        $maire = User::factory()->create([
            'mairie_id' => $this->mairie->id,
            'service'   => 0,
            'grade'     => Referentiel::GRADE_MAIRE,
        ]);

        $this->actingAs($maire)
            ->get(route('taches.document', ['tache' => $tache->id, 'liste' => 'fichiers', 'index' => 0]))
            ->assertForbidden();
    }

    public function test_modifier_retire_et_ajoute_des_documents(): void
    {
        $dgs   = $this->dgs();
        $resp  = $this->agent();
        $tache = $this->creerAvecDocuments($dgs, $resp, [
            UploadedFile::fake()->create('ancien.pdf', 10, 'application/pdf'),
        ]);
        $ancien = $tache->fichiers[0]['chemin'];

        $this->actingAs($dgs)->put(route('taches.update', $tache), [
            'statut'           => 'ouvert',
            'user_id'          => $resp->id,
            'date_butoir'      => now()->addWeek()->toDateString(),
            'retirer_fichiers' => [$ancien],
            'fichiers'         => [UploadedFile::fake()->create('nouveau.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')],
        ])->assertRedirect();

        $tache->refresh();
        $this->assertCount(1, $tache->fichiers);
        $this->assertSame('nouveau.xlsx', $tache->fichiers[0]['nom']);
        Storage::disk('local')->assertMissing($ancien);
    }

    public function test_supprimer_la_tache_efface_ses_documents(): void
    {
        $dgs   = $this->dgs();
        $tache = $this->creerAvecDocuments($dgs, $this->agent(), [
            UploadedFile::fake()->create('a-effacer.pdf', 10, 'application/pdf'),
        ]);
        $chemin = $tache->fichiers[0]['chemin'];

        $this->actingAs($dgs)->delete(route('taches.destroy', $tache))->assertRedirect();

        Storage::disk('local')->assertMissing($chemin);
    }
}
