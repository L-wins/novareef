<?php

declare(strict_types=1);

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
    });
});
