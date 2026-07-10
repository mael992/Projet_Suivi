<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin\MairieController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Gestion\AvancementController;
use App\Http\Controllers\Gestion\ContactFicheController;
use App\Http\Controllers\Gestion\UserController as GestionUserController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Marche\CommercantController;
use App\Http\Controllers\Marche\PlanController;
use App\Http\Controllers\Marche\RegistreController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TacheController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

// ── LANGUE (français / anglais) ──────────────────────────────
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');

// ── PAGES PUBLIQUES ──────────────────────────────────────────
Route::get('/',           [PageController::class, 'home'])->name('home');
Route::get('/infos',      [PageController::class, 'infos'])->name('infos');
Route::get('/nouveautes', [PageController::class, 'nouveautes'])->name('nouveautes');
Route::get('/contact',    [PageController::class, 'contact'])->name('contact');

// ── ESPACE CONNECTÉ ──────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Hub : gestionnaire des applications de MGDS
    Route::get('/apps', fn () => view('apps'))->name('apps');

    // Suivi des tâches de la mairie
    Route::get('/dashboard', [TacheController::class, 'index'])->name('dashboard');
    Route::resource('taches', TacheController::class)->except('index')
        ->parameters(['taches' => 'tache']);

    // Application Marché 🛍
    Route::prefix('marche')->name('marche.')->group(function () {
        Route::get('/', fn () => redirect()->route('marche.plan'));

        // 🗺️ Plan 2D daté
        Route::get('/plan', [PlanController::class, 'index'])->name('plan');
        Route::post('/plans', [PlanController::class, 'storePlan'])->name('plans.store');
        Route::put('/plans/{plan}', [PlanController::class, 'updatePlan'])->name('plans.update');
        Route::delete('/plans/{plan}', [PlanController::class, 'destroyPlan'])->name('plans.destroy');
        Route::post('/plans/{plan}/axes', [PlanController::class, 'storeAxe'])->name('axes.store');
        Route::delete('/axes/{axe}', [PlanController::class, 'destroyAxe'])->name('axes.destroy');
        Route::post('/plans/{plan}/image', [PlanController::class, 'storeImage'])->name('plans.image');
        Route::post('/plans/{plan}/stands', [PlanController::class, 'storeStand'])->name('stands.store');
        Route::put('/stands/{emplacement}', [PlanController::class, 'updateStand'])->name('stands.update');
        Route::post('/plans/{plan}/positions', [PlanController::class, 'savePositions'])->name('stands.positions');
        Route::post('/axes/{axe}/emplacements', [PlanController::class, 'storeEmplacement'])->name('emplacements.store');
        Route::put('/emplacements/{emplacement}', [PlanController::class, 'updateEmplacement'])->name('emplacements.update');
        Route::delete('/emplacements/{emplacement}', [PlanController::class, 'destroyEmplacement'])->name('emplacements.destroy');

        // 🏦 Registre des commerçants
        Route::get('/registre', [RegistreController::class, 'index'])->name('registre');

        // 👥 Commerçants
        Route::get('/commercants', [CommercantController::class, 'index'])->name('commercants');
        Route::get('/commercants/create', [CommercantController::class, 'create'])->name('commercants.create');
        Route::post('/commercants', [CommercantController::class, 'store'])->name('commercants.store');
        Route::put('/commercants/{commercant}', [CommercantController::class, 'update'])->name('commercants.update');
        Route::delete('/commercants/{commercant}', [CommercantController::class, 'destroy'])->name('commercants.destroy');
    });

    // Profil
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ── GESTION DE LA MAIRIE (responsables & sous-responsables) ──
Route::middleware(['auth', 'gestion'])->prefix('gestion')->name('gestion.')->group(function () {

    // Onglet 1 : gestion des utilisateurs
    Route::resource('utilisateurs', GestionUserController::class)
        ->parameters(['utilisateurs' => 'user']);
    Route::get('/utilisateurs/{user}/courrier', [GestionUserController::class, 'courrier'])->name('utilisateurs.courrier');

    // Onglet 2 : fiche contact
    Route::get('/contacts',                    [ContactFicheController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/pdf',                [ContactFicheController::class, 'pdf'])->name('contacts.pdf');
    Route::post('/contacts/standards',         [ContactFicheController::class, 'storeStandard'])->name('contacts.standards.store');
    Route::put('/contacts/standards/{standard}',    [ContactFicheController::class, 'updateStandard'])->name('contacts.standards.update');
    Route::delete('/contacts/standards/{standard}', [ContactFicheController::class, 'destroyStandard'])->name('contacts.standards.destroy');

    // Onglet 3 : avancement des tâches de travail
    Route::get('/avancement', [AvancementController::class, 'index'])->name('avancement');
});

// ── ADMIN UNIQUEMENT ─────────────────────────────────────────
Route::middleware(['auth', 'admin'])->group(function () {

    // Utilisateurs (toutes mairies — colonnes mairie + équipe)
    Route::resource('users', AdminUserController::class);
    Route::get('/users/{user}/courrier', [AdminUserController::class, 'courrier'])->name('users.courrier');

    // Gestionnaire des accès mairie
    Route::resource('mairies', MairieController::class)->except('show')
        ->parameters(['mairies' => 'mairie']);
    Route::post('/mairies/{mairie}/observateurs',                 [MairieController::class, 'storeObservateur'])->name('mairies.observateurs.store');
    Route::delete('/mairies/{mairie}/observateurs/{observateur}', [MairieController::class, 'destroyObservateur'])->name('mairies.observateurs.destroy');

    // Logs d'activité
    Route::get('/admin/logs',          [ActivityLogController::class, 'index'])->name('admin.logs.index');
    Route::get('/admin/logs/download', [ActivityLogController::class, 'download'])->name('admin.logs.download');

    // Messages (à venir — sera programmé plus tard)
    Route::get('/admin/messages', fn () => view('admin.messages.index'))->name('admin.messages.index');
});
