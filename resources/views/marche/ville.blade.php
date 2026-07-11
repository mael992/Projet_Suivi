@extends('layouts.app')

@php use App\Models\MarcheZone; @endphp

@section('content')
@php
    $peutEditer  = auth()->user()->aDroit('marche_gestion');
    $mairieParam = request()->only('mairie');
@endphp

<div class="container-fluid px-3 px-md-4 py-4">

    <a href="{{ route('apps') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">← {{ __('mgds.nav_apps') }}</a>
    <h1 class="h3 mb-3">🛍 Marché — {{ $mairie->nom }}</h1>

    @include('marche.partials.onglets')

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    <p class="text-muted mb-3" style="font-size:14px;">
        {{ __('Placez vos zones de marché (place, rue, trottoir…) sur la vue aérienne de la ville, puis cliquez sur une zone pour préparer son marché.') }}
    </p>

    @if($peutEditer)
    <div class="d-flex gap-2 flex-wrap mb-3">
        {{-- Ajouter une zone --}}
        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#formZone">
            ➕ {{ __('Ajouter une zone de marché') }}
        </button>
        {{-- Changer la vue aérienne --}}
        <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#formImage">
            🖼 {{ __('Changer la vue aérienne') }}
        </button>
        {{-- Mode déplacement --}}
        <button class="btn btn-sm btn-outline-secondary" type="button" id="btnModeDeplacer" onclick="basculerMode()">
            ✋ {{ __('Déplacer / redimensionner') }} : <span id="modeEtat">{{ __('non') }}</span>
        </button>
        <button class="btn btn-sm btn-success d-none" type="button" id="btnSauver" onclick="sauverPositions()">
            💾 {{ __('Enregistrer les positions') }}
        </button>
    </div>

    <div class="collapse mb-3 {{ $errors->any() ? 'show' : '' }}" id="formZone">
        <form method="POST" action="{{ route('marche.zones.store', $mairieParam) }}" class="card shadow-sm">
            @csrf
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label mb-1" style="font-size:12px;">{{ __('Nom de la zone') }}</label>
                        <input type="text" name="nom" value="{{ old('nom') }}" class="form-control form-control-sm"
                               placeholder="{{ __('ex : Place du marché, Rue principale…') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1" style="font-size:12px;">{{ __('Type') }}</label>
                        <select name="type" class="form-select form-select-sm" required>
                            @foreach(MarcheZone::TYPES as $cle => $label)
                                <option value="{{ $cle }}" @selected(old('type') === $cle)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1" style="font-size:12px;">{{ __('Couleur') }}</label>
                        <input type="color" name="couleur" value="{{ old('couleur', '#2e86de') }}" class="form-control form-control-sm form-control-color w-100">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-dark w-100">{{ __('Créer la zone') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="collapse mb-3" id="formImage">
        <form method="POST" action="{{ route('marche.ville.image', $mairieParam) }}" enctype="multipart/form-data" class="card shadow-sm">
            @csrf
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label mb-1" style="font-size:12px;">{{ __('Image de la vue aérienne (photo satellite, plan de la ville…)') }}</label>
                        <input type="file" name="image" class="form-control form-control-sm" accept="image/*" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-sm btn-dark w-100">{{ __('Mettre à jour') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    @endif

    {{-- ── Vue aérienne ── --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body p-2">
            <div id="villeMonde" style="position:relative;user-select:none;overflow:hidden;border-radius:8px;">
                <img src="{{ $mairie->vueAerienneUrl() }}" alt="Vue aérienne" draggable="false"
                     style="width:100%;display:block;pointer-events:none;">

                @foreach($zones as $zone)
                    <div class="zone-marche" data-id="{{ $zone->id }}" data-nom="{{ $zone->nom }}"
                         data-url="{{ route('marche.zones.show', array_merge(['zone' => $zone->id], $mairieParam)) }}"
                         style="position:absolute;
                                left:{{ $zone->pos_x }}%;top:{{ $zone->pos_y }}%;
                                width:{{ $zone->largeur_pct }}%;height:{{ $zone->hauteur_pct }}%;
                                transform:rotate({{ $zone->rotation }}deg);
                                background:{{ $zone->couleur }}55;
                                border:2px solid {{ $zone->couleur }};
                                border-radius:6px;cursor:pointer;touch-action:none;
                                display:flex;align-items:center;justify-content:center;">
                        <span style="font-size:11px;font-weight:700;color:#fff;text-shadow:0 1px 3px rgba(0,0,0,.75);text-align:center;padding:2px;pointer-events:none;">
                            {{ $zone->nom }}
                        </span>
                        <span class="zone-poignee d-none" style="position:absolute;right:-7px;bottom:-7px;width:16px;height:16px;background:#fff;border:2px solid {{ $zone->couleur }};border-radius:50%;cursor:nwse-resize;touch-action:none;"></span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Liste des zones ── --}}
    <div class="row g-3">
        @forelse($zones as $zone)
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body py-3 d-flex flex-column">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span style="width:14px;height:14px;border-radius:4px;background:{{ $zone->couleur }};display:inline-block;flex-shrink:0;"></span>
                            <span class="fw-semibold">{{ $zone->nom }}</span>
                            <span class="badge bg-secondary bg-opacity-25 text-secondary">{{ __($zone->type_label) }}</span>
                        </div>
                        <div class="text-muted mb-2" style="font-size:12px;">
                            {{ $zone->marche_type ? __($zone->marche_type_label) : __('Marché non configuré') }}
                            @if($zone->config)
                                · {{ count($zone->config['obstacles'] ?? []) }} {{ __('obstacle(s)') }}
                            @endif
                        </div>
                        <div class="mt-auto d-flex gap-2">
                            <a href="{{ route('marche.zones.show', array_merge(['zone' => $zone->id], $mairieParam)) }}" class="btn btn-sm btn-primary">
                                🎪 {{ __('Préparer le marché') }}
                            </a>
                            @if($peutEditer)
                            <form action="{{ route('marche.zones.destroy', array_merge(['zone' => $zone->id], $mairieParam)) }}" method="POST"
                                  onsubmit="return confirm('{{ __('Supprimer la zone') }} {{ $zone->nom }} ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">🗑</button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center text-muted py-4">
                {{ __('Aucune zone pour le moment. Ajoutez votre première zone de marché !') }}
            </div>
        @endforelse
    </div>
