<?php

use App\Jobs\CerrarJustificacionesVencidasJob;
use App\Jobs\VencerSancionesJob;
use App\Jobs\VerificarConfirmacionesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ── Scheduler NovaReef ────────────────────

// Marca como CRÍTICOS los partidos del día sin designaciones completas.
// Se ejecuta a las 06:00 todos los días para alertar antes del inicio de jornada.
Schedule::command('novareef:marcar-criticos')->dailyAt('06:00');

// Registra en el log qué colegios abren su ciclo de disponibilidad hoy, según
// el día que cada uno configuró (ConfiguracionColegio::DIA_DISPONIBILIDAD).
// El cálculo real del ciclo es dinámico (SemanaNavegacion) — este comando no
// modifica datos, solo deja evidencia en el log para observabilidad.
Schedule::command('novareef:habilitar-disponibilidad')->dailyAt('00:01');

// Pasa los torneos de "próximo" a "activo" y de "activo" a "finalizado" según
// fechaInicio/fechaFin — antes estadoTorneo era 100% manual y podía quedar
// desactualizado indefinidamente. Nunca toca 'cancelado' ni un 'finalizado' ya puesto.
Schedule::command('novareef:actualizar-estados-torneo')->dailyAt('00:03');

// Marca como CRÍTICOS los partidos programados con designaciones pendientes
// cuyo plazo de confirmación (configurable por colegio) ya venció.
Schedule::job(new VerificarConfirmacionesJob)->everyFifteenMinutes();

// Cierra automáticamente las sanciones activas/apeladas cuya fechaFinSancion
// ya pasó, marcándolas como cumplidas. Las de fecha indefinida no se tocan.
Schedule::job(new VencerSancionesJob)->daily();

// Cierra la ventana de justificación de asistencias académicas vencidas sin
// justificar, y finaliza sesiones pasadas que el instructor nunca cerró.
Schedule::job(new CerrarJustificacionesVencidasJob)->daily();
