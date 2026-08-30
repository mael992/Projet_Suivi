@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')
<div class="container py-4" style="max-width:760px;">

    <h1 class="h3 mb-3">🛍️ {{ __('Marché — exposants') }}</h1>

    @if(session('demande_ok'))
        <div class="alert alert-success">✅ {{ session('demande_ok') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link active" onclick="ongletMarche('rejoindre', this)">🤝 {{ __('Rejoindre le marché') }}</button></li>
        <li class="nav-item"><button class="nav-link" onclick="ongletMarche('plan', this)">🗺️ {{ __('Voir le plan du marché') }}</button></li>
    </ul>

    {{-- Demande d'inscription --}}
    <div id="ongletRejoindre">
        @if($mairies->isEmpty())
            <div class="card shadow-sm"><div class="card-body text-center text-muted py-5">
                {{ __('Aucune commune n\'accepte les inscriptions en ligne pour le moment. Contactez directement votre mairie.') }}
            </div></div>
        @else
            <form method="POST" action="{{ route('marche.public.store') }}" class="card shadow-sm">
                @csrf
                <div class="card-body">
                    <p class="text-muted" style="font-size:14px;">
                        {{ __('Vous êtes commerçant et souhaitez rejoindre un marché ? Remplissez ce formulaire : la mairie vous recontactera.') }}
                    </p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __('Commune') }} *</label>
                            <select name="mairie_id" class="form-select" required>
                                <option value="">— {{ __('Sélectionnez') }} —</option>
                                @foreach($mairies as $m)
                                    <option value="{{ $m->id }}" @selected(old('mairie_id') == $m->id)>{{ $m->nom }} ({{ $m->code_postal }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Prénom') }} *</label>
                            <input type="text" name="prenom" value="{{ old('prenom') }}" class="form-control" required minlength="2">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Nom') }} *</label>
                            <input type="text" name="nom" value="{{ old('nom') }}" class="form-control" required minlength="2">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Société') }}</label>
                            <input type="text" name="societe" value="{{ old('societe') }}" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Activité') }} *</label>
                            <input type="text" name="activite" value="{{ old('activite') }}" class="form-control" required minlength="2"
                                   placeholder="{{ __('ex : Fromager, Primeur, Vêtements…') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">{{ __('Indicatif') }}</label>
                            <select name="telephone_indicatif" class="form-select">
                                @foreach(Referentiel::INDICATIFS as $ind)
                                    <option value="{{ $ind }}" @selected(old('telephone_indicatif', '+33') === $ind)>{{ $ind }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('Téléphone') }} *</label>
                            <input type="text" name="telephone" value="{{ old('telephone') }}" class="form-control" required minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Adresse e-mail') }} *</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('Longueur souhaitée (m)') }}</label>
                            <input type="number" step="0.5" min="1" max="50" name="longueur_souhaitee"
                                   value="{{ old('longueur_souhaitee') }}" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __('Message') }}</label>
                            <textarea name="message" rows="4" class="form-control" maxlength="2000">{{ old('message') }}</textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">{{ __('Envoyer ma demande') }}</button>
                </div>
            </form>
        @endif
    </div>

    {{-- Accès au plan par code --}}
    <div id="ongletPlan" class="d-none">
        <form method="POST" action="{{ route('marche.public.plan') }}" class="card shadow-sm">
            @csrf
            <div class="card-body">
                <p class="text-muted" style="font-size:14px;">
                    {{ __('Saisissez le code remis par la mairie pour consulter le plan du marché. Il reste valable jusqu\'au jour du marché.') }}
                </p>
                <div class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Code d\'accès') }} *</label>
                        <input type="text" name="code" value="{{ old('code') }}" class="form-control text-uppercase"
                               maxlength="8" required placeholder="ABC123">
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary">{{ __('Voir le plan') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function ongletMarche(onglet, btn) {
    document.querySelectorAll('.nav-tabs .nav-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('ongletRejoindre').classList.toggle('d-none', onglet !== 'rejoindre');
    document.getElementById('ongletPlan').classList.toggle('d-none', onglet !== 'plan');
}
</script>
@endsection
