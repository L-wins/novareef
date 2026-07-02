<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;

final class PlanService
{
    /** Campos booleanos de visibilidad/estado que el panel admin puede alternar. */
    private const CAMPOS_ALTERNABLES = ['esVisible', 'esActivo'];

    /** Etiquetas [valorVerdadero, valorFalso] por campo, para el mensaje flash. */
    private const ETIQUETAS = [
        'esVisible' => ['visible', 'oculto'],
        'esActivo'  => ['activo', 'inactivo'],
    ];

    /**
     * Alterna un campo booleano del plan (visibilidad o estado).
     *
     * @return string  Etiqueta legible del valor resultante, para el mensaje flash.
     * @throws \InvalidArgumentException  Si el campo no está en la whitelist permitida.
     */
    public function alternarCampo(Plan $plan, string $campo): string
    {
        if (! in_array($campo, self::CAMPOS_ALTERNABLES, true)) {
            throw new \InvalidArgumentException('Campo de toggle no permitido.');
        }

        $plan->update([$campo => ! $plan->{$campo}]);

        [$etiquetaActivo, $etiquetaInactivo] = self::ETIQUETAS[$campo];

        return $plan->{$campo} ? $etiquetaActivo : $etiquetaInactivo;
    }
}