</div>

<script>
const PEUT_EDITER = @json($peutEditer);
let modeDeplacer  = false;
let modifie       = false;

function basculerMode() {
    modeDeplacer = !modeDeplacer;
    document.getElementById('modeEtat').textContent = modeDeplacer ? '{{ __('oui') }}' : '{{ __('non') }}';
    document.getElementById('btnModeDeplacer').classList.toggle('btn-warning', modeDeplacer);
    document.getElementById('btnSauver').classList.toggle('d-none', !modeDeplacer);
    document.querySelectorAll('.zone-poignee').forEach(p => p.classList.toggle('d-none', !modeDeplacer));
}

const monde = document.getElementById('villeMonde');

document.querySelectorAll('.zone-marche').forEach(zone => {
    const poignee = zone.querySelector('.zone-poignee');
    let drag = null;

    // Clic simple (mode consultation) → ouvrir la zone
    zone.addEventListener('click', e => {
        if (!modeDeplacer && !drag) window.location.href = zone.dataset.url;
    });

    // Déplacement
    zone.addEventListener('pointerdown', e => {
        if (!modeDeplacer || e.target === poignee) return;
        e.preventDefault();
        zone.setPointerCapture(e.pointerId);
        const r = monde.getBoundingClientRect();
        drag = {
            mode: 'move',
            dx: e.clientX - r.left - (parseFloat(zone.style.left) / 100) * r.width,
            dy: e.clientY - r.top  - (parseFloat(zone.style.top)  / 100) * r.height,
        };
    });

    // Redimensionnement (poignée)
    poignee.addEventListener('pointerdown', e => {
        if (!modeDeplacer) return;
        e.preventDefault(); e.stopPropagation();
        poignee.setPointerCapture(e.pointerId);
        drag = { mode: 'resize' };
    });

    function bouger(e) {
        if (!drag) return;
        const r = monde.getBoundingClientRect();
        if (drag.mode === 'move') {
            let x = ((e.clientX - r.left - drag.dx) / r.width)  * 100;
            let y = ((e.clientY - r.top  - drag.dy) / r.height) * 100;
            x = Math.max(0, Math.min(100 - parseFloat(zone.style.width), x));
            y = Math.max(0, Math.min(100 - parseFloat(zone.style.height), y));
            zone.style.left = x.toFixed(3) + '%';
            zone.style.top  = y.toFixed(3) + '%';
        } else {
            const zr = zone.getBoundingClientRect();
            let w = ((e.clientX - zr.left) / r.width)  * 100;
            let h = ((e.clientY - zr.top)  / r.height) * 100;
            zone.style.width  = Math.max(3, Math.min(100, w)).toFixed(3) + '%';
            zone.style.height = Math.max(2, Math.min(100, h)).toFixed(3) + '%';
        }
        modifie = true;
    }

    zone.addEventListener('pointermove', bouger);
    poignee.addEventListener('pointermove', bouger);
    ['pointerup', 'pointercancel'].forEach(ev => {
        zone.addEventListener(ev, () => setTimeout(() => drag = null, 50));
        poignee.addEventListener(ev, () => setTimeout(() => drag = null, 50));
    });

    // Rotation : double-clic = +15°
    zone.addEventListener('dblclick', e => {
        if (!modeDeplacer) return;
        e.preventDefault();
        const m = (zone.style.transform.match(/rotate\((-?[\d.]+)deg\)/) || [0, 0]);
        const angle = (parseFloat(m[1]) + 15) % 360;
        zone.style.transform = 'rotate(' + angle + 'deg)';
        modifie = true;
    });
});

function sauverPositions() {
    const zones = Array.from(document.querySelectorAll('.zone-marche')).map(z => ({
        id: parseInt(z.dataset.id, 10),
        pos_x: parseFloat(z.style.left),
        pos_y: parseFloat(z.style.top),
        largeur_pct: parseFloat(z.style.width),
        hauteur_pct: parseFloat(z.style.height),
        rotation: parseFloat((z.style.transform.match(/rotate\((-?[\d.]+)deg\)/) || [0, 0])[1]),
    }));

    fetch('{{ route('marche.zones.positions', $mairieParam) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ zones }),
    }).then(r => {
        if (r.ok) { modifie = false; basculerMode(); }
        else alert('{{ __('Erreur lors de la sauvegarde.') }}');
    });
}
</script>
@endsection
