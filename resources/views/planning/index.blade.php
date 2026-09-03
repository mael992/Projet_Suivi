@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    <a href="{{ route('apps') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">← {{ __('mgds.nav_apps') }}</a>
    <h1 class="h3 mb-1">🕒 {{ __('Planning') }} — {{ $mairie->nom }}</h1>
    <p class="text-muted mb-3" style="font-size:13px;">
        @if($peutGerer)
            {{ __('Heures de la semaine par agent. Les absences déclarées apparaissent automatiquement.') }}
        @else
            {{ __('Vos semaines de travail. Ouvrez une semaine pour la consulter et la signer.') }}
        @endif
    </p>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    @if($peutGerer)
        <form method="POST" action="{{ route('planning.store') }}" class="card shadow-sm mb-3">
            @csrf
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Numéro de la semaine') }} *</label>
                        <input type="number" name="semaine" class="form-control" min="1" max="53" required
                               value="{{ old('semaine', $defaut['semaine']) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Année') }} *</label>
                        <input type="number" name="annee" class="form-control" min="2020" max="2100" required
                               value="{{ old('annee', $defaut['annee']) }}">
                    </div>
                    <div class="col-md-3 d-grid">
                        <button class="btn btn-primary">+ {{ __('Ajouter un planning') }}</button>
                    </div>
                </div>
            </div>
        </form>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>{{ __('Semaine & Année') }}</th>
                        <th>{{ __('Date de début (Lundi)') }}</th>
                        <th>{{ __('Date de fin (Dimanche)') }}</th>
                        <th>{{ __('Agents') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($plannings as $planning)
                    <tr>
                        <td class="fw-semibold">{{ $planning->libelle() }}</td>
                        <td>{{ $planning->lundi()->format('d/m/Y') }}</td>
                        <td>{{ $planning->dimanche()->format('d/m/Y') }}</td>
                        <td>{{ $planning->lignes_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('planning.show', $planning) }}" class="btn btn-sm btn-primary">{{ __('Ouvrir') }}</a>
                            @if($peutGerer)
                                <a href="{{ route('planning.pdf', $planning) }}" class="btn btn-sm btn-outline-dark">📄 PDF</a>
                                <form method="POST" class="d-inline" action="{{ route('planning.destroy', $planning) }}"
                                      onsubmit="return confirm('{{ __('Supprimer ce planning ?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">{{ __('Supprimer') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('Aucun planning enregistré.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
