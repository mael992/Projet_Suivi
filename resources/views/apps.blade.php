@extends('layouts.app')

@section('content')
@php
    $user   = auth()->user();
    $mairie = $user->mairie;
@endphp

<div class="container py-4" style="max-width:960px;">

    {{-- ── Infos de connexion / mairie ── --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
        <div>
            <h1 class="h3 mb-1">{{ __('Gestionnaire des applications de MGDS') }}</h1>
            <p class="text-muted mb-0" style="font-size:14px;">
                👤 {{ $user->username }}
                @if($user->isAdmin())
                    — <span class="badge bg-danger">Admin</span> (toutes les mairies)
                @elseif($mairie)
                    — 🏛️ {{ $mairie->nom }} · {{ $user->service_label }} · {{ $user->grade_label }}
                @endif
            </p>
        </div>

        {{-- ── Recherche extensible (loupe → le champ apparaît) ── --}}
        <div class="d-flex align-items-center" id="appSearchWrap">
            <input type="text" id="appSearch" class="form-control form-control-sm"
                   placeholder="Rechercher une application… (ex : marché, tableau)"
                   style="width:0;opacity:0;padding:0;border:none;transition:all .25s ease;"
                   oninput="filtrerApps()">
            <button type="button" class="btn btn-outline-secondary btn-sm ms-2" onclick="toggleAppSearch()" title="Rechercher">
                🔍
            </button>
        </div>
    </div>

    <hr class="mb-4">

    {{-- ── Tuiles des applications ── --}}
    <div class="row g-4" id="appsGrid">

        {{-- 📊 {{ __('Tableau des suivis') }} --}}
        <div class="col-12 col-md-6 app-tile" data-app="tableau des suivis taches suivi anomalies demande interne externe">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none text-reset d-flex align-items-center gap-3 mb-3">
                        <span style="font-size:44px;line-height:1;">📊</span>
                        <span>
                            <span class="h5 d-block mb-1" style="color:var(--brand);">{{ __('Tableau des suivis') }}</span>
                            <span class="text-muted" style="font-size:13px;">{{ __('Suivi des tâches des services de la mairie') }}</span>
                        </span>
                    </a>
                    <div class="mt-auto d-flex gap-2 flex-wrap">
                        <span class="badge bg-secondary bg-opacity-25 text-secondary" style="cursor:not-allowed;" title="Bientôt disponible">
                            {{ __('Demande interne — à venir') }}
                        </span>
                        <span class="badge bg-secondary bg-opacity-25 text-secondary" style="cursor:not-allowed;" title="Bientôt disponible">
                            {{ __('Demande externe — à venir') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🛍 Marché (droit marché, ou admin) --}}
        @if($user->isAdmin() || $user->aDroit('marche_gestion'))
        <div class="col-12 col-md-6 app-tile" data-app="marche marché exposants commercants plan placement registre banque">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <a href="{{ route('marche.ville') }}" class="text-decoration-none text-reset d-flex align-items-center gap-3 mb-3">
                        <span style="font-size:44px;line-height:1;">🛍️</span>
                        <span>
                            <span class="h5 d-block mb-1" style="color:var(--brand);">Marché</span>
                            <span class="text-muted" style="font-size:13px;">{{ __('Placement des exposants & registre des commerçants') }}</span>
                        </span>
                    </a>
                    <div class="mt-auto d-flex gap-2 flex-wrap">
                        <a href="{{ route('marche.ville') }}" class="badge text-decoration-none" style="background:var(--brand);">🏙️ {{ __('Ville') }}</a>
                        <a href="{{ route('marche.commercants') }}" class="badge bg-dark text-decoration-none">👥 Commerçants</a>
                        <a href="{{ route('marche.registre') }}" class="badge text-decoration-none" style="background:var(--gold);">🏦 Registre</a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- 📇 {{ __('Fiche Contact') }} (droit contacts, ou admin = toutes les mairies) --}}
        @if($user->isAdmin() || ($mairie && $user->aDroit('contacts_lecture')))
        <div class="col-12 col-md-6 app-tile" data-app="fiche contact contacts annuaire standard telephone email mairie">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <a href="{{ route('gestion.contacts.index') }}" class="text-decoration-none text-reset d-flex align-items-center gap-3 mb-3">
                        <span style="font-size:44px;line-height:1;">📇</span>
                        <span>
                            <span class="h5 d-block mb-1" style="color:var(--brand);">{{ __('Fiche Contact') }}</span>
                            <span class="text-muted" style="font-size:13px;">{{ __('Annuaire de la mairie : standards, téléphones & adresses mail') }}</span>
                        </span>
                    </a>
                    <div class="mt-auto d-flex gap-2 flex-wrap">
                        <a href="{{ route('gestion.contacts.index') }}" class="badge text-decoration-none" style="background:var(--brand);">📇 {{ __('Annuaire') }}</a>
                        <a href="{{ route('gestion.contacts.pdf') }}" class="badge bg-dark text-decoration-none">⬇ PDF</a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- 👥 {{ __('Gestion des utilisateurs') }} (droit gestion_utilisateurs, hors admin) --}}
        @if(!$user->isAdmin() && $mairie && $user->peutGererMairie())
        <div class="col-12 col-md-6 app-tile" data-app="gestion des utilisateurs comptes identifiants droits grades">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <a href="{{ route('gestion.utilisateurs.index') }}" class="text-decoration-none text-reset d-flex align-items-center gap-3 mb-3">
                        <span style="font-size:44px;line-height:1;">👥</span>
                        <span>
                            <span class="h5 d-block mb-1" style="color:var(--brand);">{{ __('Gestion des utilisateurs') }}</span>
                            <span class="text-muted" style="font-size:13px;">{{ __('Comptes, statuts et droits d\'application de votre mairie') }}</span>
                        </span>
                    </a>
                    <div class="mt-auto d-flex gap-2 flex-wrap">
                        <a href="{{ route('gestion.utilisateurs.index') }}" class="badge text-decoration-none" style="background:var(--brand);">👥 {{ __('Utilisateurs') }}</a>
                        <a href="{{ route('gestion.utilisateurs.create') }}" class="badge bg-dark text-decoration-none">➕ {{ __('Ajouter') }}</a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- 💬 {{ __('Boîte de dialogue') }} (tous les utilisateurs) --}}
        <div class="col-12 col-md-6 app-tile" data-app="boite de dialogue entraide questions reponses aide support mairies faq">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <a href="{{ route('dialogue.index') }}" class="text-decoration-none text-reset d-flex align-items-center gap-3 mb-3">
                        <span style="font-size:44px;line-height:1;">💬</span>
                        <span>
                            <span class="h5 d-block mb-1" style="color:var(--brand);">
                                {{ __('Boîte de dialogue') }}
                                @php $nbDialogue = \App\Models\DialogueQuestion::nonReponduesPour($user); @endphp
                                @if($nbDialogue > 0)<span class="bulle-notif ms-1">{{ $nbDialogue }}</span>@endif
                            </span>
                            <span class="text-muted" style="font-size:13px;">{{ __('Entraide collaborative entre mairies, application par application') }}</span>
                        </span>
                    </a>
                    <div class="mt-auto d-flex gap-2 flex-wrap">
                        <a href="{{ route('dialogue.index') }}" class="badge text-decoration-none" style="background:var(--brand);">❓ {{ __('Questions & Entraide') }}</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🗓️ {{ __('Pense-bête') }} (tous les utilisateurs) --}}
        <div class="col-12 col-md-6 app-tile" data-app="pense bete calendrier notes rappels memo agenda dossiers">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <a href="{{ route('pensebete.index') }}" class="text-decoration-none text-reset d-flex align-items-center gap-3 mb-3">
                        <span style="font-size:44px;line-height:1;">🗓️</span>
                        <span>
                            <span class="h5 d-block mb-1" style="color:var(--brand);">
                                {{ __('Pense-bête') }}
                                @php
                                    $auj = now()->toDateString();
                                    $nbPense = \App\Models\Rappel::where('user_id', $user->id)->whereDate('date_rappel', $auj)->count()
                                             + \App\Models\Note::where('user_id', $user->id)->where('notifier', true)->whereDate('date_notification', $auj)->count();
                                @endphp
                                @if($nbPense > 0)<span class="bulle-notif ms-1">{{ $nbPense }}</span>@endif
                            </span>
                            <span class="text-muted" style="font-size:13px;">{{ __('Calendrier avec rappels par email & notes personnelles classées') }}</span>
                        </span>
                    </a>
                    <div class="mt-auto d-flex gap-2 flex-wrap">
                        <a href="{{ route('pensebete.index') }}" class="badge text-decoration-none" style="background:var(--brand);">📅 {{ __('Calendrier') }}</a>
                        <a href="{{ route('pensebete.index', ['onglet' => 'notes']) }}" class="badge text-decoration-none" style="background:var(--gold);">🗒️ {{ __('Notes') }}</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- 💌 {{ __('Centre de Messagerie') }} (mairie ou admin) --}}
        @if($user->isAdmin() || $mairie)
        <div class="col-12 col-md-6 app-tile" data-app="centre de messagerie message externe interne support ticket contacter mairie">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <a href="{{ route('messagerie.index') }}" class="text-decoration-none text-reset d-flex align-items-center gap-3 mb-3">
                        <span style="font-size:44px;line-height:1;">💌</span>
                        <span>
                            <span class="h5 d-block mb-1" style="color:var(--brand);">{{ __('Centre de Messagerie') }}</span>
                            <span class="text-muted" style="font-size:13px;">{{ __('Messages reçus des habitants & support') }}</span>
                        </span>
                    </a>
                    <div class="mt-auto d-flex gap-2 flex-wrap">
                        <a href="{{ route('messagerie.index') }}" class="badge text-decoration-none" style="background:var(--brand);">🌐 {{ __('Messages externes') }}</a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ⚙️ {{ __('Paramètres Administration') }} (admins uniquement) --}}
        @if($user->isAdmin())
        <div class="col-12 col-md-6 app-tile" data-app="parametres administration admin utilisateurs acces mairies logs messages support">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <a href="{{ route('users.index') }}" class="text-decoration-none text-reset d-flex align-items-center gap-3 mb-3">
                        <span style="font-size:44px;line-height:1;">⚙️</span>
                        <span>
                            <span class="h5 d-block mb-1" style="color:var(--brand);">{{ __('Paramètres Administration') }}</span>
                            <span class="text-muted" style="font-size:13px;">{{ __('Utilisateurs, accès mairies, logs, support') }}</span>
                        </span>
                    </a>
                    <div class="mt-auto d-flex gap-2 flex-wrap">
                        <a href="{{ route('users.index') }}" class="badge text-decoration-none" style="background:var(--brand);">{{ __('Gestion des utilisateurs') }}</a>
                        <a href="{{ route('mairies.index') }}" class="badge text-decoration-none" style="background:var(--gold);">Accès mairie</a>
                        <a href="{{ route('admin.logs.index') }}" class="badge bg-dark text-decoration-none">Logs</a>
                        <a href="{{ route('admin.messages.index') }}" class="badge bg-secondary text-decoration-none">Support</a>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>

    <div id="appsNoResult" class="text-center text-muted py-5 d-none">{{ __('Aucune application ne correspond à la recherche.') }}</div>
</div>

<script>
/* ── Recherche extensible ── */
function toggleAppSearch() {
    const input = document.getElementById('appSearch');
    const ouvert = input.style.width !== '0px' && input.style.width !== '';
    if (ouvert) {
        input.style.width = '0'; input.style.opacity = '0'; input.style.padding = '0'; input.style.border = 'none';
        input.value = ''; filtrerApps();
    } else {
        input.style.width = '260px'; input.style.opacity = '1'; input.style.padding = ''; input.style.border = '';
        input.focus();
    }
}

function filtrerApps() {
    const q = document.getElementById('appSearch').value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    let visibles = 0;
    document.querySelectorAll('.app-tile').forEach(t => {
        const show = !q || (t.dataset.app ?? '').includes(q);
        t.style.display = show ? '' : 'none';
        if (show) visibles++;
    });
    document.getElementById('appsNoResult').classList.toggle('d-none', visibles > 0);
}
</script>
@endsection
