@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')
<div class="container py-4" style="max-width:680px;">

    <h1 class="h4 mb-1">Modifier {{ $user->username }} — {{ $mairie->nom }}</h1>
    <p class="text-muted mb-3" style="font-size:13px;">Référence : {{ $user->reference ?? '—' }}</p>

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('gestion.utilisateurs.update', $user) }}" class="card shadow-sm">
        @csrf @method('PUT')
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('Prénom') }} *</label>
                    <input type="text" name="prenom" value="{{ old('prenom', $user->prenom) }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('Nom') }} *</label>
                    <input type="text" name="nom" value="{{ old('nom', $user->nom) }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Service (équipe) *</label>
                    <select name="service" class="form-select" required>
                        @foreach(Referentiel::SERVICES as $num => $label)
                            <option value="{{ $num }}" @selected(old('service', $user->service) == $num)>{{ $num }} — {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('Statut') }} *</label>
                    <select name="grade" class="form-select" required>
                        @foreach(Referentiel::GRADES as $num => $label)
                            <option value="{{ $num }}" @selected(old('grade', $user->grade) == $num)>{{ $num }}. {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">{{ __('Indicatif') }}</label>
                    <select name="telephone_indicatif" class="form-select">
                        @foreach(Referentiel::INDICATIFS as $ind)
                            <option value="{{ $ind }}" @selected(old('telephone_indicatif', $user->telephone_indicatif) === $ind)>{{ $ind }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">{{ __('Numéro de téléphone') }}</label>
                    <input type="text" name="telephone" value="{{ old('telephone', $user->telephone) }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">{{ __('Adresse mail') }}</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">{{ __('Nouveau mot de passe') }} provisoire</label>
                    <input type="text" name="password" class="form-control" minlength="8" placeholder="Laisser vide pour ne pas changer">
                    <small class="text-muted">S'il est renseigné, l'utilisateur devra le changer à sa prochaine connexion.</small>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
                <a href="{{ route('gestion.utilisateurs.index') }}" class="btn btn-outline-secondary">{{ __('Annuler') }}</a>
            </div>
        </div>
    </form>
</div>
@endsection
