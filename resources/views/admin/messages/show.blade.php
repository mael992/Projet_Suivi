@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    @include('admin.partials.onglets')

    <a href="{{ route('admin.messages.index', ['dossier' => $demande->statut]) }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">← {{ __('Message Support') }}</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3" style="max-width:1100px;">
        {{-- Réponses du questionnaire et identité de la personne --}}
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">🛟 {{ $demande->reference }} · {{ $demande->statut_label }}</div>
                <div class="card-body" style="font-size:14px;">
                    <div class="mb-2">
                        <div class="text-muted" style="font-size:12px;">{{ __('De') }}</div>
                        @if($demande->avec_compte)
                            <span class="badge bg-success">{{ __('Compte MGDS') }}</span>
                            {{ $demande->libelleDemandeur() }}
                        @else
                            <span class="badge bg-warning text-dark">{{ __('Sans compte MGDS') }}</span>
                            {{ trim($demande->prenom . ' ' . $demande->nom) }}<br>
                            <a href="mailto:{{ $demande->email }}">{{ $demande->email }}</a>
                        @endif
                    </div>
                    <div class="mb-2">
                        <div class="text-muted" style="font-size:12px;">{{ __('Le problème concerne') }}</div>
                        {{ $demande->concerne_label }}
                    </div>
                    @if($demande->precision)
                        <div class="mb-2">
                            <div class="text-muted" style="font-size:12px;">{{ __('Précision') }}</div>
                            <div style="white-space:pre-wrap;">{{ $demande->precision }}</div>
                        </div>
                    @endif
                    <div class="text-muted" style="font-size:12px;">{{ __('Ouverte le') }} {{ $demande->created_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                @include('support._conversation', ['vueAdmin' => true])

                <div class="card-footer">
                    @if($demande->estCloture())
                        <div class="text-muted" style="font-size:13px;">🔒 {{ __('Cette demande est clôturée.') }}</div>
                    @else
                        <form method="POST" action="{{ route('admin.messages.repondre', $demande) }}">
                            @csrf
                            <textarea name="corps" class="form-control mb-2" rows="4" required minlength="2" maxlength="5000"
                                      placeholder="{{ __('Votre réponse, signée « Admin »…') }}"></textarea>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">{{ __('La réponse est signée « Admin » : la personne ne sait pas quel admin lui répond.') }}</small>
                                <button type="submit" class="btn btn-primary">{{ __('Envoyer') }}</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.messages.cloturer', $demande) }}" class="mt-2"
                              onsubmit="return confirm('{{ __('Clôturer cette demande ?') }}')">
                            @csrf
                            <button class="btn btn-outline-dark btn-sm">🔒 {{ __('Clôturer') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@include('partials.autorefresh', ['selector' => '#zoneConversation'])
@endsection
