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

    {{-- ── Choix / création du plan (daté, modifiable) ── --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label mb-1" style="font-size:12px;">Plan (par date)</label>
                    <form method="GET" action="{{ route('marche.plan') }}">
                        @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                        <select name="plan" class="form-select form-select-sm" onchange="this.form.submit()">
                            @forelse($plans as $p)
                                <option value="{{ $p->id }}" @selected($plan && $plan->id === $p->id)>
                                    {{ $p->date->format('d/m/Y') }} — {{ $p->nom }}
                                </option>
                            @empty
                                <option value="">Aucun plan — créez-en un ci-contre</option>
                            @endforelse
                        </select>
                    </form>
                </div>

                @if($peutEditer)
                <div class="col-12 col-md-7">
                    <form method="POST" action="{{ route('marche.plans.store') }}" class="row g-2 align-items-end">
                        @csrf
                        @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                        <div class="col-5">
                            <label class="form-label mb-1" style="font-size:12px;">Nouveau plan</label>
                            <input type="text" name="nom" class="form-control form-control-sm" placeholder="Marché hebdomadaire" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-1" style="font-size:12px;">Date</label>
                            <input type="date" name="date" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-3">
                            <button class="btn btn-sm btn-primary w-100">+ Créer</button>
                        </div>
                    </form>
                </div>
                @endif
            </div>

            @if($plan && $peutEditer)
                <hr class="my-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-8">
                        <form method="POST" action="{{ route('marche.plans.update', $plan) }}" class="row g-2 align-items-end">
                            @csrf @method('PUT')
                            @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                            <div class="col-5">
                                <label class="form-label mb-1" style="font-size:12px;">Modifier le nom</label>
                                <input type="text" name="nom" value="{{ $plan->nom }}" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label mb-1" style="font-size:12px;">Modifier la date</label>
                                <input type="date" name="date" value="{{ $plan->date->format('Y-m-d') }}" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-3">
                                <button class="btn btn-sm btn-outline-primary w-100">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-12 col-md-4 text-md-end">
                        <form method="POST" action="{{ route('marche.plans.destroy', $plan) }}"
                              onsubmit="return confirm('⚠️ Supprimer ce plan et tous ses placements ?')">
                            @csrf @method('DELETE')
                            @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                            <button class="btn btn-sm btn-outline-danger">🗑 Supprimer ce plan</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if(!$plan)
        <div class="text-center text-muted py-5">
            🗺️ Créez votre premier plan daté pour commencer à placer les exposants.
        </div>
    @else

        {{-- ── Ajouter un axe ── --}}
        @if($peutEditer)
        <form method="POST" action="{{ route('marche.axes.store', $plan) }}" class="card shadow-sm mb-4">
            @csrf
            @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-6">
                        <label class="form-label mb-1" style="font-size:12px;">Nouvel axe (trottoir, allée, place…)</label>
                        <input type="text" name="nom" class="form-control form-control-sm" placeholder="Trottoir gauche — rue de la Mairie" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1" style="font-size:12px;">Longueur (mètres)</label>
                        <input type="number" name="longueur" step="0.5" min="1" class="form-control form-control-sm" placeholder="20" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <button class="btn btn-sm btn-dark w-100">+ Ajouter l'axe</button>
                    </div>
                </div>
            </div>
        </form>
        @endif

        {{-- ── Les axes du plan (vue 2D) ── --}}
        @forelse($plan->axes as $axe)
            @php
                $reste     = $axe->longueurRestante();
                $depasse   = $reste < 0;
                $palette   = ['#1d3a63', '#b08d4a', '#3f6db3', '#7a5c2e', '#2c4f80', '#94794e'];
            @endphp
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-semibold">📍 {{ $axe->nom }} — {{ rtrim(rtrim(number_format($axe->longueur, 2, ',', ' '), '0'), ',') }} m</span>
                    <span>
                        <span class="badge {{ $depasse ? 'bg-danger' : 'bg-success' }}">
                            {{ $depasse ? 'Dépassement : ' . number_format(abs($reste), 2, ',', ' ') . ' m' : 'Reste : ' . number_format($reste, 2, ',', ' ') . ' m' }}
                        </span>
                        @if($peutEditer)
                        <form method="POST" action="{{ route('marche.axes.destroy', $axe) }}" class="d-inline"
                              onsubmit="return confirm('Supprimer cet axe et ses placements ?')">
                            @csrf @method('DELETE')
                            @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                            <button class="btn btn-sm btn-outline-danger ms-2">🗑</button>
                        </form>
                        @endif
                    </span>
                </div>
                <div class="card-body">

                    {{-- Vue 2D de l'axe --}}
                    @php $echelle = max((float) $axe->longueur, $axe->finDernierStand(), 1); @endphp
                    <div style="position:relative;height:64px;background:repeating-linear-gradient(90deg,#eef1f6,#eef1f6 24px,#e4e8ef 24px,#e4e8ef 25px);border:1px solid #d5dae3;border-radius:8px;overflow:hidden;margin-bottom:6px;">
                        {{-- limite de l'axe si l'échelle dépasse --}}
                        @if($echelle > (float) $axe->longueur)
                            <div style="position:absolute;left:{{ $axe->longueur / $echelle * 100 }}%;top:0;bottom:0;width:2px;background:#dc3545;z-index:2;" title="Fin de l'axe"></div>
                        @endif
                        @foreach($axe->emplacements as $i => $emp)
                            @php
                                $left  = $emp->position / $echelle * 100;
                                $width = $emp->longueur / $echelle * 100;
                                $horsLimite = $emp->fin > (float) $axe->longueur;
                            @endphp
                            <div title="{{ $emp->commercant?->full_name }} ({{ $emp->commercant?->activite }}) — {{ $emp->longueur }} m, de {{ $emp->position }} à {{ $emp->fin }} m"
                                 style="position:absolute;left:{{ $left }}%;width:{{ $width }}%;top:8px;bottom:8px;background:{{ $horsLimite ? '#dc3545' : $palette[$i % count($palette)] }};color:#fff;border-radius:6px;font-size:11px;display:flex;align-items:center;justify-content:center;overflow:hidden;white-space:nowrap;padding:0 4px;box-shadow:0 1px 4px rgba(0,0,0,.25);">
                                👤 {{ $emp->commercant?->nom }}
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-between text-muted" style="font-size:11px;">
                        <span>0 m</span>
                        <span>{{ rtrim(rtrim(number_format($axe->longueur, 2, ',', ' '), '0'), ',') }} m</span>
                    </div>

                    {{-- Placement d'un exposant --}}
                    @if($peutEditer)
                    <form method="POST" action="{{ route('marche.emplacements.store', $axe) }}" class="row g-2 align-items-end mt-2 mb-3">
                        @csrf
                        @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1" style="font-size:12px;">Exposant</label>
                            <select name="commercant_id" class="form-select form-select-sm" required>
                                <option value="">— Choisir un commerçant —</option>
                                @foreach($commercants as $c)
                                    <option value="{{ $c->id }}">{{ $c->full_name }} — {{ $c->activite }} ({{ $c->longueur_defaut }} m)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4 col-md-2">
                            <label class="form-label mb-1" style="font-size:12px;">Longueur (m)</label>
                            <input type="number" name="longueur" step="0.5" min="0.5" class="form-control form-control-sm" placeholder="auto">
                        </div>
                        <div class="col-4 col-md-2">
                            <label class="form-label mb-1" style="font-size:12px;">Position (m)</label>
                            <input type="number" name="position" step="0.5" min="0" class="form-control form-control-sm" placeholder="à la suite">
                        </div>
                        <div class="col-4 col-md-2">
                            <label class="form-label mb-1" style="font-size:12px;">Montant (€)</label>
                            <input type="number" name="montant" step="0.01" min="0" class="form-control form-control-sm">
                        </div>
                        <div class="col-12 col-md-2">
                            <button class="btn btn-sm btn-primary w-100">+ Placer</button>
                        </div>
                    </form>
                    @endif

                    {{-- Liste des emplacements --}}
                    @if($axe->emplacements->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" style="font-size:13px;">
                            <thead>
                                <tr class="text-muted">
                                    <th>Exposant</th><th>Activité</th><th>De</th><th>À</th><th>Longueur</th><th>Montant</th><th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($axe->emplacements as $emp)
                                <tr>
                                    <td class="fw-semibold">{{ $emp->commercant?->full_name ?? '—' }}</td>
                                    <td>{{ $emp->commercant?->activite ?? '—' }}</td>
                                    @if($peutEditer)
                                    <form method="POST" action="{{ route('marche.emplacements.update', $emp) }}">
                                        @csrf @method('PUT')
                                        @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                                        <td style="width:90px;"><input type="number" name="position" value="{{ $emp->position }}" step="0.5" min="0" class="form-control form-control-sm"></td>
                                        <td>{{ number_format($emp->fin, 2, ',', ' ') }} m</td>
                                        <td style="width:90px;"><input type="number" name="longueur" value="{{ $emp->longueur }}" step="0.5" min="0.5" class="form-control form-control-sm"></td>
                                        <td style="width:110px;"><input type="number" name="montant" value="{{ $emp->montant }}" step="0.01" min="0" class="form-control form-control-sm" placeholder="€"></td>
                                        <td class="text-end" style="white-space:nowrap;">
                                            <button class="btn btn-sm btn-outline-primary">💾</button>
                                    </form>
                                            <form method="POST" action="{{ route('marche.emplacements.destroy', $emp) }}" class="d-inline"
                                                  onsubmit="return confirm('Retirer {{ addslashes($emp->commercant?->full_name ?? 'cet exposant') }} du plan ?')">
                                                @csrf @method('DELETE')
                                                @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                                                <button class="btn btn-sm btn-outline-danger" title="Il n'est finalement pas venu">🗑</button>
                                            </form>
                                        </td>
                                    @else
                                        <td>{{ $emp->position }} m</td>
                                        <td>{{ number_format($emp->fin, 2, ',', ' ') }} m</td>
                                        <td>{{ $emp->longueur }} m</td>
                                        <td>{{ $emp->montant !== null ? number_format($emp->montant, 2, ',', ' ') . ' €' : '—' }}</td>
                                        <td></td>
                                    @endif
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                </div>
            </div>
        @empty
            <div class="text-center text-muted py-4">Aucun axe sur ce plan — ajoutez un trottoir ou une allée ci-dessus.</div>
        @endforelse

    @endif
</div>
@endsection
