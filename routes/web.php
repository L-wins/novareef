<?php

use App\Http\Controllers\Academico\AsistenciaController;
use App\Http\Controllers\Academico\JustificacionController;
use App\Http\Controllers\Academico\MaterialAcademicoController;
use App\Http\Controllers\Academico\SesionAcademicaController;
use App\Http\Controllers\Academico\TipoSesionAcademicaController;
use App\Http\Controllers\Arbitro\ArbitroController;
use App\Http\Controllers\Arbitro\ArbitroFotoController;
use App\Http\Controllers\Arbitro\ArbitroPerfilController;
use App\Http\Controllers\Arbitro\CategoriaArbitroController;
use App\Http\Controllers\Arbitro\DocumentoArbitroController;
use App\Http\Controllers\Arbitro\EstadoCuentaArbitroController;
use App\Http\Controllers\Arbitro\RequisitoDocumentoArbitroController;
use App\Http\Controllers\Auth\CambioContrasenaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RecuperarContrasenaController;
use App\Http\Controllers\Configuracion\ConfiguracionController;
use App\Http\Controllers\Configuracion\CuentaAdminController;
use App\Http\Controllers\Configuracion\PreferenciaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Designacion\CalificacionController;
use App\Http\Controllers\Designacion\DesignacionAccionesController;
use App\Http\Controllers\Designacion\DesignacionController;
use App\Http\Controllers\Designacion\DisponibilidadController;
use App\Http\Controllers\Designacion\EstadisticasController;
use App\Http\Controllers\Designacion\ImportacionDesignacionesController;
use App\Http\Controllers\Designacion\MisPartidosController;
use App\Http\Controllers\Finanza\BalanceFinancieroController;
use App\Http\Controllers\Finanza\CobroMasivoController;
use App\Http\Controllers\Finanza\ComprobantesMensualesController;
use App\Http\Controllers\Finanza\CuotasMensualesController;
use App\Http\Controllers\Finanza\FichaFinancieraArbitroController;
use App\Http\Controllers\Finanza\MoraArbitrosController;
use App\Http\Controllers\Finanza\MovimientoInstitucionalController;
use App\Http\Controllers\Finanza\ReporteFinancieroController;
use App\Http\Controllers\ImpersonacionController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PoliticaPrivacidadController;
use App\Http\Controllers\Sancion\JustificacionRevisionController;
use App\Http\Controllers\Sancion\SancionController;
use App\Http\Controllers\Sancion\TipoSancionController;
use App\Http\Controllers\Torneo\DivisionTorneoController;
use App\Http\Controllers\Torneo\EmergenteTorneoController;
use App\Http\Controllers\Torneo\PartidoController;
use App\Http\Controllers\Torneo\SedeTorneoController;
use App\Http\Controllers\Torneo\TarifaTorneoController;
use App\Http\Controllers\Torneo\TorneoController;
use App\Models\Plan;
use Illuminate\Support\Facades\Route;

// Página pública
Route::get('/', function () {
    $planes = Plan::where('esVisible', true)
        ->where('esActivo', true)
        ->orderBy('orden')
        ->get();

    return view('welcome', compact('planes'));
})->name('welcome');

// Legal — públicas, sin auth: hay que poder leerlas antes de tener cuenta.
Route::get('/privacidad', [PoliticaPrivacidadController::class, 'mostrar'])->name('privacidad.politica');
Route::get('/terminos', [LegalController::class, 'terminos'])->name('legal.terminos');

// Autenticación (solo para invitados)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1');
});

// Recuperación de contraseña (solo invitados) — guard 'web'
Route::middleware('guest')->prefix('recuperar-contrasena')->group(function () {
    Route::get('/', [RecuperarContrasenaController::class, 'mostrarSolicitud'])->name('password.request');
    Route::post('/', [RecuperarContrasenaController::class, 'enviarEnlace'])
        ->middleware('throttle:6,1')->name('password.email');

    Route::get('/restablecer/{token}', [RecuperarContrasenaController::class, 'mostrarFormulario'])->name('password.reset');
    Route::post('/restablecer', [RecuperarContrasenaController::class, 'restablecer'])
        ->middleware('throttle:6,1')->name('password.update');
});

