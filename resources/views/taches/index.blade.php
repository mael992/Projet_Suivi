@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')

{{-- SIDEBAR (menu de gauche, comme PlanEx) --}}
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="sidebar" id="sidebar">
    <button class="sidebar-close" onclick="closeSidebar()">✕</button>
    <div class="sidebar-logo">
        <img src="{{ asset('images/logo-mgds.png') }}" alt="MGDS">
    </div>
    <div class="sidebar-divider"></div>
    <nav class="sidebar-nav">
        <a href="{{ route('apps') }}" class="sidebar-link">
            <span class="sidebar-icon" style="font-size:16px">🧩</span> Applications
        </a>
        <div class="sidebar-divider"></div>
        <a href="{{ route('dashboard') }}" class="sidebar-link active">
            <span class="sidebar-icon" style="font-size:16px">📋</span> {{ __('Suivi des tâches') }}
        </a>
        @if(auth()->user()->peutGererTaches())
        <a href="{{ route('taches.create') }}" class="sidebar-link">
            <span class="sidebar-icon" style="font-size:16px">➕</span> {{ __('Nouvelle tâche') }}
        </a>
        @endif
        @if(!auth()->user()->isAdmin() && auth()->user()->peutGererMairie())
            <div class="sidebar-divider"></div>
            <a href="{{ route('gestion.avancement') }}" class="sidebar-link">
                <span class="sidebar-icon" style="font-size:16px">📊</span> {{ __('Avancement des tâches') }}
            </a>
        @endif
        @if(auth()->user()->peutGererTaches())
            <div class="sidebar-divider"></div>
            <a href="{{ route('taches.create') }}" class="sidebar-cta">
                <span style="font-size:16px">💬</span> {{ __('Ajouter une tâche') }}
            </a>
        @endif
    </nav>
    <div class="sidebar-divider"></div>
    <div class="sidebar-footer">
        <div class="sidebar-footer-item">
            <span style="font-size:16px">👤</span>
            <span>{{ auth()->user()->username ?? '—' }}</span>
        </div>
        <div class="sidebar-footer-item">
            <span style="font-size:16px">🔰</span>
            <span>{{ auth()->user()->isAdmin() ? 'Admin' : auth()->user()->grade_label }}</span>
        </div>
    </div>
</div>

