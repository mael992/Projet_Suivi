@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')
<div class="container py-4" style="max-width:640px;">

    <h1 class="h4 mb-3">Ajouter une mairie</h1>

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('mairies.store') }}" class="card shadow-sm">
        @csrf
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Nom de la mairie *</label>
                    <input type="text" name="nom" value="{{ old('nom') }}" class="form-control" required placeholder="Mairie de …">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Adresse email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">{{ __('Indicatif') }}</label>
                    <select name="telephone_indicatif" class="form-select">
                        @foreach(Referentiel::INDICATIFS as $ind)
                            <option value="{{ $ind }}" @selected(old('telephone_indicatif', '+33') === $ind)>{{ $ind }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label fw-semibold">{{ __('Téléphone') }}</label>
                    <input type="text" name="telephone" value="{{ old('telephone') }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Date de fin d'abonnement *</label>
                    <input type="date" name="date_fin_abonnement" value="{{ old('date_fin_abonnement') }}" class="form-control" required>
                    <small class="text-muted">Passée cette date, les utilisateurs de la mairie ne pourront plus se connecter.</small>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Créer la mairie</button>
                <a href="{{ route('mairies.index') }}" class="btn btn-outline-secondary">{{ __('Annuler') }}</a>
            </div>
        </div>
    </form>
</div>
@endsection
