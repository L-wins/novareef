<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MovimientoFinanciero;
use App\Models\Partido;
use App\Models\TarifaTorneo;
use App\Services\DesignacionService;
use App\Services\FinanzasService;
use App\StateMachines\PartidoStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * finalizado → programado (solo ejecutivo) debe deshacer también la nómina
 * que generó la finalización — ver PartidoStateMachine::ejecutarEfectos() y
 * FinanzasService::anularMovimientosPorReversionPartido().
 */
class RevertirPartidoFinalizadoTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function prepararPartidoNominaFinalizado(): array
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);
        $ejecutivo  = $this->crearEjecutivo($colegio);

        $this->crearRolesPartido();
        $formato  = $this->crearFormatoDupla();
        $torneo   = $this->crearTorneo($colegio, $designador, ['modalidadPago' => 'nomina']);
        $division = $this->crearDivision($torneo);
        $sede     = $this->crearSede($torneo);

        TarifaTorneo::create([
            'idDivision' => $division->idDivision,
            'idRol'      => $this->idRolPorNombre('Central'),
            'idFormato'  => $formato->idFormato,
            'valorPago'  => 60000,
        ]);
        TarifaTorneo::create([
            'idDivision' => $division->idDivision,
            'idRol'      => $this->idRolPorNombre('Asistente'),
            'idFormato'  => $formato->idFormato,
            'valorPago'  => 40000,
        ]);

        $arbitroCentral   = $this->crearArbitro($colegio);
        $arbitroAsistente = $this->crearArbitro($colegio);

        $servicio = app(DesignacionService::class);

        $partido = $servicio->crearPartido($colegio->idColegio, [
            'idTorneo' => $torneo->idTorneo, 'idDivision' => $division->idDivision,
            'idSede' => $sede->idSede, 'idFormato' => $formato->idFormato,
            'equipoLocal' => 'Local FC', 'equipoVisitante' => 'Visitante FC',
            'fechaPartido' => today()->format('Y-m-d'), 'horaPartido' => '15:00',
            'observaciones' => null,
        ], $designador->idUsuario);

        $resCentral = $servicio->asignarArbitro($partido, $arbitroCentral->idArbitro, $this->idRolPorNombre('Central'), $colegio->idColegio, $designador->idUsuario);
        $resAsistente = $servicio->asignarArbitro($partido, $arbitroAsistente->idArbitro, $this->idRolPorNombre('Asistente'), $colegio->idColegio, $designador->idUsuario);

        $servicio->publicarPartido($partido->fresh('formato'), $designador);
        $servicio->confirmarDesignacion($resCentral['designacion']->fresh(), $arbitroCentral, $designador);
        $servicio->confirmarDesignacion($resAsistente['designacion']->fresh(), $arbitroAsistente, $designador);

        PartidoStateMachine::transicionarCon($partido->fresh(), Partido::ESTADO_FINALIZADO, $designador);

        return ['colegio' => $colegio, 'ejecutivo' => $ejecutivo, 'partido' => $partido->fresh()];
    }

    public function test_revertir_anula_la_nomina_generada(): void
    {
        $datos = $this->prepararPartidoNominaFinalizado();

        $this->assertSame(
            2,
            MovimientoFinanciero::where('idPartido', $datos['partido']->idPartido)
                ->where('estadoMovimiento', '!=', 'anulado')->count(),
        );

        PartidoStateMachine::transicionarCon($datos['partido']->fresh(), 'programado', $datos['ejecutivo']);

        $this->assertSame('programado', $datos['partido']->fresh()->estadoPartido);

        $movimientos = MovimientoFinanciero::where('idPartido', $datos['partido']->idPartido)->get();
        $this->assertCount(2, $movimientos);
        $movimientos->each(fn (MovimientoFinanciero $m) => $this->assertSame('anulado', $m->estadoMovimiento));
    }

    public function test_no_se_puede_revertir_si_ya_hay_un_pago_registrado(): void
    {
        $datos    = $this->prepararPartidoNominaFinalizado();
        $finanzas = app(FinanzasService::class);

        $movimiento = MovimientoFinanciero::where('idPartido', $datos['partido']->idPartido)->first();
        $finanzas->registrarAbono($movimiento, [
            'monto' => (float) $movimiento->montoTotal, 'fechaAbono' => today()->format('Y-m-d'), 'metodoPago' => 'efectivo',
        ], $datos['ejecutivo']);

        $this->expectException(\RuntimeException::class);

        PartidoStateMachine::transicionarCon($datos['partido']->fresh(), 'programado', $datos['ejecutivo']);
    }

    public function test_si_se_bloquea_la_reversion_el_partido_sigue_finalizado(): void
    {
        $datos    = $this->prepararPartidoNominaFinalizado();
        $finanzas = app(FinanzasService::class);

        $movimiento = MovimientoFinanciero::where('idPartido', $datos['partido']->idPartido)->first();
        $finanzas->registrarAbono($movimiento, [
            'monto' => (float) $movimiento->montoTotal, 'fechaAbono' => today()->format('Y-m-d'), 'metodoPago' => 'efectivo',
        ], $datos['ejecutivo']);

        try {
            PartidoStateMachine::transicionarCon($datos['partido']->fresh(), 'programado', $datos['ejecutivo']);
        } catch (\RuntimeException) {
            // esperado
        }

        // La transacción de transicionarCon() debe revertirse completa: el
        // estado del partido no puede quedar "programado" con un pago de
        // nómina ya cobrado colgando de un movimiento que no existe más.
        $this->assertSame('finalizado', $datos['partido']->fresh()->estadoPartido);
        $this->assertNotSame('anulado', $movimiento->fresh()->estadoMovimiento);
    }

    public function test_un_designador_no_puede_revertir_un_partido_finalizado(): void
    {
        $datos = $this->prepararPartidoNominaFinalizado();

        $this->expectException(\InvalidArgumentException::class);

        PartidoStateMachine::transicionarCon($datos['partido']->fresh(), 'programado', $this->crearDesignador($datos['colegio']));
    }
}
