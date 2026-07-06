<?php

declare(strict_types=1);

namespace App\StateMachines;

use App\Events\PartidoActualizadoEvent;
use App\Exceptions\OptimisticLockException;
use App\Jobs\NotificarAplazamientoJob;
use App\Jobs\NotificarCancelacionJob;
use App\Jobs\NotificarFinalizacionJob;
use App\Jobs\NotificarPublicacionJob;
use App\Jobs\GenerarPagosJob;
use App\Models\HistorialDesignacion;
use App\Models\Partido;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PartidoStateMachine
{
    const TRANSICIONES = [
        'borrador'   => ['programado', 'cancelado'],
        'programado' => ['confirmado', 'critico', 'aplazado', 'cancelado'],
        'confirmado' => ['programado', 'en_curso', 'aplazado', 'cancelado'],
        'critico'    => ['programado', 'cancelado'],
        'aplazado'   => ['programado', 'cancelado'],
        'en_curso'   => ['finalizado', 'cancelado'],
        'finalizado' => ['programado'], // reversible solo por ejecutivo
        'cancelado'  => [],
    ];

    public static function puedeTransicionar(string $desde, string $hacia): bool
    {
        return in_array($hacia, self::TRANSICIONES[$desde] ?? [], true);
    }

    public static function validarTransicion(string $desde, string $hacia): void
    {
        if (! self::puedeTransicionar($desde, $hacia)) {
            throw new \InvalidArgumentException(
                "Transición inválida: '{$desde}' → '{$hacia}'"
            );
        }
    }

    /**
     * Transiciona el partido al nuevo estado usando optimistic locking.
     * Registra el historial y despacha efectos secundarios (jobs + broadcast).
     * $usuario es null cuando la transición la ejecuta el sistema (jobs programados).
     */
    public static function transicionarCon(
        Partido $partido,
        string $estadoNuevo,
        ?User $usuario,
        ?string $detalle = null
    ): void {
        self::validarTransicion($partido->estadoPartido, $estadoNuevo);
        self::validarPermisosEspeciales($partido->estadoPartido, $estadoNuevo, $usuario);

        DB::transaction(function () use ($partido, $estadoNuevo, $usuario, $detalle): void {
            $cambios = [
                'estadoPartido' => $estadoNuevo,
                'version'       => $partido->version + 1,
                'updated_at'    => now(),
            ];

            // horaInicio alimenta la finalización automática (150 min después)
            if ($estadoNuevo === Partido::ESTADO_EN_CURSO && $partido->horaInicio === null) {
                $cambios['horaInicio'] = now();
            }

            $afectadas = DB::table('partidos')
                ->where('idPartido', $partido->idPartido)
                ->where('version', $partido->version)
                ->update($cambios);

            if ($afectadas === 0) {
                throw new OptimisticLockException();
            }

            $estadoAnterior = $partido->estadoPartido;

            $partido->estadoPartido = $estadoNuevo;
            $partido->version       = $partido->version + 1;

            if (isset($cambios['horaInicio'])) {
                $partido->horaInicio = $cambios['horaInicio'];
            }

            HistorialDesignacion::create([
                'idPartido'      => $partido->idPartido,
                'idColegio'      => $partido->idColegio,
                'idUsuarioAccion'=> $usuario?->idUsuario,
                'tipoAccion'     => HistorialDesignacion::TIPO_ESTADO_PARTIDO_CAMBIADO,
                'estadoAnterior' => $estadoAnterior,
                'estadoNuevo'    => $estadoNuevo,
                'detalle'        => $detalle,
            ]);

            self::ejecutarEfectos($partido, $estadoAnterior, $estadoNuevo);

            broadcast(new PartidoActualizadoEvent($partido))->toOthers();
        });
    }

    /**
     * Reglas de autorización ligadas a transiciones específicas.
     * finalizado → programado: reversión contable/operativa, solo ejecutivo.
     */
    private static function validarPermisosEspeciales(string $desde, string $hacia, ?User $usuario): void
    {
        if ($desde === 'finalizado' && $hacia === 'programado') {
            $esEjecutivo = $usuario !== null
                && ($usuario->rolUsuario === 'ejecutivo' || $usuario->rolUsuario === 'superadmin');

            if (! $esEjecutivo) {
                throw new \InvalidArgumentException(
                    'Solo un ejecutivo puede revertir un partido finalizado.'
                );
            }
        }
    }

    private static function ejecutarEfectos(Partido $partido, string $estadoAnterior, string $estadoNuevo): void
    {
        // Publicación: borrador → programado notifica a todos los árbitros designados
        if ($estadoAnterior === 'borrador' && $estadoNuevo === 'programado') {
            NotificarPublicacionJob::dispatch($partido);
            return;
        }

        match ($estadoNuevo) {
            'cancelado'  => NotificarCancelacionJob::dispatch($partido),
            'finalizado' => self::efectosFinalizacion($partido),
            'aplazado'   => NotificarAplazamientoJob::dispatch($partido),
            default      => null,
        };
    }

    private static function efectosFinalizacion(Partido $partido): void
    {
        NotificarFinalizacionJob::dispatch($partido);

        if ($partido->modalidadPago === 'nomina') {
            GenerarPagosJob::dispatch($partido);
        }
    }
}
