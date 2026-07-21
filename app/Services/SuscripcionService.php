<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Colegio;
use App\Models\Plan;
use App\Models\Suscripcion;
use Illuminate\Support\Facades\DB;

final class SuscripcionService
{
    /**
     * Cambia el plan de un colegio: cierra la suscripción vigente (queda como
     * histórico, estado 'vencida') y crea una nueva con el plan elegido, a
     * partir de hoy y con el vencimiento que calcula el propio plan.
     */
    public function cambiarPlan(Colegio $colegio, Plan $planNuevo, ?string $notas = null): Suscripcion
    {
        return DB::transaction(function () use ($colegio, $planNuevo, $notas): Suscripcion {
            Suscripcion::where('idColegio', $colegio->idColegio)
                ->whereIn('estado', Suscripcion::ESTADOS_VIGENTES)
                ->update(['estado' => 'vencida']);

            $inicio = now()->toDateString();

            return Suscripcion::create([
                'idColegio'        => $colegio->idColegio,
                'idPlan'           => $planNuevo->idPlan,
                'fechaInicio'      => $inicio,
                'fechaVencimiento' => $planNuevo->calcularVencimiento(now())->toDateString(),
                'estado'           => 'activa',
                'notas'            => $notas ?? "Cambio a plan \"{$planNuevo->nombre}\" desde el panel admin.",
            ]);
        });
    }

    /**
     * Extiende el vencimiento de la suscripción vigente del colegio.
     *
     * @throws \RuntimeException  Si el colegio no tiene una suscripción vigente.
     */
    public function extender(Colegio $colegio, int $dias): Suscripcion
    {
        $suscripcion = $colegio->suscripcionActiva;

        if ($suscripcion === null) {
            throw new \RuntimeException('Este colegio no tiene una suscripción vigente para extender.');
        }

        $base = $suscripcion->fechaVencimiento?->isPast() ? now() : $suscripcion->fechaVencimiento;

        $suscripcion->update([
            'fechaVencimiento' => $base->copy()->addDays($dias)->toDateString(),
        ]);

        return $suscripcion->fresh();
    }

    /**
     * Marca la suscripción vigente del colegio para no renovar. No corta el
     * acceso de inmediato — el colegio ya pagó ese período, así que lo
     * conserva hasta fechaVencimiento (mismo criterio que Netflix/Spotify al
     * cancelar). VencerSuscripcionesJob es quien, ese día, pasa el estado a
     * 'vencida'. Un corte inmediato (impago/abuso) es una decisión distinta
     * y usa el toggle de Colegio.estadoColegio, no este método.
     *
     * @throws \RuntimeException  Si el colegio no tiene una suscripción vigente
     *                            o ya tiene una cancelación programada.
     */
    public function cancelar(Colegio $colegio, ?string $notas = null): Suscripcion
    {
        $suscripcion = $colegio->suscripcionActiva;

        if ($suscripcion === null) {
            throw new \RuntimeException('Este colegio no tiene una suscripción vigente para cancelar.');
        }

        if ($suscripcion->fechaCancelacion !== null) {
            throw new \RuntimeException('Esta suscripción ya tiene una cancelación programada.');
        }

        $suscripcion->update([
            'fechaCancelacion' => now(),
            'notas'            => $notas ?? trim(($suscripcion->notas ? $suscripcion->notas . ' — ' : '') . 'Cancelación programada desde el panel admin — acceso hasta fechaVencimiento.'),
        ]);

        return $suscripcion->fresh();
    }
}
