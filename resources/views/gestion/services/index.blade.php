@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:900px;">

    <a href="{{ route('gestion.utilisateurs.index') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">
        ← {{ __('Gestion des utilisateurs') }}
    </a>
    <h1 class="h3 mb-1">🏢 {{ __('Services de la mairie') }} — {{ $mairie->nom }}</h1>
    <p class="text-muted mb-3" style="font-size:14px;">
        {{ __('Adaptez la liste à votre organisation : renommez les services, décochez ceux que vous n\'utilisez pas, ou créez les vôtres.') }}
    </p>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('gestion.services.update') }}" class="card shadow-sm mb-3">
        @csrf @method('PUT')
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width:70px;">N°</th>
                        <th>{{ __('Nom du service dans votre mairie') }}</th>
                        <th style="width:110px;" class="text-center">{{ __('Agents') }}</th>
                        <th style="width:100px;" class="text-center">{{ __('Utilisé') }}</th>
                    </tr>
                </thead>
                <tbody>
                @php
                    // Tous les numéros connus : référentiel + services propres à la commune
                    $numeros = array_unique(array_merge(array_keys($defaut), $perso->keys()->all()));
                    sort($numeros);
                @endphp
                @foreach($numeros as $num)
                    @php
                        $ligne   = $perso->get($num);
                        $nom     = $ligne->nom ?? ($defaut[$num] ?? '');
                        $actif   = $ligne ? $ligne->actif : true;
                        $agents  = $effectifs[$num] ?? 0;
                        $surMesure = $ligne && ! array_key_exists($num, $defaut);
                    @endphp
                    <tr class="{{ $actif ? '' : 'table-light text-muted' }}">
                        <td class="fw-bold" style="color:var(--brand);">{{ $num }}</td>
                        <td>
                            <input type="text" name="services[{{ $num }}][nom]" value="{{ $nom }}"
                                   class="form-control form-control-sm" maxlength="100">
                            @if($surMesure)
                                <small class="text-muted">{{ __('Service propre à votre mairie') }}</small>
                            @elseif($nom !== ($defaut[$num] ?? $nom))
                                <small class="text-muted">{{ __('Renommé — par défaut :') }} {{ $defaut[$num] }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            {{ $agents }}
                            @if($agents > 0 && ! $actif)
                                <div class="text-danger" style="font-size:11px;">⚠️ {{ __('agents rattachés') }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            <input type="hidden" name="services[{{ $num }}][actif]" value="0">
                            <input type="checkbox" class="form-check-input" name="services[{{ $num }}][actif]" value="1" @checked($actif)>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">
                {{ __('Un service décoché disparaît des formulaires ; les données déjà enregistrées restent intactes.') }}
            </small>
            <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
        </div>
    </form>

    <form method="POST" action="{{ route('gestion.services.store') }}" class="card shadow-sm">
        @csrf
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label mb-1 fw-semibold" style="font-size:13px;">➕ {{ __('Ajouter un service propre à votre commune') }}</label>
                    <input type="text" name="nom" class="form-control form-control-sm" maxlength="100"
                           placeholder="{{ __('ex : Régie des eaux, Camping municipal…') }}" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-dark w-100">{{ __('Ajouter') }}</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
