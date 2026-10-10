@extends('layouts.app')

{{-- Double authentification : saisie du code reçu par e-mail (connexion,
     modification importante du compte, ou « J'ai déjà un ticket »). --}}
@section('content')
<div class="container py-4" style="max-width:480px;">

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-2">🔐 {{ $titre }}</h1>
            <p class="text-muted" style="font-size:14px;">
                {{ __('Un code à 6 chiffres vient d\'être envoyé à') }} <strong>{{ $email }}</strong>.
                {{ __('Il est valable :minutes minutes.', ['minutes' => $minutes]) }}
            </p>

            @if(session('a2f_ok'))
                <div class="alert alert-success py-2" style="font-size:13px;">{{ session('a2f_ok') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger py-2" style="font-size:13px;">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ $action }}">
                @csrf
                <label for="code" class="form-label fw-semibold">{{ __('Code de vérification') }}</label>
                <input type="text" name="code" id="code" class="form-control form-control-lg text-center mb-3"
                       style="letter-spacing:6px;" inputmode="numeric" autocomplete="one-time-code"
                       pattern="[0-9 ]*" maxlength="7" required autofocus>

                @if($confiance)
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="confiance" value="1" id="confiance">
                        <label class="form-check-label" for="confiance" style="font-size:13px;">
                            {{ __('Je fais confiance à cet appareil : ne plus demander de code pendant 30 jours') }}
                        </label>
                        <div class="text-muted" style="font-size:12px;">
                            {{ __('Le code sera redemandé si vous changez de navigateur ou d\'adresse IP (autre réseau, box redémarrée…). À ne pas cocher sur un ordinateur partagé.') }}
                        </div>
                    </div>
                @endif

                <button type="submit" class="btn btn-primary w-100">{{ __('Valider') }}</button>
            </form>

            <div class="d-flex justify-content-between align-items-center mt-3" style="font-size:13px;">
                <form method="POST" action="{{ $renvoi }}">
                    @csrf
                    <button type="submit" class="btn btn-link btn-sm p-0">{{ __('Renvoyer un code') }}</button>
                </form>
                <a href="{{ $annulation }}" class="text-muted">{{ __('Annuler') }}</a>
            </div>
        </div>
    </div>

    <p class="text-muted text-center mt-3" style="font-size:12px;">
        {{ __('Vous ne recevez rien ? Pensez à regarder dans les courriers indésirables.') }}
    </p>
</div>
@endsection
