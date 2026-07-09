<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Arbitro;
use App\Models\Colegio;
use App\Models\Designacion;
use App\Models\MovimientoFinanciero;
use App\Models\Partido;
use App\Models\RolPartido;
use App\Models\TarifaTorneo;
use App\Models\Torneo;
use App\Models\User;
use App\Services\DesignacionService;
use App\StateMachines\PartidoStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class GenerarPagosNominaTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    /**
     * Levanta un partido publicado, confirmado por ambos árbitros (Central +
     * Asistente), con modalidadPago configurable — réplica de
     * prepararPartidoPublicado() pero permitiendo pasar la modalidad, que ese
     * helper no expone.
     *
     * @return array{colegio: Colegio, designador: User, partido: Partido,
     *               designacionCentral: Designacion, designacionAsistente: Designacion,
     *               arbitroCentral: Arbitro, arbitroAsistente: Arbitro, torneo: Torneo}
     */
    private function prepararPartidoConfirmado(string $modalidadPago): array
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);

        $this->crearRolesPartido();
        $formato  = $this->crearFormatoDupla();
        $torneo   = $this->crearTorneo($colegio, $designador, ['modalidadPago' => $modalidadPago]);
        $division = $this->crearDivision($torneo);
        $sede     = $this->crearSede($torneo);

        $arbitroCentral   = $this->crearArbitro($colegio);
        $arbitroAsistente = $this->crearArbitro($colegio);

        $servicio = app(DesignacionService::class);

        $partido = $servicio->crearPartido($colegio->idColegio, [
            'idTorneo'        => $torneo->idTorneo,
            'idDivision'      => $division->idDivision,
            'idSede'          => $sede->idSede,
            'idFormato'       => $formato->idFormato,
            'equipoLocal'     => 'Local FC',
            'equipoVisitante' => 'Visitante FC',
            'fechaPartido'    => today()->format('Y-m-d'),
            'horaPartido'     => '15:00',
            'observaciones'   => null,
        ], $designador->idUsuario);

        $resCentral = $servicio->asignarArbitro(
            $partido, $arbitroCentral->idArbitro, $this->idRolPorNombre('Central'), $colegio->idColegio, $designador->idUsuario,
        );
        $resAsistente = $servicio->asignarArbitro(
            $partido, $arbitroAsistente->idArbitro, $this->idRolPorNombre('Asistente'), $colegio->idColegio, $designador->idUsuario,
        );

        $servicio->publicarPartido($partido->fresh('formato'), $designador);

        $servicio->confirmarDesignacion($resCentral['designacion']->fresh(), $arbitroCentral, $designador);
        $servicio->confirmarDesignacion($resAsistente['designacion']->fresh(), $arbitroAsistente, $designador);

        return [
            'colegio'              => $colegio,
            'designador'           => $designador,
            'partido'              => $partido->fresh(),
            'designacionCentral'   => $resCentral['designacion']->fresh(),
            'designacionAsistente' => $resAsistente['designacion']->fresh(),
            'arbitroCentral'       => $arbitroCentral,
            'arbitroAsistente'     => $arbitroAsistente,
            'torneo'               => $torneo,
            'division'             => $division,
            'formato'              => $formato,
        ];
    }

    public function test_finalizar_un_partido_nomina_genera_ingreso_y_egresos(): void
    {
        $datos = $this->prepararPartidoConfirmado('nomina');

        TarifaTorneo::create([
            'idDivision' => $datos['division']->idDivision,
            'idRol'      => $this->idRolPorNombre('Central'),
            'idFormato'  => $datos['formato']->idFormato,
            'valorPago'  => 60000,
        ]);
        TarifaTorneo::create([
            'idDivision' => $datos['division']->idDivision,
            'idRol'      => $this->idRolPorNombre('Asistente'),
            'idFormato'  => $datos['formato']->idFormato,
            'valorPago'  => 40000,
        ]);

        $this->assertSame('confirmado', $datos['partido']->fresh()->estadoPartido);

        PartidoStateMachine::transicionarCon($datos['partido']->fresh(), Partido::ESTADO_FINALIZADO, $datos['designador']);

        $movimientos = MovimientoFinanciero::where('idPartido', $datos['partido']->idPartido)->get();

        $ingreso = $movimientos->firstWhere('categoria', MovimientoFinanciero::CATEGORIA_INGRESO_TORNEO);
        $this->assertNotNull($ingreso);
        $this->assertSame('ingreso', $ingreso->tipoMovimiento);
        $this->assertSame(100000.0, (float) $ingreso->montoTotal);
        $this->assertNull($ingreso->idUsuarioRegistro);

        $egresos = $movimientos->where('categoria', MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO);
        $this->assertCount(2, $egresos);
        $this->assertEqualsCanonicalizing(
            [60000.0, 40000.0],
            $egresos->map(fn ($m) => (float) $m->montoTotal)->values()->all(),
        );
    }

    public function test_finalizar_un_partido_campo_no_genera_ningun_movimiento(): void
    {
        $datos = $this->prepararPartidoConfirmado('campo');

        PartidoStateMachine::transicionarCon($datos['partido']->fresh(), Partido::ESTADO_FINALIZADO, $datos['designador']);

        $this->assertSame(0, MovimientoFinanciero::where('idPartido', $datos['partido']->idPartido)->count());
    }

    public function test_sin_tarifa_configurada_omite_esa_designacion_sin_bloquear_el_resto(): void
    {
        $datos = $this->prepararPartidoConfirmado('nomina');

        // Solo se configura la tarifa del Central — el Asistente no tiene tarifa.
        TarifaTorneo::create([
            'idDivision' => $datos['division']->idDivision,
            'idRol'      => $this->idRolPorNombre('Central'),
            'idFormato'  => $datos['formato']->idFormato,
            'valorPago'  => 60000,
        ]);

        PartidoStateMachine::transicionarCon($datos['partido']->fresh(), Partido::ESTADO_FINALIZADO, $datos['designador']);

        $movimientos = MovimientoFinanciero::where('idPartido', $datos['partido']->idPartido)->get();

        $egresos = $movimientos->where('categoria', MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO);
        $this->assertCount(1, $egresos);

        $ingreso = $movimientos->firstWhere('categoria', MovimientoFinanciero::CATEGORIA_INGRESO_TORNEO);
        $this->assertSame(60000.0, (float) $ingreso->montoTotal);
    }

    public function test_es_idempotente_si_se_invoca_dos_veces_para_el_mismo_partido(): void
    {
        $datos = $this->prepararPartidoConfirmado('nomina');

        TarifaTorneo::create([
            'idDivision' => $datos['division']->idDivision,
            'idRol'      => $this->idRolPorNombre('Central'),
            'idFormato'  => $datos['formato']->idFormato,
            'valorPago'  => 60000,
        ]);
        TarifaTorneo::create([
            'idDivision' => $datos['division']->idDivision,
            'idRol'      => $this->idRolPorNombre('Asistente'),
            'idFormato'  => $datos['formato']->idFormato,
            'valorPago'  => 40000,
        ]);

        $finanzas = app(\App\Services\FinanzasService::class);

        $finanzas->generarMovimientosPorFinalizacionPartido($datos['partido']->fresh());
        $finanzas->generarMovimientosPorFinalizacionPartido($datos['partido']->fresh());

        $this->assertSame(3, MovimientoFinanciero::where('idPartido', $datos['partido']->idPartido)->count());
    }
}