// Completar perfil — solo auth, sin verificar.colegio ni verificar.perfil
Route::middleware('auth')->group(function () {
    Route::get('/mi-perfil/completar', [ArbitroPerfilController::class, 'completar'])->name('arbitros.completar-perfil');
    Route::post('/mi-perfil/completar', [ArbitroPerfilController::class, 'guardar'])->name('arbitros.guardar-perfil');

    // Preferencia de tema — disponible para cualquier usuario autenticado
    Route::patch('/preferencias/tema', [PreferenciaController::class, 'actualizarTema'])
        ->name('preferencias.tema');

    // Salir de una impersonación (ver AdminColegioController::impersonar) —
    // sin verificar.colegio/verificar.perfil, para que funcione aunque el
    // colegio impersonado esté suspendido o el perfil incompleto.
    Route::post('/impersonacion/salir', [ImpersonacionController::class, 'salir'])
        ->name('impersonacion.salir');

    // Aceptación de la política de tratamiento de datos (Ley 1581 de 2012)
    // — sin verificar.colegio ni verificar.perfil: debe funcionar incluso
    // con el colegio suspendido o el perfil sin completar, y el propio
    // middleware ExigirAceptacionPolitica redirige acá antes de dejar pasar
    // a cualquier otra ruta autenticada. Ver /privacidad (pública, arriba)
    // para el texto de la política en sí.
    Route::get('/privacidad/aceptar', [PoliticaPrivacidadController::class, 'aceptar'])->name('privacidad.aceptar');
    Route::post('/privacidad/aceptar', [PoliticaPrivacidadController::class, 'guardarAceptacion'])->name('privacidad.aceptar.guardar');
});

