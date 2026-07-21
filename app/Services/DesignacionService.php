<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\DesignacionActualizadaEvent;
use App\Events\PartidoActualizadoEvent;
use App\Jobs\NotificarCriticoJob;
use App\Jobs\NotificarDesignacionJob;
use App\Jobs\NotificarRechazoJob;
use App\Models\Arbitro;
use App\Models\Designacion;
use App\Models\HistorialDesignacion;
use App\Models\Partido;
use App\Models\SlotDesignacion;
use App\Models\TarifaTorneo;
use App\Models\Torneo;
use App\Models\User;
use App\StateMachines\PartidoStateMachine;
use Illuminate\Support\Facades\DB;

/**
 * Escritura del ciclo de vida de partido/designación: crear, publicar,
 * eliminar, asignar/reasignar/quitar árbitro, confirmar/rechazar, veedor.
 * El cálculo de candidatos y advertencias de disponibilidad vive en
 * CandidatosDesignacionService (inyectado aquí para las advertencias que se
 * calculan al asignar) — separado porque este archivo superaba las ~700
 * líneas documentadas y ese cálculo es un bloque cohesivo propio (ver
 * auditoría de plataforma, punto 3.1). Lectura/agregación para dashboards
 * vive en ReporteDesignacionesService (mismo criterio que Finanzas).
 */
final class DesignacionService
{
    public function __construct(
        private readonly SlotDesignacionService $slots,
        private readonly CandidatosDesignacionService $candidatos,
    ) {}

