@php
    use App\Support\Referentiel;

    /** @var \App\Models\User|null $user */
    $droitActuel = old('droit', isset($user) ? $user->droitActuel() : '');
    $rangActuel  = $droitActuel !== '' ? Referentiel::rangDroit($droitActuel) : null;

    // Grades autorisés par service (couplage du formulaire)
    $gradesParService = [];
    foreach (array_keys(Referentiel::SERVICES) as $s) {
        $gradesParService[$s] = Referentiel::gradesAutorises($s);
    }
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
                        <th style="font-weight:600;">
                            <span style="font-size:18px;display:block;line-height:1.2;">{{ Referentiel::droitIcone($cle) }}</span>
                            {{ __($label) }}
                        </th>
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

{{-- ── Droit communication extérieur (messages « Contacter votre Mairie ») ── --}}
@php
    $catsUser = isset($user) ? $user->categoriesCommunication() : [];
    $catsOld  = old('communication', $catsUser);
@endphp
<div class="col-12">
    <label class="form-label fw-semibold mb-1">📨 {{ __('Droit communication extérieur') }}</label>
    <p class="text-muted mb-2" style="font-size:12px;">
        {{ __('Services dont cette personne reçoit les messages envoyés via « Contacter votre Mairie ». Si aucun agent ne reçoit un service, il n\'est pas proposé aux habitants.') }}
    </p>
    <div class="border rounded p-2">
        {{-- Visibilité globale : lire tous les messages sans être destinataire --}}
        <div class="form-check mb-2 pb-2 border-bottom">
            <input class="form-check-input" type="checkbox" name="voit_tous_messages" value="1" id="voitTousMessages"
                   @checked(old('voit_tous_messages', isset($user) ? $user->voitTousLesMessages() : false))>
            <label class="form-check-label fw-semibold" for="voitTousMessages" style="font-size:13px;">
                👁 {{ __('Visibilité sur tous les messages de la mairie') }}
            </label>
            <div class="text-muted" style="font-size:11px;">
                {{ __('Permet de lire et de traiter tous les messages, sans faire apparaître de service supplémentaire sur la page publique.') }}
            </div>
        </div>

        <div class="form-check mb-1">
            <input class="form-check-input" type="checkbox" name="communication[]" value="inconnu" id="com_inconnu"
                   @checked(in_array('inconnu', $catsOld, true))>
            <label class="form-check-label fw-semibold" for="com_inconnu" style="font-size:13px;">
                🤷 {{ __('Je ne sais pas') }} ({{ __('demandes générales') }})
            </label>
        </div>
        <div class="row g-1">
            @foreach(Referentiel::SERVICES as $num => $label)
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="communication[]" value="{{ $num }}" id="com_{{ $num }}"
                               @checked(in_array((string) $num, $catsOld, true))>
                        <label class="form-check-label" for="com_{{ $num }}" style="font-size:12px;">{{ $label }}</label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
(function () {
    const cases      = Array.from(document.querySelectorAll('.droit-case'));
    const droitInput = document.getElementById('droitInput');
    const serviceSel = document.querySelector('select[name=service]');
    const gradeSel   = document.querySelector('select[name=grade]');

    const GRADE_LABELS       = @json(Referentiel::GRADES);
    const GRADES_PAR_SERVICE = @json($gradesParService);
    const GRADE_EMPLOYE      = {{ Referentiel::GRADE_EMPLOYE }};

    // Droit par défaut de chaque statut (Maire/Dir Cabinet/DGS = tout coché, Employé = rien)
    const DEFAUTS = { 1: 'gestion_utilisateurs', 2: 'gestion_utilisateurs', 3: 'gestion_utilisateurs', 4: '' };

    const gradeInitial = gradeSel ? gradeSel.value : '';

    function appliquerCascade(rang, coche) {
        cases.forEach(c => {
            const r = parseInt(c.dataset.rang, 10);
            if (coche  && r >= rang) c.checked = true;
            if (!coche && r <= rang) c.checked = false;
        });
        majDroit();
    }

    function majDroit() {
        const premier = cases.find(c => c.checked);
        droitInput.value = premier ? premier.dataset.cle : '';
    }

    function cocherDepuisCle(cle) {
        cases.forEach(c => c.checked = false);
        const cible = cases.find(c => c.dataset.cle === cle);
        if (cible) appliquerCascade(parseInt(cible.dataset.rang, 10), true);
        else majDroit();
    }

    cases.forEach(c => c.addEventListener('change', () =>
        appliquerCascade(parseInt(c.dataset.rang, 10), c.checked)));

    // Statut visible = employé → champ Fonction affiché
    function majFonction() {
        const grade = parseInt(gradeSel?.value || '0', 10);
        document.getElementById('fonctionWrap').classList.toggle('d-none', grade !== GRADE_EMPLOYE);
    }

    // Au premier rendu, on ne touche pas aux cases : elles reflètent déjà
    // les droits enregistrés. Les défauts ne s'appliquent qu'ensuite,
    // quand l'utilisateur change réellement de statut.
    let initialise = false;

    function onGradeChange() {
        majFonction();
        if (initialise) {
            cocherDepuisCle(DEFAUTS[parseInt(gradeSel?.value || '0', 10)] || '');
        }
    }

    // Le Service pilote la liste des Statuts possibles
    function reconstruireStatuts(preserver) {
        if (!serviceSel || !gradeSel) return;
        const svc       = parseInt(serviceSel.value || '0', 10);
        const autorises = GRADES_PAR_SERVICE[svc];
        const avant     = gradeSel.value;

        gradeSel.innerHTML = '';

        if (!autorises) {
            const o = document.createElement('option');
            o.value = ''; o.textContent = '— {{ __('Sélectionnez d\'abord un service') }} —';
            gradeSel.appendChild(o);
            onGradeChange();
            return;
        }

        autorises.forEach(g => {
            const o = document.createElement('option');
            o.value = g; o.textContent = g + '. ' + GRADE_LABELS[g];
            gradeSel.appendChild(o);
        });

        if (preserver && autorises.includes(parseInt(avant, 10))) gradeSel.value = avant;
        onGradeChange();
    }

    gradeSel?.addEventListener('change', onGradeChange);
    serviceSel?.addEventListener('change', () => reconstruireStatuts(false));

    // Init : conserver le statut existant (édition) s'il reste autorisé
    if (serviceSel && gradeSel) {
        reconstruireStatuts(true);
        if (gradeInitial && GRADES_PAR_SERVICE[parseInt(serviceSel.value || '0', 10)]?.includes(parseInt(gradeInitial, 10))) {
            gradeSel.value = gradeInitial;
        }
    }
    majFonction();
    majDroit();       // le champ caché reflète les cases affichées
    initialise = true; // à partir d'ici, changer de statut applique les défauts
})();
</script>
