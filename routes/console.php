<?php

use App\Jobs\FinalizarPartidosAutomaticoJob;
use App\Jobs\IniciarPartidosAutomaticoJob;
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

// Inicia automáticamente partidos confirmados cuya hora ya llegó (asigna horaInicio).
Schedule::job(new IniciarPartidosAutomaticoJob)->everyFiveMinutes();

// Finaliza automáticamente partidos en_curso que lleven más de 150 minutos.
Schedule::job(new FinalizarPartidosAutomaticoJob)->everyFiveMinutes();

// Marca como CRÍTICOS los partidos programados con designaciones pendientes
// cuyo plazo de confirmación (configurable por colegio) ya venció.
Schedule::job(new VerificarConfirmacionesJob)->everyFifteenMinutes();
