<?php

namespace App\Http\Controllers;

use App\Mail\SupportNouveauMessage;
use App\Models\SupportDemande;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Support technique MGDS (page Contact) : un assistant pose quelques
 * questions préformatées, puis transmet toutes les réponses à l'équipe MGDS
 * (les admins) quand la personne demande un vrai conseiller.
 *
 * Ouvert à tous : agent connecté (la demande est rattachée à son compte et
 * se suit dans Centre de messagerie / Message Support) ou personne sans
 * compte (problème avant connexion), qui suit sa demande par un lien secret.
 * La page Contact ne sert qu'à créer la demande.
 */
class SupportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('contact', [
            'choix'      => $user ? SupportDemande::CHOIX_CONNECTE : SupportDemande::CHOIX_PUBLIC,
            'precisions' => SupportDemande::QUESTIONS_PRECISION,
        ]);
    }

    public function store(Request $request)
    {
        $user  = $request->user();
        $choix = $user ? SupportDemande::CHOIX_CONNECTE : SupportDemande::CHOIX_PUBLIC;

        $data = $request->validate([
            'concerne'  => ['required', Rule::in(array_keys($choix))],
            'precision' => ['nullable', 'string', 'max:1000',
                Rule::requiredIf(fn () => array_key_exists($request->input('concerne'), SupportDemande::QUESTIONS_PRECISION))],
            'message'   => 'required|string|min:5|max:5000',
            // Personne sans compte : de quoi lui répondre
            'nom'       => [Rule::requiredIf(! $user), 'nullable', 'string', 'min:2', 'max:100'],
            'prenom'    => [Rule::requiredIf(! $user), 'nullable', 'string', 'min:2', 'max:100'],
            'email'     => [Rule::requiredIf(! $user), 'nullable', 'email', 'max:255'],
        ]);

        $precision = array_key_exists($data['concerne'], SupportDemande::QUESTIONS_PRECISION)
            ? $data['precision']
            : null;

        $demande = SupportDemande::create([
            // Référence définitive posée juste après, à partir de l'identifiant
            'reference'   => 'tmp-' . Str::random(12),
            'jeton'       => SupportDemande::genererJeton(),
            'user_id'     => $user?->id,
            'avec_compte' => $user !== null,
            'nom'         => $user ? null : $data['nom'],
            'prenom'      => $user ? null : $data['prenom'],
            'email'       => $user ? null : $data['email'],
            'concerne'    => $data['concerne'],
            'precision'   => $precision,
            'statut'      => SupportDemande::STATUT_RECEPTION,
        ]);
        $demande->update(['reference' => 'S-' . $demande->id]);

        // L'assistant récapitule les réponses déjà données, pour que le
        // conseiller ait tout sous les yeux
        $lignes = [
            'Réponses transmises par l\'assistant :',
            '• ' . ($user ? 'Compte MGDS : ' . $user->username : 'Sans compte MGDS'),
            '• Le problème concerne : ' . SupportDemande::libelleChoix($data['concerne']),
            $precision ? '• Précision : ' . $precision : null,
        ];
        $demande->ajouterMessage(SupportMessage::AUTEUR_ASSISTANT, implode("\n", array_filter($lignes)));
        $demande->ajouterMessage(SupportMessage::AUTEUR_DEMANDEUR, $data['message']);

        $this->notifierAdmins($demande);
        $this->notifierDemandeur($demande);

        return redirect($demande->lienSuivi())
            ->with('success', __('Votre demande a bien été transmise au support. Un conseiller vous répondra au plus vite.'));
    }

    /**
     * Conversation par le lien secret. Une demande ouverte depuis un compte
     * se suit dans le Centre de messagerie.
     */
    public function suivi(Request $request, string $jeton)
    {
        $demande = $this->demandeAccessible($request, $jeton);

        if ($demande->avec_compte) {
            return redirect($demande->lienMessagerie());
        }

        $demande->load('messages');

        return view('support.suivi', compact('demande'));
    }

    public function repondre(Request $request, string $jeton)
    {
        $demande = $this->demandeAccessible($request, $jeton);
        abort_if($demande->estCloture(), 403, 'Cette demande est clôturée.');

        $data = $request->validate([
            'corps' => 'required|string|min:2|max:5000',
        ]);

        $demande->ajouterMessage(SupportMessage::AUTEUR_DEMANDEUR, $data['corps']);
        $this->notifierAdmins($demande);

        return redirect($demande->lienSuivi())
            ->with('success', __('Votre message a bien été envoyé au support.'));
    }

    /** La personne n'a plus besoin d'aide. */
    public function cloturer(Request $request, string $jeton)
    {
        $demande = $this->demandeAccessible($request, $jeton);

        if (! $demande->estCloture()) {
            $demande->update(['statut' => SupportDemande::STATUT_CLOTURE, 'cloture_at' => now()]);
        }

        return redirect($demande->lienSuivi())
            ->with('success', __('Votre demande au support est clôturée.'));
    }

    // ── Helpers ──────────────────────────────────────────────────

    /**
     * Le lien secret suffit pour une personne sans compte. Une demande
     * ouverte depuis un compte ne s'affiche qu'à ce compte (et plus à
     * personne si le compte a été supprimé).
     */
    private function demandeAccessible(Request $request, string $jeton): SupportDemande
    {
        $demande = SupportDemande::where('jeton', $jeton)->firstOrFail();

        if ($demande->avec_compte && ($demande->user_id === null || $request->user()?->id !== $demande->user_id)) {
            abort(404);
        }

        return $demande;
    }

    /** Prévient l'équipe MGDS (tous les admins) d'un nouveau message. */
    private function notifierAdmins(SupportDemande $demande): void
    {
        $admins = User::where('role', 'admin')->whereNotNull('email')->get();

        foreach ($admins as $admin) {
            try {
                Mail::to($admin->email)->send(new SupportNouveauMessage($demande, pourAdmin: true));
            } catch (\Exception $e) {
                report($e);
            }
        }
    }

    /** Envoie à la personne le lien de suivi de sa demande. */
    private function notifierDemandeur(SupportDemande $demande): void
    {
        if (! $email = $demande->emailDemandeur()) {
            return;
        }

        try {
            Mail::to($email)->send(new SupportNouveauMessage($demande, pourAdmin: false, nouvelle: true));
        } catch (\Exception $e) {
            report($e);
        }
    }
}