    /**
     * Crea un partido en borrador con sus slots según formato y registra
     * la creación en el historial.
     *
     * @param  array{idTorneo:int, idDivision:int, idSede:int, idFormato:int,
     *                equipoLocal:string, equipoVisitante:string, fechaPartido:string,
     *                horaPartido:string, observaciones:?string, idImportacion?:?int}  $datos
     */
    public function crearPartido(int $idColegio, array $datos, int $idUsuarioAccion): Partido
    {
        return DB::transaction(function () use ($datos, $idColegio, $idUsuarioAccion): Partido {
            // La modalidad de pago vive en el Torneo — se copia al partido tal
            // como ya hace Torneo\PartidoController::store() en el flujo de M03.
            // Sin esto todo partido creado desde /designaciones/crear quedaba
            // en 'campo' (default de la migración) sin importar la modalidad
            // real del torneo, y GenerarPagosJob nunca se disparaba.
            $modalidadPago = Torneo::where('idTorneo', $datos['idTorneo'])->value('modalidadPago');

            $partido = Partido::create([
                'idColegio'       => $idColegio,
                'idTorneo'        => $datos['idTorneo'],
                'idDivision'      => $datos['idDivision'],
                'idSede'          => $datos['idSede'],
                'idFormato'       => $datos['idFormato'],
                'idImportacion'   => $datos['idImportacion'] ?? null,
                'equipoLocal'     => $datos['equipoLocal'],
                'equipoVisitante' => $datos['equipoVisitante'],
                'fechaPartido'    => $datos['fechaPartido'],
                'horaPartido'     => $datos['horaPartido'],
                'estadoPartido'   => Partido::ESTADO_BORRADOR,
                'modalidadPago'   => $modalidadPago,
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
     * Elimina un partido en borrador junto con sus designaciones, slots e
     * historial. Solo permitido en borrador: una vez publicado, el partido
     * ya fue notificado a los árbitros y debe cancelarse/aplazarse en vez
     * de borrarse (deja rastro en el historial).
     *
     * @throws \RuntimeException  Si el partido no está en borrador.
     */
    public function eliminarPartido(Partido $partido, int $idColegio): void
    {
        if ($partido->estadoPartido !== Partido::ESTADO_BORRADOR) {
            throw new \RuntimeException('Solo se pueden eliminar partidos en borrador. Un partido publicado debe cancelarse o aplazarse.');
        }

        DB::transaction(function () use ($partido, $idColegio): void {
            // Orden obligatorio por las llaves foráneas (restrict en historial/designaciones,
            // cascade en slots_designacion -> se borran solos al borrar el partido).
            HistorialDesignacion::where('idPartido', $partido->idPartido)
                ->where('idColegio', $idColegio)
                ->delete();

            Designacion::where('idPartido', $partido->idPartido)
                ->where('idColegio', $idColegio)
                ->delete();

            $partido->delete();
        });
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

            $advertencias = $this->candidatos->calcularAdvertencias($arbitro, $partido);

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
                    $advertencias['noDisponible']        ? 'Se declaró no disponible ese día' : '',
                    $advertencias['otraFranja']          ? "Disponible en otra franja ({$advertencias['franjaReportada']})" : '',
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
     * Reemplaza el árbitro de un rol ya publicado (partido en programado,
     * confirmado o crítico) por otro árbitro. A diferencia de un rechazo del
     * propio árbitro (que escala el partido a crítico), esta acción la toma
     * el designador/ejecutivo deliberadamente: el estado del partido NO se
     * toca — sigue en el mismo estado hasta que el Central o el designador
     * lo finalicen, o hasta una acción manual del admin (cancelar/aplazar),
     * no por esta reasignación puntual. Solo se notifica al árbitro nuevo.
     *
     * @return array{designacion: Designacion, advertencias: array}
     * @throws \RuntimeException  Si el partido está en borrador (ahí se usa
     *                             asignarArbitro/quitarDesignacion normal), ya
     *                             terminó (finalizado/cancelado), o si el
     *                             árbitro nuevo ya está en el partido.
     */
    public function reasignarArbitro(Designacion $designacionVieja, int $idArbitroNuevo, int $idColegio, int $idUsuarioAccion): array
    {
        return DB::transaction(function () use ($designacionVieja, $idArbitroNuevo, $idColegio, $idUsuarioAccion): array {
            $partido = Partido::lockForUpdate()->where('idColegio', $idColegio)->findOrFail($designacionVieja->idPartido);

            if ($partido->estadoPartido === Partido::ESTADO_BORRADOR) {
                throw new \RuntimeException('El partido aún no se ha publicado — usa la asignación normal desde el borrador.');
            }

            if (in_array($partido->estadoPartido, [Partido::ESTADO_FINALIZADO, Partido::ESTADO_CANCELADO], true)) {
                throw new \RuntimeException('No se puede reasignar un árbitro en un partido finalizado o cancelado.');
            }

            $arbitroNuevo = Arbitro::where('idArbitro', $idArbitroNuevo)
                ->where('idColegio', $idColegio)
                ->with('usuario', 'disponibilidades', 'indisponibilidadesExtraordinarias')
                ->firstOrFail();

            $yaDesignado = Designacion::where('idPartido', $partido->idPartido)
                ->where('idArbitro', $arbitroNuevo->idArbitro)
                ->where('idDesignacion', '!=', $designacionVieja->idDesignacion)
                ->exists();

            if ($yaDesignado) {
                throw new \RuntimeException('Este árbitro ya está designado en este partido.');
            }

            $idRol = $designacionVieja->idRol;
            $slot  = SlotDesignacion::where('idDesignacion', $designacionVieja->idDesignacion)->lockForUpdate()->first();

            $advertencias = $this->candidatos->calcularAdvertencias($arbitroNuevo, $partido);

            HistorialDesignacion::create([
                'idDesignacion'   => $designacionVieja->idDesignacion,
                'idPartido'       => $partido->idPartido,
                'idArbitro'       => $designacionVieja->idArbitro,
                'idColegio'       => $idColegio,
                'idUsuarioAccion' => $idUsuarioAccion,
                'tipoAccion'      => HistorialDesignacion::TIPO_QUITADO,
                'detalle'         => 'Reemplazado por reasignación',
            ]);

            $designacionVieja->delete();

            $designacionNueva = Designacion::create([
                'idPartido'           => $partido->idPartido,
                'idArbitro'           => $arbitroNuevo->idArbitro,
                'idRol'               => $idRol,
                'idColegio'           => $idColegio,
                'estadoDesignacion'   => Designacion::ESTADO_PENDIENTE,
                'idUsuarioDesignador' => $idUsuarioAccion,
            ]);

            if ($slot) {
                $slot->update(['idDesignacion' => $designacionNueva->idDesignacion]);
            }

            HistorialDesignacion::create([
                'idDesignacion'   => $designacionNueva->idDesignacion,
                'idPartido'       => $partido->idPartido,
                'idArbitro'       => $arbitroNuevo->idArbitro,
                'idColegio'       => $idColegio,
                'idUsuarioAccion' => $idUsuarioAccion,
                'tipoAccion'      => HistorialDesignacion::TIPO_ASIGNADO,
                'detalle'         => 'Reasignación de árbitro',
            ]);

            $designacionNueva->load(['arbitro.usuario', 'rol', 'partido.torneo', 'partido.division', 'partido.sede']);

            NotificarDesignacionJob::dispatch($designacionNueva);
            broadcast(new DesignacionActualizadaEvent($designacionNueva))->toOthers();
            broadcast(new PartidoActualizadoEvent($partido))->toOthers();

            return [
                'designacion'  => $designacionNueva,
                'advertencias' => $advertencias,
            ];
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
        return DB::transaction(function () use ($designacion, $arbitro, $usuario): bool {
            // lockForUpdate + re-lectura: sin esto, dos confirmaciones/rechazos
            // casi simultáneos sobre la misma designación pueden validar sobre
            // la misma instancia obsoleta y generar historial duplicado — mismo
            // patrón ya corregido en SancionService::transicionar().
            $designacion = Designacion::whereKey($designacion->getKey())->lockForUpdate()->firstOrFail();

            if (! $designacion->estaPendiente()) {
                throw new \RuntimeException('Esta designación no está en estado pendiente.');
            }

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
        DB::transaction(function () use ($designacion, $motivo, $arbitro, $usuario): void {
            $designacion = Designacion::whereKey($designacion->getKey())->lockForUpdate()->firstOrFail();

            if (! $designacion->estaPendiente()) {
                throw new \RuntimeException('Solo se pueden rechazar designaciones pendientes.');
            }

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

            // Un rechazo deja un rol sin cubrir de forma urgente: escala a crítico
            // de inmediato (no espera al job de horas límite), salvo que el
            // partido ya esté en un estado terminal/manual (finalizado, cancelado,
            // aplazado, en curso) donde una designación pendiente no debería
            // seguir existiendo de todas formas.
            $partido = Partido::find($designacion->idPartido);
            if ($partido && PartidoStateMachine::puedeTransicionar($partido->estadoPartido, Partido::ESTADO_CRITICO)) {
                PartidoStateMachine::transicionarCon(
                    $partido,
                    Partido::ESTADO_CRITICO,
                    $usuario,
                    "Árbitro rechazó designación: {$motivo}"
                );

                NotificarCriticoJob::dispatch($partido, "Árbitro rechazó designación: {$motivo}");
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
}
