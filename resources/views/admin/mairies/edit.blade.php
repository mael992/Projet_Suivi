@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')
<div class="container py-4" style="max-width:720px;">

    <h1 class="h4 mb-3">Modifier — {{ $mairie->nom }}</h1>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('mairies.update', $mairie) }}" class="card shadow-sm mb-4">
        @csrf @method('PUT')
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nom de la mairie *</label>
                    <input type="text" name="nom" value="{{ old('nom', $mairie->nom) }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Code postal *</label>
                    <input type="text" name="code_postal" value="{{ old('code_postal', $mairie->code_postal) }}" class="form-control"
                           required pattern="[0-9]{5}" maxlength="5" inputmode="numeric" placeholder="00000">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Adresse email *</label>
                    <input type="email" name="email" value="{{ old('email', $mairie->email) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">{{ __('Indicatif') }}</label>
                    <select name="telephone_indicatif" class="form-select">
                        @foreach(Referentiel::INDICATIFS as $ind)
                            <option value="{{ $ind }}" @selected(old('telephone_indicatif', $mairie->telephone_indicatif) === $ind)>{{ $ind }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label fw-semibold">{{ __('Téléphone') }}</label>
                    <input type="text" name="telephone" value="{{ old('telephone', $mairie->telephone) }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Date de fin d'abonnement *</label>
                    <input type="date" name="date_fin_abonnement"
                           value="{{ old('date_fin_abonnement', $mairie->date_fin_abonnement?->format('Y-m-d')) }}"
                           class="form-control" required>
                    <small class="text-muted">Date incluse : ce jour-là, les utilisateurs peuvent encore se connecter.</small>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="afficher_contact" id="afficher_contact" value="1" @checked(old('afficher_contact', $mairie->afficher_contact))>
                        <label class="form-check-label" for="afficher_contact">
                            Figurer dans la liste « Contacter votre Mairie » (page publique)
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
                <a href="{{ route('mairies.index') }}" class="btn btn-outline-secondary">Retour</a>
            </div>
        </div>
    </form>

    {{-- Observateurs : copie de tous les mails de la mairie, sans limite --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Observateurs ({{ $mairie->observateurs->count() }})</span>
            <small class="text-muted">Ils reçoivent une copie de tous les e-mails de cette mairie (qui a fait quoi, passages en « fait », etc.)</small>
        </div>
        <div class="card-body">

            <form method="POST" action="{{ route('mairies.observateurs.store', $mairie) }}" class="row g-2 align-items-end mb-3">
                @csrf
                <input type="hidden" name="nom" id="obsNom">
                <input type="hidden" name="email" id="obsEmail">
                <div class="col-md-4">
                    <label class="form-label mb-1" style="font-size:12px;">🔍 {{ __('Recherche rapide') }}</label>
                    <input type="text" id="obsRecherche" class="form-control form-control-sm"
                           placeholder="{{ __('nom, identifiant, mairie…') }}" autocomplete="off">
                </div>
                <div class="col-md-5">
                    <label class="form-label mb-1" style="font-size:12px;">{{ __('Utilisateur') }} *</label>
                    <select id="obsSelect" class="form-select form-select-sm" required>
                        <option value="">— {{ __('Sélectionnez un utilisateur') }} —</option>
                        @foreach($utilisateurs as $u)
                            <option value="{{ $u->email }}" data-nom="{{ $u->prenom }} {{ $u->nom }}"
                                    data-recherche="{{ strtolower(Str::ascii($u->username . ' ' . $u->prenom . ' ' . $u->nom . ' ' . $u->email . ' ' . ($u->mairie?->nom ?? ''))) }}">
                                {{ $u->username }} — {{ $u->email }}{{ $u->mairie ? ' (' . $u->mairie->nom . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-dark w-100">+ Ajouter un observateur</button>
                </div>
            </form>

            <script>
            // Mini-recherche : filtre la liste des utilisateurs en direct
            document.getElementById('obsRecherche').addEventListener('input', function () {
                const q = this.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
                let premierVisible = null;
                document.querySelectorAll('#obsSelect option').forEach(o => {
                    if (!o.value) return;
                    const visible = !q || (o.dataset.recherche ?? '').includes(q);
                    o.hidden = !visible;
                    if (visible && !premierVisible) premierVisible = o;
                });
                if (q && premierVisible) document.getElementById('obsSelect').value = premierVisible.value;
            });

            // Le formulaire envoie l'email + le nom de l'utilisateur choisi
            document.querySelector('form[action="{{ route('mairies.observateurs.store', $mairie) }}"]').addEventListener('submit', function (e) {
                const sel = document.getElementById('obsSelect');
                const opt = sel.options[sel.selectedIndex];
                if (!sel.value) { e.preventDefault(); sel.focus(); return; }
                document.getElementById('obsEmail').value = sel.value;
                document.getElementById('obsNom').value   = opt.dataset.nom ?? '';
            });
            </script>

            @if($mairie->observateurs->isEmpty())
                <p class="text-muted mb-0" style="font-size:13px;">Aucun observateur pour le moment.</p>
            @else
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                    @foreach($mairie->observateurs as $observateur)
                        <tr>
                            <td>{{ $observateur->nom ?? '—' }}</td>
                            <td>{{ $observateur->email }}</td>
                            <td class="text-end">
                                <form action="{{ route('mairies.observateurs.destroy', [$mairie, $observateur]) }}" method="POST"
                                      onsubmit="return confirm('Retirer cet observateur ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">🗑</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

</div>
@endsection
