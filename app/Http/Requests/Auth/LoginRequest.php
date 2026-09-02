<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username'  => 'required|string',
            'password'  => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user    = User::where('username', $this->string('username'))->first();
        $isAdmin = $user?->role === 'admin';

        // La mairie reste attachée au profil : blocage si son abonnement a expiré
        if (! $isAdmin && $user?->mairie && $user->mairie->abonnementExpire()) {
            throw ValidationException::withMessages([
                'username' => __('mgds.auth_abonnement_expire'),
            ]);
        }

        $credentials = $this->only('username', 'password');

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            // Second essai : le mot de passe provisoire reçu par e-mail. Il
            // s'ajoute au mot de passe habituel sans l'avoir remplacé.
            if (! $this->connecterAvecPasswordProvisoire($user)) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'username' => trans('auth.failed'),
                ]);
            }
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Connexion par mot de passe provisoire : usage unique, et le mot de
     * passe habituel reste inchangé tant que la personne n'en a pas choisi
     * un nouveau (d'où le drapeau de session plutôt qu'en base).
     */
    private function connecterAvecPasswordProvisoire(?User $user): bool
    {
        if (! $user || ! $user->passwordProvisoireCorrespond($this->string('password')->toString())) {
            return false;
        }

        $user->consommerPasswordProvisoire();

        Auth::login($user);
        $this->session()->put('mdp_provisoire_utilise', true);

        return true;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }
}
