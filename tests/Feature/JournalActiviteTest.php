<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le 1er octobre 2026, le journal du mois a été créé en root par une tâche
 * planifiée : le site ne pouvait plus y écrire et, l'échec n'étant pas
 * intercepté, plus personne ne pouvait se déconnecter. Un journal qui ne
 * s'écrit pas ne doit jamais bloquer l'action en cours.
 */
class JournalActiviteTest extends TestCase
{
    use RefreshDatabase;

    private string $fichier;

    protected function setUp(): void
    {
        parent::setUp();

        // Mois fictif : les vrais journaux ne sont jamais touchés
        $this->travelTo(now()->setDate(2099, 1, 15));
        $this->fichier = storage_path('logs/activity/activity-2099-01.log');

        if (! is_dir(dirname($this->fichier))) {
            mkdir(dirname($this->fichier), 0775, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->fichier)) {
            rmdir($this->fichier);
        } elseif (is_file($this->fichier)) {
            unlink($this->fichier);
        }

        parent::tearDown();
    }

    /** Un dossier à la place du fichier : l'écriture échoue, même en root. */
    private function bloquerLeJournal(): void
    {
        mkdir($this->fichier);
    }

    public function test_la_deconnexion_fonctionne_meme_si_le_journal_est_bloque(): void
    {
        $this->bloquerLeJournal();

        $response = $this->actingAs(User::factory()->create())->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_la_connexion_fonctionne_meme_si_le_journal_est_bloque(): void
    {
        $this->bloquerLeJournal();
        $admin = User::factory()->admin()->create();

        $response = $this->seConnecter([
            'username' => $admin->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('apps', absolute: false));
    }

    public function test_le_fichier_du_mois_devient_inscriptible_par_le_groupe(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Les droits Unix (groupe) ne s\'appliquent pas sous Windows.');
        }

        // Fichier sans droit d'écriture pour le groupe, comme celui créé en root
        touch($this->fichier);
        chmod($this->fichier, 0644);

        ActivityLogger::system('TEST', 'Écriture de contrôle');

        clearstatcache(true, $this->fichier);
        $this->assertSame(0020, fileperms($this->fichier) & 0020);
    }
}
