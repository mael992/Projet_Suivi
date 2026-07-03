<x-guest-layout>

    {{-- Message de statut (ex: lien mot de passe envoyé) --}}
    @if(session('status'))
        <div class="alert alert-success py-2 px-3 mb-3" style="font-size:13px;">
            {{ session('status') }}
        </div>
    @endif

    {{-- Erreur mot de passe temporaire expiré --}}
    @if(session('error') === 'temp_password_expired')
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:13px;">
            ⚠️ Votre mot de passe temporaire a expiré (48h). Contactez un administrateur.
        </div>
    @endif

    {{-- Erreurs globales --}}
    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:13px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        {{-- Mairie (obligatoire sauf pour les administrateurs) --}}
        <div class="mb-3">
            <label for="mairie_id" class="form-label fw-semibold" style="font-size:13px;">
                {{ __('mgds.auth_mairie') }}
            </label>
            <select
                id="mairie_id"
                name="mairie_id"
                class="form-select @error('mairie_id') is-invalid @enderror">
                <option value="">{{ __('mgds.auth_mairie_placeholder') }}</option>
                @foreach($mairies as $mairie)
                    <option value="{{ $mairie->id }}" @selected(old('mairie_id') == $mairie->id)>
                        {{ $mairie->nom }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Identifiant --}}
        <div class="mb-3">
            <label for="username" class="form-label fw-semibold" style="font-size:13px;">
                {{ __('messages.auth_username') }}
            </label>
            <input
                type="text"
                id="username"
                name="username"
                class="form-control @error('username') is-invalid @enderror"
                value="{{ old('username') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="prenom.nom"
            >
        </div>

        {{-- Mot de passe --}}
        <div class="mb-3">
            <label for="password" class="form-label fw-semibold" style="font-size:13px;">
                {{ __('messages.auth_password') }}
            </label>
            <div class="input-group">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                >
                <button type="button" class="btn btn-outline-secondary" tabindex="-1"
                        onclick="togglePwd()" title="Afficher/Masquer">
                    <span id="eyeIcon">👁</span>
                </button>
            </div>
        </div>

        {{-- Se souvenir de moi --}}
        <div class="mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                <label class="form-check-label" for="remember_me" style="font-size:13px;color:#555;">
                    {{ __('messages.auth_remember') }}
                </label>
            </div>
        </div>

        {{-- Bouton connexion --}}
        <button type="submit" class="btn w-100 fw-semibold py-2"
                style="background:var(--brand);color:#fff;border:none;border-radius:var(--radius);font-size:15px;letter-spacing:.01em;transition:background .2s;"
                onmouseover="this.style.background='var(--brand-dark)'"
                onmouseout="this.style.background='var(--brand)'">
            {{ __('messages.auth_sign_in') }}
        </button>

        {{-- Mot de passe oublié --}}
        @if(Route::has('password.request'))
        <div class="text-center mt-3">
            <a href="{{ route('password.request') }}"
               style="font-size:12px;color:#888;text-decoration:none;">
                {{ __('messages.auth_forgot_password') }}
            </a>
        </div>
        @endif

    </form>

    <script>
    function togglePwd() {
        const p = document.getElementById('password');
        const e = document.getElementById('eyeIcon');
        if (p.type === 'password') { p.type = 'text'; e.textContent = '🙈'; }
        else { p.type = 'password'; e.textContent = '👁'; }
    }
    </script>

</x-guest-layout>
