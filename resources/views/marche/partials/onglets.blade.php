{{-- Mini-onglets de l'application Marché 🛍 --}}
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <ul class="nav nav-tabs" style="flex:1;">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('marche.ville') || request()->routeIs('marche.zones.*') ? 'active' : '' }}"
               href="{{ route('marche.ville', request()->only('mairie')) }}">🏙️ {{ __('Ville') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('marche.commercants*') ? 'active' : '' }}"
               href="{{ route('marche.commercants', request()->only('mairie')) }}">👥 Commerçants</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('marche.registre') ? 'active' : '' }}"
               href="{{ route('marche.registre', request()->only('mairie')) }}">🏦 Registre</a>
        </li>
    </ul>

    {{-- Sélecteur de mairie (admins uniquement) --}}
    @if(auth()->user()->isAdmin() && $mairies->isNotEmpty())
        <form method="GET" class="d-flex align-items-center gap-2">
            <label style="font-size:12px;" class="text-muted mb-0">Mairie :</label>
            <select name="mairie" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                @foreach($mairies as $m)
                    <option value="{{ $m->id }}" @selected($mairie->id === $m->id)>{{ $m->nom }}</option>
                @endforeach
            </select>
        </form>
    @endif
</div>
