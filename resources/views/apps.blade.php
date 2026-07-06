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
            <h1 class="h3 mb-1">Gestionnaire des applications de MGDS</h1>
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

        {{-- 📊 Tableau des suivis --}}
        <div class="col-12 col-md-6 app-tile" data-app="tableau des suivis taches suivi anomalies demande interne externe">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none text-reset d-flex align-items-center gap-3 mb-3">
                        <span style="font-size:44px;line-height:1;">📊</span>
                        <span>
                            <span class="h5 d-block mb-1" style="color:var(--brand);">Tableau des suivis</span>
                            <span class="text-muted" style="font-size:13px;">Suivi des tâches des services de la mairie</span>
                        </span>
                    </a>
                    <div class="mt-auto d-flex gap-2 flex-wrap">
                        <span class="badge bg-secondary bg-opacity-25 text-secondary" style="cursor:not-allowed;" title="Bientôt disponible">
                            Demande interne — à venir
                        </span>
                        <span class="badge bg-secondary bg-opacity-25 text-secondary" style="cursor:not-allowed;" title="Bientôt disponible">
                            Demande externe — à venir
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
                            <span class="text-muted" style="font-size:13px;">Placement des exposants & registre des commerçants</span>
                        </span>
                    </a>
                    <div class="mt-auto d-flex gap-2 flex-wrap">
                        <a href="{{ route('marche.plan') }}" class="badge text-decoration-none" style="background:var(--brand);">🗺️ Plan</a>
                        <a href="{{ route('marche.registre') }}" class="badge text-decoration-none" style="background:var(--gold);">🏦 Registre</a>
                        <a href="{{ route('marche.commercants') }}" class="badge bg-dark text-decoration-none">👥 Commerçants</a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div id="appsNoResult" class="text-center text-muted py-5 d-none">Aucune application ne correspond à la recherche.</div>
</div>

{{-- ── Fenêtre de présentation (fermable avec la croix) ── --}}
<div id="mgdsWelcome" style="display:none;position:fixed;inset:0;background:rgba(15,32,58,.55);z-index:1050;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;max-width:520px;width:92%;padding:28px 32px;position:relative;box-shadow:0 12px 48px rgba(0,0,0,.35);">
        <button type="button" onclick="fermerWelcome()"
                style="position:absolute;top:10px;right:14px;border:none;background:none;font-size:22px;color:#888;" title="Fermer">✕</button>

        <div class="text-center mb-3">
            <img src="{{ asset('images/logo-mgds.png') }}" alt="MGDS" style="height:80px;">
        </div>

        <h2 class="text-center mb-3" style="font-size:1.25rem;">
            <strong>M</strong><em>airie</em> - <strong>G</strong><em>estion</em> <strong>D</strong><em>es</em> <strong>S</strong><em>ervices</em>
        </h2>

        <p style="font-size:14px;">MGDS accompagne les Mairies dans le suivi quotidien des tâches de leurs services :</p>
        <ul style="font-size:14px;line-height:1.8;">
            <li>création et affectation des travaux</li>
            <li>photos avant/après</li>
            <li>clôtures automatiques</li>
            <li>annuaire des services</li>
            <li>suivi de la charge de travail</li>
        </ul>
        <p class="text-muted mb-0" style="font-size:13px;">Le tout dans un espace sécurisé propre à chaque service.</p>
    </div>
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

/* ── Fenêtre de présentation ── */
function fermerWelcome() {
    document.getElementById('mgdsWelcome').style.display = 'none';
    localStorage.setItem('mgdsWelcomeFerme', '1');
}
if (!localStorage.getItem('mgdsWelcomeFerme')) {
    document.getElementById('mgdsWelcome').style.display = 'flex';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') fermerWelcome(); });
</script>
@endsection
