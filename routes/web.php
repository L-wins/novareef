<?php

use App\Http\Controllers\Arbitro\ArbitroController;
use App\Http\Controllers\Arbitro\ArbitroFotoController;
use App\Http\Controllers\Arbitro\ArbitroPerfilController;
use App\Http\Controllers\Arbitro\CategoriaArbitroController;
use App\Http\Controllers\Auth\CambioContrasenaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Colegio\ColegioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Configuracion\ConfiguracionController;
use App\Http\Controllers\Configuracion\CuentaAdminController;
use App\Http\Controllers\Designacion\CalificacionController;
use App\Http\Controllers\Designacion\DesignacionController;
use App\Http\Controllers\Designacion\DisponibilidadController;
use App\Http\Controllers\Torneo\DivisionTorneoController;
use App\Http\Controllers\Torneo\EmergenteTorneoController;
use App\Http\Controllers\Torneo\PartidoController;
use App\Http\Controllers\Torneo\SedeTorneoController;
use App\Http\Controllers\Torneo\TarifaTorneoController;
use App\Http\Controllers\Torneo\TorneoController;
use Illuminate\Support\Facades\Route;

// Página pública
Route::get('/', fn () => view('welcome'))->name('welcome');

// Autenticación (solo para invitados)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1');
});

// Completar perfil — solo auth, sin verificar.colegio ni verificar.perfil
Route::middleware('auth')->group(function () {
    Route::get('/mi-perfil/completar',  [ArbitroPerfilController::class, 'completar'])->name('arbitros.completar-perfil');
    Route::post('/mi-perfil/completar', [ArbitroPerfilController::class, 'guardar'])->name('arbitros.guardar-perfil');

    // Preferencia de tema — disponible para cualquier usuario autenticado
    Route::patch('/preferencias/tema', [\App\Http\Controllers\Configuracion\PreferenciaController::class, 'actualizarTema'])
        ->name('preferencias.tema');
});

