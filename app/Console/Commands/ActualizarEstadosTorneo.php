<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Torneo;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * estadoTorneo es un campo manual — nada lo recalculaba según fechaInicio/
 * fechaFin, así que un torneo podía quedar "próximo" para siempre aunque ya
 * estuviera en curso, o "activo" para siempre aunque ya hubiera terminado.
 * Este comando corrige eso automáticamente todos los días.
 *
 * Nunca toca 'cancelado' ni 'finalizado' manual — solo avanza el ciclo
 * normal proximo -> activo -> finalizado según las fechas del torneo.
 */
class ActualizarEstadosTorneo extends Command
{
    protected $signature   = 'novareef:actualizar-estados-torneo';
    protected $description = 'Pasa los torneos de "próximo" a "activo" y de "activo" a "finalizado" según sus fechas';

    public function handle(): int
    {
        $hoy = Carbon::today();

        $activados = Torneo::where('estadoTorneo', 'proximo')
            ->whereDate('fechaInicio', '<=', $hoy)
            ->update(['estadoTorneo' => 'activo']);

        $finalizados = Torneo::where('estadoTorneo', 'activo')
            ->whereDate('fechaFin', '<', $hoy)
            ->update(['estadoTorneo' => 'finalizado']);

        $mensaje = "Torneos actualizados el {$hoy->format('d/m/Y')}: "
                 . "{$activados} pasaron a activo, {$finalizados} pasaron a finalizado.";

        $this->info($mensaje);
        Log::info('[novareef:actualizar-estados-torneo] ' . $mensaje);

        return self::SUCCESS;
    }
}
