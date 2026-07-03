<x-guest-layout>

    <h5 class="fw-bold mb-1" style="font-size:1rem;">🔑 Mot de passe oublié</h5>
    <p class="text-muted mb-3" style="font-size:13px;">{{ __('messages.auth_forgot_desc') }}</p>

    @if(session('status'))
        <div class="alert alert-success py-2 px-3 mb-3" style="font-size:13px;">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:13px;">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-4">
            <label for="email" class="form-label fw-semibold" style="font-size:13px;">
                {{ __('messages.col_email') }}
            </label>
            <input type="email" id="email" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required autofocus
                   placeholder="votre@email.com">
        </div>

        <button type="submit" class="btn w-100 fw-semibold py-2"
                style="background:var(--brand);color:#fff;border:none;border-radius:var(--radius);font-size:15px;"
                onmouseover="this.style.background='var(--brand-dark)'"
                onmouseout="this.style.background='var(--brand)'">
            {{ __('messages.auth_send_reset_link') }}
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}" style="font-size:12px;color:#aaa;text-decoration:none;">
            ← Retour à la connexion
        </a>
    </div>

</x-guest-layout>
