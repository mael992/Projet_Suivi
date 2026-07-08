@php
    $locale = app()->getLocale();
    // Langues : français / anglais uniquement
    $flagCodes  = ['fr' => 'fr', 'en' => 'gb'];
    $langLabels = ['fr' => 'FR', 'en' => 'EN'];
    $langNames  = ['fr' => 'Français', 'en' => 'English'];

    $isLoggedIn = auth()->check();
    $user       = auth()->user();

    // Surlignage de la page active (desktop + menu mobile)
    $actif = fn (string ...$routes) => request()->routeIs(...$routes) ? 'nav-actif' : '';
@endphp

<nav class="navbar">
    <div class="navbar-container">

        {{-- LOGO --}}
        <a href="{{ route('home') }}" class="logo">
            <img src="{{ asset('images/logo-mgds.png') }}" alt="MGDS">
        </a>

        {{-- LIENS CENTRE (desktop) --}}
        <ul class="nav-links-desktop">
            <li><a href="{{ route('home') }}" class="{{ $actif('home') }}">{{ __('mgds.nav_home') }}</a></li>
            <li><a href="{{ route('infos') }}" class="{{ $actif('infos') }}">{{ __('mgds.nav_infos') }}</a></li>
            <li><a href="{{ route('nouveautes') }}" class="{{ $actif('nouveautes') }}">{{ __('mgds.nav_news') }}</a></li>
            <li><a href="{{ route('contact') }}" class="{{ $actif('contact') }}">{{ __('mgds.nav_contact') }}</a></li>

            @if($isLoggedIn)
                <li><a href="{{ route('apps') }}" style="font-weight:600;" class="{{ $actif('apps') }}">{{ __('mgds.nav_apps') }}</a></li>
            @endif
        </ul>

        {{-- ZONE DROITE --}}
        <div class="nav-right">

            @auth
                <div class="nav-desktop-auth">
                    <a href="{{ route('profile.edit') }}" class="user text-decoration-none" title="{{ __('Mon compte') }}">
                        <span class="user-dot"></span>
                        {{ $user->username }}
                    </a>
                    <div class="nav-sep"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-logout">{{ __('mgds.nav_logout') }}</button>
                    </form>
                    <div class="nav-sep"></div>
                </div>
            @endauth

            @guest
                <div class="nav-desktop-auth">
                    <a href="{{ route('login') }}" class="btn-login">{{ __('mgds.nav_login') }}</a>
                    <div class="nav-sep"></div>
                </div>
            @endguest

            {{-- LANGUE --}}
            <div class="lang-dropdown" id="langDropdown">
                <button class="lang-dropdown-trigger" type="button"
                        onclick="toggleDropdown('langMenu')">
                    <span class="fi fi-{{ $flagCodes[$locale] ?? 'fr' }}"></span>
                    <span class="lang-code">{{ $langLabels[$locale] ?? 'FR' }}</span>
                    <span class="lang-arrow">▾</span>
                </button>
                <ul class="nav-dropdown-menu lang-dropdown-menu" id="langMenu">
                    @foreach($flagCodes as $code => $iso)
                        <li>
                            <a href="{{ route('lang.switch', $code) }}"
                               class="{{ $locale === $code ? 'lang-option-active' : '' }}"
                               onclick="closeAllDropdowns()">
                                <span class="fi fi-{{ $iso }}"></span>
                                <span>{{ $langNames[$code] }}</span>
                                @if($locale === $code)<span class="lang-check">✓</span>@endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- HAMBURGER --}}
            <button class="nav-hamburger" onclick="openNavMenu()" aria-label="Menu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>

        </div>
    </div>
</nav>

