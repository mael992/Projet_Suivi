<?php

namespace App\Http\Controllers;

use App\Models\DialogueQuestion;
use App\Models\DialogueReponse;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

/**
 * Boîte de dialogue : espace d'entraide entre mairies, une rubrique
 * par application. Les questions/réponses reprennent l'utilisateur
 * connecté (aucun champ « user » à saisir).
 */
class DialogueController extends Controller
{
    public function index()
    {
        $questions = DialogueQuestion::with(['auteur.mairie', 'reponses.auteur.mairie'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('section');

        return view('dialogue.index', compact('questions'));
    }

    public function storeQuestion(Request $request)
    {
        $data = $request->validate([
            'section' => 'required|in:' . implode(',', array_keys(DialogueQuestion::SECTIONS)),
            'texte'   => 'required|string|max:3000',
        ]);

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
