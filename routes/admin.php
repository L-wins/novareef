<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Admin2FAController;
use App\Http\Controllers\Admin\AdminColegioController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AdminPreferenciaController;
use App\Http\Controllers\Admin\AdminSuscripcionController;
use App\Http\Controllers\Admin\AdminUsuarioController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('admin.prefix', 'novareef-panel'))->name('admin.')->group(function (): void {

    // Públicas
    Route::get('/login', [AdminLoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('login.post');
    Route::get('/2fa', [AdminLoginController::class, 'show2fa'])->name('2fa');
    Route::post('/2fa', [AdminLoginController::class, 'verify2fa'])->name('2fa.post');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    // Protegidas
    Route::middleware(['admin.auth'])->group(function (): void {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Preferencia de tema — disponible para el superadmin autenticado
        Route::patch('/preferencias/tema', [AdminPreferenciaController::class, 'actualizarTema'])
            ->name('preferencias.tema');

        // Sin gating por permiso: solo existe (y va a existir) un único
        // superadmin (admin@novareef.com) que cubre todas las funciones —
        // no hay distintos niveles de acceso que proteger. admin.auth ya
        // exige sesión válida y activa, eso basta.
        //
        // Orden importa: las rutas literales (/crear) deben registrarse antes
        // que las de wildcard (/{id}) para que Laravel no intente resolver
        // "crear" como un id.
        Route::prefix('colegios')->name('colegios.')->group(function (): void {
            Route::get('/', [AdminColegioController::class, 'index'])->name('index');
            Route::get('/crear', [AdminColegioController::class, 'create'])->name('create');
            Route::post('/', [AdminColegioController::class, 'store'])->name('store');
            Route::get('/{id}', [AdminColegioController::class, 'show'])->name('show');
            Route::get('/{id}/editar', [AdminColegioController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminColegioController::class, 'update'])->name('update');
            Route::put('/{id}/estado', [AdminColegioController::class, 'toggleEstado'])->name('toggleEstado');
            Route::post('/{id}/impersonar', [AdminColegioController::class, 'impersonar'])->name('impersonar');
        });
        Route::prefix('planes')->name('planes.')->group(function (): void {
            Route::get('/', [AdminPlanController::class, 'index'])->name('index');
            Route::get('/{id}', [AdminPlanController::class, 'show'])->name('show');
            Route::get('/{id}/editar', [AdminPlanController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminPlanController::class, 'update'])->name('update');
            Route::put('/{id}/visible', [AdminPlanController::class, 'toggleVisible'])->name('toggleVisible');
            Route::put('/{id}/activo', [AdminPlanController::class, 'toggleActivo'])->name('toggleActivo');
        });
        Route::prefix('usuarios')->name('usuarios.')->group(function (): void {
            Route::get('/', [AdminUsuarioController::class, 'index'])->name('index');
            Route::put('/{id}/estado', [AdminUsuarioController::class, 'toggleEstado'])->name('toggleEstado');
        });
        Route::prefix('suscripciones')->name('suscripciones.')->group(function (): void {
            Route::get('/', [AdminSuscripcionController::class, 'index'])->name('index');
            Route::get('/exportar', [AdminSuscripcionController::class, 'exportarCsv'])->name('exportar');
            Route::put('/colegio/{idColegio}/plan', [AdminSuscripcionController::class, 'cambiarPlan'])->name('cambiarPlan');
            Route::put('/colegio/{idColegio}/extender', [AdminSuscripcionController::class, 'extender'])->name('extender');
            Route::put('/colegio/{idColegio}/cancelar', [AdminSuscripcionController::class, 'cancelar'])->name('cancelar');
        });
        Route::get('/logs', [AdminLogController::class, 'index'])->name('logs.index');

        Route::get('/2fa/configurar', [Admin2FAController::class, 'show'])->name('2fa.config');
        Route::post('/2fa/activar', [Admin2FAController::class, 'enable'])->name('2fa.enable');
        Route::post('/2fa/desactivar', [Admin2FAController::class, 'disable'])->name('2fa.disable');
    });
});