{{-- DRAWER MOBILE --}}
<div class="nav-mobile-overlay" id="navMobileOverlay" onclick="closeNavMenu()"></div>
<div class="nav-mobile-menu" id="navMobileMenu" role="dialog">

    <div class="nav-mobile-header">
        <img src="{{ asset('images/logo-mgds.png') }}" alt="MGDS" style="height:34px;">
        <button onclick="closeNavMenu()" class="nav-mobile-close">✕</button>
    </div>

    @auth
        <div class="nav-mobile-user">
            <span class="user-dot"></span>
            <span class="nav-mobile-username">{{ $user->username }}</span>
            <span class="nav-mobile-role">{{ $user->isAdmin() ? 'Admin' : $user->grade_label }}</span>
        </div>
        <div class="nav-mobile-divider"></div>
    @endauth

    <nav class="nav-mobile-links">
        <a href="{{ route('home') }}"       onclick="closeNavMenu()" class="{{ $actif('home') }}"><span class="nav-mobile-icon">🏠</span>{{ __('mgds.nav_home') }}</a>
        <a href="{{ route('infos') }}"      onclick="closeNavMenu()" class="{{ $actif('infos') }}"><span class="nav-mobile-icon">ℹ️</span>{{ __('mgds.nav_infos') }}</a>
        <a href="{{ route('nouveautes') }}" onclick="closeNavMenu()" class="{{ $actif('nouveautes') }}"><span class="nav-mobile-icon">🆕</span>{{ __('mgds.nav_news') }}</a>
        <a href="{{ route('contact') }}"    onclick="closeNavMenu()" class="{{ $actif('contact') }}"><span class="nav-mobile-icon">✉️</span>{{ __('mgds.nav_contact') }}</a>

        @auth
            <div class="nav-mobile-divider"></div>
            <a href="{{ route('apps') }}" onclick="closeNavMenu()" class="nav-mobile-special {{ $actif('apps') }}">
                <span class="nav-mobile-icon">🧩</span>{{ __('mgds.nav_apps') }}
            </a>
            <a href="{{ route('profile.edit') }}" onclick="closeNavMenu()" class="{{ $actif('profile.edit') }}">
                <span class="nav-mobile-icon">👤</span>{{ __('Mon compte') }}
            </a>
        @endauth
    </nav>

    <div class="nav-mobile-footer">
        <div class="nav-mobile-divider"></div>

        {{-- Langue --}}
        <div class="nav-mobile-langs">
            @foreach($flagCodes as $code => $iso)
                <a href="{{ route('lang.switch', $code) }}"
                   class="nav-mobile-lang {{ $locale === $code ? 'nav-mobile-lang--active' : '' }}">
                    <span class="fi fi-{{ $iso }}"></span>
                    {{ $langLabels[$code] }}
                </a>
            @endforeach
        </div>

        <div class="nav-mobile-divider"></div>

        @auth
            <form method="POST" action="{{ route('logout') }}" style="padding:0 16px 10px">
                @csrf
                <button type="submit" class="btn-logout-mobile">{{ __('mgds.nav_logout') }}</button>
            </form>
        @endauth
        @guest
            <div style="padding:0 16px 8px">
                <a href="{{ route('login') }}" class="btn-login-mobile">
                    {{ __('mgds.nav_login') }}
                </a>
            </div>
        @endguest
    </div>
</div>

<script>
function openNavMenu() {
    document.getElementById('navMobileMenu').classList.add('open');
    document.getElementById('navMobileOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeNavMenu() {
    document.getElementById('navMobileMenu').classList.remove('open');
    document.getElementById('navMobileOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
const _dropdowns = ['langMenu'];
function toggleDropdown(id) {
    const isOpen = document.getElementById(id).classList.contains('open');
    closeAllDropdowns();
    if (!isOpen) document.getElementById(id).classList.add('open');
}
function closeAllDropdowns() {
    _dropdowns.forEach(id => document.getElementById(id)?.classList.remove('open'));
}
document.addEventListener('click', e => {
    if (!e.target.closest('.nav-dropdown, .lang-dropdown')) closeAllDropdowns();
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeAllDropdowns(); closeNavMenu(); }
});
</script>
