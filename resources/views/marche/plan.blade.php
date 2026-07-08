@extends('layouts.app')

@section('content')
@php
    $peutEditer  = auth()->user()->peutGererTaches();
    $mairieParam = request('mairie');
@endphp

<div class="container-fluid px-3 px-md-4 py-4">

    <h1 class="h3 mb-3">🛍️ Marché — {{ $mairie->nom }}</h1>

    @include('marche.partials.onglets')

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning mb-3">{{ session('warning') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    {{-- ── 1. Choisir son marché ── --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <label class="form-label mb-1" style="font-size:12px;">{{ __('Choisissez votre marché') }}</label>
            <form method="GET" action="{{ route('marche.plan') }}">
                @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                <select name="plan" class="form-select" onchange="this.form.submit()">
                    @forelse($plans as $p)
                        <option value="{{ $p->id }}" @selected($plan && $plan->id === $p->id)>
                            {{ $p->date->format('d/m/Y') }} — {{ $p->nom }}
                        </option>
                    @empty
                        <option value="">Aucun marché — créez-en un ci-dessous</option>
                    @endforelse
                </select>
            </form>

            @if($peutEditer)
            <div class="d-flex gap-2 flex-wrap mt-3">
                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#formCreerMarche">
                    {{ __('+ Créer un marché') }}
                </button>
                @if($plan)
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#formModifierMarche">
                    {{ __('Modifier les informations du marché') }}
                </button>
                <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#formFondPlan">
                    🖼 {{ __('Changer le fond de plan') }}
                </button>
                @endif
            </div>

            {{-- {{ __('+ Créer un marché') }} --}}
            <div class="collapse mt-3" id="formCreerMarche">
                <form method="POST" action="{{ route('marche.plans.store') }}" class="row g-2 align-items-end border rounded p-2 bg-light">
                    @csrf
                    @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                    <div class="col-5">
                        <label class="form-label mb-1" style="font-size:12px;">Nom du marché</label>
                        <input type="text" name="nom" class="form-control form-control-sm" placeholder="Marché hebdomadaire" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label mb-1" style="font-size:12px;">{{ __('Date') }}</label>
                        <input type="date" name="date" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-3">
                        <button class="btn btn-sm btn-primary w-100">Créer</button>
                    </div>
                </form>
            </div>

            @if($plan)
            {{-- {{ __('Modifier les informations du marché') }} --}}
            <div class="collapse mt-3" id="formModifierMarche">
                <div class="border rounded p-2 bg-light">
                    <form method="POST" action="{{ route('marche.plans.update', $plan) }}" class="row g-2 align-items-end">
                        @csrf @method('PUT')
                        @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                        <div class="col-5">
                            <label class="form-label mb-1" style="font-size:12px;">{{ __('Nom') }}</label>
                            <input type="text" name="nom" value="{{ $plan->nom }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-1" style="font-size:12px;">{{ __('Date') }}</label>
                            <input type="date" name="date" value="{{ $plan->date->format('Y-m-d') }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-3">
                            <button class="btn btn-sm btn-outline-primary w-100">{{ __('Enregistrer') }}</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('marche.plans.destroy', $plan) }}" class="mt-2 text-end"
                          onsubmit="return confirm('⚠️ Supprimer ce marché et tous ses placements ?')">
                        @csrf @method('DELETE')
                        @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                        <button class="btn btn-sm btn-outline-danger">🗑 Supprimer ce marché</button>
                    </form>
                </div>
            </div>

            {{-- {{ __('Changer le fond de plan') }} --}}
            <div class="collapse mt-3" id="formFondPlan">
                <form method="POST" action="{{ route('marche.plans.image', $plan) }}" enctype="multipart/form-data"
                      class="row g-2 align-items-end border rounded p-2 bg-light">
                    @csrf
                    @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                    <div class="col-9">
                        <label class="form-label mb-1" style="font-size:12px;">Image du plan de la place (PNG/JPG)</label>
                        <input type="file" name="image" class="form-control form-control-sm" accept="image/*" required>
                    </div>
                    <div class="col-3">
                        <button class="btn btn-sm btn-dark w-100">Mettre à jour</button>
                    </div>
                </form>
            </div>
            @endif
            @endif
        </div>
    </div>

    @if(!$plan)
        <div class="text-center text-muted py-5">
            🗺️ Créez votre premier marché (bouton « {{ __('+ Créer un marché') }} ») pour commencer à placer les exposants.
        </div>
    @else

        {{-- ── 2. Le plan interactif ── --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold">🗺️ {{ $plan->nom }} — {{ $plan->date->format('d/m/Y') }}</span>
                <div class="d-flex gap-2 flex-wrap">
                    @if($peutEditer)
                        <button type="button" class="btn btn-sm btn-primary" id="btnPoser" onclick="basculerPose()">➕ {{ __('Poser un emplacement') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-dark" id="btnEditer" onclick="basculerEdition()">✋ {{ __('Déplacer / redimensionner') }}</button>
                        <button type="button" class="btn btn-sm btn-success d-none" id="btnSauver" onclick="sauverPositions()">💾 {{ __('Enregistrer les positions') }}</button>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btnDezoom" onclick="dezoomer()">🔍 {{ __("Vue d'ensemble") }}</button>
                </div>
            </div>
            <div class="card-body p-2">
                <p class="text-muted mb-2" style="font-size:12px;">
                    👆 Touchez un carré : le plan <strong>s'agrandit dessus</strong> et sa fiche s'affiche.
                    @if($peutEditer) En mode « Poser », touchez le plan à l'endroit voulu. En mode « Déplacer », faites glisser les carrés (poignée en bas à droite pour la taille), puis enregistrez. @endif
                </p>

                <div id="planViewport" style="overflow:hidden;border:1px solid #d5dae3;border-radius:8px;background:#fff;">
                    <div id="planMonde" style="position:relative;transition:transform .45s ease;transform-origin:0 0;">
                        <img id="planFond" src="{{ $plan->fondUrl() }}" alt="Fond de plan" style="display:block;width:100%;user-select:none;-webkit-user-drag:none;" draggable="false">

                        @foreach($plan->emplacements as $emp)
                            <div class="stand"
                                 data-id="{{ $emp->id }}"
                                 data-label="{{ $emp->label }}"
                                 data-commercant="{{ $emp->commercant?->full_name }}"
                                 data-commercant-id="{{ $emp->commercant_id }}"
                                 data-activite="{{ $emp->commercant?->activite }}"
                                 data-montant="{{ $emp->montant }}"
                                 data-couleur="{{ $emp->couleur }}"
                                 data-rotation="{{ $emp->rotation }}"
                                 data-elec="{{ $emp->electricite ? 1 : 0 }}"
                                 style="position:absolute;left:{{ $emp->pos_x }}%;top:{{ $emp->pos_y }}%;width:{{ $emp->largeur_pct }}%;height:{{ $emp->hauteur_pct }}%;transform:rotate({{ $emp->rotation }}deg);background:{{ $emp->couleur }}CC;border:2px solid {{ $emp->couleur }};border-radius:4px;color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;text-align:center;overflow:hidden;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,.3);touch-action:manipulation;">
                                <span class="stand-txt" style="pointer-events:none;text-shadow:0 1px 2px rgba(0,0,0,.6);">
                                    @if($emp->electricite)⚡@endif{{ $emp->label ?: ($emp->commercant?->nom ?? '?') }}
                                </span>
                                <span class="poignee d-none" style="position:absolute;right:-1px;bottom:-1px;width:12px;height:12px;background:#fff;border:2px solid #333;border-radius:2px;cursor:nwse-resize;"></span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Fiche de l'emplacement touché --}}
                <div id="ficheStand" class="d-none border rounded p-3 mt-2 bg-light">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="font-size:14px;">
                            <div class="fw-bold" id="ficheTitre"></div>
                            <div class="text-muted" id="ficheDetails" style="font-size:13px;"></div>
                        </div>
                        <button type="button" class="btn-close" onclick="dezoomer()"></button>
                    </div>
                    @if($peutEditer)
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="ouvrirEditionStand()">✏️ Modifier</button>
                        <form method="POST" id="formRetirer" onsubmit="return confirm('Retirer cet emplacement du plan ?')">
                            @csrf @method('DELETE')
                            @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                            <input type="hidden" name="plan" value="{{ $plan->id }}">
                            <button class="btn btn-sm btn-outline-danger">🗑 {{ __('Retirer (pas venu)') }}</button>
                        </form>
                    </div>
                    @endif
                </div>

                @if($peutEditer)
                {{-- Formulaire pose / modification d'un emplacement --}}
                <div id="formStand" class="d-none border rounded p-3 mt-2 bg-light">
                    <div class="fw-semibold mb-2" id="formStandTitre">{{ __('Nouvel emplacement') }}</div>
                    <form method="POST" id="formStandForm">
                        @csrf
                        <span id="formStandMethod"></span>
                        @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                        <input type="hidden" name="pos_x" id="fs_x">
                        <input type="hidden" name="pos_y" id="fs_y">
                        <div class="row g-2 align-items-end">
                            <div class="col-6 col-md-3">
                                <label class="form-label mb-1" style="font-size:12px;">N° / nom de l'emplacement</label>
                                <input type="text" name="label" id="fs_label" class="form-control form-control-sm" placeholder="488, B12, CHALET 3…">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label mb-1" style="font-size:12px;">Commerçant (optionnel)</label>
                                <select name="commercant_id" id="fs_commercant" class="form-select form-select-sm">
                                    <option value="">— Libre —</option>
                                    @foreach($commercants as $c)
                                        <option value="{{ $c->id }}">{{ $c->full_name }} — {{ $c->activite }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label mb-1" style="font-size:12px;">{{ __('Couleur') }}</label>
                                <select name="couleur" id="fs_couleur" class="form-select form-select-sm">
                                    <option value="#e6a23c">🟧 Orange</option>
                                    <option value="#2f9fd0">🟦 Bleu</option>
                                    <option value="#1d3a63">🔷 Marine</option>
                                    <option value="#57a05a">🟩 Vert</option>
                                    <option value="#8e44ad">🟪 Violet</option>
                                </select>
                            </div>
                            <div class="col-4 col-md-1">
                                <label class="form-label mb-1" style="font-size:12px;">{{ __('Rotation (°)') }}</label>
                                <input type="number" name="rotation" id="fs_rotation" step="5" min="-360" max="360" value="0" class="form-control form-control-sm">
                            </div>
                            <div class="col-4 col-md-1">
                                <label class="form-label mb-1" style="font-size:12px;">⚡ Élec</label>
                                <select name="electricite" id="fs_elec" class="form-select form-select-sm">
                                    <option value="0">Non</option>
                                    <option value="1">Oui</option>
                                </select>
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label mb-1" style="font-size:12px;">{{ __('Montant (€)') }}</label>
                                <input type="number" name="montant" id="fs_montant" step="0.01" min="0" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 col-md-1 d-flex gap-1">
                                <button class="btn btn-sm btn-primary w-100">💾</button>
                            </div>
                        </div>
                    </form>
                    <button type="button" class="btn btn-sm btn-link text-muted px-0 mt-1" onclick="fermerFormStand()">{{ __('Annuler') }}</button>
                </div>
                @endif
            </div>
        </div>

    @endif
</div>

@if($plan)
<script>
const PEUT_EDITER   = @json($peutEditer);
const CSRF          = document.querySelector('meta[name="csrf-token"]').content;
const URL_POSER     = @json(route('marche.stands.store', $plan));
const URL_POSITIONS = @json(route('marche.stands.positions', $plan));
const URL_STAND     = @json(url('marche/stands')) + '/';
const URL_EMPL      = @json(url('marche/emplacements')) + '/';

const monde    = document.getElementById('planMonde');
const viewport = document.getElementById('planViewport');

let modePose = false, modeEdition = false, standActif = null, positionsModifiees = false;

/* ── Zoom sur le carré touché ── */
function zoomerSur(stand) {
    const r  = { x: parseFloat(stand.style.left), y: parseFloat(stand.style.top),
                 w: parseFloat(stand.style.width), h: parseFloat(stand.style.height) };
    const cx = (r.x + r.w / 2) / 100, cy = (r.y + r.h / 2) / 100;
    const zoom = Math.min(Math.max(28 / Math.max(r.w, r.h * 1.4), 1.6), 6);

    const vw = monde.offsetWidth, vh = monde.offsetHeight;
    let tx = Math.min(0, Math.max(vw - vw * zoom, vw / 2 - cx * vw * zoom));
    let ty = Math.min(0, Math.max(vh - vh * zoom, vh / 2 - cy * vh * zoom));

    monde.style.transform = `translate(${tx}px, ${ty}px) scale(${zoom})`;
    document.getElementById('btnDezoom').classList.remove('d-none');

    // fiche
    standActif = stand;
    const d = stand.dataset;
    document.getElementById('ficheTitre').textContent =
        (d.elec === '1' ? '⚡ ' : '') + (d.label || 'Emplacement') + (d.commercant ? ' — ' + d.commercant : ' (libre)');
    document.getElementById('ficheDetails').textContent =
        (d.activite ? d.activite + ' · ' : '') + (d.montant ? d.montant + ' € · ' : '') +
        'Touchez « Vue d\'ensemble » pour revenir au plan complet.';
    const fiche = document.getElementById('ficheStand');
    fiche.classList.remove('d-none');
    const fr = document.getElementById('formRetirer');
    if (fr) fr.action = URL_EMPL + d.id;
}

function dezoomer() {
    monde.style.transform = '';
    document.getElementById('btnDezoom').classList.add('d-none');
    document.getElementById('ficheStand').classList.add('d-none');
    standActif = null;
}

/* ── Modes édition ── */
function basculerPose() {
    modePose = !modePose; modeEdition = false;
    majBoutons();
}
function basculerEdition() {
    modeEdition = !modeEdition; modePose = false;
    document.querySelectorAll('.stand .poignee').forEach(p => p.classList.toggle('d-none', !modeEdition));
    // bloque le scroll tactile sur les carrés pendant le glisser-déposer
    document.querySelectorAll('.stand').forEach(s => s.style.touchAction = modeEdition ? 'none' : 'manipulation');
    majBoutons();
}
function majBoutons() {
    document.getElementById('btnPoser')?.classList.toggle('btn-warning', modePose);
    document.getElementById('btnEditer')?.classList.toggle('btn-warning', modeEdition);
    viewport.style.cursor = modePose ? 'crosshair' : '';
    dezoomer();
}

/* ── Clic sur le plan : poser un emplacement (ou dézoomer) ── */
const TAILLE_DEFAUT = { w: 6, h: 4 };

monde.addEventListener('click', e => {
    if (e.target.closest('.stand')) return;
    if (!modePose) {
        if (standActif) dezoomer();   // toucher le fond quand on est zoomé → vue d'ensemble
        return;
    }
    const rect  = monde.getBoundingClientRect();
    const scale = rect.width / monde.offsetWidth;
    // le carré est CENTRÉ sur le point touché
    const x = ((e.clientX - rect.left) / scale) / monde.offsetWidth * 100 - TAILLE_DEFAUT.w / 2;
    const y = ((e.clientY - rect.top) / scale) / monde.offsetHeight * 100 - TAILLE_DEFAUT.h / 2;
    ouvrirFormStand(null, Math.max(0, Math.min(x, 100 - TAILLE_DEFAUT.w)), Math.max(0, Math.min(y, 100 - TAILLE_DEFAUT.h)));
});

/* ── Clic sur un carré : zoom / re-clic : dézoom ── */
document.querySelectorAll('.stand').forEach(stand => {
    stand.addEventListener('click', e => {
        if (modeEdition || modePose) return;
        e.stopPropagation();
        if (standActif === stand) { dezoomer(); return; }
        zoomerSur(stand);
    });
});

document.addEventListener('keydown', e => { if (e.key === 'Escape') dezoomer(); });

/* ── Formulaire pose / édition ── */
function ouvrirFormStand(stand, x, y) {
    const form = document.getElementById('formStand');
    if (!form) return;
    form.classList.remove('d-none');
    const f = document.getElementById('formStandForm');
    document.getElementById('formStandMethod').innerHTML = stand ? '<input type="hidden" name="_method" value="PUT">' : '';
    f.action = stand ? (URL_STAND + stand.dataset.id) : URL_POSER;
    document.getElementById('formStandTitre').textContent = stand ? 'Modifier l\'emplacement' : '{{ __('Nouvel emplacement') }}';
    document.getElementById('fs_x').value = x !== undefined ? x.toFixed(3) : '';
    document.getElementById('fs_y').value = y !== undefined ? y.toFixed(3) : '';
    document.getElementById('fs_label').value = stand ? (stand.dataset.label || '') : '';
    document.getElementById('fs_commercant').value = stand ? (stand.dataset.commercantId || '') : '';
    document.getElementById('fs_couleur').value = stand ? (stand.dataset.couleur || '#e6a23c') : '#e6a23c';
    document.getElementById('fs_rotation').value = stand ? (stand.dataset.rotation || 0) : 0;
    document.getElementById('fs_elec').value = stand ? stand.dataset.elec : '0';
    document.getElementById('fs_montant').value = stand ? (stand.dataset.montant || '') : '';
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function fermerFormStand() { document.getElementById('formStand').classList.add('d-none'); }
function ouvrirEditionStand() { if (standActif) { dezoomer(); ouvrirFormStand(standActif); } }

/* ── Déplacement / redimensionnement (mode édition) ── */
let dragCible = null, dragMode = null, dragStart = null;

document.querySelectorAll('.stand').forEach(stand => {
    stand.addEventListener('pointerdown', e => {
        if (!modeEdition) return;
        e.preventDefault(); e.stopPropagation();
        dragCible = stand;
        dragMode  = e.target.classList.contains('poignee') ? 'resize' : 'move';
        dragStart = {
            px: e.clientX, py: e.clientY,
            x: parseFloat(stand.style.left), y: parseFloat(stand.style.top),
            w: parseFloat(stand.style.width), h: parseFloat(stand.style.height),
        };
        stand.setPointerCapture(e.pointerId);
    });
});

document.addEventListener('pointermove', e => {
    if (!dragCible) return;
    const dx = (e.clientX - dragStart.px) / monde.offsetWidth * 100;
    const dy = (e.clientY - dragStart.py) / monde.offsetHeight * 100;
    if (dragMode === 'move') {
        dragCible.style.left = Math.min(Math.max(dragStart.x + dx, 0), 100 - dragStart.w) + '%';
        dragCible.style.top  = Math.min(Math.max(dragStart.y + dy, 0), 100 - dragStart.h) + '%';
    } else {
        dragCible.style.width  = Math.min(Math.max(dragStart.w + dx, 0.8), 100) + '%';
        dragCible.style.height = Math.min(Math.max(dragStart.h + dy, 0.8), 100) + '%';
    }
    positionsModifiees = true;
    document.getElementById('btnSauver')?.classList.remove('d-none');
});

document.addEventListener('pointerup', () => { dragCible = null; });

/* ── Sauvegarde des positions ── */
async function sauverPositions() {
    const stands = [...document.querySelectorAll('.stand')].map(s => ({
        id: parseInt(s.dataset.id),
        pos_x: parseFloat(s.style.left),
        pos_y: parseFloat(s.style.top),
        largeur_pct: parseFloat(s.style.width),
        hauteur_pct: parseFloat(s.style.height),
    }));

    const rep = await fetch(URL_POSITIONS, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ stands }),
    });

    const btn = document.getElementById('btnSauver');
    if (rep.ok) {
        positionsModifiees = false;
        btn.classList.add('d-none');
        btn.classList.remove('btn-danger');
    } else {
        btn.classList.add('btn-danger');
        alert('Erreur lors de l\'enregistrement des positions.');
    }
}

window.addEventListener('beforeunload', e => {
    if (positionsModifiees) { e.preventDefault(); e.returnValue = ''; }
});
</script>
@endif
@endsection
