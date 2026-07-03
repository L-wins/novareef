<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Colegio;
use App\Models\ConfiguracionColegio;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class HabilitarDisponibilidadSemanal extends Command
{
    protected $signature   = 'novareef:habilitar-disponibilidad';
    protected $description = 'Registra en el log qué colegios abren hoy su ciclo de disponibilidad, según el día que cada uno configuró';

    public function handle(): int
    {
        $hoy = Carbon::today();

        $colegiosQueAbrenHoy = Colegio::query()
            ->pluck('idColegio')
            ->filter(function (int $idColegio) use ($hoy): bool {
                $diaConfigurado = ConfiguracionColegio::getDiaDisponibilidad($idColegio);

                // Carbon numera domingo=0...sábado=6; nuestro esquema es lunes=1...domingo=7.
                return $hoy->dayOfWeek === ($diaConfigurado % 7);
            })
            ->values();

        $mensaje = "Ciclo de disponibilidad evaluado para {$hoy->format('d/m/Y')}. "
                 . "Colegios cuyo ciclo abre hoy: {$colegiosQueAbrenHoy->count()}.";

        $this->info($mensaje);

        Log::info('[novareef:habilitar-disponibilidad] ' . $mensaje, [
            'fecha'               => $hoy->toDateString(),
            'idsColegiosAbiertos' => $colegiosQueAbrenHoy->all(),
        ]);

        return self::SUCCESS;
    }
}
