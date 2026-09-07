@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')
<div class="container py-4" style="max-width:680px;">

    <h1 class="h4 mb-3">Ajouter un utilisateur (admin)</h1>

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('users.store') }}" class="card shadow-sm">
        @csrf
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('Prénom') }} *</label>
                    <input type="text" name="prenom" value="{{ old('prenom') }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('Nom') }} *</label>
                    <input type="text" name="nom" value="{{ old('nom') }}" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">{{ __('Rôle') }} *</label>
                    <select name="role" id="roleSelect" class="form-select" required onchange="toggleMairie()">
                        <option value="user" @selected(old('role', 'user') === 'user')>Utilisateur d'une mairie</option>
                        <option value="admin" @selected(old('role') === 'admin')>Administrateur (accès total)</option>
                    </select>
                </div>
                <div class="col-md-6 champ-mairie">
                    <label class="form-label fw-semibold">{{ __('Mairie') }} *</label>
                    <select name="mairie_id" class="form-select">
                        <option value="">— Sélectionnez —</option>
                        @foreach($mairies as $m)
                            <option value="{{ $m->id }}" @selected(old('mairie_id') == $m->id)>{{ $m->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 champ-mairie">
                    <label class="form-label fw-semibold">Service (équipe) *</label>
                    <select name="service" class="form-select">
                        <option value="">— Sélectionnez —</option>
                        @foreach(Referentiel::SERVICES as $num => $label)
                            <option value="{{ $num }}" @selected(old('service') == $num)>{{ $num }} — {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 champ-mairie">
                    <label class="form-label fw-semibold">{{ __('Statut') }} *</label>
                    <select name="grade" class="form-select">
                        <option value="">— Sélectionnez —</option>
                        @foreach(Referentiel::GRADES as $num => $label)
                            <option value="{{ $num }}" @selected(old('grade') == $num)>{{ $num }}. {{ $label }}</option>
                        @endforeach
                    </select>
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
                    <label class="form-label fw-semibold">{{ __('Téléphone') }}</label>
                    <input type="text" name="telephone" value="{{ old('telephone') }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">{{ __('Adresse mail') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                </div>
                <div class="col-12 champ-mairie">
                    <div class="row g-3">
                        @include('gestion.utilisateurs.partials.droits')
                    </div>
                </div>
                {{-- Un administrateur choisit son mot de passe ; un agent de mairie
                     reçoit un provisoire tiré au sort par le système. --}}
                <div class="col-12" id="blocMotDePasse">
                    <label class="form-label fw-semibold">{{ __('Mot de passe') }} *</label>
                    <input type="text" name="password" value="{{ old('password') }}" class="form-control" minlength="8">
                    <small class="text-muted">{{ __('Compte administrateur : mot de passe choisi ici.') }}</small>
                </div>
                <div class="col-12 d-none" id="blocMotDePasseAuto">
                    <label class="form-label fw-semibold">🔑 {{ __('Mot de passe provisoire') }}</label>
                    <div class="border rounded p-2 text-muted" style="font-size:13px;">
                        {{ __('Généré automatiquement à la création : rien à saisir. Il figure sur le courrier d\'identifiants, reste valable 48 heures, et doit être changé à la première connexion.') }}
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Créer</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">{{ __('Annuler') }}</a>
            </div>
        </div>
    </form>
</div>

<script>
function toggleMairie() {
    const estAdmin = document.getElementById('roleSelect').value === 'admin';
    document.querySelectorAll('.champ-mairie').forEach(el => el.style.display = estAdmin ? 'none' : '');

    // Seul l'administrateur saisit son mot de passe
    const saisie = document.getElementById('blocMotDePasse');
    const auto   = document.getElementById('blocMotDePasseAuto');
    saisie.classList.toggle('d-none', ! estAdmin);
    auto.classList.toggle('d-none', estAdmin);
    saisie.querySelector('input[name=password]').required = estAdmin;
}
toggleMairie();
</script>
@endsection
