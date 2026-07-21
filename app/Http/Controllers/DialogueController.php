<?php

namespace App\Http\Controllers;

use App\Models\DialogueQuestion;
use App\Models\DialogueReponse;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

/**
 * Boîte de dialogue : espace d'entraide entre mairies, une rubrique
 * par application. Les questions/réponses reprennent l'utilisateur
 * connecté (aucun champ « user » à saisir). Chaque utilisateur ne voit
 * que les rubriques des applications auxquelles il a accès.
 */
class DialogueController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $sections = DialogueQuestion::sectionsVisiblesPour($user);

        $questions = DialogueQuestion::with(['auteur.mairie', 'reponses.auteur.mairie'])
            ->withCount('reponses')
            ->whereIn('section', $sections)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('section');

        // Compteurs par section : total (gris) et non répondus (bulle bleue)
        $compteurs = [];
        foreach ($sections as $cle) {
            $liste = $questions->get($cle, collect());
            $compteurs[$cle] = [
                'total'       => $liste->count(),
                'non_repondu' => $liste->where('fermee_at', null)->where('reponses_count', 0)->count(),
            ];
        }

        return view('dialogue.index', compact('questions', 'sections', 'compteurs'));
    }

    public function storeQuestion(Request $request)
    {
        $data = $request->validate([
            'section' => 'required|in:' . implode(',', array_keys(DialogueQuestion::SECTIONS)),
            'texte'   => 'required|string|max:3000',
        ]);

        abort_unless(in_array($data['section'], DialogueQuestion::sectionsVisiblesPour(auth()->user()), true), 403);

        DialogueQuestion::create([
            'user_id' => auth()->id(),
            'section' => $data['section'],
            'texte'   => $data['texte'],
        ]);

        ActivityLogger::log('DIALOGUE', 'CREATE', 'Question posée (' . DialogueQuestion::SECTIONS[$data['section']] . ')');

        return redirect()->route('dialogue.index', ['section' => $data['section']])
            ->with('success', 'Question publiée.');
    }

    public function storeReponse(Request $request, DialogueQuestion $question)
    {
        abort_if($question->estFermee(), 403, 'Cette question est clôturée.');
        abort_unless(in_array($question->section, DialogueQuestion::sectionsVisiblesPour(auth()->user()), true), 403);

        $data = $request->validate([
            'texte' => 'required|string|max:3000',
        ]);

        DialogueReponse::create([
            'dialogue_question_id' => $question->id,
            'user_id'              => auth()->id(),
            'texte'                => $data['texte'],
        ]);

        return redirect()->route('dialogue.index', ['section' => $question->section])
            ->with('success', 'Réponse publiée.');
    }

    /** L'auteur clôture sa question : elle est verrouillée définitivement. */
    public function cloturerQuestion(DialogueQuestion $question)
    {
        abort_unless($question->user_id === auth()->id(), 403);

        if (! $question->estFermee()) {
            $question->update(['fermee_at' => now()]);
            ActivityLogger::log('DIALOGUE', 'CLOTURE', 'Question clôturée par son auteur');
        }

        return redirect()->route('dialogue.index', ['section' => $question->section])
            ->with('success', 'Question clôturée.');
    }

    public function destroyQuestion(DialogueQuestion $question)
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $question->user_id === $user->id, 403);

        $section = $question->section;
        $question->delete();

        return redirect()->route('dialogue.index', ['section' => $section])
            ->with('success', 'Question supprimée.');
    }
}
