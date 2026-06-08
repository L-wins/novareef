<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ── Scheduler NovaReef ────────────────────────────────────────────────────────

// Marca como CRÍTICOS los partidos del día sin designaciones completas.
// Se ejecuta a las 06:00 todos los días para alertar antes del inicio de jornada.
Schedule::command('novareef:marcar-criticos')->dailyAt('06:00');

// Limpia disponibilidades de semanas anteriores al inicio de cada semana.
// weeklyOn(1) = lunes. El comando también verifica el día para proteger ejecuciones manuales.
Schedule::command('novareef:habilitar-disponibilidad')->weeklyOn(1, '00:01');
