<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Designacion;
use App\Models\Partido;
use App\Models\RolPartido;
use App\Models\SlotDesignacion;
use Illuminate\Support\Facades\Log;

final class SlotDesignacionService
{
    /**
     * Crea los slots de designación del partido según su formato.
     * Idempotente: usa firstOrCreate sobre la clave única (partido, rol, numeroSlot).
     */
    public function crear(Partido $partido): void
    {
        $definicion = $this->definicionSlots($partido->formato);

        $roles = RolPartido::where('esActivo', true)
            ->whereIn('nombre', array_keys($definicion))
            ->get()
            ->keyBy('nombre');

        foreach ($definicion as $nombreRol => $cantidad) {
            $rol = $roles->get($nombreRol);

            if ($rol === null) {
                Log::warning("SlotDesignacionService::crear: rol '{$nombreRol}' no existe o está inactivo. idPartido={$partido->idPartido}");
                continue;
            }

            for ($n = 1; $n <= $cantidad; $n++) {
                SlotDesignacion::firstOrCreate([
                    'idPartido'  => $partido->idPartido,
                    'idRol'      => $rol->idRol,
                    'numeroSlot' => $n,
                ]);
            }
        }
    }

    /**
     * Garantiza que el partido tenga slots: los crea y vincula las designaciones
     * existentes (partidos creados antes del sistema de slots).
     */
    public function asegurar(Partido $partido): void
    {
        if (SlotDesignacion::where('idPartido', $partido->idPartido)->exists()) {
            return;
        }

        $this->crear($partido);

        $designaciones = $partido->designaciones()
            ->whereIn('estadoDesignacion', [Designacion::ESTADO_PENDIENTE, Designacion::ESTADO_CONFIRMADA])
            ->get();

        foreach ($designaciones as $designacion) {
            SlotDesignacion::where('idPartido', $partido->idPartido)
                ->where('idRol', $designacion->idRol)
                ->whereNull('idDesignacion')
                ->orderBy('numeroSlot')
                ->limit(1)
                ->update(['idDesignacion' => $designacion->idDesignacion]);
        }
    }

    /**
     * Define cuántos slots de cada rol exige el formato del partido.
     * VAR y AVAR nunca en formatos estándar.
     */
    private function definicionSlots(?object $formato): array
    {
        $nombreFormato = strtolower($formato?->nombre ?? '');

        return match (true) {
            str_contains($nombreFormato, 'solo')   => ['Central' => 1],
            str_contains($nombreFormato, 'dupla')  => ['Central' => 1, 'Asistente' => 1],
            str_contains($nombreFormato, 'cuarto') => ['Central' => 1, 'Asistente' => 2, 'Cuarto' => 1],
            str_contains($nombreFormato, 'terna')  => ['Central' => 1, 'Asistente' => 2],
            default                                => ['Central' => 1, 'Asistente' => 1],
        };
    }
}
