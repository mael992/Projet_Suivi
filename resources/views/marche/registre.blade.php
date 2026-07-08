@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    <h1 class="h3 mb-3">🛍️ Marché — {{ $mairie->nom }}</h1>

    @include('marche.partials.onglets')

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif

    {{-- ── Filtres : activité + période ── --}}
    <form method="GET" action="{{ route('marche.registre') }}" class="card shadow-sm mb-4">
        @if(request('mairie'))<input type="hidden" name="mairie" value="{{ request('mairie') }}">@endif
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1" style="font-size:12px;">Activité</label>
                    <select name="activite" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        @foreach($activites as $a)
                            <option value="{{ $a }}" @selected(request('activite') === $a)>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1" style="font-size:12px;">Du</label>
                    <input type="date" name="date_debut" value="{{ request('date_debut') }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1" style="font-size:12px;">Au</label>
                    <input type="date" name="date_fin" value="{{ request('date_fin') }}" class="form-control form-control-sm">
                </div>
                <div class="col-3 col-md-1">
                    <button class="btn btn-sm btn-dark w-100">Filtrer</button>
                </div>
                @if(request('activite') || request('date_debut') || request('date_fin'))
                <div class="col-3 col-md-2">
                    <a href="{{ route('marche.registre', request()->only('mairie')) }}" class="btn btn-sm btn-outline-secondary w-100">Réinitialiser</a>
                </div>
                @endif
            </div>
        </div>
    </form>

    {{-- ── Répartition par activité sur la période ── --}}
    @if($parActivite->isNotEmpty())
    <div class="row g-3 mb-4">
        @foreach($parActivite as $activite => $s)
            <div class="col-6 col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body py-3 text-center">
                        <div class="fw-semibold" style="font-size:14px;">{{ $activite }}</div>
                        <div class="text-muted" style="font-size:12px;">
                            {{ $s['commercants'] }} commerçant(s) · {{ $s['venues'] }} venue(s)
                        </div>
                        <div class="fw-bold mt-1" style="color:var(--gold);">{{ number_format($s['montant'], 2, ',', ' ') }} €</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- ── Statistiques par commerçant ── --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header fw-semibold">🏦 Par commerçant (sur la période filtrée)</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:14px;">
                <thead class="table-dark">
                    <tr>
                        <th>Nom</th><th>Prénom</th><th>Activité</th>
                        <th class="text-center">Venues</th>
                        <th class="text-center">Dernière venue</th>
                        <th class="text-end">Rapporté à la mairie</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($stats as $ligne)
                    <tr>
                        <td class="fw-semibold">{{ $ligne['commercant']?->nom }}</td>
                        <td>{{ $ligne['commercant']?->prenom ?? '—' }}</td>
                        <td>{{ $ligne['commercant']?->activite }}</td>
                        <td class="text-center">{{ $ligne['nb_venues'] }}</td>
                        <td class="text-center">{{ $ligne['derniere_venue']?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-end fw-semibold">{{ number_format($ligne['total_montant'], 2, ',', ' ') }} €</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucune venue sur cette période.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Historique détaillé des venues ── --}}
    <div class="card shadow-sm">
        <div class="card-header fw-semibold">🗓 Historique des venues</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th>Date</th><th>Commerçant</th><th>Activité</th><th>Axe</th><th>Longueur</th><th class="text-end">Montant</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($venues as $venue)
                    <tr>
                        <td>{{ $venue->planParent()?->date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="fw-semibold">{{ $venue->commercant?->full_name ?? ($venue->label ? 'Empl. ' . $venue->label : '—') }}</td>
                        <td>{{ $venue->commercant?->activite ?? '—' }}</td>
                        <td>{{ $venue->axe?->nom ?? ($venue->label ? 'Plan — ' . $venue->label : 'Plan') }}</td>
                        <td>{{ $venue->longueur ? $venue->longueur . ' m' : '—' }}</td>
                        <td class="text-end">{{ $venue->montant !== null ? number_format($venue->montant, 2, ',', ' ') . ' €' : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucune venue enregistrée.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
