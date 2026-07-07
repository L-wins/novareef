<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\Designacion;
use App\Models\DivisionTorneo;
use App\Models\FormatoDesignacion;
use App\Models\Partido;
use App\Models\SedeTorneo;
use App\Models\Torneo;
use App\Models\User;
use App\Services\DesignacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Conteo informativo de partidos del día por árbitro: sin mezclar partidos
 * de días distintos y sin contar designaciones rechazadas. Es puramente
 * informativo — no hay umbral ni bloqueo.
 */
class SobrecargaDiariaArbitroTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_solo_cuenta_partidos_del_mismo_dia_no_ayer_ni_manana(): void
    {
        $ctx = $this->prepararEscenario();

        // Arbitro con partidos AYER, HOY (x2) y MAÑANA — solo hoy debe contar
        $this->crearPartidoConArbitro($ctx, $ctx['arbitro'], today()->subDay());
        $this->crearPartidoConArbitro($ctx, $ctx['arbitro'], today());
        $this->crearPartidoConArbitro($ctx, $ctx['arbitro'], today());
        $this->crearPartidoConArbitro($ctx, $ctx['arbitro'], today()->addDay());

        $partidoActual = $this->crearPartidoBorrador($ctx, today());

        $candidatos = $this->obtenerCandidatos($ctx, $partidoActual);
        $datos      = collect($candidatos)->firstWhere('idArbitro', $ctx['arbitro']->idArbitro);

        $this->assertSame(2, $datos['partidosHoy'], 'No debe contar los partidos de ayer/mañana, solo los de hoy.');
    }

    public function test_no_cuenta_designaciones_rechazadas(): void
    {
        $ctx = $this->prepararEscenario();

        $rechazada = $this->crearPartidoConArbitro($ctx, $ctx['arbitro'], today());
        $rechazada->update(['estadoDesignacion' => Designacion::ESTADO_RECHAZADA]);

        $partidoActual = $this->crearPartidoBorrador($ctx, today());

        $candidatos = $this->obtenerCandidatos($ctx, $partidoActual);
        $datos      = collect($candidatos)->firstWhere('idArbitro', $ctx['arbitro']->idArbitro);

        $this->assertSame(0, $datos['partidosHoy'], 'Una designación rechazada no debe contar como partido activo del día.');
    }

    public function test_cuenta_los_partidos_activos_del_arbitro_ese_dia(): void
    {
        $ctx = $this->prepararEscenario();

        $this->crearPartidoConArbitro($ctx, $ctx['arbitro'], today());
        $this->crearPartidoConArbitro($ctx, $ctx['arbitro'], today());

        $partidoActual = $this->crearPartidoBorrador($ctx, today());

        $candidatos = $this->obtenerCandidatos($ctx, $partidoActual);
        $datos      = collect($candidatos)->firstWhere('idArbitro', $ctx['arbitro']->idArbitro);

        $this->assertSame(2, $datos['partidosHoy']);
    }

    public function test_un_arbitro_sin_partidos_ese_dia_reporta_cero(): void
    {
        $ctx = $this->prepararEscenario();

        $partidoActual = $this->crearPartidoBorrador($ctx, today());

        $candidatos = $this->obtenerCandidatos($ctx, $partidoActual);
        $datos      = collect($candidatos)->firstWhere('idArbitro', $ctx['arbitro']->idArbitro);

        $this->assertSame(0, $datos['partidosHoy']);
    }

    /**
     * @return array{colegio: Colegio, designador: User, torneo: Torneo,
     *               division: DivisionTorneo, sede: SedeTorneo, formato: FormatoDesignacion, arbitro: \App\Models\Arbitro}
     */
    private function prepararEscenario(): array
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);

        $this->crearRolesPartido();

        return [
            'colegio'    => $colegio,
            'designador' => $designador,
            'torneo'     => $this->crearTorneo($colegio, $designador),
            'formato'    => $this->crearFormatoDupla(),
            'arbitro'    => $this->crearArbitro($colegio),
        ] + ['division' => null, 'sede' => null];
    }

    private function crearPartidoBorrador(array $ctx, \Illuminate\Support\Carbon $fecha): Partido
    {
        $division = $this->crearDivision($ctx['torneo']);
        $sede     = $this->crearSede($ctx['torneo']);

        return app(DesignacionService::class)->crearPartido($ctx['colegio']->idColegio, [
            'idTorneo'        => $ctx['torneo']->idTorneo,
            'idDivision'      => $division->idDivision,
            'idSede'          => $sede->idSede,
            'idFormato'       => $ctx['formato']->idFormato,
            'equipoLocal'     => 'A',
            'equipoVisitante' => 'B',
            'fechaPartido'    => $fecha->format('Y-m-d'),
            'horaPartido'     => '15:00',
            'observaciones'   => null,
        ], $ctx['designador']->idUsuario);
    }

    /**
     * Crea un partido adicional en la fecha dada y designa al árbitro como
     * Central (pendiente) — representa un compromiso ya existente ese día.
     */
    private function crearPartidoConArbitro(array $ctx, \App\Models\Arbitro $arbitro, \Illuminate\Support\Carbon $fecha): Designacion
    {
        $partido  = $this->crearPartidoBorrador($ctx, $fecha);
        $servicio = app(DesignacionService::class);

        $resultado = $servicio->asignarArbitro(
            $partido,
            $arbitro->idArbitro,
            $this->idRolPorNombre('Central'),
            $ctx['colegio']->idColegio,
            $ctx['designador']->idUsuario,
        );

        return $resultado['designacion'];
    }

    private function obtenerCandidatos(array $ctx, Partido $partido): array
    {
        $respuesta = $this->actingAs($ctx['designador'])
            ->getJson(route('api.partidos.arbitros-disponibles', $partido->idPartido));

        $respuesta->assertOk();

        return $respuesta->json();
    }
}
