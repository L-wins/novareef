<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\DesignacionActualizadaEvent;
use App\Events\PartidoActualizadoEvent;
use App\Jobs\NotificarRechazoJob;
use App\Models\Arbitro;
use App\Models\Designacion;
use App\Models\DisponibilidadArbitro;
use App\Models\HistorialDesignacion;
use App\Models\Partido;
use App\Models\SlotDesignacion;
use App\Models\TarifaTorneo;
use App\Models\User;
use App\StateMachines\PartidoStateMachine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DesignacionService
{
    private const MINUTOS_MARGEN_PARTIDO_CERCANO = 60;

    public function __construct(
        private readonly SlotDesignacionService $slots,
    ) {}

    /**
     * Crea un partido en borrador con sus slots según formato y registra
     * la creación en el historial.
     *
     * @param  array{idTorneo:int, idDivision:int, idSede:int, idFormato:int,
     *                equipoLocal:string, equipoVisitante:string, fechaPartido:string,
     *                horaPartido:string, observaciones:?string}  $datos
     */
    public function crearPartido(int $idColegio, array $datos, int $idUsuarioAccion): Partido
    {
        return DB::transaction(function () use ($datos, $idColegio, $idUsuarioAccion): Partido {
            $partido = Partido::create([
                'idColegio'       => $idColegio,
                'idTorneo'        => $datos['idTorneo'],
                'idDivision'      => $datos['idDivision'],
                'idSede'          => $datos['idSede'],
                'idFormato'       => $datos['idFormato'],
                'equipoLocal'     => $datos['equipoLocal'],
                'equipoVisitante' => $datos['equipoVisitante'],
                'fechaPartido'    => $datos['fechaPartido'],
                'horaPartido'     => $datos['horaPartido'],
                'estadoPartido'   => Partido::ESTADO_BORRADOR,
                'version'         => 0,
                'observaciones'   => $datos['observaciones'] ?? null,
            ]);

            // Slots de roles según formato — fuente de verdad para la asignación
            $this->slots->crear($partido->load('formato'));

            HistorialDesignacion::create([
                'idPartido'       => $partido->idPartido,
                'idColegio'       => $idColegio,
                'idUsuarioAccion' => $idUsuarioAccion,
                'tipoAccion'      => HistorialDesignacion::TIPO_PARTIDO_CREADO,
                'estadoNuevo'     => Partido::ESTADO_BORRADOR,
                'detalle'         => "Partido creado: {$partido->equipoLocal} vs {$partido->equipoVisitante}",
            ]);

            return $partido;
        });
    }

    /**
     * Publica un partido en borrador: valida que tenga Central asignado y
     * lo transiciona a 'programado' (la state machine notifica a los árbitros).
     *
     * @throws \RuntimeException  Si el partido no está en borrador o no tiene Central.
     */
    public function publicarPartido(Partido $partido, User $usuario): void
    {
        if ($partido->estadoPartido !== Partido::ESTADO_BORRADOR) {
            throw new \RuntimeException('Solo se pueden publicar partidos en borrador.');
        }

        $tieneCentral = $partido->designaciones()
            ->whereIn('estadoDesignacion', [Designacion::ESTADO_PENDIENTE, Designacion::ESTADO_CONFIRMADA])
            ->whereHas('rol', fn ($q) => $q->where('nombre', 'Central'))
            ->exists();

        if (! $tieneCentral) {
            throw new \RuntimeException('Debes asignar al menos el árbitro Central antes de publicar.');
        }

        PartidoStateMachine::transicionarCon($partido, Partido::ESTADO_PROGRAMADO, $usuario, 'Partido publicado');
    }

    /**
     * Asigna un árbitro a un rol libre del partido con optimistic locking
     * sobre el slot. Solo permitido mientras el partido esté en borrador.
     *
     * @return array{designacion: Designacion, advertencias: array}
     * @throws \RuntimeException  Si el partido no está en borrador, el árbitro ya
     *                            está designado, o no hay slots libres para el rol.
     */
    public function asignarArbitro(Partido $partido, int $idArbitro, int $idRol, int $idColegio, int $idUsuarioDesignador): array
    {
        return DB::transaction(function () use ($partido, $idArbitro, $idRol, $idColegio, $idUsuarioDesignador): array {
            $partido = Partido::lockForUpdate()->where('idColegio', $idColegio)->findOrFail($partido->idPartido);

            // Después de publicar solo se puede cambiar el veedor
            if ($partido->estadoPartido !== Partido::ESTADO_BORRADOR) {
                throw new \RuntimeException('No puedes asignar árbitros después de publicar el partido. Solo puedes asignar el veedor.');
            }

            $arbitro = Arbitro::where('idArbitro', $idArbitro)
                ->where('idColegio', $idColegio)
                ->with('usuario', 'disponibilidades', 'indisponibilidadesExtraordinarias')
                ->firstOrFail();

            // No duplicar árbitro en el mismo partido
            if ($partido->designaciones()->where('idArbitro', $arbitro->idArbitro)->exists()) {
                throw new \RuntimeException('Este árbitro ya está designado en este partido.');
            }

            // Slots son la fuente de verdad: tomar el primer slot libre del rol
            $this->slots->asegurar($partido->load('formato'));

            $slot = SlotDesignacion::where('idPartido', $partido->idPartido)
                ->where('idRol', $idRol)
                ->whereNull('idDesignacion')
                ->orderBy('numeroSlot')
                ->lockForUpdate()
                ->first();

            if ($slot === null) {
                throw new \RuntimeException('No hay slots disponibles para este rol.');
            }

            $advertencias = $this->calcularAdvertencias($arbitro, $partido);

            $designacion = Designacion::create([
                'idPartido'           => $partido->idPartido,
                'idArbitro'           => $arbitro->idArbitro,
                'idRol'               => $idRol,
                'idColegio'           => $idColegio,
                'estadoDesignacion'   => Designacion::ESTADO_PENDIENTE,
                'idUsuarioDesignador' => $idUsuarioDesignador,
            ]);

            $slot->update(['idDesignacion' => $designacion->idDesignacion]);

            HistorialDesignacion::create([
                'idDesignacion'   => $designacion->idDesignacion,
                'idPartido'       => $partido->idPartido,
                'idArbitro'       => $arbitro->idArbitro,
                'idColegio'       => $idColegio,
                'idUsuarioAccion' => $idUsuarioDesignador,
                'tipoAccion'      => HistorialDesignacion::TIPO_ASIGNADO,
                'detalle'         => implode(', ', array_filter([
                    $advertencias['sinDisponibilidad']   ? 'Sin disponibilidad reportada' : '',
                    $advertencias['tieneExtraordinaria'] ? 'Indisponibilidad extraordinaria' : '',
                    $advertencias['esSuspendido']        ? 'Árbitro suspendido' : '',
                    $advertencias['advertenciaTiempo']   ? "Partido cercano ({$advertencias['minutosAlPartidoCercano']} min)" : '',
                ])) ?: null,
            ]);

            // El árbitro se notifica al publicar el partido (NotificarPublicacionJob),
            // nunca mientras el partido siga en borrador.
            broadcast(new DesignacionActualizadaEvent($designacion))->toOthers();

            return [
                'designacion'  => $designacion->load(['arbitro.usuario', 'rol']),
                'advertencias' => $advertencias,
            ];
        });
    }

    /**
     * Quita una designación pendiente del partido.
     *
     * @throws \RuntimeException  Si la designación ya está confirmada.
     */
    public function quitarDesignacion(Designacion $designacion, int $idColegio, int $idUsuarioAccion): void
    {
        if ($designacion->estaConfirmada()) {
            throw new \RuntimeException('No se puede quitar una designación ya confirmada.');
        }

        DB::transaction(function () use ($designacion, $idColegio, $idUsuarioAccion): void {
            HistorialDesignacion::create([
                'idDesignacion'   => $designacion->idDesignacion,
                'idPartido'       => $designacion->idPartido,
                'idArbitro'       => $designacion->idArbitro,
                'idColegio'       => $idColegio,
                'idUsuarioAccion' => $idUsuarioAccion,
                'tipoAccion'      => HistorialDesignacion::TIPO_QUITADO,
            ]);

            $designacion->delete();

            $partido = Partido::find($designacion->idPartido);
            if ($partido) {
                broadcast(new PartidoActualizadoEvent($partido))->toOthers();
            }
        });
    }

    /**
     * Asigna (o remueve, si $idVeedor es null) el veedor de un partido.
     *
     * @throws \RuntimeException  Si el veedor no pertenece al colegio del partido.
     */
    public function asignarVeedor(Partido $partido, ?int $idVeedor, int $idColegio, int $idUsuarioAccion): void
    {
        if ($idVeedor !== null) {
            $perteneceAlColegio = User::where('idUsuario', $idVeedor)
                ->where('idColegio', $idColegio)
                ->exists();

            if (! $perteneceAlColegio) {
                throw new \RuntimeException('El veedor no pertenece a este colegio.');
            }
        }

        $partido->update(['idVeedor' => $idVeedor]);

        HistorialDesignacion::create([
            'idPartido'       => $partido->idPartido,
            'idColegio'       => $idColegio,
            'idUsuarioAccion' => $idUsuarioAccion,
            'tipoAccion'      => HistorialDesignacion::TIPO_ESTADO_PARTIDO_CAMBIADO,
            'detalle'         => $idVeedor
                ? 'Veedor asignado: ' . User::find($idVeedor)?->nombreUsuario
                : 'Veedor removido',
        ]);
    }

    /**
     * El árbitro confirma su designación pendiente. Si con esta confirmación
     * el partido queda completo, lo transiciona automáticamente a 'confirmado'.
     *
     * @return bool  true si el partido quedó completo tras esta confirmación.
     * @throws \RuntimeException  Si la designación no está pendiente.
     */
    public function confirmarDesignacion(Designacion $designacion, Arbitro $arbitro, User $usuario): bool
    {
        if (! $designacion->estaPendiente()) {
            throw new \RuntimeException('Esta designación no está en estado pendiente.');
        }

        return DB::transaction(function () use ($designacion, $arbitro, $usuario): bool {
            $designacion->update([
                'estadoDesignacion' => Designacion::ESTADO_CONFIRMADA,
                'fechaConfirmacion' => now(),
            ]);

            HistorialDesignacion::create([
                'idDesignacion'   => $designacion->idDesignacion,
                'idPartido'       => $designacion->idPartido,
                'idArbitro'       => $arbitro->idArbitro,
                'idColegio'       => $designacion->idColegio,
                'idUsuarioAccion' => $usuario->idUsuario,
                'tipoAccion'      => HistorialDesignacion::TIPO_CONFIRMADO,
                'estadoAnterior'  => Designacion::ESTADO_PENDIENTE,
                'estadoNuevo'     => Designacion::ESTADO_CONFIRMADA,
            ]);

            broadcast(new DesignacionActualizadaEvent($designacion->fresh()))->toOthers();

            $partido = Partido::with('formato')->find($designacion->idPartido);
            if ($partido && $partido->estaCompleto()) {
                PartidoStateMachine::transicionarCon($partido, Partido::ESTADO_CONFIRMADO, $usuario);
                return true;
            }

            return false;
        });
    }

    /**
     * El árbitro rechaza su designación pendiente con un motivo. Si el partido
     * estaba confirmado, regresa a 'programado'.
     *
     * @throws \RuntimeException  Si la designación no está pendiente.
     */
    public function rechazarDesignacion(Designacion $designacion, Arbitro $arbitro, string $motivo, User $usuario): void
    {
        if (! $designacion->estaPendiente()) {
            throw new \RuntimeException('Solo se pueden rechazar designaciones pendientes.');
        }

        DB::transaction(function () use ($designacion, $motivo, $arbitro, $usuario): void {
            $designacion->update([
                'estadoDesignacion' => Designacion::ESTADO_RECHAZADA,
                'motivoRechazo'     => $motivo,
                'fechaRechazo'      => now(),
            ]);

            HistorialDesignacion::create([
                'idDesignacion'   => $designacion->idDesignacion,
                'idPartido'       => $designacion->idPartido,
                'idArbitro'       => $arbitro->idArbitro,
                'idColegio'       => $designacion->idColegio,
                'idUsuarioAccion' => $usuario->idUsuario,
                'tipoAccion'      => HistorialDesignacion::TIPO_RECHAZADO,
                'estadoAnterior'  => Designacion::ESTADO_PENDIENTE,
                'estadoNuevo'     => Designacion::ESTADO_RECHAZADA,
                'detalle'         => $motivo,
            ]);

            // Si el partido estaba confirmado → regresa a programado
            $partido = Partido::find($designacion->idPartido);
            if ($partido && $partido->estadoPartido === Partido::ESTADO_CONFIRMADO) {
                PartidoStateMachine::transicionarCon(
                    $partido,
                    Partido::ESTADO_PROGRAMADO,
                    $usuario,
                    "Árbitro rechazó designación: {$motivo}"
                );
            }

            NotificarRechazoJob::dispatch($designacion->load(['partido', 'arbitro.usuario', 'designador']));
            broadcast(new PartidoActualizadoEvent($partido ?? Partido::find($designacion->idPartido)))->toOthers();
        });
    }

    /**
     * Calcula la compensación del árbitro para su designación según la tarifa
     * del torneo (división + rol + formato) y la modalidad de pago del partido.
     *
     * @return array{valor: float|null, modalidad: string|null}
     */
    public function calcularPago(Designacion $designacion): array
    {
        $partido = $designacion->partido;
        $valor   = null;

        if ($partido !== null && $partido->idDivision && $partido->idFormato && $designacion->idRol) {
            $valor = TarifaTorneo::where('idDivision', $partido->idDivision)
                ->where('idRol', $designacion->idRol)
                ->where('idFormato', $partido->idFormato)
                ->value('valorPago');
        }

        return [
            'valor'     => $valor !== null ? (float) $valor : null,
            'modalidad' => $partido?->modalidadPago,
        ];
    }

    /**
     * Lista de árbitros candidatos para un partido, con sus indicadores de
     * disponibilidad y ordenados: disponibles sin advertencias → con
     * advertencias → sin reporte → suspendidos al final.
     *
     * @return Collection<int, array>
     */
    public function candidatosParaPartido(Partido $partido, int $idColegio): Collection
    {
        $fecha      = $partido->fechaPartido;
        $franjaNeed = $this->franjaDesdeHora($partido->horaPartido ?? '00:00');

        $arbitros = Arbitro::where('idColegio', $idColegio)
            ->whereNotIn('estadoArbitro', ['retirado'])
            ->with([
                'usuario',
                'categoria',
                'disponibilidades' => fn ($q) => $q->where('fechaDisponibilidad', $fecha),
                'indisponibilidadesExtraordinarias' => fn ($q) => $q->where('fechaAfectada', $fecha),
            ])
            ->get();

        $yaAsignados = $partido->designaciones()->pluck('idArbitro')->flip();

        // Choques de horario para todos los árbitros con una sola query
        $advertenciasPorArbitro = $this->advertenciasPorLista($arbitros, $partido);

        $resultado = $arbitros->map(function (Arbitro $a) use ($franjaNeed, $yaAsignados, $advertenciasPorArbitro): array {
            $disponibilidad = $a->disponibilidades->first();
            $extraordinaria = $a->indisponibilidadesExtraordinarias->first();

            $dispEstado  = 'sin_reporte';
            $franjaDisp  = null;
            $franjaLabel = null;

            if ($extraordinaria) {
                $dispEstado = 'extraordinaria';
            } elseif ($disponibilidad) {
                $dispEstado = $this->franjaCoincide($disponibilidad->franjaHoraria, $franjaNeed)
                    ? 'disponible'
                    : 'sin_reporte';
                $franjaDisp  = $disponibilidad->franjaHoraria;
                $franjaLabel = DisponibilidadArbitro::getFranjas()[$franjaDisp] ?? $franjaDisp;
            }

            $advertencias = $advertenciasPorArbitro[$a->idArbitro];

            return [
                'idArbitro'               => $a->idArbitro,
                'nombreUsuario'           => $a->usuario?->nombreUsuario,
                'codigoCarnet'            => $a->codigoCarnet,
                'nombreCategoria'         => $a->categoria?->nombreCategoria,
                'disponibilidad'          => $dispEstado,
                'franjaDisponible'        => $franjaDisp,
                'franjaLabel'             => $franjaLabel,
                'advertenciaTiempo'       => $advertencias['advertenciaTiempo'],
                'minutosAlPartidoCercano' => $advertencias['minutosAlPartidoCercano'],
                'yaAsignado'              => isset($yaAsignados[$a->idArbitro]),
                'esSuspendido'            => $a->estadoArbitro === 'suspendido',
                'estadoArbitro'           => $a->estadoArbitro,
            ];
        });

        // Ordenar: disponibles sin advertencias → disponibles con advertencias
        //          → sin reporte → suspendidos al final
        return $resultado->sortBy(fn ($r) => match (true) {
            $r['yaAsignado']          => 99,
            $r['esSuspendido']        => 4,
            $r['disponibilidad'] === 'extraordinaria' => 3,
            $r['disponibilidad'] === 'sin_reporte'     => 2,
            $r['advertenciaTiempo']   => 1,
            default                   => 0,
        })->values();
    }

    /**
     * Calcula las advertencias a mostrar al designador antes de confirmar
     * la asignación de un árbitro a un partido: disponibilidad, indisponibilidad
     * extraordinaria, suspensión y choques de horario con otros partidos del día.
     *
     * @return array{
     *     sinDisponibilidad: bool,
     *     tieneExtraordinaria: bool,
     *     advertenciaTiempo: bool,
     *     minutosAlPartidoCercano: int|null,
     *     esSuspendido: bool,
     * }
     */
    public function calcularAdvertencias(Arbitro $arbitro, Partido $partido): array
    {
        $disponibilidad = $arbitro->disponibilidades->first();
        $extraordinaria = $arbitro->indisponibilidadesExtraordinarias
            ->where('fechaAfectada', $partido->fechaPartido->toDateString())
            ->first();

        [$advertenciaTiempo, $minutosAlPartidoCercano] = $this->detectarPartidoCercano(
            $partido,
            $this->designacionesDelDia($partido, $arbitro->idArbitro)
        );

        return [
            'sinDisponibilidad'       => $disponibilidad === null,
            'tieneExtraordinaria'     => $extraordinaria !== null,
            'advertenciaTiempo'       => $advertenciaTiempo,
            'minutosAlPartidoCercano' => $minutosAlPartidoCercano,
            'esSuspendido'            => $arbitro->estadoArbitro === 'suspendido',
        ];
    }

    /**
     * Variante para listas de árbitros (ej. selector de disponibles): calcula
     * los choques de horario con una sola query en lugar de una por árbitro.
     *
     * @param  Collection<int, Arbitro>  $arbitros
     * @return array<int, array{advertenciaTiempo: bool, minutosAlPartidoCercano: int|null}>
     *         indexado por idArbitro
     */
    public function advertenciasPorLista(Collection $arbitros, Partido $partido): array
    {
        $designacionesDia = Designacion::whereIn('idArbitro', $arbitros->pluck('idArbitro'))
            ->whereHas('partido', fn ($q) => $q->where('fechaPartido', $partido->fechaPartido)
                ->where('idPartido', '!=', $partido->idPartido))
            ->with('partido:idPartido,horaPartido')
            ->get()
            ->groupBy('idArbitro');

        return $arbitros->mapWithKeys(function (Arbitro $arbitro) use ($partido, $designacionesDia): array {
            [$advertenciaTiempo, $minutosAlPartidoCercano] = $this->detectarPartidoCercano(
                $partido,
                $designacionesDia->get($arbitro->idArbitro, collect())
            );

            return [$arbitro->idArbitro => [
                'advertenciaTiempo'       => $advertenciaTiempo,
                'minutosAlPartidoCercano' => $minutosAlPartidoCercano,
            ]];
        })->all();
    }

    // ── Helpers privados ──────────────────

    private function franjaDesdeHora(string $hora): string
    {
        $h = (int) substr($hora, 0, 2);

        return match (true) {
            $h >= 6  && $h < 12 => 'am',
            $h >= 12 && $h < 18 => 'pm',
            default             => 'noche',
        };
    }

    private function franjaCoincide(string $franjaArbitro, string $franjaPartido): bool
    {
        $mapa = [
            'am'         => ['am'],
            'pm'         => ['pm'],
            'noche'      => ['noche'],
            'am_pm'      => ['am', 'pm'],
            'am_noche'   => ['am', 'noche'],
            'pm_noche'   => ['pm', 'noche'],
            'todo_el_dia'=> ['am', 'pm', 'noche'],
        ];

        return in_array($franjaPartido, $mapa[$franjaArbitro] ?? [], true);
    }

    private function designacionesDelDia(Partido $partido, int $idArbitro): Collection
    {
        return Designacion::where('idArbitro', $idArbitro)
            ->whereHas('partido', fn ($q) => $q->where('fechaPartido', $partido->fechaPartido)
                ->where('idPartido', '!=', $partido->idPartido))
            ->with('partido:idPartido,horaPartido')
            ->get();
    }

    /**
     * @param  Collection<int, Designacion>  $otrasDesignacionesDelDia
     * @return array{0: bool, 1: int|null}
     */
    private function detectarPartidoCercano(Partido $partido, Collection $otrasDesignacionesDelDia): array
    {
        $horaPartido = Carbon::createFromFormat('H:i', substr($partido->horaPartido ?? '00:00', 0, 5));

        foreach ($otrasDesignacionesDelDia as $otra) {
            $otraHora = Carbon::createFromFormat('H:i', substr($otra->partido->horaPartido ?? '00:00', 0, 5));
            $diff     = abs($horaPartido->diffInMinutes($otraHora));

            if ($diff < self::MINUTOS_MARGEN_PARTIDO_CERCANO) {
                return [true, $diff];
            }
        }

        return [false, null];
    }
}
