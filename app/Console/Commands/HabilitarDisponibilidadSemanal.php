<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DisponibilidadArbitro;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class HabilitarDisponibilidadSemanal extends Command
{
    protected $signature   = 'novareef:habilitar-disponibilidad';
    protected $description = 'Elimina disponibilidades de semanas anteriores para abrir el reporte de la semana actual';

    public function handle(): int
    {
        $hoy = Carbon::today();

        // El scheduler ya limita la ejecución a los lunes (weeklyOn(1, '00:01')),
        // pero esta guarda protege contra ejecuciones manuales accidentales.
        if (! $hoy->isMonday()) {
            $this->warn(
                "Este comando debe ejecutarse los lunes. Hoy es {$hoy->translatedFormat('l')} — sin cambios."
            );
            return self::FAILURE;
        }

        // $hoy ya es lunes = inicio de semana; no necesita copy()->startOfWeek()
        $eliminados = DisponibilidadArbitro::whereDate('fechaDisponibilidad', '<', $hoy)->delete();

        $mensaje = "Disponibilidad semanal habilitada para la semana del {$hoy->format('d/m/Y')}. "
                 . "Registros de semanas anteriores eliminados: {$eliminados}.";

        $this->info($mensaje);

        Log::info('[novareef:habilitar-disponibilidad] ' . $mensaje, [
            'fecha_inicio_semana' => $hoy->toDateString(),
            'registros_eliminados' => $eliminados,
        ]);

        return self::SUCCESS;
    }
}
