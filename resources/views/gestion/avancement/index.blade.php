@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    <a href="{{ route('dashboard') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">← {{ __('Suivi des tâches') }}</a>
    <h1 class="h3 mb-3">📊 {{ __('Avancement des tâches') }} — {{ $mairie->nom }}</h1>

    {{-- Filtrage par période --}}
    <form method="GET" action="{{ route('gestion.avancement') }}" class="card shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1" style="font-size:12px;">Début du filtrage</label>
                    <input type="date" name="date_debut" value="{{ request('date_debut') }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1" style="font-size:12px;">Fin du filtrage</label>
                    <input type="date" name="date_fin" value="{{ request('date_fin') }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <button type="submit" class="btn btn-sm btn-dark w-100">{{ __('Filtrer') }}</button>
                </div>
                @if(request('date_debut') || request('date_fin'))
                <div class="col-6 col-md-2">
                    <a href="{{ route('gestion.avancement') }}" class="btn btn-sm btn-outline-secondary w-100">{{ __('Réinitialiser') }}</a>
                </div>
                @endif
            </div>
        </div>
    </form>

    <div class="card shadow-sm" id="zoneAvancement">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Services</th>
                        <th class="text-center">Taux de charge de travail</th>
                        <th class="text-center">En cours de réalisation</th>
                        <th class="text-center">Déjà réalisées et clôturées</th>
                        <th class="text-center">À venir</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($stats as $ligne)
                    @php
                        $pct = fn ($n) => $ligne['total'] > 0 ? round($n / $ligne['total'] * 100) : 0;
                    @endphp
                    <tr class="{{ $ligne['total'] === 0 ? 'text-muted' : '' }}">
                        <td style="font-size:13px;">{{ $ligne['service'] }}</td>
                        <td class="text-center fw-semibold">{{ $ligne['total'] }}</td>
                        <td class="text-center">{{ $ligne['en_cours'] }} ({{ $pct($ligne['en_cours']) }}%)</td>
                        <td class="text-center">{{ $ligne['fait'] }} ({{ $pct($ligne['fait']) }}%)</td>
                        <td class="text-center">{{ $ligne['a_venir'] }} ({{ $pct($ligne['a_venir']) }}%)</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@include('partials.autorefresh', ['selector' => '#zoneAvancement'])
@endsection
