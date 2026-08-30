@extends('layouts.app')

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

    {{-- ── Inscriptions en ligne ── --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <strong>🤝 {{ __('Inscriptions en ligne des commerçants') }}</strong>
                <div class="text-muted" style="font-size:13px;">
                    @if($mairie->marche_inscription_ouverte)
                        {{ __('Ouvertes : les commerçants peuvent demander à rejoindre votre marché depuis le site public.') }}
                    @else
                        {{ __('Fermées : votre commune n\'apparaît pas dans le formulaire public.') }}
                    @endif
                </div>
            </div>
            @if($peutEditer)
                <form method="POST" action="{{ route('marche.demandes.bascule', $mairieParam) }}">
                    @csrf
                    <button class="btn btn-sm {{ $mairie->marche_inscription_ouverte ? 'btn-outline-danger' : 'btn-success' }}">
                        {{ $mairie->marche_inscription_ouverte ? __('Fermer les inscriptions') : __('Ouvrir les inscriptions') }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- ── Demandes reçues ── --}}
    <div class="card shadow-sm mb-3" id="zoneDemandes">
        <div class="card-header py-2 fw-semibold">
            📥 {{ __('Demandes reçues') }}
            @php $enAttente = $demandes->where('statut', 'en_attente')->count(); @endphp
            @if($enAttente > 0)<span class="bulle-notif ms-1">{{ $enAttente }}</span>@endif
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>{{ __('Reçue le') }}</th>
                        <th>{{ __('Commerçant') }}</th>
                        <th>{{ __('Activité') }}</th>
                        <th>{{ __('Contact') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($demandes as $demande)
                    <tr class="{{ $demande->statut === 'en_attente' ? 'table-warning' : '' }}">
                        <td style="font-size:13px;">{{ $demande->created_at->format('d/m/Y') }}</td>
                        <td>
                            <span class="fw-semibold">{{ $demande->nom_complet }}</span>
                            @if($demande->societe)<div class="text-muted" style="font-size:11px;">{{ $demande->societe }}</div>@endif
                        </td>
                        <td style="font-size:13px;">
                            {{ $demande->activite }}
                            @if($demande->longueur_souhaitee)
                                <div class="text-muted" style="font-size:11px;">{{ $demande->longueur_souhaitee }} m souhaités</div>
                            @endif
                        </td>
                        <td style="font-size:12px;">
                            {{ $demande->email }}<br>({{ $demande->telephone_indicatif }}) {{ $demande->telephone }}
                        </td>
                        <td>
                            <span class="badge bg-{{ $demande->statut === 'acceptee' ? 'success' : ($demande->statut === 'refusee' ? 'secondary' : 'warning text-dark') }}">
                                {{ __($demande->statut_label) }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if($peutEditer && $demande->statut === 'en_attente')
                                <form action="{{ route('marche.demandes.accepter', array_merge(['demande' => $demande->id], $mairieParam)) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success">✅ {{ __('Accepter') }}</button>
                                </form>
                                <form action="{{ route('marche.demandes.refuser', array_merge(['demande' => $demande->id], $mairieParam)) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('{{ __('Refuser cette demande ?') }}')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">✕ {{ __('Refuser') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @if($demande->message)
                        <tr class="{{ $demande->statut === 'en_attente' ? 'table-warning' : '' }}">
                            <td colspan="6" class="text-muted" style="font-size:12px;white-space:pre-wrap;">💬 {{ $demande->message }}</td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Aucune demande pour le moment.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Codes d'accès au plan ── --}}
    <div class="card shadow-sm">
        <div class="card-header py-2 fw-semibold">🔑 {{ __('Codes d\'accès au plan (exposants)') }}</div>
        <div class="card-body py-3">
            <p class="text-muted" style="font-size:13px;">
                {{ __('Remettez ce code aux exposants : il leur donne accès au plan du marché jusqu\'au jour J inclus, puis devient inutilisable.') }}
            </p>

            @if($peutEditer && $zones->isNotEmpty())
                <form method="POST" action="{{ route('marche.codes.store', $mairieParam) }}" class="row g-2 align-items-end mb-3">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label mb-1" style="font-size:12px;">{{ __('Zone de marché') }}</label>
                        <select name="marche_zone_id" class="form-select form-select-sm" required>
                            @foreach($zones as $z)
                                <option value="{{ $z->id }}">{{ $z->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1" style="font-size:12px;">{{ __('Jour du marché') }}</label>
                        <input type="date" name="valable_le" class="form-control form-control-sm" required min="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-sm btn-dark w-100">{{ __('Générer un code') }}</button>
                    </div>
                </form>
            @endif

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Code') }}</th><th>{{ __('Zone') }}</th><th>{{ __('Valable jusqu\'au') }}</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($codes as $c)
                        <tr class="{{ $c->estValide() ? '' : 'text-muted' }}">
                            <td class="fw-bold" style="letter-spacing:1px;">{{ $c->code }}</td>
                            <td style="font-size:13px;">{{ $c->zone?->nom }}</td>
                            <td style="font-size:13px;">
                                {{ $c->valable_le->format('d/m/Y') }}
                                @unless($c->estValide())<span class="badge bg-secondary ms-1">{{ __('Expiré') }}</span>@endunless
                            </td>
                            <td class="text-end">
                                @if($peutEditer)
                                    <form action="{{ route('marche.codes.destroy', array_merge(['code' => $c->id], $mairieParam)) }}" method="POST"
                                          onsubmit="return confirm('{{ __('Supprimer ce code ?') }}')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger py-0">🗑</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">{{ __('Aucun code généré.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@include('partials.autorefresh', ['selector' => '#zoneDemandes'])
@endsection
