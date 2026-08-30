@extends('layouts.app')

@php use App\Models\Devis; @endphp

@section('content')
<div class="container py-4" style="max-width:1000px;">

    @include('admin.partials.onglets')

    <h2 class="h5 mb-1">🧾 {{ __('Devis d\'abonnement') }}</h2>
    <p class="text-muted mb-3" style="font-size:14px;">
        {{ __('Établissez un devis conforme (mentions obligatoires) pour une commune, puis téléchargez-le en PDF.') }}
    </p>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    @if(! $editeur['siret'] || ! $editeur['forme'])
        <div class="alert alert-warning" style="font-size:13px;">
            ⚠️ {{ __('Vos coordonnées d\'éditeur sont incomplètes (forme juridique, SIRET…). Un devis sans ces mentions n\'est pas conforme : renseignez-les dans le fichier .env du serveur.') }}
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-header py-2 fw-semibold">➕ {{ __('Nouveau devis') }}</div>
        <form method="POST" action="{{ route('admin.devis.store') }}" class="card-body">
            @csrf
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Mairie') }}</label>
                    <select name="mairie_id" class="form-select form-select-sm" id="mairieDevis" onchange="remplirClient()">
                        <option value="">— {{ __('Client libre') }} —</option>
                        @foreach($mairies as $m)
                            <option value="{{ $m->id }}" data-nom="{{ $m->nom }}" data-email="{{ $m->email }}"
                                    data-cp="{{ $m->code_postal }}">{{ $m->nom }} ({{ $m->code_postal }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Nom du client') }} *</label>
                    <input type="text" name="client_nom" id="clientNom" value="{{ old('client_nom') }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('E-mail du client') }}</label>
                    <input type="email" name="client_email" id="clientEmail" value="{{ old('client_email') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Adresse du client') }}</label>
                    <input type="text" name="client_adresse" value="{{ old('client_adresse') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Date') }} *</label>
                    <input type="date" name="date_devis" value="{{ old('date_devis', now()->toDateString()) }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Validité (jours)') }} *</label>
                    <input type="number" name="validite_jours" value="{{ old('validite_jours', 30) }}" min="1" max="365" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('TVA (%)') }} *</label>
                    <input type="number" step="0.1" name="taux_tva" value="{{ old('taux_tva', 20) }}" min="0" max="30" class="form-control form-control-sm" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Lieu d\'exécution') }}</label>
                    <input type="text" name="lieu_execution" value="{{ old('lieu_execution', 'Plateforme en ligne m-gds.com') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Délai d\'exécution') }}</label>
                    <input type="text" name="delai_execution" value="{{ old('delai_execution', 'Mise en service sous 5 jours ouvrés après accord') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Modalités de paiement') }}</label>
                    <input type="text" name="modalites_paiement" value="{{ old('modalites_paiement', 'Virement à réception de facture, à 30 jours') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Conditions d\'exécution') }}</label>
                    <input type="text" name="conditions" value="{{ old('conditions', 'Abonnement annuel, hébergement et support inclus') }}" class="form-control form-control-sm">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Prestations') }} *</label>
                    <table class="table table-sm align-middle mb-1">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Désignation') }}</th>
                                <th style="width:110px;">{{ __('Quantité') }}</th>
                                <th style="width:140px;">{{ __('Prix unitaire HT') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @for($i = 0; $i < 4; $i++)
                            <tr>
                                <td><input type="text" name="lignes[{{ $i }}][designation]" class="form-control form-control-sm"
                                           value="{{ old("lignes.$i.designation", $i === 0 ? 'Abonnement annuel MGDS — accès à toutes les applications' : '') }}"></td>
                                <td><input type="number" step="0.5" min="0" name="lignes[{{ $i }}][quantite]" class="form-control form-control-sm"
                                           value="{{ old("lignes.$i.quantite", $i === 0 ? 1 : 0) }}"></td>
                                <td><input type="number" step="0.01" min="0" name="lignes[{{ $i }}][prix_unitaire]" class="form-control form-control-sm"
                                           value="{{ old("lignes.$i.prix_unitaire", 0) }}"></td>
                            </tr>
                        @endfor
                        </tbody>
                    </table>
                    <small class="text-muted">{{ __('Les lignes sans désignation sont ignorées.') }}</small>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3">{{ __('Créer le devis') }}</button>
        </form>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>{{ __('Référence') }}</th>
                        <th>{{ __('Client') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th class="text-end">{{ __('Total TTC') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($devis as $d)
                    <tr>
                        <td class="fw-semibold">{{ $d->reference }}</td>
                        <td>{{ $d->client_nom }}</td>
                        <td style="font-size:13px;">
                            {{ $d->date_devis->format('d/m/Y') }}
                            <div class="text-muted" style="font-size:11px;">{{ __('valable au') }} {{ $d->valableJusquau()->format('d/m/Y') }}</div>
                        </td>
                        <td class="text-end fw-semibold">{{ number_format($d->totalTtc(), 2, ',', ' ') }} €</td>
                        <td>
                            <form method="POST" action="{{ route('admin.devis.statut', $d) }}">
                                @csrf
                                <select name="statut" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                                    @foreach(Devis::STATUTS as $cle => $label)
                                        <option value="{{ $cle }}" @selected($d->statut === $cle)>{{ __($label) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.devis.pdf', $d) }}" class="btn btn-sm btn-outline-dark">⬇ PDF</a>
                            <form action="{{ route('admin.devis.destroy', $d) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('{{ __('Supprimer ce devis ?') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">🗑</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Aucun devis pour le moment.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Pré-remplit le client à partir de la mairie choisie
function remplirClient() {
    const sel = document.getElementById('mairieDevis');
    const opt = sel.options[sel.selectedIndex];
    if (! sel.value) return;
    document.getElementById('clientNom').value   = opt.dataset.nom || '';
    document.getElementById('clientEmail').value = opt.dataset.email || '';
}
</script>
@endsection
