@extends('layouts.app')

@php $mairieParam = request()->filled('mairie') ? ['mairie' => request('mairie')] : []; @endphp

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    <a href="{{ route('apps') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">← {{ __('mgds.nav_apps') }}</a>

    <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
        <span style="font-size:13px;">
            🧪 {{ __('Présentation « option 2 » — à comparer avec la vue aérienne avant de choisir.') }}
        </span>
        <a href="{{ route('marche.ville', $mairieParam) }}" class="btn btn-sm btn-outline-dark">
            🏙️ {{ __('Voir l\'option 1 (vue aérienne)') }}
        </a>
    </div>

    <h1 class="h3 mb-3">🛍️ {{ __('Marché') }} — {{ $mairie->nom }}</h1>

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
                <input type="text" id="rechercheMarche" class="search-input"
                       placeholder="{{ __('Rechercher un marché ou une date…') }}" autocomplete="off">
            </div>
        </div>
        <button class="btn btn-primary" onclick="ouvrirModaleMarche()">+ {{ __('Ajouter un nouveau marché') }}</button>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>{{ __('Nom du marché') }}</th>
                        <th>{{ __('Date du déroulement') }}</th>
                        <th>{{ __('Endroits') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody id="corpsMarches">
                @forelse($marches as $marche)
                    <tr class="{{ $marche->estPasse() ? 'text-muted' : '' }}"
                        data-recherche="{{ strtolower(\Illuminate\Support\Str::ascii($marche->nom . ' ' . $marche->dateLabel())) }}">
                        <td class="fw-semibold">{{ $marche->nom }}</td>
                        <td>
                            {{ $marche->dateLabel() }}
                            @if($marche->estPasse())
                                <span class="badge bg-secondary ms-1" style="font-size:10px;">{{ __('Passé') }}</span>
                            @endif
                        </td>
                        <td>{{ $marche->endroits_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('marche.liste.show', array_merge(['marche' => $marche->id], $mairieParam)) }}"
                               class="btn btn-sm btn-primary">{{ __('Ouvrir') }}</a>
                            <form method="POST" class="d-inline"
                                  action="{{ route('marche.liste.destroy', array_merge(['marche' => $marche->id], $mairieParam)) }}"
                                  onsubmit="return confirm('{{ __('Supprimer ce marché ? Ses endroits et leurs plans sont conservés.') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">{{ __('Supprimer') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">{{ __('Aucun marché enregistré.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Modale : nouveau marché ── --}}
<div id="modaleMarche" class="d-none position-fixed top-0 start-0 w-100 h-100" style="background:rgba(0,0,0,.5);z-index:1050;">
    <div class="bg-white rounded shadow position-absolute top-50 start-50 translate-middle p-4" style="width:100%;max-width:420px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">{{ __('Ajouter un nouveau marché') }}</h2>
            <button type="button" class="btn-close" onclick="fermerModaleMarche()"></button>
        </div>
        <form method="POST" action="{{ route('marche.liste.store', $mairieParam) }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Nom du nouveau marché') }} *</label>
                <input type="text" name="nom" class="form-control" maxlength="120" required
                       value="{{ old('nom') }}" placeholder="{{ __('ex : Marché du samedi matin') }}">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Date du déroulement') }}</label>
                <input type="date" name="date_deroulement" class="form-control" value="{{ old('date_deroulement') }}">
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="fermerModaleMarche()">{{ __('Annuler') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Créer') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Recherche immediate cote navigateur : la liste peut s'allonger vite
    document.getElementById('rechercheMarche').addEventListener('input', function () {
        const q = this.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
        document.querySelectorAll('#corpsMarches tr[data-recherche]').forEach(ligne => {
            ligne.style.display = (! q || ligne.dataset.recherche.includes(q)) ? '' : 'none';
        });
    });

    const modaleMarche = document.getElementById('modaleMarche');
    function ouvrirModaleMarche() { modaleMarche.classList.remove('d-none'); }
    function fermerModaleMarche() { modaleMarche.classList.add('d-none'); }
    @if($errors->any() && old('nom')) ouvrirModaleMarche(); @endif
</script>
@endsection
