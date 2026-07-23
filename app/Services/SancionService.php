<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HistorialSancion;
use App\Models\MovimientoFinanciero;
use App\Models\Sancion;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SancionService
{
    public function __construct(
        private readonly FinanzasService $finanzas,
    ) {}

    /**
     * Registra una sanción disciplinaria. Si tieneMultaEconomica, genera en la
     * misma transacción el movimiento financiero (categoria=multa,
     * tipoOrigenMulta='sancion') y lo enlaza de vuelta en la sanción.
     *
     * No bloquea designaciones — Sanciones es un registro disciplinario e
     * histórico puro; si el colegio quiere sacar de circulación a un árbitro,
     * usa el toggle manual ya existente en M02 (Arbitro.estadoArbitro).
     *
     * @param  array{
     *     idArbitro: int, idTipoSancion: int, idPartido?: ?int,
     *     motivoSancion: string, fechaHecho: string, fechaInicioSancion: string,
     *     fechaFinSancion?: ?string, tieneMultaEconomica: bool, montoMulta?: float|string,
     * }  $datos
     */
    public function crearSancion(int $idColegio, array $datos, User $usuarioImpuso): Sancion
    {
        return DB::transaction(function () use ($idColegio, $datos, $usuarioImpuso): Sancion {
            $sancion = Sancion::create([
                'idColegio'           => $idColegio,
                'idArbitro'           => $datos['idArbitro'],
                'idTipoSancion'       => $datos['idTipoSancion'],
                'idPartido'           => $datos['idPartido'] ?? null,
                'motivoSancion'       => $datos['motivoSancion'],
                'fechaHecho'          => $datos['fechaHecho'],
                'fechaInicioSancion'  => $datos['fechaInicioSancion'],
                'fechaFinSancion'     => $datos['fechaFinSancion'] ?? null,
                'estadoSancion'       => Sancion::ESTADO_ACTIVA,
                'tieneMultaEconomica' => $datos['tieneMultaEconomica'] ?? false,
                'idUsuarioImpuso'     => $usuarioImpuso->idUsuario,
                'version'             => 0,
            ]);

            if ($sancion->tieneMultaEconomica) {
                $tipo = $sancion->tipo()->first();

                $movimiento = $this->finanzas->registrarMovimiento($idColegio, [
                    'tipoMovimiento'  => MovimientoFinanciero::TIPO_INGRESO,
                    'categoria'       => MovimientoFinanciero::CATEGORIA_MULTA,
                    'concepto'        => 'Multa por sanción: ' . ($tipo->etiqueta ?? $tipo->nombre ?? 'sin tipo'),
                    'montoTotal'      => $datos['montoMulta'],
                    'fechaMovimiento' => $datos['fechaHecho'],
                    'idArbitro'       => $datos['idArbitro'],
                    'tipoOrigenMulta' => MovimientoFinanciero::ORIGEN_MULTA_SANCION,
                    'idOrigenMulta'   => $sancion->idSancion,
                ], $usuarioImpuso);

                $sancion->update(['idMovimientoFinanciero' => $movimiento->idMovimiento]);
            }

            HistorialSancion::create([
                'idSancion'       => $sancion->idSancion,
                'idColegio'       => $idColegio,
                'idArbitro'       => $sancion->idArbitro,
                'idUsuarioAccion' => $usuarioImpuso->idUsuario,
                'tipoAccion'      => HistorialSancion::TIPO_IMPUESTA,
                'estadoNuevo'     => Sancion::ESTADO_ACTIVA,
                'detalle'         => $datos['motivoSancion'],
            ]);

            return $sancion->fresh();
        });
    }

    /**
     * Marca una sanción activa o apelada como cumplida (se sostiene y se da
     * por concluida).
     *
     * @throws \RuntimeException  Si la sanción ya está cumplida o anulada.
     */
    public function cumplir(Sancion $sancion, ?User $usuario, ?string $motivo = null): void
    {
        DB::transaction(function () use ($sancion, $usuario, $motivo): void {
            $sancion = $this->bloquear($sancion);

            if (! in_array($sancion->estadoSancion, [Sancion::ESTADO_ACTIVA, Sancion::ESTADO_APELADA], true)) {
                throw new \RuntimeException('Solo se pueden cumplir sanciones activas o apeladas.');
            }

            $this->transicionar($sancion, Sancion::ESTADO_CUMPLIDA, $usuario, HistorialSancion::TIPO_CUMPLIDA, $motivo);
        });
    }

    /**
     * Anula una sanción (error de registro, o apelación resuelta a favor del
     * árbitro). No revierte el movimiento financiero de la multa asociada si
     * ya tiene abonos — mismo criterio que anularMovimiento en Finanzas.
     *
     * @throws \RuntimeException  Si la sanción ya está anulada.
     */
    public function anular(Sancion $sancion, User $usuario, string $motivo): void
    {
        DB::transaction(function () use ($sancion, $usuario, $motivo): void {
            $sancion = $this->bloquear($sancion);

            if ($sancion->estadoSancion === Sancion::ESTADO_ANULADA) {
                throw new \RuntimeException('La sanción ya está anulada.');
            }

            $this->transicionar($sancion, Sancion::ESTADO_ANULADA, $usuario, HistorialSancion::TIPO_ANULADA, $motivo);

            if ($sancion->idMovimientoFinanciero !== null) {
                $movimiento = $sancion->movimientoFinanciero()->first();

                if ($movimiento !== null && ! $movimiento->estaAnulado() && $movimiento->abonos()->where('anulado', false)->doesntExist()) {
                    $this->finanzas->anularMovimiento($movimiento, $usuario, "Anulado junto con la sanción #{$sancion->idSancion}: {$motivo}");
                }
            }
        });
    }

    /**
     * El árbitro (o el colegio en su representación) apela una sanción activa.
     *
     * @throws \RuntimeException  Si la sanción no está activa.
     */
    public function apelar(Sancion $sancion, User $usuario, ?string $motivo = null): void
    {
        DB::transaction(function () use ($sancion, $usuario, $motivo): void {
            $sancion = $this->bloquear($sancion);

            if (! $sancion->estaActiva()) {
                throw new \RuntimeException('Solo se pueden apelar sanciones activas.');
            }

            $this->transicionar($sancion, Sancion::ESTADO_APELADA, $usuario, HistorialSancion::TIPO_APELADA, $motivo);
        });
    }

    /**
     * Resuelve una apelación: 'confirmada' sostiene la sanción (pasa a
     * cumplida), 'revocada' la deja sin efecto (pasa a anulada, revirtiendo
     * también la multa si aún no tiene abonos).
     *
     * @throws \RuntimeException  Si la sanción no está apelada o el resultado no es válido.
     */
    public function resolverApelacion(Sancion $sancion, string $resultado, User $usuario, ?string $motivo = null): void
    {
        if (! in_array($resultado, ['confirmada', 'revocada'], true)) {
            throw new \RuntimeException('El resultado de la apelación debe ser "confirmada" o "revocada".');
        }

        DB::transaction(function () use ($sancion, $resultado, $usuario, $motivo): void {
            $sancion = $this->bloquear($sancion);

            if (! $sancion->estaApelada()) {
                throw new \RuntimeException('Solo se pueden resolver apelaciones de sanciones en estado apelada.');
            }

            if ($resultado === 'confirmada') {
                $this->transicionar($sancion, Sancion::ESTADO_CUMPLIDA, $usuario, HistorialSancion::TIPO_APELACION_RESUELTA, $motivo ?? 'Apelación confirmada — sanción sostenida');
                return;
            }

            $this->transicionar($sancion, Sancion::ESTADO_ANULADA, $usuario, HistorialSancion::TIPO_APELACION_RESUELTA, $motivo ?? 'Apelación revocada — sanción anulada');

            if ($sancion->idMovimientoFinanciero !== null) {
                $movimiento = $sancion->movimientoFinanciero()->first();

                if ($movimiento !== null && ! $movimiento->estaAnulado() && $movimiento->abonos()->where('anulado', false)->doesntExist()) {
                    $this->finanzas->anularMovimiento($movimiento, $usuario, "Anulado por apelación revocada de la sanción #{$sancion->idSancion}");
                }
            }
        });
    }

    // ── Lectura para dashboards (por rol) ──

    /**
     * Resumen para el dashboard de sanciones/ejecutivo: cuántas sanciones
     * activas y apelaciones pendientes de resolver hay, más una vista previa
     * de las sanciones activas más recientes.
     *
     * @return array{activasCount: int, apelacionesPendientes: int, recientes: Collection<int, Sancion>}
     */
    public function resumenParaDashboard(int $idColegio, int $limiteRecientes = 5): array
    {
        $activasCount = Sancion::where('idColegio', $idColegio)
            ->where('estadoSancion', Sancion::ESTADO_ACTIVA)
            ->count();

        $apelacionesPendientes = Sancion::where('idColegio', $idColegio)
            ->where('estadoSancion', Sancion::ESTADO_APELADA)
            ->count();

        $recientes = Sancion::where('idColegio', $idColegio)
            ->where('estadoSancion', Sancion::ESTADO_ACTIVA)
            ->with(['arbitro.usuario', 'tipo'])
            ->orderByDesc('fechaHecho')
            ->limit($limiteRecientes)
            ->get();

        return compact('activasCount', 'apelacionesPendientes', 'recientes');
    }

    // ── Helpers privados ──────────────────

    /**
     * Relee la sanción con lockForUpdate() dentro de la transacción activa —
     * sin esto, dos acciones casi simultáneas sobre la misma sanción (ej.
     * apelar() vs cumplir(), o VencerSancionesJob corriendo contra una
     * acción manual) pueden ambas validar el estado sobre la misma instancia
     * obsoleta y la que escribe último pisa silenciosamente a la otra,
     * dejando historial contradictorio. Mismo patrón que
     * FinanzasService::registrarAbono()/anularMovimiento(). Este es el
     * mecanismo de concurrencia real de Sancion — la columna `version` de
     * abajo NO es optimistic locking (a diferencia de
     * PartidoStateMachine::transicionarCon(), que sí compara
     * WHERE version = $esperada antes de escribir): solo se incrementa,
     * nunca se lee para decidir nada. lockForUpdate() ya es suficiente
     * protección; no se agregó la comparación real porque duplicaría lo que
     * el lock pesimista ya garantiza.
     */
    private function bloquear(Sancion $sancion): Sancion
    {
        return Sancion::whereKey($sancion->getKey())->lockForUpdate()->firstOrFail();
    }

    private function transicionar(Sancion $sancion, string $estadoNuevo, ?User $usuario, string $tipoAccion, ?string $detalle): void
    {
        $estadoAnterior = $sancion->estadoSancion;

        $sancion->update([
            'estadoSancion' => $estadoNuevo,
            'version'       => $sancion->version + 1,
        ]);

        HistorialSancion::create([
            'idSancion'       => $sancion->idSancion,
            'idColegio'       => $sancion->idColegio,
            'idArbitro'       => $sancion->idArbitro,
            'idUsuarioAccion' => $usuario?->idUsuario,
            'tipoAccion'      => $tipoAccion,
            'estadoAnterior'  => $estadoAnterior,
            'estadoNuevo'     => $estadoNuevo,
            'detalle'         => $detalle,
        ]);
    }
}
