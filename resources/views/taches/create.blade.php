@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')
<div class="container py-4" style="max-width:720px;">

    <h1 class="h4 mb-3">{{ __('Ajouter une tâche') }}</h1>

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('taches.store') }}" enctype="multipart/form-data" class="card shadow-sm">
        @csrf
        <div class="card-body">

            @if(auth()->user()->isAdmin())
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Mairie') }} *</label>
                    <select name="mairie_id" class="form-select" required>
                        <option value="">— Sélectionnez —</option>
                        @foreach($mairies as $m)
                            <option value="{{ $m->id }}" @selected(old('mairie_id') == $m->id)>{{ $m->nom }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Mairie') }}</label>
                    <input type="text" class="form-control" value="{{ auth()->user()->mairie?->nom }}" disabled>
                    <small class="text-muted">Formatée automatiquement : c'est la mairie à laquelle vous êtes affecté.</small>
                </div>
            @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('Service chargé de réaliser la tâche *') }}</label>
                <select name="service" id="serviceSelect" class="form-select" required onchange="filtrerUsers()">
                    <option value="">— Sélectionnez —</option>
                    @foreach(Referentiel::SERVICES as $num => $label)
                        <option value="{{ $num }}" @selected(old('service') == $num)>{{ $num }} — {{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('Responsable chargé de la tâche') }} *</label>
                <select name="user_id" id="userSelect" class="form-select" required>
                    <option value="">— {{ __('Sélectionnez') }} —</option>
                </select>
                <small class="text-muted">{{ __('Cette personne recevra un email et devra prendre en charge la tâche ou la substituer à un employé.') }}</small>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('Clôture prévue (date butoir) *') }}</label>
                <input type="date" name="date_butoir" value="{{ old('date_butoir') }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('Photo de la tâche à faire') }} <span class="text-muted">(optionnelle)</span></label>
                <input type="file" name="photo_avant" class="form-control" accept="image/*">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description & remarques (d'instruction)</label>
                <textarea name="description_instruction" rows="4" class="form-control">{{ old('description_instruction') }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('Créer la tâche') }}</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">{{ __('Annuler') }}</a>
            </div>
        </div>
    </form>
</div>

<script>
// Utilisateurs groupés par service (clé = "service" ou "mairie:service" pour l'admin)
const usersService = @json($usersService);
const isAdmin = @json(auth()->user()->isAdmin());

function filtrerUsers() {
    const service = document.getElementById('serviceSelect').value;
    const mairie  = document.querySelector('[name=mairie_id]')?.value ?? '';
    const cle     = isAdmin ? (mairie + ':' + service) : service;
    const select  = document.getElementById('userSelect');

    select.innerHTML = '<option value="">— Personne (le responsable du service affectera) —</option>';
    (usersService[cle] ?? []).forEach(u => {
        const opt = document.createElement('option');
        opt.value = u.id;
        opt.textContent = u.label;
        select.appendChild(opt);
    });
}

document.querySelector('[name=mairie_id]')?.addEventListener('change', filtrerUsers);
filtrerUsers();
</script>
@endsection
