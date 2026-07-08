@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')
@php
    $standardsParService = $standards->groupBy('service');
    $contactsParService  = $contacts->groupBy('service');
@endphp

<div class="container-fluid px-3 px-md-4 py-4">

    <h1 class="h3 mb-1">{{ __('Gestion de la Mairie') }} — {{ $mairie->nom }}</h1>
    <p class="mb-3">
        <span class="badge bg-dark">🔒 Fiche contact — privé & confidentiel</span>
    </p>

    @include('gestion.partials.onglets')

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        {{-- Recherche prénom, nom ou service --}}
        <div style="max-width:400px;flex:1;">
            <div class="search-input-group">
                <span class="search-icon">🔍</span>
                <input type="text" id="contactSearch" class="search-input"
                       placeholder="{{ __('Recherche : prénom, nom ou service…') }}" autocomplete="off">
            </div>
        </div>
        <a href="{{ route('gestion.contacts.pdf') }}" class="btn btn-outline-dark">⬇ {{ __('Télécharger en PDF') }}</a>
    </div>

    {{-- Formulaire numéro de standard (ouvert via les boutons ➕ des lignes) --}}
    <div class="collapse mb-3 {{ $errors->any() ? 'show' : '' }}" id="formStandard">
        <form method="POST" action="{{ route('gestion.contacts.standards.store') }}" class="card shadow-sm">
            @csrf
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label mb-1" style="font-size:12px;">{{ __('Service') }}</label>
                        <select name="service" id="standardService" class="form-select form-select-sm" required>
                            <option value="">— Sélectionnez —</option>
                            @foreach(Referentiel::SERVICES as $num => $label)
                                <option value="{{ $num }}" @selected(old('service') == $num)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1" style="font-size:12px;">{{ __('Indicatif') }}</label>
                        <select name="telephone_indicatif" class="form-select form-select-sm">
                            @foreach(Referentiel::INDICATIFS as $ind)
                                <option value="{{ $ind }}" @selected(old('telephone_indicatif', '+33') === $ind)>{{ $ind }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1" style="font-size:12px;">{{ __('Numéro de téléphone') }}</label>
                        <input type="text" name="telephone" value="{{ old('telephone') }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-dark w-100">Ajouter</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>{{ __('Service') }}</th>
                        <th>{{ __('Téléphone') }}</th>
                        <th>{{ __('Adresse mail') }}</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody id="contactsBody">
                {{-- Chaque service a TOUJOURS sa ligne standard, remplie ou non --}}
                @foreach(Referentiel::SERVICES as $num => $label)
                    @php
                        $lignes = $standardsParService->get($num, collect());
                        $ascii  = strtolower(\Illuminate\Support\Str::ascii($label . ' standard'));
                    @endphp

                    @if($lignes->isEmpty())
                        <tr data-search="{{ $ascii }}" class="table-light">
                            <td class="fw-semibold">{{ $label }} <span class="badge bg-dark ms-1" style="font-size:10px;">Standard</span></td>
                            <td class="text-muted">—</td>
                            <td class="text-muted">—</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-dark" title="Ajouter le numéro de standard"
                                        onclick="ouvrirFormStandard({{ $num }})">➕</button>
                            </td>
                        </tr>
                    @else
                        @foreach($lignes as $standard)
                            <tr data-search="{{ $ascii }}" class="table-light">
                                <td class="fw-semibold">{{ $label }} <span class="badge bg-dark ms-1" style="font-size:10px;">Standard</span></td>
                                <td>{{ $standard->telephone_complet }}</td>
                                <td class="text-muted">—</td>
                                <td class="text-end">
                                    <form action="{{ route('gestion.contacts.standards.destroy', $standard) }}" method="POST"
                                          onsubmit="return confirm('Supprimer ce numéro de standard ?')" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">🗑</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @endif

                    {{-- Annuaire automatique du service --}}
                    @foreach($contactsParService->get($num, collect()) as $contact)
                        <tr data-search="{{ strtolower(\Illuminate\Support\Str::ascii($contact->prenom . ' ' . $contact->nom . ' ' . $label)) }}">
                            <td style="font-size:13px;">
                                {{ $label }}<br>
                                <span class="fw-semibold" style="font-size:14px;">{{ $contact->prenom }} {{ $contact->nom }}</span>
                                <span class="text-muted" style="font-size:12px;">({{ $contact->grade_label }})</span>
                            </td>
                            <td>{{ $contact->telephone_complet }}</td>
                            <td>{{ $contact->email ?? '—' }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
            <div id="noResults" class="text-center text-muted py-4 d-none">{{ __('Aucun résultat.') }}</div>
        </div>
    </div>

</div>

<script>
function ouvrirFormStandard(service) {
    document.getElementById('standardService').value = service;
    const collapse = new bootstrap.Collapse(document.getElementById('formStandard'), { show: true });
    document.getElementById('formStandard').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

document.getElementById('contactSearch').addEventListener('input', function () {
    const q     = this.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    const rows  = document.querySelectorAll('#contactsBody tr');
    let visible = 0;

    rows.forEach(row => {
        const data = row.dataset.search ?? '';
        const show = !q || data.includes(q);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('noResults').classList.toggle('d-none', visible > 0);
});
</script>
@endsection
