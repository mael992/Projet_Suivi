@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')
<div class="container py-4" style="max-width:760px;">

    <h1 class="h3 mb-3">✉️ {{ __('Contacter votre Mairie') }}</h1>

    @if(session('ticket_ok'))
        <div class="alert alert-success">
            ✅ {{ __('Votre demande a bien été envoyée.') }}
            {{ __('Votre numéro de ticket est') }} <strong>{{ session('ticket_ok') }}</strong>.
            {{ __('Conservez-le pour suivre votre demande.') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link active" onclick="ongletContact('aide', this)">🆘 {{ __('J\'ai besoin d\'aide') }}</button></li>
        <li class="nav-item"><button class="nav-link" onclick="ongletContact('ticket', this)">🎫 {{ __('J\'ai déjà un ticket') }}</button></li>
    </ul>

    {{-- Onglet 1 : nouvelle demande --}}
    <div id="ongletAide">
        <form method="POST" action="{{ route('contact.mairie.store') }}" enctype="multipart/form-data" class="card shadow-sm">
            @csrf
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Votre ville ou village') }} *</label>
                        <select name="mairie_id" class="form-select" required>
                            <option value="">— {{ __('Sélectionnez') }} —</option>
                            @foreach($mairies as $m)
                                <option value="{{ $m->id }}" @selected(old('mairie_id') == $m->id)>{{ $m->nom }} ({{ $m->code_postal }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Service à contacter') }} *</label>
                        <select name="service" class="form-select" required>
                            <option value="">{{ __('Je ne sais pas') }}</option>
                            @foreach($services as $num => $label)
                                <option value="{{ $num }}" @selected(old('service') == $num)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Nom') }} *</label>
                        <input type="text" name="nom" value="{{ old('nom') }}" class="form-control" required minlength="2">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Prénom') }} *</label>
                        <input type="text" name="prenom" value="{{ old('prenom') }}" class="form-control" required minlength="2">
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
                        <label class="form-label fw-semibold">{{ __('Numéro de téléphone') }} *</label>
                        <input type="text" name="telephone" value="{{ old('telephone') }}" class="form-control" required minlength="6">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Adresse e-mail') }} *</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">{{ __('Sujet') }} *</label>
                        <input type="text" name="sujet" value="{{ old('sujet') }}" class="form-control" required minlength="2"
                               placeholder="{{ __('ex : Problème de voirie, rendez-vous…') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">{{ __('Décrivez au maximum et en détaillant votre problème') }} *</label>
                        <textarea name="message" rows="5" class="form-control" required minlength="2">{{ old('message') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">{{ __('Photos (2 maximum)') }}</label>
                        <input type="file" name="photos[]" class="form-control" accept="image/*" multiple
                               onchange="if(this.files.length>2){alert('{{ __('2 photos maximum.') }}');this.value='';}">
                    </div>
                </div>

                <p class="text-muted mt-3 mb-0" style="font-size:12px;">
                    {{ __('Tous les champs sont obligatoires. Pas de champ vide ni rempli d\'un seul caractère.') }}
                </p>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">{{ __('Envoyer ma demande') }}</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Onglet 2 : suivi d'un ticket existant (à venir) --}}
    <div id="ongletTicket" class="d-none">
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                🎫 {{ __('Le suivi de ticket sera bientôt disponible. Conservez votre numéro de ticket ; votre mairie vous recontactera par e-mail.') }}
            </div>
        </div>
    </div>
</div>

<script>
function ongletContact(onglet, btn) {
    document.querySelectorAll('.nav-tabs .nav-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('ongletAide').classList.toggle('d-none', onglet !== 'aide');
    document.getElementById('ongletTicket').classList.toggle('d-none', onglet !== 'ticket');
}
</script>
@endsection
