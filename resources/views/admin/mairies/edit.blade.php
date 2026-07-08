@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')
<div class="container py-4" style="max-width:720px;">

    <h1 class="h4 mb-3">Modifier — {{ $mairie->nom }}</h1>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('mairies.update', $mairie) }}" class="card shadow-sm mb-4">
        @csrf @method('PUT')
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Nom de la mairie *</label>
                    <input type="text" name="nom" value="{{ old('nom', $mairie->nom) }}" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Adresse email *</label>
                    <input type="email" name="email" value="{{ old('email', $mairie->email) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">{{ __('Indicatif') }}</label>
                    <select name="telephone_indicatif" class="form-select">
                        @foreach(Referentiel::INDICATIFS as $ind)
                            <option value="{{ $ind }}" @selected(old('telephone_indicatif', $mairie->telephone_indicatif) === $ind)>{{ $ind }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label fw-semibold">{{ __('Téléphone') }}</label>
                    <input type="text" name="telephone" value="{{ old('telephone', $mairie->telephone) }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Date de fin d'abonnement *</label>
                    <input type="date" name="date_fin_abonnement"
                           value="{{ old('date_fin_abonnement', $mairie->date_fin_abonnement?->format('Y-m-d')) }}"
                           class="form-control" required>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
                <a href="{{ route('mairies.index') }}" class="btn btn-outline-secondary">Retour</a>
            </div>
        </div>
    </form>

    {{-- Observateurs : copie de tous les mails de la mairie, sans limite --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Observateurs ({{ $mairie->observateurs->count() }})</span>
            <small class="text-muted">Ils reçoivent une copie de tous les e-mails de cette mairie (qui a fait quoi, passages en « fait », etc.)</small>
        </div>
        <div class="card-body">

            <form method="POST" action="{{ route('mairies.observateurs.store', $mairie) }}" class="row g-2 align-items-end mb-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label mb-1" style="font-size:12px;">Nom (optionnel)</label>
                    <input type="text" name="nom" value="{{ old('nom') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-5">
                    <label class="form-label mb-1" style="font-size:12px;">Adresse email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-dark w-100">+ Ajouter un observateur</button>
                </div>
            </form>

            @if($mairie->observateurs->isEmpty())
                <p class="text-muted mb-0" style="font-size:13px;">Aucun observateur pour le moment.</p>
            @else
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                    @foreach($mairie->observateurs as $observateur)
                        <tr>
                            <td>{{ $observateur->nom ?? '—' }}</td>
                            <td>{{ $observateur->email }}</td>
                            <td class="text-end">
                                <form action="{{ route('mairies.observateurs.destroy', [$mairie, $observateur]) }}" method="POST"
                                      onsubmit="return confirm('Retirer cet observateur ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">🗑</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

</div>
@endsection
