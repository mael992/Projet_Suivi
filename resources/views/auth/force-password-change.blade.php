<x-guest-layout>

    <h5 class="fw-bold mb-1" style="font-size:1rem;">🔐 {{ __('messages.force_change_title') }}</h5>
    <p class="text-muted mb-3" style="font-size:13px;">{{ __('messages.force_change_subtitle') }}</p>

    @php $expires = auth()->user()->temp_password_expires_at; @endphp
    @if($expires)
        @php
            $diff    = now()->diff($expires);
            $heures  = ($diff->days * 24) + $diff->h;
            $minutes = $diff->i;
        @endphp
        <div class="alert py-2 px-3 mb-3" style="background:#fff3f3;border:1px solid #e30613;color:#c0040f;font-size:13px;">
            ⏱ Mot de passe provisoire valable encore
            <strong>{{ $heures }}h {{ str_pad($minutes, 2, '0', STR_PAD_LEFT) }}min</strong>
            <br><span style="font-size:11px;">(expire le {{ $expires->format('d/m/Y à H:i') }})</span>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success py-2 px-3 mb-3" style="font-size:13px;">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:13px;">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.force-change.update') }}">
        @csrf

        <div class="mb-3">
            <label for="password" class="form-label fw-semibold" style="font-size:13px;">
                {{ __('messages.force_change_new_password') }}
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
                {{ __('messages.force_change_confirm') }}
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
            {{ __('messages.force_change_btn') }}
        </button>
    </form>

    <div class="text-center mt-3">
        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
            @csrf
            <button type="submit" style="background:none;border:none;color:#aaa;font-size:12px;cursor:pointer;text-decoration:underline;">
                {{ __('messages.nav_logout') }}
            </button>
        </form>
    </div>

    <script>
    function togglePwd(id, eyeId) {
        const p = document.getElementById(id);
        const e = document.getElementById(eyeId);
        if (p.type === 'password') { p.type = 'text'; e.textContent = '🙈'; }
        else { p.type = 'password'; e.textContent = '👁'; }
    }
    </script>

</x-guest-layout>