// Rutas privadas (requieren autenticación)
Route::middleware(['auth', 'verificar.colegio', 'verificar.perfil'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Cambio de contraseña obligatorio
    Route::get('/cambiar-contrasena',  [CambioContrasenaController::class, 'show'])->name('password.change');
    Route::post('/cambiar-contrasena', [CambioContrasenaController::class, 'update'])->name('password.change.update');

    //  Mi perfil (árbitro autenticado)
    Route::get('/mi-perfil', [ArbitroPerfilController::class, 'show'])->name('arbitros.mi-perfil');
    Route::put('/mi-perfil', [ArbitroPerfilController::class, 'update'])->name('arbitros.mi-perfil.update');

    // Foto de perfil — el árbitro siempre, y editores con permiso
    Route::post('/arbitros/{id}/foto',   [ArbitroFotoController::class, 'subir'])->name('arbitros.foto.subir');
    Route::delete('/arbitros/{id}/foto', [ArbitroFotoController::class, 'eliminar'])->name('arbitros.foto.eliminar');

    //  Árbitros archivados ─
    Route::get('/arbitros-archivados', [ArbitroController::class, 'archivados'])
        ->middleware('permission:editar-arbitros')
        ->name('arbitros.archivados');

    //  Árbitros ─
    Route::prefix('arbitros')->name('arbitros.')->middleware('permission:ver-arbitros')->group(function () {
        Route::get('/',              [ArbitroController::class, 'index'])->name('index');
        Route::get('/crear',         [ArbitroController::class, 'create'])->middleware('permission:crear-arbitros')->name('create');
        Route::post('/',             [ArbitroController::class, 'store'])->middleware('permission:crear-arbitros')->name('store');
        Route::get('/{id}',          [ArbitroController::class, 'show'])->name('show');
        Route::get('/{id}/editar',   [ArbitroController::class, 'edit'])->middleware('permission:editar-arbitros')->name('edit');
        Route::put('/{id}',          [ArbitroController::class, 'update'])->middleware('permission:editar-arbitros')->name('update');
        Route::put('/{id}/estado',   [ArbitroController::class, 'toggleEstado'])->middleware('permission:editar-arbitros')->name('toggleEstado');
        Route::post('/{id}/archivar',  [ArbitroController::class, 'archivar'])->middleware('permission:editar-arbitros')->name('archivar');
        Route::post('/{id}/restaurar', [ArbitroController::class, 'restaurar'])->middleware('permission:editar-arbitros')->name('restaurar');
    });

    //  Categorías de árbitro ─
    Route::prefix('categorias-arbitro')->name('categorias.arbitro.')->middleware('permission:editar-arbitros')->group(function () {
        Route::get('/',        [CategoriaArbitroController::class, 'index'])->name('index');
        Route::post('/',       [CategoriaArbitroController::class, 'store'])->name('store');
        Route::put('/{id}',    [CategoriaArbitroController::class, 'toggleActiva'])->name('toggleActiva');
        Route::delete('/{id}', [CategoriaArbitroController::class, 'destroy'])->name('destroy');
    });

    //  Torneos 
    Route::prefix('torneos')->name('torneos.')->middleware(['permission:ver-torneos', 'modulo:torneos'])->group(function () {
        Route::get('/',              [TorneoController::class, 'index'])->name('index');
        Route::get('/crear',         [TorneoController::class, 'create'])->middleware('permission:crear-torneos')->name('create');
        Route::post('/',             [TorneoController::class, 'store'])->middleware('permission:crear-torneos')->name('store');
        Route::get('/{id}',          [TorneoController::class, 'show'])->name('show');
        Route::get('/{id}/perfil',   [TorneoController::class, 'perfil'])->middleware('permission:editar-torneos')->name('perfil');
        Route::post('/{id}/perfil',  [TorneoController::class, 'guardarPerfil'])->middleware('permission:editar-torneos')->name('perfil.guardar');
        Route::get('/{id}/editar',   [TorneoController::class, 'edit'])->middleware('permission:editar-torneos')->name('edit');
        Route::put('/{id}',          [TorneoController::class, 'update'])->middleware('permission:editar-torneos')->name('update');
        Route::put('/{id}/estado',   [TorneoController::class, 'cambiarEstado'])->middleware('permission:editar-torneos')->name('estado');
        Route::post('/{id}/archivar', [TorneoController::class, 'archivar'])->middleware('permission:editar-torneos')->name('archivar');
    });

    //  Divisiones del torneo ─
    Route::middleware('permission:editar-torneos')->group(function () {
        Route::post('/torneos/{torneoId}/divisiones', [DivisionTorneoController::class, 'store'])->name('divisiones.store');
        Route::put('/divisiones/{id}',                [DivisionTorneoController::class, 'update'])->name('divisiones.update');
        Route::delete('/divisiones/{id}',             [DivisionTorneoController::class, 'destroy'])->name('divisiones.destroy');
    });

    //  Sedes del torneo
    Route::middleware('permission:editar-torneos')->group(function () {
        Route::post('/torneos/{torneoId}/sedes', [SedeTorneoController::class, 'store'])->name('sedes.store');
        Route::put('/sedes/{id}',                [SedeTorneoController::class, 'update'])->name('sedes.update');
        Route::delete('/sedes/{id}',             [SedeTorneoController::class, 'destroy'])->name('sedes.destroy');
    });

    //  Tarifas por división
    Route::middleware('permission:editar-torneos')->group(function () {
        Route::post('/divisiones/{divisionId}/tarifas', [TarifaTorneoController::class, 'store'])->name('tarifas.store');
        Route::put('/tarifas/{id}',                     [TarifaTorneoController::class, 'update'])->name('tarifas.update');
        Route::delete('/tarifas/{id}',                  [TarifaTorneoController::class, 'destroy'])->name('tarifas.destroy');
    });

    //  Reglamentos
    Route::middleware('permission:editar-torneos')->group(function () {
        Route::delete('/reglamentos/{id}', [TorneoController::class, 'eliminarReglamento'])->name('reglamentos.destroy');
    });

    //  Emergentes del torneo
    Route::prefix('torneos/{torneoId}/emergentes')->name('emergentes.')
        ->middleware('permission:crear-designaciones')->group(function () {
            Route::get('/',       [EmergenteTorneoController::class, 'index'])->name('index');
            Route::post('/',      [EmergenteTorneoController::class, 'store'])->name('store');
            Route::delete('/{id}',[EmergenteTorneoController::class, 'destroy'])->name('destroy');
        });

    //  Partidos de un torneo ─
    Route::prefix('torneos/{torneoId}/partidos')->name('partidos.')->middleware(['permission:ver-torneos', 'modulo:torneos'])->group(function () {
        Route::get('/',           [PartidoController::class, 'index'])->name('index');
        Route::post('/',          [PartidoController::class, 'store'])->middleware('permission:crear-torneos')->name('store');
        Route::put('/{id}',       [PartidoController::class, 'update'])->middleware('permission:editar-torneos')->name('update');
        Route::put('/{id}/estado',[PartidoController::class, 'cambiarEstado'])->middleware('permission:editar-torneos')->name('estado');
    });

    //  Designaciones — M04 Bloque 3
    Route::prefix('designaciones')->name('designaciones.')->middleware(['permission:ver-designaciones', 'modulo:designaciones'])->group(function () {
        Route::get('/',         [DesignacionController::class, 'index'])->name('index');
        Route::get('/crear',    [DesignacionController::class, 'crearPartido'])->middleware('permission:crear-designaciones')->name('create');
        Route::post('/',        [DesignacionController::class, 'guardarPartido'])->middleware('permission:crear-designaciones')->name('store');
        Route::get('/{id}',     [DesignacionController::class, 'show'])->name('show');

        // AJAX — requieren permiso crear-designaciones
        Route::post('/{id}/asignar',           [DesignacionController::class, 'asignarArbitro'])->middleware('permission:crear-designaciones')->name('asignar');
        Route::delete('/designacion/{id}',     [DesignacionController::class, 'quitarDesignacion'])->middleware('permission:crear-designaciones')->name('quitar');
        Route::put('/designacion/{id}/reasignar', [DesignacionController::class, 'reasignarArbitro'])->middleware('permission:crear-designaciones')->name('reasignar');
        Route::put('/{id}/estado',             [DesignacionController::class, 'cambiarEstadoPartido'])->middleware('permission:crear-designaciones')->name('estado');
        Route::post('/partido/{id}/publicar',  [DesignacionController::class, 'publicarPartido'])->middleware('permission:crear-designaciones')->name('partido.publicar');
        Route::delete('/partido/{id}',         [DesignacionController::class, 'eliminarPartido'])->middleware('permission:crear-designaciones')->name('partido.eliminar');
        Route::put('/partido/{id}/veedor',     [DesignacionController::class, 'asignarVeedor'])->middleware('permission:crear-designaciones')->name('partido.veedor');
        Route::get('/partido/{id}/acta',       [DesignacionController::class, 'generarActa'])->middleware('permission:ver-designaciones')->name('partido.acta');
        Route::get('/partido/{id}/calificaciones', [CalificacionController::class, 'index'])->middleware('permission:crear-calificaciones')->name('calificaciones.index');
    });

    //  Finalizar partido — solo el árbitro Central (validado en el controlador,
    //  fuera del grupo designaciones porque el árbitro no tiene ver-designaciones)
    Route::post('/designaciones/partido/{id}/finalizar', [DesignacionController::class, 'finalizarPartido'])
        ->name('designaciones.partido.finalizar');

    //  Mis partidos (árbitro)
    Route::prefix('mis-partidos')->name('mis-partidos.')->group(function () {
        Route::get('/',           [DesignacionController::class, 'misPartidos'])->name('index');
        Route::get('/historial',  [DesignacionController::class, 'historialPartidos'])->name('historial');
        Route::get('/historial/pdf', [DesignacionController::class, 'historialPdf'])->name('historial.pdf');
        Route::get('/{id}',       [DesignacionController::class, 'detallePartido'])->name('detalle');
        Route::post('/{id}/confirmar', [DesignacionController::class, 'confirmarDesignacion'])->name('confirmar');
        Route::post('/{id}/rechazar',  [DesignacionController::class, 'rechazarDesignacion'])->name('rechazar');
    });

    //  Calificaciones (veedor/ejecutivo)
    Route::post('/calificaciones/{designacionId}', [CalificacionController::class, 'store'])
        ->name('calificaciones.store')
        ->middleware('permission:crear-calificaciones');

    //  API AJAX — Designaciones (JSON endpoints sin Sanctum, misma sesión web)
    Route::middleware('permission:ver-designaciones')->group(function () {
        Route::get('/api/torneos/{id}/divisiones',            [DesignacionController::class, 'getDivisiones'])->name('api.torneos.divisiones');
        Route::get('/api/torneos/{id}/sedes',                 [DesignacionController::class, 'getSedes'])->name('api.torneos.sedes');
        Route::get('/api/partidos/{id}/arbitros-disponibles', [DesignacionController::class, 'getArbitrosDisponibles'])->name('api.partidos.arbitros-disponibles');
    });

    //  Disponibilidad — árbitro (registro semanal e indisponibilidad extraordinaria)
    Route::prefix('disponibilidad')->name('disponibilidad.')
        ->middleware(['verificar.perfil'])
        ->group(function () {
            Route::get('/',                  [DisponibilidadController::class, 'index'])->name('index');
            Route::post('/',                 [DisponibilidadController::class, 'store'])->name('store');
            Route::post('/extraordinaria',   [DisponibilidadController::class, 'indisponibilidadExtraordinaria'])->name('extraordinaria');
            Route::delete('/{fecha}',        [DisponibilidadController::class, 'marcarNoDisponible'])->name('eliminar');
        });

    //  Disponibilidad general (designador/ejecutivo)
    Route::get('/disponibilidad/general', [DesignacionController::class, 'disponibilidadGeneral'])
        ->name('disponibilidad.general')
        ->middleware('permission:crear-designaciones');

    //  Ver disponibilidad de árbitro específico (respuesta JSON para AJAX)
    Route::get('/disponibilidad/arbitro/{id}', [DisponibilidadController::class, 'verDisponibilidad'])
        ->name('disponibilidad.arbitro')
        ->middleware('permission:crear-designaciones');

    //  Finanzas ─
    Route::prefix('finanzas')->name('finanzas.')->middleware(['permission:ver-finanzas', 'modulo:finanzas'])->group(function () {
        Route::get('/',      fn () => redirect()->route('dashboard'))->name('index');
        Route::get('/crear', fn () => redirect()->route('dashboard'))->middleware('permission:crear-finanzas')->name('create');
        Route::post('/',     fn () => redirect()->route('dashboard'))->middleware('permission:crear-finanzas')->name('store');
    });

    //  Académico 
    Route::prefix('academico')->name('academico.')->middleware(['permission:ver-academico', 'modulo:academico'])->group(function () {
        Route::get('/',      fn () => redirect()->route('dashboard'))->name('index');
        Route::get('/crear', fn () => redirect()->route('dashboard'))->middleware('permission:crear-academico')->name('create');
        Route::post('/',     fn () => redirect()->route('dashboard'))->middleware('permission:crear-academico')->name('store');
    });

    //  Sanciones 
    Route::prefix('sanciones')->name('sanciones.')->middleware(['permission:ver-sanciones', 'modulo:sanciones'])->group(function () {
        Route::get('/',      fn () => redirect()->route('dashboard'))->name('index');
        Route::get('/crear', fn () => redirect()->route('dashboard'))->middleware('permission:crear-sanciones')->name('create');
        Route::post('/',     fn () => redirect()->route('dashboard'))->middleware('permission:crear-sanciones')->name('store');
    });

    //  Configuración del colegio — solo ejecutivo
    Route::prefix('configuracion')->name('configuracion.')->middleware('permission:editar-arbitros')->group(function () {
        Route::get('/',        [ConfiguracionController::class, 'index'])->name('index');
        Route::put('/',        [ConfiguracionController::class, 'update'])->name('update');
        Route::post('/logo',   [ConfiguracionController::class, 'actualizarLogo'])->name('logo.actualizar');
        Route::delete('/logo', [ConfiguracionController::class, 'eliminarLogo'])->name('logo.eliminar');
    });

    //  Cuentas admin (tesorero, designador, sanciones, tecnico, veedor, co-ejecutivo)
    Route::prefix('configuracion/cuentas-admin')->name('configuracion.cuentas-admin.')
        ->middleware('permission:gestionar-cuentas-admin')->group(function () {
            Route::get('/',            [CuentaAdminController::class, 'index'])->name('index');
            Route::get('/crear',       [CuentaAdminController::class, 'create'])->name('create');
            Route::get('/verificar-username', [CuentaAdminController::class, 'verificarUsername'])->name('verificar-username');
            Route::post('/',           [CuentaAdminController::class, 'store'])->name('store');
            Route::get('/{id}/editar', [CuentaAdminController::class, 'edit'])->name('edit');
            Route::put('/{id}',        [CuentaAdminController::class, 'update'])->name('update');
            Route::put('/{id}/revocar',  [CuentaAdminController::class, 'revocar'])->name('revocar');
            Route::put('/{id}/reactivar',[CuentaAdminController::class, 'reactivar'])->name('reactivar');
        });

    //  Colegios — solo superadmin
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
