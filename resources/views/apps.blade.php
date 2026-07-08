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

        {{-- 🛍 Marché --}}
        <div class="col-12 col-md-6 app-tile" data-app="marche marché exposants commercants plan placement registre banque">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <a href="{{ route('marche.plan') }}" class="text-decoration-none text-reset d-flex align-items-center gap-3 mb-3">
                        <span style="font-size:44px;line-height:1;">🛍️</span>
                        <span>
                            <span class="h5 d-block mb-1" style="color:var(--brand);">Marché</span>
                            <span class="text-muted" style="font-size:13px;">{{ __('Placement des exposants & registre des commerçants') }}</span>
                        </span>
                    </a>
                    <div class="mt-auto d-flex gap-2 flex-wrap">
                        <a href="{{ route('marche.plan') }}" class="badge text-decoration-none" style="background:var(--brand);">🗺️ Plan</a>
                        <a href="{{ route('marche.commercants') }}" class="badge bg-dark text-decoration-none">👥 Commerçants</a>
                        <a href="{{ route('marche.registre') }}" class="badge text-decoration-none" style="background:var(--gold);">🏦 Registre</a>
                    </div>
                </div>
            </div>
        </div>

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