<div class="container-fluid px-3 px-md-4 py-4">

    <button class="sidebar-toggle" onclick="openSidebar()">☰</button>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h3 mb-0">
            {{ __('Suivi des tâches') }}
            @if(!auth()->user()->isAdmin() && auth()->user()->mairie)
                — {{ auth()->user()->mairie->nom }}
            @endif
        </h1>
        @if(auth()->user()->peutGererTaches())
            <a href="{{ route('taches.create') }}" class="btn btn-primary">{{ __('+ Ajouter une tâche') }}</a>
        @endif
    </div>

    {{-- Barre de recherche centrée --}}
    <form method="GET" action="{{ route('dashboard') }}" class="mb-3" style="max-width:560px;margin-left:auto;margin-right:auto;">
        <div class="search-input-group">
            <span class="search-icon">🔍</span>
            <input type="text"
                   name="q"
                   class="search-input"
                   placeholder="Recherche : référence, utilisateur, description…"
                   value="{{ request('q') }}"
                   autocomplete="off">
            @if(request('q'))
                <a href="{{ route('dashboard') }}" class="search-clear" title="Effacer">✕</a>
            @endif
        </div>
        {{-- conserver les autres filtres actifs lors d'une recherche --}}
        @foreach(['statut','service','mairie','date_debut','date_fin'] as $f)
            @if(request($f))<input type="hidden" name="{{ $f }}" value="{{ request($f) }}">@endif
        @endforeach
    </form>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('dashboard') }}" class="card shadow-sm mb-3">
        @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
        <div class="card-body py-3">
            <div class="row g-2 align-items-end justify-content-center">
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:12px;">{{ __('Statut') }}</label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">{{ __('Tous') }}</option>
                        @foreach(Referentiel::STATUTS as $key => $label)
                            <option value="{{ $key }}" @selected(request('statut') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->voitTousLesServices())
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1" style="font-size:12px;">{{ __('Équipe / Service') }}</label>
                    <select name="service" class="form-select form-select-sm">
                        <option value="">{{ __('Tous') }}</option>
                        @foreach(Referentiel::SERVICES as $num => $label)
                            <option value="{{ $num }}" @selected(request('service') == $num)>{{ $num }} — {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if(auth()->user()->isAdmin() && $mairies->isNotEmpty())
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:12px;">{{ __('Mairie') }}</label>
                    <select name="mairie" class="form-select form-select-sm">
                        <option value="">{{ __('Toutes') }}</option>
                        @foreach($mairies as $m)
                            <option value="{{ $m->id }}" @selected(request('mairie') == $m->id)>{{ $m->nom }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:12px;">{{ __('Du') }}</label>
                    <input type="date" name="date_debut" value="{{ request('date_debut') }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" style="font-size:12px;">{{ __('Au') }}</label>
                    <input type="date" name="date_fin" value="{{ request('date_fin') }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-dark w-100">{{ __('Filtrer') }}</button>
                </div>
            </div>
        </div>
    </form>

    <div class="card shadow-sm" id="zoneTaches">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Réf</th>
                        @if(auth()->user()->isAdmin())<th>{{ __('Mairie') }}</th>@endif
                        <th>{{ __('Date de création') }}</th>
                        <th>{{ __('Photo de la tâche à faire') }}</th>
                        <th>{{ __('Photo une fois finie') }}</th>
                        <th>{{ __('Date et heure de clôture') }}</th>
                        <th>{{ __('Équipe') }}</th>
                        <th>{{ __('Assignée à') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($taches as $tache)
                    @php
                        $u              = auth()->user();
                        $estCreateur    = $tache->peutEtreGereePar($u);
                        $enAttentePourMoi = $tache->user_id === $u->id && $tache->enAttentePriseEnCharge();
                        $peutCloturerLigne = $tache->peutEtreClotureePar($u);
                    @endphp
                    <tr @if($enAttentePourMoi) class="table-warning" title="{{ __('Nouvelle tâche en attente de votre prise en charge') }}" @endif>
                        <td class="fw-semibold">{{ $tache->reference }}</td>
                        @if(auth()->user()->isAdmin())<td>{{ $tache->mairie?->nom ?? '—' }}</td>@endif
                        <td>{{ $tache->created_at->format('d/m/Y') }}</td>
                        <td>
                            @if($tache->photo_avant)
                                <a href="{{ asset('storage/' . $tache->photo_avant) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $tache->photo_avant) }}" alt="Avant" style="height:42px;width:56px;object-fit:cover;border-radius:6px;">
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($tache->photo_apres)
                                <a href="{{ asset('storage/' . $tache->photo_apres) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $tache->photo_apres) }}" alt="Après" style="height:42px;width:56px;object-fit:cover;border-radius:6px;">
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $tache->date_cloture?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td style="font-size:13px;">{{ $tache->service_label }}</td>
                        <td>
                            {{ $tache->assigne?->username ?? '—' }}
                            @if($tache->prise_en_charge === 'substitution')
                                <div class="text-muted" style="font-size:11px;">🔄 {{ $tache->substitut?->username }}</div>
                            @elseif($tache->enAttentePriseEnCharge())
                                <div class="text-warning" style="font-size:11px;">⏳ {{ __('en attente') }}</div>
                            @endif
                        </td>
                        <td>
                            @php
                                $couleurs = ['ouvert' => 'secondary', 'en_cours' => 'warning', 'fait' => 'success'];
                            @endphp
                            <span class="badge bg-{{ $couleurs[$tache->statut] ?? 'secondary' }}">{{ $tache->statut_label }}</span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('taches.show', $tache) }}" class="btn btn-outline-secondary">👁 Voir</a>
                                @if($peutCloturerLigne)
                                    <a href="{{ route('taches.show', $tache) }}#cloture" class="btn btn-outline-success">🏁 {{ __('Clôturer') }}</a>
                                @endif
                                @if($estCreateur)
                                    <a href="{{ route('taches.edit', $tache) }}" class="btn btn-outline-primary">✏️ Modifier</a>
                                    <form action="{{ route('taches.destroy', $tache) }}" method="POST"
                                          onsubmit="return confirm('⚠️ Supprimer la tâche {{ $tache->reference }} ?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger" type="submit">🗑 Supprimer</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isAdmin() ? 10 : 9 }}" class="text-center text-muted py-4">
                            {{ __('Aucune tâche pour le moment.') }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeSidebar();
});
</script>
@include('partials.autorefresh', ['selector' => '#zoneTaches'])
@endsection
