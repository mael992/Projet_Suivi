<?php

namespace Tests\Feature;

use App\Models\DialogueQuestion;
use App\Models\Mairie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DialogueTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $mairie = Mairie::create([
            'nom'                 => 'Mairie Test',
            'email'               => 'test@mairie.fr',
            'date_fin_abonnement' => now()->addYear()->toDateString(),
        ]);

        $this->user = User::factory()->create(['mairie_id' => $mairie->id]);
    }

    public function test_page_dialogue_accessible(): void
    {
        $this->actingAs($this->user)->get('/dialogue')->assertOk();
    }

    public function test_question_et_reponse_reprennent_l_utilisateur_connecte(): void
    {
        $this->actingAs($this->user)->post('/dialogue/questions', [
            'section' => 'tableau-suivis',
            'texte'   => 'Comment ajouter une tâche ?',
        ])->assertRedirect();

        $question = DialogueQuestion::first();
        $this->assertSame($this->user->id, $question->user_id);

        $autre = User::factory()->create(['mairie_id' => $this->user->mairie_id]);
        $this->actingAs($autre)->post("/dialogue/questions/{$question->id}/reponses", [
            'texte' => 'Via l\'onglet Commerçants.',
        ])->assertRedirect();

        $this->assertSame($autre->id, $question->reponses()->first()->user_id);
    }

    public function test_auteur_peut_cloturer_sa_question(): void
    {
        $question = DialogueQuestion::create([
            'user_id' => $this->user->id,
            'section' => 'marche',
            'texte'   => 'Question test',
        ]);

        $autre = User::factory()->create(['mairie_id' => $this->user->mairie_id]);
        // Un autre utilisateur ne peut pas la clôturer
        $this->actingAs($autre)->post("/dialogue/questions/{$question->id}/cloturer")->assertForbidden();

        // L'auteur clôture ; ensuite plus aucune réponse possible
        $this->actingAs($this->user)->post("/dialogue/questions/{$question->id}/cloturer")->assertRedirect();
        $this->assertNotNull($question->fresh()->fermee_at);

        $this->actingAs($autre)->post("/dialogue/questions/{$question->id}/reponses", [
            'texte' => 'Trop tard',
        ])->assertForbidden();
    }

    public function test_sections_filtrees_par_droits(): void
    {
        // Employé sans droit marché : ne voit pas la section marché
        $employe = User::factory()->create([
            'mairie_id' => $this->user->mairie_id,
            'grade'     => \App\Support\Referentiel::GRADE_EMPLOYE,
        ]);
        $sections = DialogueQuestion::sectionsVisiblesPour($employe);
        $this->assertContains('tableau-suivis', $sections);
        $this->assertNotContains('marche', $sections);
        $this->assertNotContains('administration', $sections);

        // Il ne peut pas poster dans une section interdite
        $this->actingAs($employe)->post('/dialogue/questions', [
            'section' => 'marche',
            'texte'   => 'Interdit',
        ])->assertForbidden();
    }

    public function test_seul_l_auteur_ou_admin_supprime_une_question(): void
    {
        $question = DialogueQuestion::create([
            'user_id' => $this->user->id,
            'section' => 'marche',
            'texte'   => 'Question test',
        ]);

        $autre = User::factory()->create(['mairie_id' => $this->user->mairie_id]);
        $this->actingAs($autre)->delete("/dialogue/questions/{$question->id}")->assertForbidden();

        $this->actingAs($this->user)->delete("/dialogue/questions/{$question->id}")->assertRedirect();
        $this->assertDatabaseMissing('dialogue_questions', ['id' => $question->id]);
    }
}
