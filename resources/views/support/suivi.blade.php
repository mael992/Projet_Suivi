@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:720px;">

    <a href="{{ route('contact') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">← {{ __('Contacter le Support technique') }}</a>
    <h1 class="h4 mb-1">🛟 {{ __('Demande') }} {{ $demande->reference }} — {{ $demande->concerne_label }}</h1>
    <p class="text-muted mb-3" style="font-size:13px;">
        {{ $demande->statut_label }} · {{ $demande->created_at->format('d/m/Y H:i') }}
    </p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @unless($demande->avec_compte)
        <div class="alert alert-info py-2" style="font-size:13px;">
            🔒 {{ __('Gardez le lien de cette page : il vous permet de suivre votre demande. Nous vous l\'avons aussi envoyé par e-mail.') }}
        </div>
    @endunless

    <div class="card shadow-sm mb-3">
        @include('support._conversation', ['vueAdmin' => false])

        <div class="card-footer">
            @if($demande->estCloture())
                <div class="alert alert-secondary py-2 mb-0" style="font-size:13px;">
                    🔒 {{ __('Cette demande est clôturée. Pour un nouveau problème, ouvrez une nouvelle demande depuis la page Contact.') }}
                </div>
            @else
                <form method="POST" action="{{ route('support.repondre', $demande->jeton) }}">
                    @csrf
                    <textarea name="corps" class="form-control mb-2" rows="4" required minlength="2" maxlength="5000"
                              placeholder="{{ __('Écrire un message au support…') }}"></textarea>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">{{ __('Envoyer') }}</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('support.cloturer', $demande->jeton) }}" class="mt-2"
                      onsubmit="return confirm('{{ __('Clôturer votre demande au support ?') }}')">
                    @csrf
                    <button class="btn btn-outline-dark btn-sm">🔒 {{ __('Je n\'ai plus besoin d\'aide — clôturer') }}</button>
                </form>
            @endif
        </div>
    </div>
</div>
@include('partials.autorefresh', ['selector' => '#zoneConversation'])
@endsection
