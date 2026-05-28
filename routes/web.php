<?php

use App\Http\Controllers\Arbitro\ArbitroController;
use App\Http\Controllers\Auth\CambioContrasenaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Colegio\ColegioController;
use Illuminate\Support\Facades\Route;

// Página pública
Route::get('/', fn () => view('welcome'))->name('welcome');

// Autenticación (solo para invitados)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1');
});

// Rutas privadas (requieren autenticación)
Route::middleware(['auth', 'verificar.colegio'])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Cambio de contraseña obligatorio
    Route::get('/cambiar-contrasena',  [CambioContrasenaController::class, 'show'])->name('password.change');
    Route::post('/cambiar-contrasena', [CambioContrasenaController::class, 'update'])->name('password.change.update');

    // ── Árbitros ─────────────────────────────────────────────────────────────
    Route::prefix('arbitros')->name('arbitros.')->middleware('permission:ver-arbitros')->group(function () {
        Route::get('/',            [ArbitroController::class, 'index'])->name('index');
        Route::get('/{id}',        [ArbitroController::class, 'show'])->name('show');
        Route::get('/crear',       [ArbitroController::class, 'create'])->middleware('permission:crear-arbitros')->name('create');
        Route::post('/',           [ArbitroController::class, 'store'])->middleware('permission:crear-arbitros')->name('store');
        Route::get('/{id}/editar', [ArbitroController::class, 'edit'])->middleware('permission:editar-arbitros')->name('edit');
        Route::put('/{id}',        [ArbitroController::class, 'update'])->middleware('permission:editar-arbitros')->name('update');
        Route::put('/{id}/estado', [ArbitroController::class, 'toggleEstado'])->middleware('permission:editar-arbitros')->name('toggleEstado');
    });

    // ── Torneos ──────────────────────────────────────────────────────────────
    Route::prefix('torneos')->name('torneos.')->middleware('permission:ver-torneos')->group(function () {
        Route::get('/',            fn () => redirect()->route('dashboard'))->name('index');
        Route::get('/{id}',        fn () => redirect()->route('dashboard'))->name('show');
        Route::get('/crear',       fn () => redirect()->route('dashboard'))->middleware('permission:crear-torneos')->name('create');
        Route::post('/',           fn () => redirect()->route('dashboard'))->middleware('permission:crear-torneos')->name('store');
        Route::get('/{id}/editar', fn () => redirect()->route('dashboard'))->middleware('permission:editar-torneos')->name('edit');
        Route::put('/{id}',        fn () => redirect()->route('dashboard'))->middleware('permission:editar-torneos')->name('update');
    });

    // ── Designaciones ────────────────────────────────────────────────────────
    Route::prefix('designaciones')->name('designaciones.')->middleware('permission:ver-designaciones')->group(function () {
        Route::get('/',      fn () => redirect()->route('dashboard'))->name('index');
        Route::get('/{id}',  fn () => redirect()->route('dashboard'))->name('show');
        Route::get('/crear', fn () => redirect()->route('dashboard'))->middleware('permission:crear-designaciones')->name('create');
        Route::post('/',     fn () => redirect()->route('dashboard'))->middleware('permission:crear-designaciones')->name('store');
    });

    // ── Finanzas ─────────────────────────────────────────────────────────────
    Route::prefix('finanzas')->name('finanzas.')->middleware('permission:ver-finanzas')->group(function () {
        Route::get('/',      fn () => redirect()->route('dashboard'))->name('index');
        Route::get('/crear', fn () => redirect()->route('dashboard'))->middleware('permission:crear-finanzas')->name('create');
        Route::post('/',     fn () => redirect()->route('dashboard'))->middleware('permission:crear-finanzas')->name('store');
    });

    // ── Académico ────────────────────────────────────────────────────────────
    Route::prefix('academico')->name('academico.')->middleware('permission:ver-academico')->group(function () {
        Route::get('/',      fn () => redirect()->route('dashboard'))->name('index');
        Route::get('/crear', fn () => redirect()->route('dashboard'))->middleware('permission:crear-academico')->name('create');
        Route::post('/',     fn () => redirect()->route('dashboard'))->middleware('permission:crear-academico')->name('store');
    });

    // ── Sanciones ────────────────────────────────────────────────────────────
    Route::prefix('sanciones')->name('sanciones.')->middleware('permission:ver-sanciones')->group(function () {
        Route::get('/',      fn () => redirect()->route('dashboard'))->name('index');
        Route::get('/crear', fn () => redirect()->route('dashboard'))->middleware('permission:crear-sanciones')->name('create');
        Route::post('/',     fn () => redirect()->route('dashboard'))->middleware('permission:crear-sanciones')->name('store');
    });

    // ── Colegios — solo superadmin ────────────────────────────────────────────
    Route::prefix('colegios')->name('colegios.')->middleware('solo.superadmin')->group(function () {
        Route::get('/',            [ColegioController::class, 'index'])->name('index');
        Route::get('/crear',       [ColegioController::class, 'create'])->name('create');
        Route::post('/',           [ColegioController::class, 'store'])->name('store');
        Route::get('/{id}',        [ColegioController::class, 'show'])->name('show');
        Route::get('/{id}/editar', [ColegioController::class, 'edit'])->name('edit');
        Route::put('/{id}',        [ColegioController::class, 'update'])->name('update');
        Route::put('/{id}/estado', [ColegioController::class, 'toggleEstado'])->name('toggleEstado');
    });
});
