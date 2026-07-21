<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Arbitro;
use App\Models\Colegio;
use App\Models\ConfiguracionColegio;
use App\Services\FinanzasService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Genera el cargo de mensualidad del mes vigente para cada árbitro activo de
 * cada colegio con cobro automático configurado
 * (ConfiguracionColegio::MONTO_MENSUALIDAD > 0). Corre diario, no mensual —
 * sin precedente de ->monthly() en el scheduler — y usa >= en vez de ===
 * contra el día de vencimiento: si el job no corrió el día exacto (caída,
 * mantenimiento), el día siguiente igual genera el cargo del mes; la
 * deduplicación mensual ya incluida en
 * FinanzasService::generarCuotaMensualAutomatica() evita que esto duplique
 * si sí se generó a tiempo.
 */
class GenerarCuotasMensualesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(FinanzasService $finanzas): void
    {
        $hoy       = Carbon::today();
        $generados = 0;
        $errores   = 0;

        Colegio::where('estadoColegio', 'activo')->pluck('idColegio')->each(function (int $idColegio) use ($finanzas, $hoy, &$generados, &$errores): void {
            $monto = ConfiguracionColegio::getMontoMensualidad($idColegio);
            if ($monto <= 0.0) {
                return;
            }

            $diaVencimiento = ConfiguracionColegio::getDiaVencimientoMensualidad($idColegio);
            if ($hoy->day < $diaVencimiento) {
                return;
            }

            $idLoteCobro = (string) Str::uuid();
            $fecha       = $hoy->toDateString();

            Arbitro::where('idColegio', $idColegio)
                ->whereNotIn('estadoArbitro', ['retirado'])
                ->get()
                ->each(function (Arbitro $arbitro) use ($finanzas, $idColegio, $monto, $fecha, $idLoteCobro, &$generados, &$errores): void {
                    try {
                        if ($finanzas->generarCuotaMensualAutomatica($idColegio, $arbitro, $monto, $fecha, $idLoteCobro) !== null) {
                            $generados++;
                        }
                    } catch (\Throwable $e) {
                        $errores++;
                        Log::error("GenerarCuotasMensualesJob: error generando cuota para árbitro {$arbitro->idArbitro}", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                });
        });

        Log::info('GenerarCuotasMensualesJob ejecutado', ['cuotasGeneradas' => $generados, 'errores' => $errores]);
    }
}
