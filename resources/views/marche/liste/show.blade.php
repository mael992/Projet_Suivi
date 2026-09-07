@extends('layouts.app')

@php
    use App\Models\MarcheZone;
    $mairieParam = request()->filled('mairie') ? ['mairie' => request('mairie')] : [];
@endphp

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    <a href="{{ route('marche.liste.index', $mairieParam) }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">
        ← {{ __('Retour') }}
    </a>

    <h1 class="h3 mb-1">🛍️ {{ __('Marché') }} : {{ $marche->nom }}</h1>
    <p class="text-muted mb-3" style="font-size:13px;">
        {{ __('Déroulement') }} : <strong>{{ $marche->dateLabel() }}</strong> ·
        {{ __('Cliquez sur un endroit pour ouvrir son plan 2D / 3D.') }}
    </p>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    {{-- ── Renommer / redater le marché ── --}}
    <form method="POST" action="{{ route('marche.liste.update', array_merge(['marche' => $marche->id], $mairieParam)) }}"
          class="card shadow-sm mb-3">
        @csrf @method('PUT')
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Nom du marché') }} *</label>
                    <input type="text" name="nom" class="form-control" maxlength="120" required value="{{ old('nom', $marche->nom) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Date du déroulement') }}</label>
                    <input type="date" name="date_deroulement" class="form-control"
                           value="{{ old('date_deroulement', $marche->date_deroulement?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-outline-primary">{{ __('Enregistrer') }}</button>
                </div>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div style="max-width:400px;flex:1;">
            <div class="search-input-group">
                <span class="search-icon">🔍</span>
                <input type="text" id="rechercheEndroit" class="search-input"
                       placeholder="{{ __('Rechercher une rue, une place…') }}" autocomplete="off">
            </div>
        </div>
        <button class="btn btn-primary" onclick="ouvrirModaleEndroit()">
            + {{ __('Ajouter un nom de rue, place…') }}
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>{{ __('Nom de la rue, place…') }}</th>
                        <th>{{ __('Type d\'endroit') }}</th>
                        <th>{{ __('Date de modification') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody id="corpsEndroits">
                @forelse($endroits as $endroit)
                    <tr data-recherche="{{ strtolower(\Illuminate\Support\Str::ascii($endroit->nom . ' ' . $endroit->type_label)) }}">
                        <td class="fw-semibold">{{ $endroit->nom }}</td>
                        <td>{{ $endroit->type_label }}</td>
                        <td style="font-size:13px;">{{ $endroit->updated_at?->format('d/m/Y à H:i') ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('marche.zones.show', array_merge(['zone' => $endroit->id], $mairieParam)) }}"
                               class="btn btn-sm btn-primary">🗺️ {{ __('Plan 2D / 3D') }}</a>
                            <form method="POST" class="d-inline"
                                  action="{{ route('marche.liste.endroits.detach', array_merge(['marche' => $marche->id, 'zone' => $endroit->id], $mairieParam)) }}"
                                  onsubmit="return confirm('{{ __('Retirer cet endroit du marché ? Son plan est conservé.') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-secondary">{{ __('Retirer') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">{{ __('Aucun endroit pour ce marché.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Modale : ajouter un endroit ── --}}
<div id="modaleEndroit" class="d-none position-fixed top-0 start-0 w-100 h-100" style="background:rgba(0,0,0,.5);z-index:1050;">
    <div class="bg-white rounded shadow position-absolute top-50 start-50 translate-middle p-4" style="width:100%;max-width:420px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">{{ __('Ajouter une zone') }}</h2>
            <button type="button" class="btn-close" onclick="fermerModaleEndroit()"></button>
        </div>
        <form method="POST" action="{{ route('marche.liste.endroits.store', array_merge(['marche' => $marche->id], $mairieParam)) }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Type d\'endroit') }} *</label>
                <select name="type" class="form-select" required>
                    <option value="">{{ __('Veuillez choisir le type d\'endroit') }}</option>
                    @foreach(MarcheZone::TYPES as $cle => $label)
                        <option value="{{ $cle }}" @selected(old('type') === $cle)>{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Nom de l\'endroit') }} *</label>
                <input type="text" name="nom" class="form-control" maxlength="100" required
                       value="{{ old('nom') }}" placeholder="{{ __('ex : Place de la Mairie') }}">
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="fermerModaleEndroit()">{{ __('Annuler') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Ajouter') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('rechercheEndroit').addEventListener('input', function () {
        const q = this.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
        document.querySelectorAll('#corpsEndroits tr[data-recherche]').forEach(ligne => {
            ligne.style.display = (! q || ligne.dataset.recherche.includes(q)) ? '' : 'none';
        });
    });

    const modaleEndroit = document.getElementById('modaleEndroit');
    function ouvrirModaleEndroit() { modaleEndroit.classList.remove('d-none'); }
    function fermerModaleEndroit() { modaleEndroit.classList.add('d-none'); }
    @if($errors->any() && old('type')) ouvrirModaleEndroit(); @endif
</script>
@endsection
