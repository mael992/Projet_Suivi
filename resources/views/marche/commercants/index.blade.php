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

    {{-- ── Ajouter un commerçant ── --}}
    @if($peutEditer)
    <form method="POST" action="{{ route('marche.commercants.store') }}" class="card shadow-sm mb-4">
        @csrf
        @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:12px;">Nom *</label>
                    <input type="text" name="nom" value="{{ old('nom') }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:12px;">Prénom</label>
                    <input type="text" name="prenom" value="{{ old('prenom') }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:12px;">Activité *</label>
                    <input type="text" name="activite" value="{{ old('activite') }}" class="form-control form-control-sm" placeholder="Fleuriste, vêtements…" required>
                </div>
                <div class="col-3 col-md-1">
                    <label class="form-label mb-1" style="font-size:12px;">Indicatif</label>
                    <select name="telephone_indicatif" class="form-select form-select-sm">
                        @foreach(Referentiel::INDICATIFS as $ind)
                            <option value="{{ $ind }}">{{ $ind }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-3 col-md-2">
                    <label class="form-label mb-1" style="font-size:12px;">Téléphone</label>
                    <input type="text" name="telephone" value="{{ old('telephone') }}" class="form-control form-control-sm">
                </div>
                <div class="col-3 col-md-1">
                    <label class="form-label mb-1" style="font-size:12px;">Stand (m) *</label>
                    <input type="number" name="longueur_defaut" value="{{ old('longueur_defaut', 3) }}" step="0.5" min="0.5" class="form-control form-control-sm" required>
                </div>
                <div class="col-3 col-md-2">
                    <button class="btn btn-sm btn-primary w-100">+ Ajouter</button>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label mb-1" style="font-size:12px;">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-sm">
                </div>
            </div>
        </div>
    </form>
    @endif

    {{-- ── Liste des commerçants ── --}}
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:14px;">
                <thead class="table-dark">
                    <tr>
                        <th>Nom</th><th>Prénom</th><th>Activité</th><th>Téléphone</th><th>Email</th>
                        <th class="text-center">Stand (m)</th>
                        <th class="text-center">Venues</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($commercants as $c)
                    <tr>
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
        </div>
    </div>

</div>
@endsection
