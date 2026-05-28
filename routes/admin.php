<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Admin2FAController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLoginController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('admin.prefix', 'novareef-panel'))->name('admin.')->group(function (): void {

    // Públicas
    Route::get('/login',   [AdminLoginController::class, 'showLogin'])->name('login');
    Route::post('/login',  [AdminLoginController::class, 'login'])->name('login.post');
    Route::get('/2fa',     [AdminLoginController::class, 'show2fa'])->name('2fa');
    Route::post('/2fa',    [AdminLoginController::class, 'verify2fa'])->name('2fa.post');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    // Protegidas
    Route::middleware(['admin.auth'])->group(function (): void {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/colegios',  fn () => view('admin.colegios.index'))->name('colegios.index');
        Route::get('/planes',    fn () => view('admin.planes.index'))->name('planes.index');
        Route::get('/usuarios',  fn () => view('admin.usuarios.index'))->name('usuarios.index');
        Route::get('/logs',      fn () => view('admin.logs.index'))->name('logs.index');

        Route::get('/2fa/configurar',  [Admin2FAController::class, 'show'])->name('2fa.config');
        Route::post('/2fa/activar',    [Admin2FAController::class, 'enable'])->name('2fa.enable');
        Route::post('/2fa/desactivar', [Admin2FAController::class, 'disable'])->name('2fa.disable');
    });
});
