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
     * Cancela la suscripción vigente del colegio. El esquema no tiene un
     * estado "cancelada" propio — se usa 'suspendida' (el mismo que usaría
     * un impago), que ya excluye al colegio de ESTADOS_VIGENTES.
     *
     * @throws \RuntimeException  Si el colegio no tiene una suscripción vigente.
     */
    public function cancelar(Colegio $colegio, ?string $notas = null): Suscripcion
    {
        $suscripcion = $colegio->suscripcionActiva;

        if ($suscripcion === null) {
            throw new \RuntimeException('Este colegio no tiene una suscripción vigente para cancelar.');
        }

        $suscripcion->update([
            'estado' => 'suspendida',
            'notas'  => $notas ?? trim(($suscripcion->notas ? $suscripcion->notas . ' — ' : '') . 'Cancelada desde el panel admin.'),
        ]);

        return $suscripcion->fresh();
    }
}
