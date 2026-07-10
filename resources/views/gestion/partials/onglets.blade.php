{{-- Onglets de la {{ __('Gestion de la Mairie') }} (comme côté admin de PlanEx) --}}
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('gestion.utilisateurs.*') ? 'active' : '' }}"
           href="{{ route('gestion.utilisateurs.index') }}">{{ __('Gestion des utilisateurs') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('gestion.avancement') ? 'active' : '' }}"
           href="{{ route('gestion.avancement') }}">{{ __('Avancement des tâches') }}</a>
    </li>
</ul>
