<x-guest-layout>

    <h5 class="fw-bold mb-1" style="font-size:1rem;">🔐 Zone sécurisée</h5>
    <p class="text-muted mb-3" style="font-size:13px;">
        Veuillez confirmer votre mot de passe avant de continuer.
    </p>

    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:13px;">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-4">
            <label for="password" class="form-label fw-semibold" style="font-size:13px;">
                Mot de passe
            </label>
            <div class="input-group">
                <input type="password" id="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       required autocomplete="current-password" placeholder="••••••••">
                <button type="button" class="btn btn-outline-secondary" tabindex="-1"
                        onclick="togglePwd()"><span id="eyeIcon">👁</span></button>
            </div>
        </div>

        <button type="submit" class="btn w-100 fw-semibold py-2"
                style="background:var(--brand);color:#fff;border:none;border-radius:var(--radius);font-size:15px;"
                onmouseover="this.style.background='var(--brand-dark)'"
                onmouseout="this.style.background='var(--brand)'">
            Confirmer
        </button>
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
