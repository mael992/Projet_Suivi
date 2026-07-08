{{-- ⚙️ {{ __('Paramètres Administration') }} : les 4 onglets du dashboard admin --}}
<h1 class="h3 mb-3">⚙️ {{ __('Paramètres Administration') }}</h1>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
           href="{{ route('users.index') }}">{{ __('Gestion des utilisateurs') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('mairies.*') ? 'active' : '' }}"
           href="{{ route('mairies.index') }}">{{ __('Gestion des accès mairie') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}"
           href="{{ route('admin.logs.index') }}">{{ __("Logs d'activité") }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.messages.*') ? 'active' : '' }}"
           href="{{ route('admin.messages.index') }}">{{ __('Message Support') }}</a>
    </li>
</ul>
