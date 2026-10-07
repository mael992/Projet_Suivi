<?php

namespace Tests\Feature;

use App\Http\Middleware\ProtectionFormulairePublic;
use App\Models\Mairie;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Protection anti-robots des formulaires publics : champ piège, délai
 * minimum avant l'envoi et limite d'envois par adresse IP.
 */
class AntiRobotTest extends TestCase
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

        // Un agent réceptionne : la mairie est joignable publiquement
        User::factory()->create([
            'mairie_id'     => $this->mairie->id,
            'grade'         => \App\Support\Referentiel::GRADE_EMPLOYE,
            'communication' => ['inconnu'],
        ]);
    }

    private function demande(array $extra = []): array
    {
        return array_merge([
            'mairie_id' => $this->mairie->id,
            'nom'       => 'Dupont', 'prenom' => 'Marie',
            'telephone' => '0612345678', 'email' => 'marie@example.fr',
            'sujet'     => 'Lampadaire', 'message' => 'Le lampadaire est en panne.',
        ], $extra);
    }

    public function test_les_formulaires_publics_contiennent_les_champs_anti_robots(): void
    {
        $this->get('/contacter-mairie')->assertOk()
            ->assertSee('name="' . ProtectionFormulairePublic::CHAMP_PIEGE . '"', false)
            ->assertSee('name="' . ProtectionFormulairePublic::CHAMP_HEURE . '"', false);
    }

    public function test_un_humain_envoie_sa_demande(): void
    {
        $this->post('/contacter-mairie', $this->jetonFormulaire() + $this->demande())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(1, Ticket::count());
    }

    public function test_champ_piege_rempli_refuse(): void
    {
        $this->post('/contacter-mairie', $this->jetonFormulaire() + $this->demande([
            ProtectionFormulairePublic::CHAMP_PIEGE => 'https://spam.example',
        ]))->assertSessionHasErrors('anti_robot');

        $this->assertSame(0, Ticket::count());
    }

    public function test_envoi_sans_jeton_ou_jeton_falsifie_refuse(): void
    {
        $this->post('/contacter-mairie', $this->demande())->assertSessionHasErrors('anti_robot');

        $this->post('/contacter-mairie', $this->demande([
            ProtectionFormulairePublic::CHAMP_HEURE => (string) now()->subHour()->timestamp,
        ]))->assertSessionHasErrors('anti_robot');

        $this->assertSame(0, Ticket::count());
    }

    public function test_envoi_trop_rapide_refuse(): void
    {
        $this->post('/contacter-mairie', $this->demande([
            ProtectionFormulairePublic::CHAMP_HEURE => encrypt(now()->timestamp),
        ]))->assertSessionHasErrors('anti_robot');

        $this->assertSame(0, Ticket::count());
    }

    public function test_trop_d_envois_depuis_la_meme_ip_refuses(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/contacter-mairie', $this->jetonFormulaire() + $this->demande(['sujet' => "Demande $i"]))
                ->assertSessionHasNoErrors();
        }

        $this->post('/contacter-mairie', $this->jetonFormulaire() + $this->demande(['sujet' => 'De trop']))
            ->assertSessionHasErrors('anti_robot');

        $this->assertSame(5, Ticket::count());
    }

    public function test_suivi_de_ticket_limite_par_ip(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->post('/mon-ticket', ['reference' => "X-$i", 'email' => 'a@example.fr'])
                ->assertSessionHasErrors('ticket');
        }

        $this->post('/mon-ticket', ['reference' => 'X-11', 'email' => 'a@example.fr'])
            ->assertSessionHasErrors('anti_robot');
    }
}
