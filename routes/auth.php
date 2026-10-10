<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\DoubleAuthentificationController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Http\Controllers\Auth\MotDePasseProvisoireController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Inscription publique désactivée — les comptes sont créés par l'admin uniquement
    Route::get('register', fn() => redirect()->route('login'))->name('register');
    Route::post('register', fn() => redirect()->route('login'));

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Double authentification : code reçu par e-mail après le mot de passe
    Route::get('verification-connexion', [DoubleAuthentificationController::class, 'connexion'])
        ->name('a2f.connexion');
    Route::post('verification-connexion', [DoubleAuthentificationController::class, 'verifierConnexion'])
        ->middleware('throttle:10,1')
        ->name('a2f.connexion.verifier');
    Route::post('verification-connexion/renvoyer', [DoubleAuthentificationController::class, 'renvoyerConnexion'])
        ->middleware('throttle:3,1')
        ->name('a2f.connexion.renvoyer');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    // « Recevoir un mot de passe provisoire » : n'écrase pas le mot de passe actuel
    Route::post('forgot-password/provisoire', [MotDePasseProvisoireController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.provisoire');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Double authentification avant une modification importante du compte
    Route::get('verification-modification', [DoubleAuthentificationController::class, 'action'])
        ->name('a2f.action');
    Route::post('verification-modification', [DoubleAuthentificationController::class, 'verifierAction'])
        ->middleware('throttle:10,1')
        ->name('a2f.action.verifier');
    Route::post('verification-modification/renvoyer', [DoubleAuthentificationController::class, 'renvoyerAction'])
        ->middleware('throttle:3,1')
        ->name('a2f.action.renvoyer');

    // Changement de mot de passe obligatoire (première connexion)
    Route::get('password/change-required',  [ForcePasswordChangeController::class, 'show'])->name('password.force-change');
    Route::post('password/change-required', [ForcePasswordChangeController::class, 'update'])->name('password.force-change.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
