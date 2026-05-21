<?php

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
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::prefix('colegios')->name('colegios.')->group(function () {
        Route::get('/',               [ColegioController::class, 'index'])->name('index');
        Route::get('/crear',          [ColegioController::class, 'create'])->name('create');
        Route::post('/',              [ColegioController::class, 'store'])->name('store');
        Route::get('/{id}',           [ColegioController::class, 'show'])->name('show');
        Route::get('/{id}/editar',    [ColegioController::class, 'edit'])->name('edit');
        Route::put('/{id}',           [ColegioController::class, 'update'])->name('update');
        Route::put('/{id}/estado',    [ColegioController::class, 'toggleEstado'])->name('toggleEstado');
    });
});
