@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

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
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    {{-- ── Recherche + bouton d'ajout ── --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div style="max-width:400px;flex:1;">
            <div class="search-input-group">
                <span class="search-icon">🔍</span>
                <input type="text" id="commercantSearch" class="search-input"
                       placeholder="{{ __('Recherche : nom, prénom ou activité…') }}" autocomplete="off">
            </div>
        </div>
        @if($peutEditer)
            <a href="{{ route('marche.commercants.create', request()->only('mairie')) }}" class="btn btn-primary">{{ __('+ Ajouter') }}</a>
        @endif
    </div>

    {{-- ── Liste des commerçants ── --}}
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:14px;">
                <thead class="table-dark">
                    <tr>
                        <th>{{ __('Nom') }}</th><th>{{ __('Prénom') }}</th><th>{{ __('Activité') }}</th><th>{{ __('Téléphone') }}</th><th>{{ __('Email') }}</th>
                        <th class="text-center">Stand (m)</th>
                        <th class="text-center">{{ __('Venues') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody id="commercantsBody">
                @forelse($commercants as $c)
                    <tr data-search="{{ strtolower(\Illuminate\Support\Str::ascii($c->nom . ' ' . ($c->prenom ?? '') . ' ' . $c->activite)) }}">
                        @if($peutEditer)
                        <form method="POST" action="{{ route('marche.commercants.update', $c) }}">
                            @csrf @method('PUT')
                            @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                            <td style="min-width:110px;"><input type="text" name="nom" value="{{ $c->nom }}" class="form-control form-control-sm" required></td>
                            <td style="min-width:110px;"><input type="text" name="prenom" value="{{ $c->prenom }}" class="form-control form-control-sm"></td>
                            <td style="min-width:120px;"><input type="text" name="activite" value="{{ $c->activite }}" class="form-control form-control-sm" required></td>
                            <td style="min-width:150px;">
                                <div class="input-group input-group-sm">
                                    <select name="telephone_indicatif" class="form-select" style="max-width:80px;">
                                        @foreach(Referentiel::INDICATIFS as $ind)
                                            <option value="{{ $ind }}" @selected($c->telephone_indicatif === $ind)>{{ $ind }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="telephone" value="{{ $c->telephone }}" class="form-control">
                                </div>
                            </td>
                            <td style="min-width:150px;"><input type="email" name="email" value="{{ $c->email }}" class="form-control form-control-sm"></td>
                            <td class="text-center" style="width:90px;"><input type="number" name="longueur_defaut" value="{{ $c->longueur_defaut }}" step="0.5" min="0.5" class="form-control form-control-sm" required></td>
                            <td class="text-center">{{ $c->emplacements_count }}</td>
                            <td class="text-end" style="white-space:nowrap;">
                                <button class="btn btn-sm btn-outline-primary" title="Enregistrer">💾</button>
                        </form>
                                <form method="POST" action="{{ route('marche.commercants.destroy', $c) }}" class="d-inline"
                                      onsubmit="return confirm('⚠️ Supprimer {{ addslashes($c->full_name) }} et tout son historique de venues ?')">
                                    @csrf @method('DELETE')
                                    @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
                                    <button class="btn btn-sm btn-outline-danger" title="Supprimer">🗑</button>
                                </form>
                            </td>
                        @else
                            <td class="fw-semibold">{{ $c->nom }}</td>
                            <td>{{ $c->prenom ?? '—' }}</td>
                            <td>{{ $c->activite }}</td>
                            <td>{{ $c->telephone ? '(' . $c->telephone_indicatif . ') ' . $c->telephone : '—' }}</td>
                            <td>{{ $c->email ?? '—' }}</td>
                            <td class="text-center">{{ $c->longueur_defaut }}</td>
                            <td class="text-center">{{ $c->emplacements_count }}</td>
                            <td></td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucun commerçant — ajoutez-les ci-dessus, ils apparaîtront dans le plan et le registre.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div id="noResults" class="text-center text-muted py-4 d-none">{{ __('Aucun résultat.') }}</div>
        </div>
    </div>

</div>

<script>
document.getElementById('commercantSearch').addEventListener('input', function () {
    const q     = this.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    const rows  = document.querySelectorAll('#commercantsBody tr');
    let visible = 0;

    rows.forEach(row => {
        const data = row.dataset.search ?? '';
        const show = !q || data.includes(q);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('noResults').classList.toggle('d-none', visible > 0);
});
</script>
@endsection
