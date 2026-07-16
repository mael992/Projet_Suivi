@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width:720px;">
    <div class="card shadow-sm">
        <div class="card-body p-4 p-md-5 text-center">
            <div style="font-size:52px;line-height:1;" class="mb-3">🆕</div>
            <h1 class="h4 mb-4" style="color:var(--brand);">{{ __('mgds.nav_news') }}</h1>

            <p class="mb-3">{{ __('La rubrique Nouveautés est actuellement en cours de préparation.') }}</p>
            <p class="mb-3">{{ __('Elle sera disponible très prochainement et vous permettra de suivre les dernières évolutions, mises à jour et informations concernant MGDS.') }}</p>
            <p class="mb-4">{{ __('Nous vous remercions de votre patience.') }}</p>

            <p class="text-muted mb-0" style="font-size:14px;">
                {{ __('Cordialement,') }}<br>
                <strong>{{ __('L\'équipe MGDS') }}</strong>
            </p>
        </div>
    </div>
</div>
@endsection
