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
        @if($mairiesMarche->isNotEmpty())
            <li class="nav-item"><button class="nav-link" onclick="ongletContact('marche', this)">🛍️ {{ __('Demande adhésion marché') }}</button></li>
        @endif
    </ul>

    {{-- Onglet 1 : nouvelle demande --}}
    <div id="ongletAide">
        <form method="POST" action="{{ route('contact.mairie.store') }}" enctype="multipart/form-data" class="card shadow-sm">
            @csrf
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">{{ __('Votre ville ou village') }} *</label>
                        <select name="mairie_id" id="mairieSelect" class="form-select" required
                                @disabled($mairies->isEmpty())>
                            <option value="">— {{ __('Sélectionnez') }} —</option>
                            @foreach($mairies as $m)
                                <option value="{{ $m->id }}" @selected(old('mairie_id') == $m->id)>{{ $m->nom }} ({{ $m->code_postal }})</option>
                            @endforeach
                        </select>
                        @error('mairie_id')<div class="text-danger" style="font-size:13px;">{{ $message }}</div>@enderror
                        @if($mairies->isEmpty())
                            <small class="text-danger">
                                {{ __('Aucune mairie ne reçoit de demandes en ligne pour le moment.') }}
                            </small>
                        @else
                            <small class="text-muted">
                                {{ __('Votre demande sera orientée vers le bon service par la mairie : vous n\'avez rien d\'autre à choisir.') }}
                            </small>
                        @endif
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

    {{-- Onglet 2 : suivi d'un ticket existant --}}
    <div id="ongletTicket" class="d-none">
        <form method="POST" action="{{ route('contact.ticket.suivi') }}" class="card shadow-sm">
            @csrf
            <div class="card-body">
                <p class="text-muted" style="font-size:14px;">
                    {{ __('Saisissez votre numéro de ticket et l\'adresse e-mail utilisée lors de votre demande.') }}
                </p>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">{{ __('Numéro de ticket') }} *</label>
                        <input type="text" name="reference" value="{{ old('reference') }}" class="form-control" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">{{ __('Adresse e-mail') }} *</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">{{ __('Accéder à mon ticket') }}</button>
            </div>
        </form>
    </div>

    {{-- Onglet 3 : demande d'adhésion au marché --}}
    @if($mairiesMarche->isNotEmpty())
    <div id="ongletMarche" class="d-none">
        <form method="POST" action="{{ route('contact.marche.store') }}" class="card shadow-sm">
            @csrf
            <div class="card-body">
                <p class="text-muted" style="font-size:13px;">
                    {{ __('Vous souhaitez tenir un stand sur le marché ? Remplissez ce formulaire : la mairie vous répondra dans la même conversation, et pourra vous poser des questions avant de décider.') }}
                </p>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">{{ __('Commune du marché') }} *</label>
                        <select name="mairie_id" class="form-select" required>
                            <option value="">— {{ __('Sélectionnez') }} —</option>
                            @foreach($mairiesMarche as $m)
                                <option value="{{ $m->id }}" @selected(old('mairie_id') == $m->id)>{{ $m->nom }} ({{ $m->code_postal }})</option>
                            @endforeach
                        </select>
                        @error('mairie_id')<div class="text-danger" style="font-size:13px;">{{ $message }}</div>@enderror
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
                        <input type="text" name="societe" value="{{ old('societe') }}" class="form-control" maxlength="150">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('Activité') }} *</label>
                        <input type="text" name="activite" value="{{ old('activite') }}" class="form-control" required
                               placeholder="{{ __('ex : primeur, fromager, vêtements…') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">{{ __('Indicatif') }}</label>
                        <select name="telephone_indicatif" class="form-select">
                            @foreach(\App\Support\Referentiel::INDICATIFS as $ind)
                                <option value="{{ $ind }}" @selected(old('telephone_indicatif', '+33') === $ind)>{{ $ind }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">{{ __('Téléphone') }} *</label>
                        <input type="tel" name="telephone" value="{{ old('telephone') }}" class="form-control" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">{{ __('Adresse e-mail') }} *</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">{{ __('Longueur souhaitée (m)') }}</label>
                        <input type="number" name="longueur_souhaitee" value="{{ old('longueur_souhaitee') }}"
                               class="form-control" min="1" max="50" step="0.5">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">{{ __('Message') }}</label>
                        <textarea name="message" rows="4" class="form-control" maxlength="2000">{{ old('message') }}</textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">{{ __('Envoyer ma demande') }}</button>
            </div>
        </form>
    </div>
    @endif
</div>

<script>
function ongletContact(onglet, btn) {
    document.querySelectorAll('.nav-tabs .nav-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('ongletAide').classList.toggle('d-none', onglet !== 'aide');
    document.getElementById('ongletTicket').classList.toggle('d-none', onglet !== 'ticket');
    document.getElementById('ongletMarche')?.classList.toggle('d-none', onglet !== 'marche');
}
</script>
@endsection
