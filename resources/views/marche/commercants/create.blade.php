@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')
@php $mairieParam = request('mairie'); @endphp

<div class="container py-4" style="max-width:680px;">

    <h1 class="h4 mb-1">{{ __('Ajouter un commerçant') }} — {{ $mairie->nom }}</h1>
    <p class="text-muted mb-3" style="font-size:13px;">
        Il apparaîtra ensuite dans le plan (placement) et dans le registre.
    </p>

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('marche.commercants.store') }}" class="card shadow-sm">
        @csrf
        @if($mairieParam)<input type="hidden" name="mairie" value="{{ $mairieParam }}">@endif
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('Nom') }} *</label>
                    <input type="text" name="nom" value="{{ old('nom') }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('Prénom') }}</label>
                    <input type="text" name="prenom" value="{{ old('prenom') }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('Activité') }} *</label>
                    <input type="text" name="activite" value="{{ old('activite') }}" class="form-control" placeholder="Fleuriste, vêtements, food truck…" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Longueur du stand par défaut (m) *</label>
                    <input type="number" name="longueur_defaut" value="{{ old('longueur_defaut', 3) }}" step="0.5" min="0.5" class="form-control" required>
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
                    <input type="text" name="telephone" value="{{ old('telephone') }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">{{ __('Adresse mail') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">{{ __('Créer le commerçant') }}</button>
                <a href="{{ route('marche.commercants', request()->only('mairie')) }}" class="btn btn-outline-secondary">{{ __('Annuler') }}</a>
            </div>
        </div>
    </form>
</div>
@endsection
