@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:820px;">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Tâche {{ $tache->reference }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('taches.edit', $tache) }}" class="btn btn-sm btn-outline-primary">✏️ Modifier</a>
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">← Retour</a>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3" style="font-size:14px;">
                <div class="col-md-6"><strong>Mairie :</strong> {{ $tache->mairie?->nom ?? '—' }}</div>
                <div class="col-md-6"><strong>Équipe :</strong> {{ $tache->service_label }}</div>
                <div class="col-md-6"><strong>Créée le :</strong> {{ $tache->created_at->format('d/m/Y H:i') }}</div>
                <div class="col-md-6"><strong>Créée par :</strong> {{ $tache->createur?->username ?? '—' }}</div>
                <div class="col-md-6"><strong>{{ __('Assignée à') }} :</strong> {{ $tache->assigne?->username ?? '—' }}</div>
                <div class="col-md-6">
                    <strong>Statut :</strong>
                    @php $couleurs = ['ouvert' => 'secondary', 'en_cours' => 'warning', 'fait' => 'success']; @endphp
                    <span class="badge bg-{{ $couleurs[$tache->statut] ?? 'secondary' }}">{{ $tache->statut_label }}</span>
                </div>
                <div class="col-md-6"><strong>Clôture prévue :</strong> {{ $tache->date_butoir->format('d/m/Y') }}</div>
                <div class="col-md-6"><strong>Clôturée le :</strong> {{ $tache->date_cloture?->format('d/m/Y H:i') ?? '—' }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header py-2" style="font-size:13px;font-weight:600;">{{ __('Photo de la tâche à faire') }}</div>
                <div class="card-body text-center">
                    @if($tache->photo_avant)
                        <a href="{{ asset('storage/' . $tache->photo_avant) }}" target="_blank">
                            <img src="{{ asset('storage/' . $tache->photo_avant) }}" class="img-fluid" style="max-height:260px;border-radius:8px;">
                        </a>
                    @else
                        <span class="text-muted">Aucune photo</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header py-2" style="font-size:13px;font-weight:600;">{{ __('Photo de la tâche une fois finie') }}</div>
                <div class="card-body text-center">
                    @if($tache->photo_apres)
                        <a href="{{ asset('storage/' . $tache->photo_apres) }}" target="_blank">
                            <img src="{{ asset('storage/' . $tache->photo_apres) }}" class="img-fluid" style="max-height:260px;border-radius:8px;">
                        </a>
                    @else
                        <span class="text-muted">Aucune photo</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($tache->description_instruction)
        <div class="card shadow-sm mb-3">
            <div class="card-header py-2" style="font-size:13px;font-weight:600;">Description & remarques (d'instruction)</div>
            <div class="card-body" style="font-size:14px;white-space:pre-wrap;">{{ $tache->description_instruction }}</div>
        </div>
    @endif

    @if($tache->description_cloture)
        <div class="card shadow-sm mb-3">
            <div class="card-header py-2" style="font-size:13px;font-weight:600;">Description & remarques (de clôture)</div>
            <div class="card-body" style="font-size:14px;white-space:pre-wrap;">{{ $tache->description_cloture }}</div>
        </div>
    @endif

</div>
@endsection
