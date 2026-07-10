<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AsistenciaAcademica;
use App\Models\SesionAcademica;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Corre diariamente. Cierra la ventana de justificación de las asistencias
 * cuyo plazo (fechaSesion + 3 días) ya venció sin que se haya presentado
 * ninguna justificación, y finaliza automáticamente las sesiones ya pasadas
 * que el instructor nunca confirmó/cerró.
 */
class CerrarJustificacionesVencidasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        $fechaCorte = now()->subDays(SesionAcademica::DIAS_LIMITE_JUSTIFICACION)->toDateString();

        $asistenciasVencidas = AsistenciaAcademica::where('estadoAsistencia', AsistenciaAcademica::ESTADO_JUSTIFICACION_PENDIENTE)
            ->whereDoesntHave('justificacion')
            ->whereHas('sesion', fn ($q) => $q->where('fechaSesion', '<', $fechaCorte))
            ->get();

        foreach ($asistenciasVencidas as $asistencia) {
            $asistencia->update(['estadoAsistencia' => AsistenciaAcademica::ESTADO_AUSENTE]);
        }

        $sesionesSinConfirmar = SesionAcademica::whereIn('estadoSesion', [
                SesionAcademica::ESTADO_PROGRAMADA,
                SesionAcademica::ESTADO_EN_CURSO,
            ])
            ->where('fechaSesion', '<', now()->toDateString())
            ->get();

        foreach ($sesionesSinConfirmar as $sesion) {
            $sesion->update([
                'estadoSesion'  => SesionAcademica::ESTADO_FINALIZADA,
                'sesionAbierta' => false,
            ]);
        }

        Log::info('CerrarJustificacionesVencidasJob ejecutado', [
            'asistenciasCerradas' => $asistenciasVencidas->count(),
            'sesionesFinalizadas' => $sesionesSinConfirmar->count(),
        ]);
    }
}
