<?php

namespace Tests\Feature;

use App\Mail\RappelCalendrier;
use App\Models\Mairie;
use App\Models\Note;
use App\Models\Rappel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PenseBeteTest extends TestCase
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

        $this->user = User::factory()->create([
            'mairie_id' => $mairie->id,
            'email'     => 'user@mairie.fr',
        ]);
    }

    public function test_page_pense_bete_accessible(): void
    {
        $this->actingAs($this->user)->get('/pense-bete')->assertOk();
    }

    public function test_creation_et_suppression_de_rappel(): void
    {
        $this->actingAs($this->user)->post('/pense-bete/rappels', [
            'date_rappel' => now()->addDays(3)->toDateString(),
            'texte'       => 'Préparer la réunion du conseil',
        ])->assertRedirect();

        $rappel = Rappel::first();
        $this->assertSame($this->user->id, $rappel->user_id);

        // Un autre utilisateur ne peut pas le supprimer
        $autre = User::factory()->create(['mairie_id' => $this->user->mairie_id]);
        $this->actingAs($autre)->delete("/pense-bete/rappels/{$rappel->id}")->assertForbidden();

        $this->actingAs($this->user)->delete("/pense-bete/rappels/{$rappel->id}")->assertRedirect();
        $this->assertDatabaseMissing('rappels', ['id' => $rappel->id]);
    }

    public function test_email_de_rappel_envoye_le_jour_j(): void
    {
        Mail::fake();

        Rappel::create([
            'user_id'     => $this->user->id,
            'date_rappel' => now()->toDateString(),
            'texte'       => 'Aujourd\'hui !',
        ]);
        Rappel::create([
            'user_id'     => $this->user->id,
            'date_rappel' => now()->addWeek()->toDateString(),
            'texte'       => 'Plus tard',
        ]);

        $this->artisan('mgds:envoyer-rappels')->assertSuccessful();

        Mail::assertSent(RappelCalendrier::class, 1);
        $this->assertSame(1, Rappel::where('envoye', true)->count());
    }

    public function test_note_notifiable_par_email_le_jour_j(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        // Note à rappeler aujourd'hui
        Note::create([
            'user_id'           => $this->user->id,
            'titre'             => 'Rappel important',
            'notifier'          => true,
            'date_notification' => now()->toDateString(),
        ]);
        // Note sans rappel
        Note::create(['user_id' => $this->user->id, 'titre' => 'Note simple']);

        $this->artisan('mgds:envoyer-rappels')->assertSuccessful();

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\NoteRappel::class, 1);
        $this->assertSame(1, Note::where('notifiee', true)->count());
    }

    public function test_notes_crud_avec_titre_obligatoire(): void
    {
        $this->actingAs($this->user)->post('/pense-bete/notes', [
            'contenu' => 'Sans titre',
        ])->assertSessionHasErrors('titre');

        $this->actingAs($this->user)->post('/pense-bete/notes', [
            'titre'   => 'Idées budget',
            'dossier' => 'Réunions',
            'contenu' => 'Prévoir le budget 2027.',
        ])->assertRedirect();

        $note = Note::first();
        $this->assertSame('Réunions', $note->dossier);

        $this->actingAs($this->user)->put("/pense-bete/notes/{$note->id}", [
            'titre'   => 'Idées budget 2027',
            'contenu' => 'Mis à jour.',
        ])->assertRedirect();

        $this->assertSame('Idées budget 2027', $note->fresh()->titre);

        $this->actingAs($this->user)->delete("/pense-bete/notes/{$note->id}")->assertRedirect();
        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }
}
