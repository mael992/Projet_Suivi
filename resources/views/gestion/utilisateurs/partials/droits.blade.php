@php
    use App\Support\Referentiel;

    /** @var \App\Models\User|null $user */
    $droitActuel = old('droit', isset($user) ? $user->droitActuel() : '');
    $rangActuel  = $droitActuel !== '' ? Referentiel::rangDroit($droitActuel) : null;
@endphp

{{-- ── Fonction libre (fiche contact) — visible pour les Employés ── --}}
<div class="col-12 d-none" id="fonctionWrap">
    <label class="form-label fw-semibold">{{ __('Fonction (fiche contact)') }}</label>
    <input type="text" name="fonction" value="{{ old('fonction', $user->fonction ?? '') }}"
           class="form-control" maxlength="150"
           placeholder="{{ __('ex : Agent d\'accueil, Policier municipal…') }}">
    <small class="text-muted">{{ __('Rôle affiché sur la Fiche Contact quand la personne n\'est ni Maire, ni Directeur de Cabinet, ni DGS.') }}</small>
</div>

{{-- ── Droits d'application (du plus fort au plus faible) ── --}}
<div class="col-12">
    <label class="form-label fw-semibold mb-1">{{ __('Droits d\'application') }}</label>
    <p class="text-muted mb-2" style="font-size:12px;">
        {{ __('Du plus élevé (gauche) au plus faible (droite) : cocher un droit donne automatiquement tous les droits situés à sa droite.') }}
    </p>
    <input type="hidden" name="droit" id="droitInput" value="{{ $droitActuel }}">
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-1 text-center align-middle" style="font-size:12px;">
            <thead class="table-dark">
                <tr>
                    @foreach(Referentiel::DROITS as $cle => $label)
                        <th style="font-weight:600;">{{ __($label) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach(array_keys(Referentiel::DROITS) as $i => $cle)
                        <td>
                            <input type="checkbox" class="form-check-input droit-case" data-rang="{{ $i }}" data-cle="{{ $cle }}"
                                   @checked($rangActuel !== null && $i >= $rangActuel)>
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
    <small class="text-muted">{{ __('Si aucune case n\'est cochée, le droit par défaut du statut est appliqué.') }}</small>
</div>

<script>
(function () {
    const cases      = Array.from(document.querySelectorAll('.droit-case'));
    const droitInput = document.getElementById('droitInput');
    const gradeSel   = document.querySelector('select[name=grade]');

    // Droit par défaut de chaque statut (même logique que Referentiel::droitDefaut)
    const DEFAUTS = { 1: 'gestion_utilisateurs', 2: 'contacts_modification', 3: 'contacts_modification', 4: '' };

    function appliquerCascade(rang, coche) {
        cases.forEach(c => {
            const r = parseInt(c.dataset.rang, 10);
            if (coche  && r >= rang) c.checked = true;   // tout ce qui est plus faible est inclus
            if (!coche && r <= rang) c.checked = false;  // décocher retire aussi les droits plus forts
        });
        majDroit();
    }

    function majDroit() {
        const premier = cases.find(c => c.checked);
        droitInput.value = premier ? premier.dataset.cle : '';
    }

    function cocherDepuisCle(cle) {
        const cible = cases.find(c => c.dataset.cle === cle);
        cases.forEach(c => c.checked = false);
        if (cible) appliquerCascade(parseInt(cible.dataset.rang, 10), true);
        else majDroit();
    }

    cases.forEach(c => c.addEventListener('change', () =>
        appliquerCascade(parseInt(c.dataset.rang, 10), c.checked)));

    function majFonction() {
        const grade = parseInt(gradeSel?.value || '0', 10);
        document.getElementById('fonctionWrap').classList.toggle('d-none', grade !== 4);
    }

    gradeSel?.addEventListener('change', () => {
        majFonction();
        cocherDepuisCle(DEFAUTS[parseInt(gradeSel.value || '0', 10)] || '');
    });

    majFonction();
})();
</script>
