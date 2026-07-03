@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')
<div class="container py-4" style="max-width:720px;">

    <h1 class="h4 mb-1">Modifier la tâche {{ $tache->reference }}</h1>
    <p class="text-muted mb-3">{{ $tache->service_label }} — {{ $tache->mairie?->nom }}</p>

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('taches.update', $tache) }}" enctype="multipart/form-data" class="card shadow-sm">
        @csrf @method('PUT')
        <div class="card-body">

            @unless($employeSeul)
                <div class="mb-3">
                    <label class="form-label fw-semibold">Responsabilité (utilisateur chargé de réaliser la tâche)</label>
                    <select name="user_id" class="form-select">
                        <option value="">— Personne —</option>
                        @foreach(($usersService[(string) $tache->service] ?? []) as $u)
                            <option value="{{ $u['id'] }}" @selected(old('user_id', $tache->user_id) == $u['id'])>{{ $u['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Clôture prévue (date butoir) *</label>
                    <input type="date" name="date_butoir" value="{{ old('date_butoir', $tache->date_butoir->format('Y-m-d')) }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Photo de la tâche à faire</label>
                    @if($tache->photo_avant)
                        <div class="mb-2">
                            <img src="{{ asset('storage/' . $tache->photo_avant) }}" style="height:80px;border-radius:8px;">
                        </div>
                    @endif
                    <input type="file" name="photo_avant" class="form-control" accept="image/*">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Description & remarques (d'instruction)</label>
                    <textarea name="description_instruction" rows="3" class="form-control">{{ old('description_instruction', $tache->description_instruction) }}</textarea>
                </div>
            @else
                {{-- Côté employé : lecture seule des instructions --}}
                @if($tache->photo_avant)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Photo de la tâche à faire</label><br>
                        <img src="{{ asset('storage/' . $tache->photo_avant) }}" style="height:100px;border-radius:8px;">
                    </div>
                @endif
                @if($tache->description_instruction)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Instructions</label>
                        <div class="border rounded p-2 bg-light" style="font-size:14px;">{{ $tache->description_instruction }}</div>
                    </div>
                @endif
            @endunless

            <div class="mb-3">
                <label class="form-label fw-semibold">Statut</label>
                <select name="statut" class="form-select" required>
                    @foreach(Referentiel::STATUTS as $key => $label)
                        @php
                            // Un employé ne peut pas rouvrir une tâche déjà faite
                            $bloque = $employeSeul && $tache->estFaite() && $key !== Referentiel::STATUT_FAIT;
                        @endphp
                        <option value="{{ $key }}" @selected(old('statut', $tache->statut) === $key) @disabled($bloque)>{{ $label }}</option>
                    @endforeach
                </select>
                <small class="text-muted">La date et l'heure de clôture sont automatiques dès que le statut passe à « Fait ».</small>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Photo de la tâche une fois finie
                    @if($tache->photoApresObligatoire())
                        <span class="text-danger">* (obligatoire pour clôturer : une photo « à faire » existe)</span>
                    @else
                        <span class="text-muted">(optionnelle)</span>
                    @endif
                </label>
                @if($tache->photo_apres)
                    <div class="mb-2">
                        <img src="{{ asset('storage/' . $tache->photo_apres) }}" style="height:80px;border-radius:8px;">
                    </div>
                @endif
                <input type="file" name="photo_apres" class="form-control" accept="image/*">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description & remarques (de clôture)</label>
                <textarea name="description_cloture" rows="3" class="form-control">{{ old('description_cloture', $tache->description_cloture) }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </div>
    </form>
</div>
@endsection
