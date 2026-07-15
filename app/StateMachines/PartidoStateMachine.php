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
use App\Services\FinanzasService;
use Illuminate\Support\Facades\DB;

class PartidoStateMachine
{
    // confirmado->critico y critico->confirmado deben existir juntas: un rechazo
    // o una designación reasignada pendiente puede escalar un partido confirmado
    // a crítico, y confirmarDesignacion() intenta volver a 'confirmado' apenas
    // el último slot pendiente se confirma, sin importar el estado previo.
    //
    // No existe estado "en_curso": el partido queda en 'confirmado' hasta que
    // el árbitro Central o el designador lo finalizan manualmente — no hay
    // transición automática por horario.
    const TRANSICIONES = [
        'borrador'   => ['programado', 'cancelado'],
        'programado' => ['confirmado', 'critico', 'aplazado', 'cancelado'],
        'confirmado' => ['programado', 'critico', 'finalizado', 'aplazado', 'cancelado'],
        'critico'    => ['programado', 'confirmado', 'cancelado'],
        'aplazado'   => ['programado', 'cancelado'],
        'finalizado' => ['programado'], // reversible solo por ejecutivo
        'cancelado'  => [],
    ];

    public static function puedeTransicionar(string $desde, string $hacia): bool
    {
        return in_array($hacia, self::TRANSICIONES[$desde] ?? [], true);
    }

    /**
     * Subconjunto de TRANSICIONES ofrecido en el selector manual de "Cambiar
     * estado". 'critico' y 'confirmado' nunca son destinos manuales — los
     * activa el propio sistema (rechazo/vencimiento de confirmación, o
     * cuando el último árbitro pendiente confirma). 'programado' solo es
     * manual para reactivar un aplazado o revertir un finalizado (ejecutivo);
     * nunca para "deshacer" un confirmado o crítico sin resolver la causa real.
     *
     * @return list<string>
     */
    public static function transicionesManuales(string $desde): array
    {
        return array_values(array_filter(
            self::TRANSICIONES[$desde] ?? [],
            fn (string $hacia) => match ($hacia) {
                'critico', 'confirmado' => false,
                'programado'            => in_array($desde, ['aplazado', 'finalizado'], true),
                default                 => true,
            }
        ));
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

            HistorialDesignacion::create([
                'idPartido'      => $partido->idPartido,
                'idColegio'      => $partido->idColegio,
                'idUsuarioAccion'=> $usuario?->idUsuario,
                'tipoAccion'     => HistorialDesignacion::TIPO_ESTADO_PARTIDO_CAMBIADO,
                'estadoAnterior' => $estadoAnterior,
                'estadoNuevo'    => $estadoNuevo,
                'detalle'        => $detalle,
            ]);

            self::ejecutarEfectos($partido, $estadoAnterior, $estadoNuevo, $usuario);

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

    private static function ejecutarEfectos(Partido $partido, string $estadoAnterior, string $estadoNuevo, ?User $usuario): void
    {
        // Publicación: borrador → programado notifica a todos los árbitros designados
        if ($estadoAnterior === 'borrador' && $estadoNuevo === 'programado') {
            NotificarPublicacionJob::dispatch($partido);
            return;
        }

        // Reversión: finalizado → programado anula la nómina que se generó
        // al finalizar — validarPermisosEspeciales() ya garantizó que
        // $usuario no es null acá (solo un ejecutivo llega a este punto).
        if ($estadoAnterior === 'finalizado' && $estadoNuevo === 'programado') {
            app(FinanzasService::class)->anularMovimientosPorReversionPartido($partido, $usuario);
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
