<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\Referentiel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Absences des agents de la mairie : deux onglets (en cours / à venir, puis
 * l'historique avec les justificatifs). Le binôme désigné sur le compte
 * reprend automatiquement les tâches pendant la période.
 */
class AbsenceController extends Controller
{
    public function index()
    {
        $mairie = $this->mairie();

        $base = Absence::where('mairie_id', $mairie->id)->with('user');

        return view('gestion.absences.index', [
            'mairie'     => $mairie,
            'actuelles'  => (clone $base)->where(fn ($q) => $q->enCours()->orWhere(fn ($q2) => $q2->aVenir()))
                                ->orderBy('date_debut')->get(),
            'historique' => (clone $base)->terminees()->orderByDesc('date_fin')->get(),
            'agents'     => User::where('mairie_id', $mairie->id)->where('role', 'user')
                                ->orderBy('nom')->orderBy('prenom')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $mairie = $this->mairie();

        $data = $request->validate([
            'user_id'      => 'required|exists:users,id',
            'motif'        => 'required|string|in:' . implode(',', array_keys(Referentiel::MOTIFS_ABSENCE)),
            'date_debut'   => 'required|date',
            'date_fin'     => 'required|date|after_or_equal:date_debut',
            'justificatif' => 'nullable|file|max:8192|mimes:jpg,jpeg,png,webp,pdf,doc,docx',
        ]);

        $agent = User::findOrFail($data['user_id']);
        abort_unless($agent->mairie_id === $mairie->id, 403);

        $absence = Absence::create([
            'mairie_id'    => $mairie->id,
            'user_id'      => $agent->id,
            'motif'        => $data['motif'],
            'date_debut'   => $data['date_debut'],
            'date_fin'     => $data['date_fin'],
            'justificatif' => $request->file('justificatif')?->store('justificatifs', 'local'),
            'cree_par'     => auth()->id(),
        ]);

        ActivityLogger::log('GESTION', 'CREATE', "Absence enregistrée : {$agent->username} — {$absence->motifLabel()} ({$absence->periodeLabel()})");

        return redirect()->route('gestion.absences.index')
            ->with('success', __('Absence enregistrée.'));
    }

    public function destroy(Absence $absence)
    {
        $this->verifierMairie($absence);

        if ($absence->justificatif) {
            Storage::disk('local')->delete($absence->justificatif);
        }

        $agent = $absence->user;
        $absence->delete();

        ActivityLogger::log('GESTION', 'DELETE', "Absence supprimée : {$agent?->username}");

        return redirect()->route('gestion.absences.index')
            ->with('success', __('Absence supprimée.'));
    }

    /** Le justificatif est privé : il ne transite que par cette route. */
    public function justificatif(Absence $absence)
    {
        $this->verifierMairie($absence);
        abort_unless($absence->justificatif, 404);

        return Storage::disk('local')->download($absence->justificatif);
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function mairie()
    {
        $mairie = auth()->user()->mairie;
        abort_unless($mairie, 403);

        return $mairie;
    }

    private function verifierMairie(Absence $absence): void
    {
        abort_unless($absence->mairie_id === auth()->user()->mairie_id, 403);
    }
}
