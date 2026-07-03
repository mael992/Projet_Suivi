{{-- Onglets de la Gestion de la Mairie (comme côté admin de PlanEx) --}}
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('gestion.utilisateurs.*') ? 'active' : '' }}"
           href="{{ route('gestion.utilisateurs.index') }}">Gestion des utilisateurs</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('gestion.contacts.*') ? 'active' : '' }}"
           href="{{ route('gestion.contacts.index') }}">Fiche Contact</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('gestion.avancement') ? 'active' : '' }}"
           href="{{ route('gestion.avancement') }}">Avancement des tâches</a>
    </li>
</ul>
