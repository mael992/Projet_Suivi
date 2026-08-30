@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:900px;">

    @include('admin.partials.onglets')

    <h2 class="h5 mb-1">🗄️ {{ __('Données des mairies (RGPD)') }}</h2>
    <p class="text-muted mb-3" style="font-size:14px;">
        {{ __('Une commune peut récupérer l\'intégralité de ses données avant de partir, ou en demander la destruction définitive avec attestation.') }}
    </p>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width:70px;">Réf.</th>
                        <th>{{ __('Mairie') }}</th>
                        <th class="text-center">{{ __('Utilisateurs') }}</th>
                        <th class="text-center">{{ __('Tâches') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($mairies as $mairie)
                    <tr>
                        <td class="fw-bold" style="color:var(--brand);">{{ $mairie->id }}</td>
                        <td class="fw-semibold">{{ $mairie->nom }} <span class="text-muted fw-normal">({{ $mairie->code_postal ?? '—' }})</span></td>
                        <td class="text-center">{{ $mairie->users_count }}</td>
                        <td class="text-center">{{ $mairie->taches_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.donnees.export', $mairie) }}" class="btn btn-sm btn-outline-dark">
                                ⬇ {{ __('Exporter (ZIP)') }}
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal" data-bs-target="#detruire{{ $mairie->id }}">
                                🗑 {{ __('Détruire') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('Aucune mairie enregistrée.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modales de destruction (confirmation par saisie du nom) --}}
@foreach($mairies as $mairie)
<div class="modal fade" id="detruire{{ $mairie->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.donnees.detruire', $mairie) }}" class="modal-content">
            @csrf
            <div class="modal-header py-2">
                <h5 class="modal-title text-danger" style="font-size:16px;">⚠️ {{ __('Destruction définitive') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:14px;">
                    {{ __('Toutes les données de') }} <strong>{{ $mairie->nom }}</strong>
                    {{ __('seront supprimées définitivement : utilisateurs, tâches, messages, marché, annuaire et fichiers déposés.') }}
                </p>
                <p style="font-size:14px;">
                    {{ __('Une attestation de destruction est téléchargée automatiquement à la fin de l\'opération.') }}
                    <strong>{{ __('Cette action est irréversible.') }}</strong>
                </p>
                <label class="form-label fw-semibold" style="font-size:13px;">
                    {{ __('Pour confirmer, saisissez exactement le nom de la mairie :') }}
                </label>
                <input type="text" name="confirmation" class="form-control" required
                       placeholder="{{ $mairie->nom }}" autocomplete="off">
                <small class="text-muted">{{ __('Pensez à faire l\'export ZIP avant, si la commune souhaite récupérer ses données.') }}</small>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                <button type="submit" class="btn btn-danger btn-sm">{{ __('Détruire définitivement') }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endsection
