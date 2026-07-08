@extends('layouts.app')

@section('content')
@php $user = auth()->user(); @endphp

<div class="container py-4" style="max-width:640px;">

    <h1 class="h3 mb-4">{{ __('Mon compte') }}</h1>

    {{-- ── Identifiants (gérés par l'administration) ── --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">{{ __('Identifiants') }}</h2>
            <div class="row mb-2" style="font-size:14px;">
                <div class="col-5 fw-semibold">{{ __('Identifiant de connexion') }}</div>
                <div class="col-7">{{ $user->username }}</div>
            </div>
            <div class="row mb-2" style="font-size:14px;">
                <div class="col-5 fw-semibold">{{ __('Rôle') }}</div>
                <div class="col-7">{{ $user->isAdmin() ? 'Admin' : $user->grade_label }}</div>
            </div>
            @if(! $user->isAdmin() && $user->mairie)
            <div class="row mb-2" style="font-size:14px;">
                <div class="col-5 fw-semibold">Mairie / Service</div>
                <div class="col-7">{{ $user->mairie->nom }} — {{ $user->service_label }}</div>
            </div>
            @endif
            <p class="text-muted mb-0" style="font-size:13px;">
                Votre identifiant de connexion et votre rôle sont gérés par l'administration et ne peuvent pas
                être modifiés ici.
            </p>
        </div>
    </div>

    {{-- ── {{ __('Adresse e-mail') }} ── --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">{{ __('Adresse e-mail') }}</h2>

            @if(session('status') === 'profile-updated')
                <div class="alert alert-success py-2">{{ __('Adresse e-mail') }} mise à jour.</div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf @method('PATCH')

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">{{ __('Adresse e-mail') }} <span class="text-danger">*</span></label>
                    <input type="email" id="email" name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Utilisée pour recevoir les courriers et notifications de votre compte. Ce champ est obligatoire.</small>
                </div>

                <div class="mb-3">
                    <label for="current_password_email" class="form-label fw-semibold">{{ __('Mot de passe actuel') }}</label>
                    <input type="password" id="current_password_email" name="current_password"
                           class="form-control @error('current_password') is-invalid @enderror"
                           autocomplete="current-password">
                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">{{ __('Requis pour confirmer la modification.') }}</small>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
            </form>
        </div>
    </div>

    {{-- ── Mot de passe ── --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">{{ __('Mot de passe') }}</h2>

            @if(session('status') === 'password-updated')
                <div class="alert alert-success py-2">Mot de passe mis à jour.</div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf @method('PUT')

                <div class="mb-3">
                    <label for="update_password_current_password" class="form-label fw-semibold">{{ __('Mot de passe actuel') }}</label>
                    <input type="password" id="update_password_current_password" name="current_password"
                           class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                           autocomplete="current-password" required>
                    @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="update_password_password" class="form-label fw-semibold">{{ __('Nouveau mot de passe') }}</label>
                    <input type="password" id="update_password_password" name="password"
                           class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                           autocomplete="new-password" required>
                    @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">8 caractères minimum.</small>
                </div>

                <div class="mb-3">
                    <label for="update_password_password_confirmation" class="form-label fw-semibold">{{ __('Confirmer le nouveau mot de passe') }}</label>
                    <input type="password" id="update_password_password_confirmation" name="password_confirmation"
                           class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                           autocomplete="new-password" required>
                    @error('password_confirmation', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
            </form>
        </div>
    </div>

</div>
@endsection
