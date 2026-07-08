@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')
<div class="container py-4" style="max-width:680px;">

    <h1 class="h4 mb-1">Ajouter un utilisateur — {{ $mairie->nom }}</h1>
    <p class="text-muted mb-3" style="font-size:13px;">
        L'identifiant <strong>prenom.nom</strong> est généré automatiquement. S'il existe déjà
        (même dans une autre mairie), un numéro est ajouté : prenom.nom1, prenom.nom2…
    </p>

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('gestion.utilisateurs.store') }}" class="card shadow-sm">
        @csrf
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('Prénom') }} *</label>
                    <input type="text" name="prenom" value="{{ old('prenom') }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('Nom') }} *</label>
                    <input type="text" name="nom" value="{{ old('nom') }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Service (équipe) *</label>
                    <select name="service" class="form-select" required>
                        <option value="">— Sélectionnez —</option>
                        @foreach(Referentiel::SERVICES as $num => $label)
                            <option value="{{ $num }}" @selected(old('service') == $num)>{{ $num }} — {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('Statut') }} *</label>
                    <select name="grade" class="form-select" required>
                        <option value="">— Sélectionnez —</option>
                        @foreach(Referentiel::GRADES as $num => $label)
                            <option value="{{ $num }}" @selected(old('grade') == $num)>{{ $num }}. {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">{{ __('Indicatif') }}</label>
                    <select name="telephone_indicatif" class="form-select">
                        @foreach(Referentiel::INDICATIFS as $ind)
                            <option value="{{ $ind }}" @selected(old('telephone_indicatif', '+33') === $ind)>{{ $ind }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">{{ __('Numéro de téléphone') }}</label>
                    <input type="text" name="telephone" value="{{ old('telephone') }}" class="form-control" placeholder="6 12 34 56 78">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">{{ __('Adresse mail') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Mot de passe provisoire *</label>
                    <input type="text" name="password" value="{{ old('password') }}" class="form-control" required minlength="8">
                    <small class="text-muted">L'utilisateur devra le changer à sa première connexion (valable 48h).</small>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
                <a href="{{ route('gestion.utilisateurs.index') }}" class="btn btn-outline-secondary">{{ __('Annuler') }}</a>
            </div>
        </div>
    </form>
</div>
@endsection