// Rutas privadas (requieren autenticación)
Route::middleware(['auth', 'verificar.colegio', 'verificar.perfil'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Cambio de contraseña obligatorio
    Route::get('/cambiar-contrasena', [CambioContrasenaController::class, 'show'])->name('password.change');
    Route::post('/cambiar-contrasena', [CambioContrasenaController::class, 'update'])->name('password.change.update');

    //  Mi perfil (árbitro autenticado)
    Route::get('/mi-perfil', [ArbitroPerfilController::class, 'show'])->name('arbitros.mi-perfil');
    Route::put('/mi-perfil', [ArbitroPerfilController::class, 'update'])->name('arbitros.mi-perfil.update');

    //  Mi estado de cuenta — fuera del grupo finanzas: el árbitro no tiene
    //  ver-finanzas, mismo criterio que finalizarPartido con ver-designaciones.
    Route::get('/mi-estado-cuenta', [EstadoCuentaArbitroController::class, 'show'])->name('arbitros.estado-cuenta');
    Route::get('/mi-estado-cuenta/comprobante/{lote}', [EstadoCuentaArbitroController::class, 'comprobante'])->name('arbitros.estado-cuenta.comprobante');
    Route::get('/mi-estado-cuenta/comprobante-cobro/{lote}', [EstadoCuentaArbitroController::class, 'comprobanteCobro'])->name('arbitros.estado-cuenta.comprobante-cobro');

    // Foto de perfil — el árbitro siempre, y editores con permiso
    Route::post('/arbitros/{id}/foto', [ArbitroFotoController::class, 'subir'])->name('arbitros.foto.subir');
    Route::delete('/arbitros/{id}/foto', [ArbitroFotoController::class, 'eliminar'])->name('arbitros.foto.eliminar');

    Route::prefix('documentos-arbitro')->name('documentos.arbitro.')->group(function () {
        Route::get('/plantillas/{idRequisito}', [RequisitoDocumentoArbitroController::class, 'descargarPlantilla'])->name('plantilla');
        Route::get('/{idDocumento}/descargar', [DocumentoArbitroController::class, 'descargar'])->name('descargar');
        Route::post('/{idArbitro}/requisitos/{idRequisito}', [DocumentoArbitroController::class, 'store'])->name('store');
        Route::put('/{idDocumento}/aprobar', [DocumentoArbitroController::class, 'aprobar'])
            ->middleware('permission:editar-arbitros')->name('aprobar');
        Route::put('/{idDocumento}/devolver', [DocumentoArbitroController::class, 'devolver'])
            ->middleware('permission:editar-arbitros')->name('devolver');
    });

    //  Árbitros archivados ─
    Route::get('/arbitros-archivados', [ArbitroController::class, 'archivados'])
        ->middleware('permission:editar-arbitros')
        ->name('arbitros.archivados');

    //  Árbitros ─
    Route::prefix('arbitros')->name('arbitros.')->middleware('permission:ver-arbitros')->group(function () {
        Route::get('/', [ArbitroController::class, 'index'])->name('index');
        Route::get('/crear', [ArbitroController::class, 'create'])->middleware('permission:crear-arbitros')->name('create');
        Route::post('/', [ArbitroController::class, 'store'])->middleware('permission:crear-arbitros')->name('store');
        Route::get('/{id}', [ArbitroController::class, 'show'])->name('show');
        Route::get('/{id}/editar', [ArbitroController::class, 'edit'])->middleware('permission:editar-arbitros')->name('edit');
        Route::put('/{id}', [ArbitroController::class, 'update'])->middleware('permission:editar-arbitros')->name('update');
        Route::put('/{id}/estado', [ArbitroController::class, 'cambiarEstado'])->middleware('permission:editar-arbitros')->name('estado');
        Route::post('/{id}/archivar', [ArbitroController::class, 'archivar'])->middleware('permission:editar-arbitros')->name('archivar');
        Route::post('/{id}/restaurar', [ArbitroController::class, 'restaurar'])->middleware('permission:editar-arbitros')->name('restaurar');
    });

    //  Categorías de árbitro ─
    Route::prefix('categorias-arbitro')->name('categorias.arbitro.')->middleware('permission:editar-arbitros')->group(function () {
        Route::get('/', [CategoriaArbitroController::class, 'index'])->name('index');
        Route::post('/', [CategoriaArbitroController::class, 'store'])->name('store');
        Route::put('/{id}/estado', [CategoriaArbitroController::class, 'cambiarEstado'])->name('estado');
        Route::delete('/{id}', [CategoriaArbitroController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('requisitos-documentos-arbitro')->name('requisitos-documentos-arbitro.')
        ->middleware('permission:editar-arbitros')->group(function () {
            Route::get('/', [RequisitoDocumentoArbitroController::class, 'index'])->name('index');
            Route::get('/{idRequisito}', [RequisitoDocumentoArbitroController::class, 'enfocar'])->name('show');
            Route::post('/', [RequisitoDocumentoArbitroController::class, 'store'])->name('store');
            Route::put('/{idRequisito}', [RequisitoDocumentoArbitroController::class, 'update'])->name('update');
            Route::put('/{idRequisito}/estado', [RequisitoDocumentoArbitroController::class, 'cambiarEstado'])->name('estado');
        });

    //  Torneos
    Route::prefix('torneos')->name('torneos.')->middleware(['permission:ver-torneos', 'modulo:torneos'])->group(function () {
        Route::get('/', [TorneoController::class, 'index'])->name('index');
        Route::get('/crear', [TorneoController::class, 'create'])->middleware('permission:crear-torneos')->name('create');
        Route::post('/', [TorneoController::class, 'store'])->middleware('permission:crear-torneos')->name('store');
        Route::get('/{id}', [TorneoController::class, 'show'])->name('show');
        Route::get('/{id}/perfil', [TorneoController::class, 'perfil'])->middleware('permission:editar-torneos')->name('perfil');
        Route::post('/{id}/perfil', [TorneoController::class, 'guardarPerfil'])->middleware('permission:editar-torneos')->name('perfil.guardar');
        Route::get('/{id}/editar', [TorneoController::class, 'edit'])->middleware('permission:editar-torneos')->name('edit');
        Route::put('/{id}', [TorneoController::class, 'update'])->middleware('permission:editar-torneos')->name('update');
        Route::put('/{id}/estado', [TorneoController::class, 'cambiarEstado'])->middleware('permission:editar-torneos')->name('estado');
        Route::post('/{id}/archivar', [TorneoController::class, 'archivar'])->middleware('permission:editar-torneos')->name('archivar');
    });

    //  Divisiones del torneo ─
    Route::middleware('permission:editar-torneos')->group(function () {
        Route::post('/torneos/{torneoId}/divisiones', [DivisionTorneoController::class, 'store'])->name('torneos.divisiones.store');
        Route::put('/divisiones/{id}', [DivisionTorneoController::class, 'update'])->name('torneos.divisiones.update');
        Route::delete('/divisiones/{id}', [DivisionTorneoController::class, 'destroy'])->name('torneos.divisiones.destroy');
    });

    //  Sedes del torneo
    Route::middleware('permission:editar-torneos')->group(function () {
        Route::post('/torneos/{torneoId}/sedes', [SedeTorneoController::class, 'store'])->name('torneos.sedes.store');
        Route::put('/sedes/{id}', [SedeTorneoController::class, 'update'])->name('torneos.sedes.update');
        Route::delete('/sedes/{id}', [SedeTorneoController::class, 'destroy'])->name('torneos.sedes.destroy');
    });

    //  Tarifas por división
    Route::middleware('permission:editar-torneos')->group(function () {
        Route::post('/divisiones/{divisionId}/tarifas', [TarifaTorneoController::class, 'store'])->name('torneos.divisiones.tarifas.store');
        Route::put('/tarifas/{id}', [TarifaTorneoController::class, 'update'])->name('torneos.divisiones.tarifas.update');
        Route::delete('/tarifas/{id}', [TarifaTorneoController::class, 'destroy'])->name('torneos.divisiones.tarifas.destroy');
    });

    //  Reglamentos
    Route::middleware('permission:editar-torneos')->group(function () {
        Route::delete('/reglamentos/{id}', [TorneoController::class, 'eliminarReglamento'])->name('reglamentos.destroy');
    });

    //  Emergentes del torneo
    Route::prefix('torneos/{torneoId}/emergentes')->name('torneos.emergentes.')
        ->middleware('permission:crear-designaciones')->group(function () {
            Route::get('/', [EmergenteTorneoController::class, 'index'])->name('index');
            Route::post('/', [EmergenteTorneoController::class, 'store'])->name('store');
            Route::delete('/{id}', [EmergenteTorneoController::class, 'destroy'])->name('destroy');
        });

    //  Partidos de un torneo ─
    Route::prefix('torneos/{torneoId}/partidos')->name('partidos.')->middleware(['permission:ver-torneos', 'modulo:torneos'])->group(function () {
        Route::get('/', [PartidoController::class, 'index'])->name('index');
        Route::post('/', [PartidoController::class, 'store'])->middleware('permission:crear-torneos')->name('store');
        Route::put('/{id}', [PartidoController::class, 'update'])->middleware('permission:editar-torneos')->name('update');
        Route::put('/{id}/estado', [PartidoController::class, 'cambiarEstado'])->middleware('permission:editar-torneos')->name('estado');
    });

    //  Designaciones — M04 Bloque 3
    Route::prefix('designaciones')->name('designaciones.')->middleware(['permission:ver-designaciones', 'modulo:designaciones'])->group(function () {
        Route::get('/', [DesignacionController::class, 'index'])->name('index');
        Route::get('/crear', [DesignacionController::class, 'crearPartido'])->middleware('permission:crear-designaciones')->name('create');
        Route::post('/', [DesignacionController::class, 'guardarPartido'])->middleware('permission:crear-designaciones')->name('store');

        //  Importador de partidos desde .docx — rutas de prefijo fijo,
        //  deben ir antes de '/{id}' (linea siguiente) o Laravel las
        //  capturaria como si {id} fuera el literal "importar".
        Route::prefix('importar')->name('importar.')->middleware('permission:crear-designaciones')->group(function () {
            Route::get('/', [ImportacionDesignacionesController::class, 'mostrar'])->name('mostrar');
            Route::post('/', [ImportacionDesignacionesController::class, 'procesar'])->name('procesar');
            Route::post('/revisar', [ImportacionDesignacionesController::class, 'revisar'])->name('revisar');
            Route::post('/confirmar', [ImportacionDesignacionesController::class, 'confirmar'])->name('confirmar');
            Route::post('/cancelar', [ImportacionDesignacionesController::class, 'cancelar'])->name('cancelar');
            // Rango fijo — igual que arriba, deben ir antes de cualquier {id}.
            Route::get('/historial', [ImportacionDesignacionesController::class, 'historial'])->name('historial');
            Route::put('/{idImportacion}/revertir', [ImportacionDesignacionesController::class, 'revertir'])->name('revertir');
        });

        Route::get('/torneo/{idTorneo}/listado-pdf', [DesignacionController::class, 'generarListado'])
            ->middleware('permission:ver-designaciones')->name('listado.pdf');

        // Estadísticas — rango fijo, debe ir antes de '/{id}' (línea siguiente)
        // por la misma razón que el importador: si no, Laravel capturaría
        // "estadisticas" como si fuera el literal del parámetro {id}.
        Route::get('/estadisticas', [EstadisticasController::class, 'index'])
            ->middleware('permission:crear-designaciones')->name('estadisticas');
        Route::prefix('estadisticas')->name('estadisticas.')
            ->middleware('permission:crear-designaciones')
            ->group(function () {
                Route::get('/disponibilidad', [EstadisticasController::class, 'disponibilidad'])->name('disponibilidad');
                Route::get('/partidos-arbitro', [EstadisticasController::class, 'partidosArbitro'])->name('partidos-arbitro');
                Route::get('/confiabilidad', [EstadisticasController::class, 'confiabilidad'])->name('confiabilidad');
                Route::get('/coincidencias', [EstadisticasController::class, 'coincidencias'])->name('coincidencias');
            });

        Route::get('/{id}', [DesignacionController::class, 'show'])->name('show');

        // AJAX — requieren permiso crear-designaciones
        Route::post('/{id}/asignar', [DesignacionAccionesController::class, 'asignarArbitro'])->middleware('permission:crear-designaciones')->name('asignar');
        Route::delete('/designacion/{id}', [DesignacionAccionesController::class, 'quitarDesignacion'])->middleware('permission:crear-designaciones')->name('quitar');
        Route::put('/designacion/{id}/reasignar', [DesignacionAccionesController::class, 'reasignarArbitro'])->middleware('permission:crear-designaciones')->name('reasignar');
        Route::put('/{id}/estado', [DesignacionAccionesController::class, 'cambiarEstadoPartido'])->middleware('permission:crear-designaciones')->name('estado');
        Route::put('/partido/{id}', [DesignacionController::class, 'actualizarPartido'])->middleware('permission:crear-designaciones')->name('partido.actualizar');
        Route::post('/partido/{id}/publicar', [DesignacionController::class, 'publicarPartido'])->middleware('permission:crear-designaciones')->name('partido.publicar');
        Route::delete('/partido/{id}', [DesignacionController::class, 'eliminarPartido'])->middleware('permission:crear-designaciones')->name('partido.eliminar');
        Route::put('/partido/{id}/veedor', [DesignacionAccionesController::class, 'asignarVeedor'])->middleware('permission:crear-designaciones')->name('partido.veedor');
        Route::get('/partido/{id}/acta', [DesignacionController::class, 'generarActa'])->middleware('permission:ver-designaciones')->name('partido.acta');
        Route::get('/partido/{id}/calificaciones', [CalificacionController::class, 'index'])->middleware('permission:crear-calificaciones')->name('calificaciones.index');
    });

    //  Finalizar partido — solo el árbitro Central (validado en el controlador,
    //  fuera del grupo designaciones porque el árbitro no tiene ver-designaciones)
    Route::post('/designaciones/partido/{id}/finalizar', [MisPartidosController::class, 'finalizarPartido'])
        ->name('designaciones.partido.finalizar');

    //  Mis partidos (árbitro)
    Route::prefix('mis-partidos')->name('mis-partidos.')->group(function () {
        Route::get('/', [MisPartidosController::class, 'misPartidos'])->name('index');
        Route::get('/historial', [MisPartidosController::class, 'historialPartidos'])->name('historial');
        Route::get('/historial/pdf', [MisPartidosController::class, 'historialPdf'])->name('historial.pdf');
        Route::get('/{id}', [MisPartidosController::class, 'detallePartido'])->name('detalle');
        Route::post('/{id}/confirmar', [MisPartidosController::class, 'confirmarDesignacion'])->name('confirmar');
        // Fallback GET: los correos de designación antiguos traían el botón
        // "Confirmar" como enlace directo a la URL POST — un clic desde el
        // correo hace GET y explotaba con MethodNotAllowed. Redirige a la
        // card correspondiente en Mis partidos.
        Route::get('/{id}/confirmar', [MisPartidosController::class, 'redirigirConfirmacionEmail'])->name('confirmar.email');
        Route::post('/{id}/rechazar', [MisPartidosController::class, 'rechazarDesignacion'])->name('rechazar');
    });

    //  Calificaciones (veedor/ejecutivo)
    Route::post('/calificaciones/{designacionId}', [CalificacionController::class, 'store'])
        ->name('calificaciones.store')
        ->middleware('permission:crear-calificaciones');

    //  API AJAX — Designaciones (JSON endpoints sin Sanctum, misma sesión web)
    Route::middleware('permission:ver-designaciones')->group(function () {
        Route::get('/api/torneos/{id}/divisiones', [DesignacionController::class, 'getDivisiones'])->name('api.torneos.divisiones');
        Route::get('/api/torneos/{id}/sedes', [DesignacionController::class, 'getSedes'])->name('api.torneos.sedes');
        Route::get('/api/partidos/{id}/arbitros-disponibles', [DesignacionAccionesController::class, 'getArbitrosDisponibles'])->name('api.partidos.arbitros-disponibles');
    });

    //  Disponibilidad — árbitro (registro semanal e indisponibilidad extraordinaria)
    Route::prefix('disponibilidad')->name('disponibilidad.')
        ->middleware(['verificar.perfil'])
        ->group(function () {
            Route::get('/', [DisponibilidadController::class, 'index'])->name('index');
            Route::post('/', [DisponibilidadController::class, 'store'])->name('store');
            Route::post('/extraordinaria', [DisponibilidadController::class, 'indisponibilidadExtraordinaria'])->name('extraordinaria');
            Route::delete('/{fecha}', [DisponibilidadController::class, 'marcarNoDisponible'])->name('eliminar');
        });

    //  Disponibilidad general (designador/ejecutivo)
    Route::get('/disponibilidad/general', [DisponibilidadController::class, 'general'])
        ->name('disponibilidad.general')
        ->middleware('permission:crear-designaciones');

    //  Ver disponibilidad de árbitro específico (respuesta JSON para AJAX)
    Route::get('/disponibilidad/arbitro/{id}', [DisponibilidadController::class, 'verDisponibilidad'])
        ->name('disponibilidad.arbitro')
        ->middleware('permission:crear-designaciones');

    //  Finanzas — M06
    Route::prefix('finanzas')->name('finanzas.')->middleware(['permission:ver-finanzas', 'modulo:finanzas'])->group(function () {
        Route::prefix('cobro-masivo')->name('cobro-masivo.')->group(function () {
            Route::get('/', [CobroMasivoController::class, 'index'])->name('index');
            Route::post('/', [CobroMasivoController::class, 'store'])->middleware('permission:crear-finanzas')->name('store');
        });

        Route::get('/reportes', [ReporteFinancieroController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/pdf', [ReporteFinancieroController::class, 'pdf'])->name('reportes.pdf');
        Route::get('/balance', [BalanceFinancieroController::class, 'index'])->name('balance.index');
        Route::post('/saldo-inicial', [BalanceFinancieroController::class, 'registrarSaldoInicial'])
            ->middleware('permission:crear-finanzas')->name('saldo-inicial.store');
        Route::get('/mora', [MoraArbitrosController::class, 'index'])->name('mora.index');
        Route::get('/cuotas', [CuotasMensualesController::class, 'index'])->name('cuotas.index');
        Route::get('/comprobantes', [ComprobantesMensualesController::class, 'index'])->name('comprobantes.index');

        // Gastos e ingresos institucionales — los 5 tipos de movimiento sin
        // árbitro asociado (ingreso_torneo, otro_ingreso, gasto_fijo,
        // gasto_institucional, gasto_vario). Separado de la ficha de árbitro.
        Route::prefix('gastos-ingresos')->name('institucional.')->group(function () {
            Route::get('/', [MovimientoInstitucionalController::class, 'index'])->name('index');
            Route::post('/', [MovimientoInstitucionalController::class, 'store'])
                ->middleware('permission:crear-finanzas')->name('store');
        });

        // Ficha financiera de un árbitro — único lugar de pago/abono/anulación
        // individual, siempre scopeado a un árbitro concreto. Reemplaza la
        // vieja vista de "pago acumulado": pagar nómina (uno a la vez o en
        // lote) y compensar una deuda contra la nómina disponible viven acá.
        Route::prefix('arbitro/{idArbitro}')->name('arbitro.')->group(function () {
            Route::get('/', [FichaFinancieraArbitroController::class, 'show'])->name('show');
            Route::post('/cargos', [FichaFinancieraArbitroController::class, 'store'])
                ->middleware('permission:crear-finanzas')->name('cargos.store');
            Route::post('/cargos/{idMovimiento}/abonos', [FichaFinancieraArbitroController::class, 'abonar'])
                ->middleware('permission:crear-finanzas')->name('cargos.abonar');
            Route::put('/cargos/{idMovimiento}/anular', [FichaFinancieraArbitroController::class, 'anular'])
                ->middleware('permission:editar-finanzas')->name('cargos.anular');
            Route::post('/cargos/{idMovimiento}/compensar', [FichaFinancieraArbitroController::class, 'compensar'])
                ->middleware('permission:crear-finanzas')->name('cargos.compensar');
            Route::post('/nomina/pagar', [FichaFinancieraArbitroController::class, 'pagarNomina'])
                ->middleware('permission:crear-finanzas')->name('nomina.pagar');
            Route::get('/comprobante/{lote}', [FichaFinancieraArbitroController::class, 'comprobante'])->name('comprobante');
            Route::get('/comprobante-cobro/{lote}', [FichaFinancieraArbitroController::class, 'comprobanteCobro'])->name('comprobante-cobro');
        });
    });

    //  Académico — M08
    Route::prefix('academico')->name('academico.')->middleware(['permission:ver-academico', 'modulo:academico'])->group(function () {
        // Instructor / ejecutivo — gestión de sesiones
        Route::get('/', [SesionAcademicaController::class, 'index'])->middleware('permission:crear-academico')->name('sesiones.index');
        Route::get('/crear', [SesionAcademicaController::class, 'create'])->middleware('permission:crear-academico')->name('sesiones.create');
        Route::post('/', [SesionAcademicaController::class, 'store'])->middleware('permission:crear-academico')->name('sesiones.store');

        // Árbitro
        Route::get('/mis-clases', [SesionAcademicaController::class, 'misClases'])->name('mis-clases');

        // Scanner (terminal del instructor)
        Route::post('/scanner', [AsistenciaController::class, 'scanner'])->middleware('permission:crear-academico')->name('scanner');

        // Asistencias
        Route::post('/asistencias/{id}/marcar', [AsistenciaController::class, 'marcar'])->name('asistencias.marcar');
        Route::put('/asistencias/{id}', [AsistenciaController::class, 'corregir'])->middleware('permission:crear-academico')->name('asistencias.corregir');
        Route::get('/asistencias/{id}/justificar', [JustificacionController::class, 'create'])->name('justificaciones.create');
        Route::post('/asistencias/{id}/justificar', [JustificacionController::class, 'store'])->name('justificaciones.store');

        // Material de clase — se puede adjuntar antes, durante o después de
        // la sesión; la descarga queda abierta a cualquiera con ver-academico
        // (visible para todos los árbitros del colegio, no solo instructor/ejecutivo).
        Route::delete('/materiales/{id}', [MaterialAcademicoController::class, 'destroy'])->middleware('permission:crear-academico')->name('materiales.destroy');
        Route::get('/materiales/{id}/descargar', [MaterialAcademicoController::class, 'descargar'])->name('materiales.descargar');

        // Sesión individual — rutas fijas antes de /{id}
        Route::post('/{id}/materiales', [MaterialAcademicoController::class, 'store'])->middleware('permission:crear-academico')->name('materiales.store');
        Route::get('/{id}/editar', [SesionAcademicaController::class, 'edit'])->middleware('permission:crear-academico')->name('sesiones.edit');
        Route::put('/{id}', [SesionAcademicaController::class, 'update'])->middleware('permission:crear-academico')->name('sesiones.update');
        Route::delete('/{id}', [SesionAcademicaController::class, 'destroy'])->middleware('permission:crear-academico')->name('sesiones.destroy');
        Route::put('/{id}/abrir', [SesionAcademicaController::class, 'abrir'])->middleware('permission:crear-academico')->name('sesiones.abrir');
        Route::put('/{id}/cerrar', [SesionAcademicaController::class, 'cerrar'])->middleware('permission:crear-academico')->name('sesiones.cerrar');
        Route::put('/{id}/cancelar', [SesionAcademicaController::class, 'cancelar'])->middleware('permission:crear-academico')->name('sesiones.cancelar');
        Route::get('/{id}', [SesionAcademicaController::class, 'show'])->middleware('permission:crear-academico')->name('sesiones.show');
    });

    Route::prefix('tipos-sesion-academica')->name('tipos-sesion-academica.')->middleware(['permission:editar-academico', 'modulo:academico'])->group(function () {
        Route::get('/', [TipoSesionAcademicaController::class, 'index'])->name('index');
        Route::post('/', [TipoSesionAcademicaController::class, 'store'])->name('store');
        Route::put('/{id}/estado', [TipoSesionAcademicaController::class, 'cambiarEstado'])->name('estado');
        Route::delete('/{id}', [TipoSesionAcademicaController::class, 'destroy'])->name('destroy');
    });

    //  Sanciones — M07
    Route::prefix('sanciones')->name('sanciones.')->middleware(['permission:ver-sanciones', 'modulo:sanciones'])->group(function () {
        Route::get('/', [SancionController::class, 'index'])->name('index');
        Route::get('/crear', [SancionController::class, 'create'])->middleware('permission:crear-sanciones')->name('create');
        Route::post('/', [SancionController::class, 'store'])->middleware('permission:crear-sanciones')->name('store');
        Route::get('/{id}', [SancionController::class, 'show'])->name('show');
        Route::put('/{id}/estado', [SancionController::class, 'cambiarEstado'])->middleware('permission:crear-sanciones')->name('estado');
    });

    //  Revisión de justificaciones académicas — vive bajo /sanciones por
    //  ubicación (quien revisa suele trabajar desde acá), pero el permiso
    //  sigue siendo editar-academico (instructor/ejecutivo/sanciones), no
    //  ver-sanciones — el rol tecnico no tiene permisos de sanciones y
    //  igual debe poder revisar. Por eso NO va anidado en el grupo de
    //  arriba (heredaría permission:ver-sanciones como blanket).
    Route::prefix('sanciones/justificaciones')->name('sanciones.justificaciones.')->middleware(['permission:editar-academico', 'modulo:academico'])->group(function () {
        Route::get('/pendientes', [JustificacionRevisionController::class, 'pendientes'])->name('pendientes');
        Route::put('/{id}', [JustificacionRevisionController::class, 'revisar'])->name('revisar');
        Route::get('/{id}/documento', [JustificacionRevisionController::class, 'descargarDocumento'])->name('documento');
    });

    //  Catálogo de tipos de sanción — gestión, solo editar-sanciones
    Route::prefix('tipos-sancion')->name('tipos-sancion.')->middleware(['permission:editar-sanciones', 'modulo:sanciones'])->group(function () {
        Route::get('/', [TipoSancionController::class, 'index'])->name('index');
        Route::post('/', [TipoSancionController::class, 'store'])->name('store');
        Route::put('/{id}/estado', [TipoSancionController::class, 'cambiarEstado'])->name('estado');
        Route::delete('/{id}', [TipoSancionController::class, 'destroy'])->name('destroy');
    });

    //  Configuración del colegio — solo ejecutivo. Dos secciones (General /
    //  Colegio) con subnav propia, mismo patrón que finanzas.partials.subnav —
    //  ver App\Http\Controllers\Configuracion\ConfiguracionController.
    Route::prefix('configuracion')->name('configuracion.')->middleware('permission:editar-arbitros')->group(function () {
        Route::get('/', [ConfiguracionController::class, 'general'])->name('index');
        Route::get('/colegio', [ConfiguracionController::class, 'colegio'])->name('colegio');
        Route::put('/', [ConfiguracionController::class, 'update'])->name('update');
        Route::post('/logo', [ConfiguracionController::class, 'actualizarLogo'])->name('logo.actualizar');
        Route::delete('/logo', [ConfiguracionController::class, 'eliminarLogo'])->name('logo.eliminar');
    });

    //  Cuentas admin (tesorero, designador, sanciones, tecnico, veedor, co-ejecutivo)
    Route::prefix('configuracion/cuentas-admin')->name('configuracion.cuentas-admin.')
        ->middleware('permission:gestionar-cuentas-admin')->group(function () {
            Route::get('/', [CuentaAdminController::class, 'index'])->name('index');
            Route::get('/crear', [CuentaAdminController::class, 'create'])->name('create');
            Route::get('/verificar-username', [CuentaAdminController::class, 'verificarUsername'])->name('verificar-username');
            Route::post('/', [CuentaAdminController::class, 'store'])->name('store');
            Route::get('/{id}/editar', [CuentaAdminController::class, 'edit'])->name('edit');
            Route::put('/{id}', [CuentaAdminController::class, 'update'])->name('update');
            Route::put('/{id}/revocar', [CuentaAdminController::class, 'revocar'])->name('revocar');
            Route::put('/{id}/reactivar', [CuentaAdminController::class, 'reactivar'])->name('reactivar');
        });
});
