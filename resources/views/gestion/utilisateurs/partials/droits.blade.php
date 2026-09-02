@php
    use App\Support\Referentiel;

    /** @var \App\Models\User|null $user */
    // Cases réellement cochées ; les droits impliqués sont recalculés en JS
    $droitsCoches = old('droits', isset($user) ? $user->droitsCoches() : []);

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

{{-- ── Droits d'application (une case par application) ── --}}
<div class="col-12">
    <label class="form-label fw-semibold mb-1">{{ __('Droits d\'application') }}</label>
    <p class="text-muted mb-2" style="font-size:12px;">
        {{ __('Chaque application se coche indépendamment. Deux exceptions : « Gestion des utilisateurs » donne accès à tout, et « modification » donne la « lecture » de la même application.') }}
    </p>
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
                    @foreach(array_keys(Referentiel::DROITS) as $cle)
                        <td>
                            <input type="checkbox" class="form-check-input droit-case" name="droits[]"
                                   value="{{ $cle }}" data-cle="{{ $cle }}"
                                   @checked(in_array($cle, $droitsCoches, true))>
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
    <small class="text-muted">{{ __('Une case grisée est accordée automatiquement par une autre : décochez celle qui la donne pour la retirer. Aucune case cochée = aucun accès aux applications.') }}</small>
</div>

{{-- ── Binôme (l'absence se déclare désormais sur sa propre page) ── --}}
<div class="col-12">
    <label class="form-label fw-semibold mb-1">🤝 {{ __('Binôme') }}</label>
    <p class="text-muted mb-2" style="font-size:12px;">
        {{ __('Pendant une absence (congés, arrêt…), le binôme voit et peut traiter les tâches de cette personne.') }}
        @if(\Illuminate\Support\Facades\Route::has('gestion.absences.index'))
            <a href="{{ route('gestion.absences.index') }}">{{ __('Déclarer une absence →') }}</a>
        @endif
    </p>
    <div class="border rounded p-2">
        <label class="form-label mb-1" style="font-size:12px;">{{ __('Binôme (remplaçant)') }}</label>
        <select name="binome_id" class="form-select form-select-sm">
            <option value="">— {{ __('Aucun') }} —</option>
            @foreach($binomesPossibles ?? [] as $b)
                <option value="{{ $b->id }}" @selected(old('binome_id', $user->binome_id ?? null) == $b->id)>
                    {{ $b->username }} ({{ $b->service_label }})
                </option>
            @endforeach
        </select>
    </div>
</div>

{{-- ── Droit communication extérieur (messages « Contacter votre Mairie ») ── --}}
@php
    $catsOld = old('communication', isset($user) ? $user->categoriesCommunication() : []);
@endphp
<div class="col-12">
    <label class="form-label fw-semibold mb-1">📨 {{ __('Droit communication extérieur') }}</label>
    <p class="text-muted mb-2" style="font-size:12px;">
        {{ __('L\'habitant ne choisit plus de service : toutes les demandes arrivent au même endroit, et la mairie les oriente ensuite par transfert.') }}
    </p>
    <div class="border rounded p-2">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="communication[]" value="inconnu" id="com_inconnu"
                   @checked(in_array('inconnu', $catsOld, true))>
            <label class="form-check-label fw-semibold" for="com_inconnu" style="font-size:13px;">
                📥 {{ __('Réceptionner les messages extérieurs') }}
            </label>
            <div class="text-muted" style="font-size:11px;">
                {{ __('Cette personne reçoit les demandes envoyées via « Contacter votre Mairie » et peut les transférer au bon service.') }}
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const cases      = Array.from(document.querySelectorAll('.droit-case'));
    const serviceSel = document.querySelector('select[name=service]');
    const gradeSel   = document.querySelector('select[name=grade]');

    const GRADE_LABELS       = @json(Referentiel::GRADES);
    const GRADES_PAR_SERVICE = @json($gradesParService);
    const GRADE_EMPLOYE      = {{ Referentiel::GRADE_EMPLOYE }};

    // Droits accordés automatiquement par un droit coché (même liste qu'en PHP)
    const IMPLIQUES = @json(Referentiel::DROITS_IMPLIQUES);

    // Droits par défaut de chaque statut (direction = accès complet, employé = rien)
    const DEFAUTS = { 1: ['gestion_utilisateurs'], 2: ['gestion_utilisateurs'], 3: ['gestion_utilisateurs'], 4: [] };

    const gradeInitial = gradeSel ? gradeSel.value : '';

    /**
     * Une case accordée par une autre est cochée et verrouillée : on la
     * retire en décochant celle qui la donne, pas directement.
     * Les cases verrouillées ne sont pas envoyées — le serveur réapplique
     * les mêmes implications.
     */
    // Cases explicitement choisies ; les cases accordées automatiquement
    // n'y figurent pas et ne sont pas envoyées (le serveur les recalcule).
    const choisis = new Set(cases.filter(c => c.checked).map(c => c.dataset.cle));

    function rendre() {
        const forces = new Set();
        choisis.forEach(cle => (IMPLIQUES[cle] || []).forEach(x => forces.add(x)));
        // Un droit devenu implicite n'a plus besoin d'être coché explicitement
        forces.forEach(cle => choisis.delete(cle));

        cases.forEach(c => {
            const force = forces.has(c.dataset.cle);
            c.disabled  = force;
            c.checked   = force || choisis.has(c.dataset.cle);
            c.closest('td')?.classList.toggle('table-secondary', force);
        });
    }

    function cocherDroits(cles) {
        choisis.clear();
        cles.forEach(cle => choisis.add(cle));
        rendre();
    }

    cases.forEach(c => c.addEventListener('change', () => {
        if (c.checked) choisis.add(c.dataset.cle);
        else           choisis.delete(c.dataset.cle);
        rendre();
    }));

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
            cocherDroits(DEFAUTS[parseInt(gradeSel?.value || '0', 10)] || []);
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
    rendre();         // verrouille les cases accordées automatiquement
    initialise = true; // à partir d'ici, changer de statut applique les défauts
})();
</script>
