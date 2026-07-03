<x-guest-layout>

    <h5 class="fw-bold mb-1" style="font-size:1rem;">🔒 Réinitialiser le mot de passe</h5>
    <p class="text-muted mb-3" style="font-size:13px;">Choisissez un nouveau mot de passe sécurisé.</p>

    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:13px;">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <label for="email" class="form-label fw-semibold" style="font-size:13px;">
                Adresse e-mail
            </label>
            <input type="email" id="email" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
        </div>

        <div class="mb-3">
            <label for="password" class="form-label fw-semibold" style="font-size:13px;">
                Nouveau mot de passe
            </label>
            <div class="input-group">
                <input type="password" id="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       required minlength="8" autocomplete="new-password" placeholder="••••••••">
                <button type="button" class="btn btn-outline-secondary" tabindex="-1"
                        onclick="togglePwd('password','eye1')"><span id="eye1">👁</span></button>
            </div>
            <small class="text-muted">Minimum 8 caractères</small>
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label fw-semibold" style="font-size:13px;">
                Confirmer le mot de passe
            </label>
            <div class="input-group">
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="form-control" required minlength="8" autocomplete="new-password" placeholder="••••••••">
                <button type="button" class="btn btn-outline-secondary" tabindex="-1"
                        onclick="togglePwd('password_confirmation','eye2')"><span id="eye2">👁</span></button>
            </div>
        </div>

        <button type="submit" class="btn w-100 fw-semibold py-2"
                style="background:var(--brand);color:#fff;border:none;border-radius:var(--radius);font-size:15px;"
                onmouseover="this.style.background='var(--brand-dark)'"
                onmouseout="this.style.background='var(--brand)'">
            Réinitialiser le mot de passe
        </button>
    </form>

    <script>
    function togglePwd(id, eyeId) {
        const p = document.getElementById(id);
        const e = document.getElementById(eyeId);
        if (p.type === 'password') { p.type = 'text'; e.textContent = '🙈'; }
        else { p.type = 'password'; e.textContent = '👁'; }
    }
    </script>

</x-guest-layout>
