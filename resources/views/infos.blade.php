@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width:720px;">
    <div class="card shadow-sm">
        <div class="card-body p-4 p-md-5 text-center">
            <div style="font-size:52px;line-height:1;" class="mb-3">🚧</div>
            <h1 class="h4 mb-4" style="color:var(--brand);">{{ __('Site en cours de développement') }}</h1>

            <p class="mb-3">{{ __('Bienvenue sur MGDS – Mairie Gestion Des Services.') }}</p>
            <p class="mb-3">{{ __('Notre plateforme est actuellement en cours de finalisation. Nous apportons les dernières améliorations à notre site web et à nos applications afin de vous proposer un service fiable, sécurisé et performant.') }}</p>
            <p class="mb-3">{{ __('Durant cette phase de déploiement, le site est temporairement fermé au grand public.') }}</p>
            <p class="mb-4">{{ __('Nous vous remercions de votre compréhension et de votre patience.') }}</p>

            <p class="text-muted mb-0" style="font-size:14px;">
                {{ __('Cordialement,') }}<br>
                <strong>{{ __('L\'équipe MGDS') }}</strong>
            </p>
        </div>
    </div>
</div>
@endsection
