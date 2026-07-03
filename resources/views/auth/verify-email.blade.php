<x-guest-layout>

    <h5 class="fw-bold mb-1" style="font-size:1rem;">✉️ Vérification de l'adresse e-mail</h5>
    <p class="text-muted mb-3" style="font-size:13px;">
        Merci pour votre inscription. Avant de continuer, veuillez vérifier votre adresse e-mail en cliquant sur le lien que nous venons de vous envoyer.
    </p>

    @if(session('status') == 'verification-link-sent')
        <div class="alert alert-success py-2 px-3 mb-3" style="font-size:13px;">
            Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
        </div>
    @endif

    <div class="d-flex align-items-center justify-content-between gap-2 mt-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn fw-semibold py-2 px-4"
                    style="background:var(--brand);color:#fff;border:none;border-radius:var(--radius);font-size:14px;"
                    onmouseover="this.style.background='var(--brand-dark)'"
                    onmouseout="this.style.background='var(--brand)'">
                Renvoyer le lien
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" style="background:none;border:none;color:#aaa;font-size:12px;cursor:pointer;text-decoration:underline;">
                {{ __('messages.nav_logout') }}
            </button>
        </form>
    </div>

</x-guest-layout>
