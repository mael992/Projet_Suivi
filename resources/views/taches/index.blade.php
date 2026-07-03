@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h3 mb-0">
            Tableau des anomalies
            @if(!auth()->user()->isAdmin() && auth()->user()->mairie)
                — {{ auth()->user()->mairie->nom }}
            @endif
        </h1>
        @if(auth()->user()->peutGererTaches())
            <a href="{{ route('taches.create') }}" class="btn btn-primary">+ Ajouter une tâche</a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    {{-- Filtres --}}
    <form method="GET" action="{{ route('dashboard') }}" class="card shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:12px;">Statut</label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach(Referentiel::STATUTS as $key => $label)
                            <option value="{{ $key }}" @selected(request('statut') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->voitTousLesServices())
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1" style="font-size:12px;">Équipe / Service</label>
                    <select name="service" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach(Referentiel::SERVICES as $num => $label)
                            <option value="{{ $num }}" @selected(request('service') == $num)>{{ $num }} — {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if(auth()->user()->isAdmin() && $mairies->isNotEmpty())
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:12px;">Mairie</label>
                    <select name="mairie" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        @foreach($mairies as $m)
                            <option value="{{ $m->id }}" @selected(request('mairie') == $m->id)>{{ $m->nom }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:12px;">Du</label>
                    <input type="date" name="date_debut" value="{{ request('date_debut') }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:12px;">Au</label>
                    <input type="date" name="date_fin" value="{{ request('date_fin') }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:12px;">Recherche</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Réf, utilisateur…">
                </div>
                <div class="col-6 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-dark w-100">Filtrer</button>
                </div>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Réf</th>
                        @if(auth()->user()->isAdmin())<th>Mairie</th>@endif
                        <th>Date de création</th>
                        <th>Photo de la tâche à faire</th>
                        <th>Photo une fois finie</th>
                        <th>Date et heure de clôture</th>
                        <th>Équipe</th>
                        <th>Assignée à</th>
                        <th>Statut</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($taches as $tache)
                    <tr>
                        <td class="fw-semibold">{{ $tache->reference }}</td>
                        @if(auth()->user()->isAdmin())<td>{{ $tache->mairie?->nom ?? '—' }}</td>@endif
                        <td>{{ $tache->created_at->format('d/m/Y') }}</td>
                        <td>
                            @if($tache->photo_avant)
                                <a href="{{ asset('storage/' . $tache->photo_avant) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $tache->photo_avant) }}" alt="Avant" style="height:42px;width:56px;object-fit:cover;border-radius:6px;">
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($tache->photo_apres)
                                <a href="{{ asset('storage/' . $tache->photo_apres) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $tache->photo_apres) }}" alt="Après" style="height:42px;width:56px;object-fit:cover;border-radius:6px;">
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $tache->date_cloture?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td style="font-size:13px;">{{ $tache->service_label }}</td>
                        <td>{{ $tache->assigne?->username ?? '—' }}</td>
                        <td>
                            @php
                                $couleurs = ['ouvert' => 'secondary', 'en_cours' => 'warning', 'fait' => 'success'];
                            @endphp
                            <span class="badge bg-{{ $couleurs[$tache->statut] ?? 'secondary' }}">{{ $tache->statut_label }}</span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('taches.show', $tache) }}" class="btn btn-outline-secondary">👁 Voir</a>
                                <a href="{{ route('taches.edit', $tache) }}" class="btn btn-outline-primary">✏️ Modifier</a>
                                @if(auth()->user()->peutGererTaches())
                                <form action="{{ route('taches.destroy', $tache) }}" method="POST"
                                      onsubmit="return confirm('⚠️ Supprimer la tâche {{ $tache->reference }} ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger" type="submit">🗑 Supprimer</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isAdmin() ? 10 : 9 }}" class="text-center text-muted py-4">
                            Aucune tâche pour le moment.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
