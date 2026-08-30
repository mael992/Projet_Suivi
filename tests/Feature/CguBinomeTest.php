<?php

namespace Tests\Feature;

use App\Models\Mairie;
use App\Models\Tache;
use App\Models\User;
use App\Support\Referentiel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CguBinomeTest extends TestCase
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

    public function test_cgu_obligatoires_avant_acces(): void
    {
        $user = User::factory()->create([
            'mairie_id'        => $this->mairie->id,
            'cgu_acceptees_at' => null,
        ]);

        // Toute page redirige vers les CGU tant qu'elles ne sont pas acceptées
        $this->actingAs($user)->get('/apps')->assertRedirect(route('cgu'));
        $this->actingAs($user)->get('/cgu')->assertOk();

        // Case non cochée → refus
        $this->actingAs($user)->post('/cgu', [])->assertSessionHasErrors('cgu');

        // Case cochée → accès rétabli
        $this->actingAs($user)->post('/cgu', ['cgu' => '1'])->assertRedirect(route('apps'));
        $this->assertNotNull($user->fresh()->cgu_acceptees_at);
        $this->actingAs($user->fresh())->get('/apps')->assertOk();
    }

    public function test_binome_reprend_les_taches_pendant_absence(): void
    {
        $titulaire = User::factory()->create([
            'mairie_id'        => $this->mairie->id,
            'service'          => 12,
            'grade'            => Referentiel::GRADE_EMPLOYE,
            'cgu_acceptees_at' => now(),
        ]);
        $binome = User::factory()->create([
            'mairie_id'        => $this->mairie->id,
            'service'          => 12,
            'grade'            => Referentiel::GRADE_EMPLOYE,
            'cgu_acceptees_at' => now(),
        ]);

        $tache = Tache::create([
            'mairie_id'       => $this->mairie->id,
            'reference'       => '12-1',
            'service'         => 12,
            'user_id'         => $titulaire->id,
            'prise_en_charge' => 'responsable',
            'statut'          => 'en_cours',
            'date_butoir'     => now()->addWeek()->toDateString(),
        ]);

        // Sans absence déclarée, le binôme ne voit rien
        $titulaire->update(['binome_id' => $binome->id]);
        $this->assertFalse(Tache::visiblesPar($binome->fresh())->whereKey($tache->id)->exists());

        // Absence en cours → le binôme voit la tâche et peut la clôturer
        $titulaire->update([
            'absent'    => true,
            'absent_du' => now()->subDay()->toDateString(),
            'absent_au' => now()->addWeek()->toDateString(),
        ]);

        $binome->refresh();
        $this->assertTrue(Tache::visiblesPar($binome)->whereKey($tache->id)->exists());
        $this->assertTrue($tache->peutEtreClotureePar($binome));

        $this->actingAs($binome)->post("/taches/{$tache->id}/cloturer", [
            'description_cloture' => 'Traité par le binôme pendant l\'absence.',
        ])->assertRedirect();

        $this->assertSame('fait', $tache->fresh()->statut);
    }
}
