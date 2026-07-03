<?php

namespace App\Http\Requests\Auth;

use App\Models\Mairie;
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
     * Tous les champs sont obligatoires — sauf la mairie pour les
     * administrateurs (leurs identifiants passent par-dessus la sélection).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mairie_id' => 'nullable|exists:mairies,id',
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

        // La mairie est obligatoire pour tout le monde sauf les admins
        if (! $isAdmin && ! $this->filled('mairie_id')) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'mairie_id' => __('mgds.auth_mairie_required'),
            ]);
        }

        $credentials = $this->only('username', 'password');

        if (! $isAdmin) {
            // L'utilisateur doit appartenir à la mairie sélectionnée
            $credentials['mairie_id'] = (int) $this->input('mairie_id');

            $mairie = Mairie::find($this->input('mairie_id'));
            if ($mairie && $mairie->abonnementExpire()) {
                throw ValidationException::withMessages([
                    'mairie_id' => __('mgds.auth_abonnement_expire'),
                ]);
            }
        }

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
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
