@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')
@php $admin = $admin ?? false; @endphp

<div class="container-fluid px-3 px-md-4 py-4">

    <a href="{{ route('apps') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">← {{ __('mgds.nav_apps') }}</a>
    <h1 class="h3 mb-1">📇 {{ __('Fiche Contact') }}@unless($admin) — {{ $blocs->first()['mairie']->nom }}@endunless</h1>
    <p class="mb-3">
        <span class="badge bg-dark">🔒 Fiche contact — privé & confidentiel</span>
    </p>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div style="max-width:400px;flex:1;">
            <div class="search-input-group">
                <span class="search-icon">🔍</span>
                <input type="text" id="contactSearch" class="search-input"
                       placeholder="{{ __('Recherche : prénom, nom ou service…') }}" autocomplete="off">
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            @if($admin)
                @include('partials.tri-mairie', ['route' => 'gestion.contacts.index'])
            @endif
            @unless($admin)
                <a href="{{ route('gestion.contacts.pdf') }}" class="btn btn-outline-dark">⬇ {{ __('Télécharger en PDF') }}</a>
            @endunless
        </div>
    </div>

    @foreach($blocs as $bloc)
        @php
            $mairie              = $bloc['mairie'];
            $standardsParService = $bloc['standards']->groupBy('service');
            $contactsParService  = $bloc['contacts']->groupBy('service');
            $peutModifier        = $admin ? ($mairieEdit === $mairie->id) : auth()->user()->aDroit('contacts_modification');
            $sfx                 = $mairie->id; // suffixe unique par mairie
        @endphp

        @if($admin)
            <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                <h2 class="h5 mb-0" style="color:var(--brand);">🏛️ {{ $mairie->nom }} <span class="text-muted">({{ $mairie->code_postal ?? '—' }})</span></h2>
                <a href="{{ route('gestion.contacts.pdf', ['mairie' => $mairie->id]) }}" class="btn btn-sm btn-outline-dark">⬇ PDF</a>
            </div>
        @endif

        {{-- Formulaire d'ajout d'un numéro de standard --}}
        @if($peutModifier)
        <div class="collapse mb-2" id="formStandard{{ $sfx }}">
            <form method="POST" action="{{ route('gestion.contacts.standards.store') }}" class="card shadow-sm">
                @csrf
                @if($admin)<input type="hidden" name="mairie_id" value="{{ $mairie->id }}">@endif
                <div class="card-body py-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label mb-1" style="font-size:12px;">{{ __('Service') }}</label>
                            <select name="service" id="standardService{{ $sfx }}" class="form-select form-select-sm" required>
                                <option value="">— Sélectionnez —</option>
                                @foreach(Referentiel::SERVICES as $num => $label)
                                    <option value="{{ $num }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1" style="font-size:12px;">{{ __('Indicatif') }}</label>
                            <select name="telephone_indicatif" class="form-select form-select-sm">
                                @foreach(Referentiel::INDICATIFS as $ind)
                                    <option value="{{ $ind }}" @selected('+33' === $ind)>{{ $ind }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1" style="font-size:12px;">{{ __('Numéro de téléphone') }}</label>
                            <input type="text" name="telephone" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1" style="font-size:12px;">{{ __('Adresse mail') }} <span class="text-muted">({{ __('facultatif') }})</span></label>
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="service@mairie.fr">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-dark w-100">Ajouter</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        @endif

        <div class="card shadow-sm mb-3">
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
                    <tbody class="contactsBody">
                    @foreach(Referentiel::SERVICES as $num => $label)
                        @php
                            $lignes = $standardsParService->get($num, collect());
                            $ascii  = strtolower(\Illuminate\Support\Str::ascii($label . ' standard'));
                        @endphp

                        @if($lignes->isEmpty())
                            <tr data-search="{{ $ascii }}" class="table-light contact-row">
                                <td class="fw-semibold">{{ $label }} <span class="badge bg-dark ms-1" style="font-size:10px;">Standard</span></td>
                                <td class="text-muted">—</td>
                                <td class="text-muted">—</td>
                                <td class="text-end">
                                    @if($peutModifier)
                                    <button type="button" class="btn btn-sm btn-outline-dark" title="Ajouter le numéro de standard"
                                            onclick="ouvrirFormStandard('{{ $sfx }}', {{ $num }})">➕</button>
                                    @endif
                                </td>
                            </tr>
                        @else
                            @foreach($lignes as $standard)
                                <tr data-search="{{ $ascii }}" class="table-light contact-row">
                                    <td class="fw-semibold">{{ $label }} <span class="badge bg-dark ms-1" style="font-size:10px;">Standard</span></td>
                                    <td>{{ $standard->telephone_complet }}</td>
                                    <td>{{ $standard->email ?? '—' }}</td>
                                    <td class="text-end">
                                        @if($peutModifier)
                                        <button type="button" class="btn btn-sm btn-outline-primary" title="{{ __('Modifier') }}"
                                                onclick="document.getElementById('editStandard{{ $standard->id }}').classList.toggle('d-none')">✏️</button>
                                        <form action="{{ route('gestion.contacts.standards.destroy', $standard) }}" method="POST"
                                              onsubmit="return confirm('Supprimer ce numéro de standard ?')" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">🗑</button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @if($peutModifier)
                                <tr id="editStandard{{ $standard->id }}" class="d-none contact-row" data-search="{{ $ascii }}">
                                    <td colspan="4" class="bg-light">
                                        <form action="{{ route('gestion.contacts.standards.update', $standard) }}" method="POST" class="row g-2 align-items-end">
                                            @csrf @method('PUT')
                                            <div class="col-md-2">
                                                <label class="form-label mb-1" style="font-size:12px;">{{ __('Indicatif') }}</label>
                                                <select name="telephone_indicatif" class="form-select form-select-sm">
                                                    @foreach(Referentiel::INDICATIFS as $ind)
                                                        <option value="{{ $ind }}" @selected($standard->telephone_indicatif === $ind)>{{ $ind }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label mb-1" style="font-size:12px;">{{ __('Numéro de téléphone') }}</label>
                                                <input type="text" name="telephone" value="{{ $standard->telephone }}" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label mb-1" style="font-size:12px;">{{ __('Adresse mail') }}</label>
                                                <input type="email" name="email" value="{{ $standard->email }}" class="form-control form-control-sm" placeholder="service@mairie.fr">
                                            </div>
                                            <div class="col-md-3 d-flex gap-1">
                                                <button type="submit" class="btn btn-sm btn-dark">{{ __('Enregistrer') }}</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                        onclick="document.getElementById('editStandard{{ $standard->id }}').classList.add('d-none')">{{ __('Annuler') }}</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        @endif

                        @foreach($contactsParService->get($num, collect()) as $contact)
                            <tr data-search="{{ strtolower(\Illuminate\Support\Str::ascii($contact->prenom . ' ' . $contact->nom . ' ' . $label)) }}" class="contact-row">
                                <td style="font-size:13px;">
                                    {{ $label }}<br>
                                    <span class="fw-semibold" style="font-size:14px;">{{ $contact->prenom }} {{ $contact->nom }}</span>
                                    <span class="text-muted" style="font-size:12px;">({{ $contact->fonction ?: $contact->grade_label }})</span>
                                </td>
                                <td>{{ $contact->telephone_complet }}</td>
                                <td>{{ $contact->email ?? '—' }}</td>
                                <td></td>
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <div id="noResults" class="text-center text-muted py-4 d-none">{{ __('Aucun résultat.') }}</div>

</div>

<script>
function ouvrirFormStandard(sfx, service) {
    document.getElementById('standardService' + sfx).value = service;
    new bootstrap.Collapse(document.getElementById('formStandard' + sfx), { show: true });
    document.getElementById('formStandard' + sfx).scrollIntoView({ behavior: 'smooth', block: 'center' });
}

document.getElementById('contactSearch').addEventListener('input', function () {
    const q     = this.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    let visible = 0;
    document.querySelectorAll('.contact-row').forEach(row => {
        if (row.id && row.id.startsWith('editStandard')) return; // lignes d'édition
        const data = row.dataset.search ?? '';
        const show = !q || data.includes(q);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('noResults').classList.toggle('d-none', visible > 0);
});
</script>
@endsection
