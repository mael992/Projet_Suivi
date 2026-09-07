@extends('layouts.app')

@php
    use App\Support\Referentiel;

    // Absences de la page, pour préremplir la modale de correction
    $absencesJson = $actuelles->concat($historique)->mapWithKeys(fn ($a) => [
        $a->id => [
            'agent'        => $a->user?->username,
            'motif'        => $a->motif,
            'debut'        => $a->date_debut->format('Y-m-d'),
            'fin'          => $a->date_fin->format('Y-m-d'),
            'justificatif' => (bool) $a->justificatif,
        ],
    ]);
@endphp

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    <a href="{{ route('gestion.utilisateurs.index') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">
        ← {{ __('Gestion des utilisateurs') }}
    </a>
    <h1 class="h3 mb-3">🗓️ {{ __('Absences') }} — {{ $mairie->nom }}</h1>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <ul class="nav nav-tabs border-0" id="ongletsAbsence">
            <li class="nav-item">
                <button class="nav-link active" data-cible="actuelles">
                    📌 {{ __('Absences actuelles / à venir') }}
                    <span class="badge bg-secondary ms-1">{{ $actuelles->count() }}</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-cible="historique">
                    📚 {{ __('Historique & Justificatifs') }}
                    <span class="badge bg-secondary ms-1">{{ $historique->count() }}</span>
                </button>
            </li>
        </ul>
        <button class="btn btn-primary" onclick="ouvrirModaleAbsence()">
            + {{ __('Ajouter une absence') }}
        </button>
    </div>

    <div id="zoneAbsences">
    {{-- ── Onglet 1 : en cours et à venir ── --}}
    <div class="card shadow-sm" id="panneauActuelles">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>{{ __('Service') }}</th>
                        <th>{{ __('Utilisateur') }}</th>
                        <th>{{ __('Motif d\'absence') }}</th>
                        <th>{{ __('Date de début') }}</th>
                        <th>{{ __('Date de fin') }}</th>
                        <th>{{ __('Remplacé par') }}</th>
                        <th>{{ __('Justificatif') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($actuelles as $absence)
                    <tr class="{{ $absence->estEnCours() ? 'table-warning' : '' }}">
                        <td style="font-size:13px;">{{ $absence->user?->service_label }}</td>
                        <td class="fw-semibold">
                            {{ $absence->user?->username }}
                            @if($absence->estEnCours())
                                <span class="badge bg-warning text-dark ms-1" style="font-size:10px;">{{ __('En cours') }}</span>
                            @endif
                        </td>
                        <td>{{ $absence->motifLabel() }}</td>
                        <td>{{ $absence->date_debut->format('d/m/Y') }}</td>
                        <td>{{ $absence->date_fin->format('d/m/Y') }}</td>
                        <td style="font-size:13px;">{{ $absence->user?->binome?->username ?? '—' }}</td>
                        <td>
                            @if($absence->justificatif)
                                <a href="{{ route('gestion.absences.justificatif', $absence) }}" class="btn btn-sm btn-outline-dark">📎 {{ __('Voir') }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="modifierAbsence({{ $absence->id }})">{{ __('Modifier') }}</button>
                            <form method="POST" action="{{ route('gestion.absences.destroy', $absence) }}"
                                  onsubmit="return confirm('{{ __('Supprimer cette absence ?') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">{{ __('Supprimer') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('Aucune absence en cours ni à venir.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Onglet 2 : historique ── --}}
    <div class="card shadow-sm d-none" id="panneauHistorique">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>{{ __('Service') }}</th>
                        <th>{{ __('Utilisateur') }}</th>
                        <th>{{ __('Motif') }}</th>
                        <th>{{ __('Période') }}</th>
                        <th>{{ __('Justificatif') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($historique as $absence)
                    <tr>
                        <td style="font-size:13px;">{{ $absence->user?->service_label }}</td>
                        <td class="fw-semibold">{{ $absence->user?->username }}</td>
                        <td>{{ $absence->motifLabel() }}</td>
                        <td>{{ $absence->periodeLabel() }}</td>
                        <td>
                            @if($absence->justificatif)
                                <a href="{{ route('gestion.absences.justificatif', $absence) }}" class="btn btn-sm btn-outline-dark">📎 {{ __('Voir') }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="modifierAbsence({{ $absence->id }})">{{ __('Modifier') }}</button>
                            <form method="POST" action="{{ route('gestion.absences.destroy', $absence) }}"
                                  onsubmit="return confirm('{{ __('Supprimer cette absence ?') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">{{ __('Supprimer') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Aucune absence terminée.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>{{-- /#zoneAbsences --}}
</div>

{{-- ── Modale : corriger une absence ── --}}
<div id="modaleEditAbsence" class="d-none position-fixed top-0 start-0 w-100 h-100"
     style="background:rgba(0,0,0,.5);z-index:1050;">
    <div class="bg-white rounded shadow position-absolute top-50 start-50 translate-middle p-4"
         style="width:100%;max-width:460px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">{{ __('Modifier l\'absence') }}</h2>
            <button type="button" class="btn-close" onclick="fermerEditAbsence()"></button>
        </div>

        <form method="POST" id="formEditAbsence" enctype="multipart/form-data">
            @csrf @method('PUT')
            <p class="text-muted" id="editAgent" style="font-size:13px;"></p>

            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Motif de l\'absence') }} *</label>
                <select name="motif" id="editMotif" class="form-select" required>
                    @foreach(Referentiel::MOTIFS_ABSENCE as $cle => $label)
                        <option value="{{ $cle }}">{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Date de début') }} *</label>
                    <input type="date" name="date_debut" id="editDebut" class="form-control" required>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Date de fin') }} *</label>
                    <input type="date" name="date_fin" id="editFin" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Justificatif') }}</label>
                <input type="file" name="justificatif" class="form-control"
                       accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx">
                <div class="form-check mt-2 d-none" id="blocRetrait">
                    <input class="form-check-input" type="checkbox" name="retirer_justificatif" value="1" id="retirerJustificatif">
                    <label class="form-check-label" for="retirerJustificatif" style="font-size:12px;">
                        {{ __('Retirer le justificatif actuel') }}
                    </label>
                </div>
                <small class="text-muted">{{ __('Un nouveau fichier remplace l\'ancien. 8 Mo maximum.') }}</small>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="fermerEditAbsence()">{{ __('Annuler') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modale : ajouter une absence ── --}}
<div id="modaleAbsence" class="d-none position-fixed top-0 start-0 w-100 h-100"
     style="background:rgba(0,0,0,.5);z-index:1050;">
    <div class="bg-white rounded shadow position-absolute top-50 start-50 translate-middle p-4"
         style="width:100%;max-width:460px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">{{ __('Ajouter une absence') }}</h2>
            <button type="button" class="btn-close" onclick="fermerModaleAbsence()"></button>
        </div>

        <form method="POST" action="{{ route('gestion.absences.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Utilisateur') }} *</label>
                <select name="user_id" class="form-select" required>
                    <option value="">— {{ __('Sélectionnez') }} —</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" @selected(old('user_id') == $agent->id)>
                            {{ $agent->username }} ({{ $agent->service_label }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Motif de l\'absence') }} *</label>
                <select name="motif" class="form-select" required>
                    @foreach(Referentiel::MOTIFS_ABSENCE as $cle => $label)
                        <option value="{{ $cle }}" @selected(old('motif') === $cle)>{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Date de début') }} *</label>
                    <input type="date" name="date_debut" class="form-control" value="{{ old('date_debut') }}" required>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Date de fin') }} *</label>
                    <input type="date" name="date_fin" class="form-control" value="{{ old('date_fin') }}" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Justificatif (optionnel)') }}</label>
                <input type="file" name="justificatif" class="form-control"
                       accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx">
                <small class="text-muted">{{ __('8 Mo maximum. Consultable uniquement depuis cette page.') }}</small>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="fermerModaleAbsence()">{{ __('Annuler') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const onglets = document.querySelectorAll('#ongletsAbsence .nav-link');
    const panneaux = {
        actuelles:  document.getElementById('panneauActuelles'),
        historique: document.getElementById('panneauHistorique'),
    };

    onglets.forEach(btn => btn.addEventListener('click', () => {
        onglets.forEach(b => b.classList.toggle('active', b === btn));
        Object.entries(panneaux).forEach(([cle, el]) =>
            el.classList.toggle('d-none', cle !== btn.dataset.cible));
    }));

    const modale = document.getElementById('modaleAbsence');
    window.ouvrirModaleAbsence = () => modale.classList.remove('d-none');
    window.fermerModaleAbsence = () => modale.classList.add('d-none');

    // Édition : les absences connues de la page, indexées par identifiant
    const ABSENCES = @json($absencesJson);

    const modaleEdit = document.getElementById('modaleEditAbsence');
    const formEdit   = document.getElementById('formEditAbsence');
    const urlEdit    = @json(route('gestion.absences.update', ['absence' => '__ID__']));

    window.modifierAbsence = (id) => {
        const a = ABSENCES[id];
        if (! a) return;

        formEdit.action = urlEdit.replace('__ID__', id);
        document.getElementById('editAgent').textContent = a.agent ?? '';
        document.getElementById('editMotif').value = a.motif;
        document.getElementById('editDebut').value = a.debut;
        document.getElementById('editFin').value   = a.fin;
        document.getElementById('blocRetrait').classList.toggle('d-none', ! a.justificatif);
        document.getElementById('retirerJustificatif').checked = false;

        modaleEdit.classList.remove('d-none');
    };

    window.fermerEditAbsence = () => modaleEdit.classList.add('d-none');

    // Le formulaire rouvre la modale s'il a été refusé
    @if($errors->any() && old('user_id'))
        ouvrirModaleAbsence();
    @endif
})();
</script>

@include('partials.autorefresh', ['selector' => '#zoneAbsences'])
@endsection
