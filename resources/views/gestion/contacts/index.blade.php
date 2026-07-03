@extends('layouts.app')

@php use App\Support\Referentiel; @endphp

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    <h1 class="h3 mb-3">Gestion de la Mairie — {{ $mairie->nom }}</h1>

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
                       placeholder="Recherche : prénom, nom ou service…" autocomplete="off">
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('gestion.contacts.pdf') }}" class="btn btn-outline-dark">⬇ Télécharger en PDF</a>
            <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#formStandard">
                + Ajouter un numéro de standard
            </button>
        </div>
    </div>

    {{-- Formulaire numéro de standard --}}
    <div class="collapse mb-3 {{ $errors->any() ? 'show' : '' }}" id="formStandard">
        <form method="POST" action="{{ route('gestion.contacts.standards.store') }}" class="card shadow-sm">
            @csrf
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label mb-1" style="font-size:12px;">Service</label>
                        <select name="service" class="form-select form-select-sm" required>
                            <option value="">— Sélectionnez —</option>
                            @foreach(Referentiel::SERVICES as $num => $label)
                                <option value="{{ $num }}" @selected(old('service') == $num)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1" style="font-size:12px;">Indicatif</label>
                        <select name="telephone_indicatif" class="form-select form-select-sm">
                            @foreach(Referentiel::INDICATIFS as $ind)
                                <option value="{{ $ind }}" @selected(old('telephone_indicatif', '+33') === $ind)>{{ $ind }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1" style="font-size:12px;">Numéro de téléphone</label>
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
                        <th>Service</th>
                        <th>Téléphone</th>
                        <th>Adresse mail</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody id="contactsBody">
                {{-- Numéros de standard --}}
                @foreach($standards as $standard)
                    <tr data-search="{{ strtolower(\Illuminate\Support\Str::ascii($standard->service_label . ' standard')) }}" class="table-light">
                        <td class="fw-semibold">{{ $standard->service_label }} <span class="badge bg-dark ms-1" style="font-size:10px;">Standard</span></td>
                        <td>{{ $standard->telephone_complet }}</td>
                        <td>—</td>
                        <td class="text-end">
                            <form action="{{ route('gestion.contacts.standards.destroy', $standard) }}" method="POST"
                                  onsubmit="return confirm('Supprimer ce numéro de standard ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">🗑</button>
                            </form>
                        </td>
                    </tr>
                @endforeach

                {{-- Annuaire automatique (depuis la gestion des utilisateurs) --}}
                @forelse($contacts as $contact)
                    <tr data-search="{{ strtolower(\Illuminate\Support\Str::ascii($contact->prenom . ' ' . $contact->nom . ' ' . $contact->service_label)) }}">
                        <td style="font-size:13px;">
                            {{ $contact->service_label }}<br>
                            <span class="fw-semibold" style="font-size:14px;">{{ $contact->prenom }} {{ $contact->nom }}</span>
                            <span class="text-muted" style="font-size:12px;">({{ $contact->grade_label }})</span>
                        </td>
                        <td>{{ $contact->telephone_complet }}</td>
                        <td>{{ $contact->email ?? '—' }}</td>
                        <td></td>
                    </tr>
                @empty
                    @if($standards->isEmpty())
                        <tr><td colspan="4" class="text-center text-muted py-4">Aucun contact.</td></tr>
                    @endif
                @endforelse
                </tbody>
            </table>
            <div id="noResults" class="text-center text-muted py-4 d-none">Aucun résultat.</div>
        </div>
    </div>

</div>

<script>
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
