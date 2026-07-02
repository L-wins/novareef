<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Partido;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarcarPartidosCriticos extends Command
{
    protected $signature   = 'novareef:marcar-criticos';
    protected $description = 'Marca como críticos los partidos de hoy sin designaciones completas';

    public function handle(): int
    {
        $hoy = Carbon::today();

        $this->info("Verificando partidos del {$hoy->format('d/m/Y')}...");

        // Carga solo formato (para maxArbitros) y el conteo de confirmadas via withCount.
        // withCount evita el N+1: en lugar de ejecutar designacionesConfirmadas()->count()
        // por cada partido en estaCompleto(), usa un subquery COUNT en la misma query principal.
        $partidos = Partido::withCount(['designacionesConfirmadas'])
            ->with(['formato', 'torneo'])
            ->whereDate('fechaPartido', $hoy)
            ->whereIn('estadoPartido', [
                Partido::ESTADO_PROGRAMADO,
                Partido::ESTADO_CONFIRMADO,
            ])
            ->get();

        $total    = $partidos->count();
        $marcados = 0;

        if ($total === 0) {
            $this->info('No hay partidos programados o confirmados para hoy.');
            return self::SUCCESS;
        }

        // Filtrar los incompletos y actualizar en una sola transacción
        $idsCriticos = $partidos
            ->filter(fn ($p) => ! $this->estaCompleto($p))
            ->pluck('idPartido');

        if ($idsCriticos->isNotEmpty()) {
            DB::transaction(static function () use ($idsCriticos): void {
                Partido::whereIn('idPartido', $idsCriticos)
                    ->update(['estadoPartido' => Partido::ESTADO_CRITICO]);
            });

            $marcados = $idsCriticos->count();

            Log::warning('[novareef:marcar-criticos] Partidos marcados como críticos', [
                'fecha'       => $hoy->toDateString(),
                'idPartidos'  => $idsCriticos->values()->toArray(),
                'total'       => $marcados,
            ]);
        }

        $this->newLine();
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Partidos evaluados hoy',     $total],
                ['Marcados como críticos',      $marcados],
                ['Completos / sin acción',      $total - $marcados],
            ]
        );

        $this->info($marcados > 0
            ? "Se marcaron {$marcados} partido(s) como CRÍTICOS."
            : 'No hay partidos críticos hoy. Todo en orden.'
        );

        return self::SUCCESS;
    }

    /**
     * Usa el withCount precargado en lugar de relanzar la query de designacionesConfirmadas.
     */
    private function estaCompleto(Partido $partido): bool
    {
        $maxArbitros = $partido->formato?->maxArbitros ?? 0;

        if ($maxArbitros === 0) {
            return false;
        }

        return ($partido->designaciones_confirmadas_count ?? 0) >= $maxArbitros;
    }
}
