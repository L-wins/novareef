<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DisponibilidadArbitro;
use App\Models\IndisponibilidadExtraordinaria;
use App\Models\Partido;
use App\Services\DesignacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Reglas de publicación (nunca sin árbitros) y estados de disponibilidad de
 * los candidatos a asignar: no disponibles se excluyen, y "reportó otra
 * franja" se distingue de "no reportó".
 */
class PublicarYDisponibilidadCandidatosTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    // ── Publicación ───────────────────────

    public function test_no_se_puede_publicar_un_borrador_sin_arbitros(): void
    {
        $ctx     = $this->prepararEscenario();
        $partido = $this->crearPartidoBorrador($ctx);

        $this->expectException(\RuntimeException::class);

        app(DesignacionService::class)->publicarPartido($partido->fresh('formato'), $ctx['designador']);
    }

    public function test_partido_creado_desde_torneos_nace_en_borrador(): void
    {
        $ctx      = $this->prepararEscenario();
        $division = $this->crearDivision($ctx['torneo']);
        $gestor   = $this->crearGestorTorneos($ctx['colegio']);

        $respuesta = $this->actingAs($gestor)->post(
            route('partidos.store', $ctx['torneo']->idTorneo),
            [
                'idDivision'      => $division->idDivision,
                'idFormato'       => $ctx['formato']->idFormato,
                'equipoLocal'     => 'Local FC',
                'equipoVisitante' => 'Visitante FC',
                'fechaPartido'    => today()->addDay()->format('Y-m-d'),
                'horaPartido'     => '15:00',
            ],
        );

        $respuesta->assertSessionHasNoErrors();

        $partido = Partido::where('idTorneo', $ctx['torneo']->idTorneo)->firstOrFail();
        $this->assertSame(
            Partido::ESTADO_BORRADOR,
            $partido->estadoPartido,
            'Un partido creado desde el módulo de torneos no debe nacer publicado.'
        );
    }

    public function test_cambiar_estado_desde_torneos_no_publica_un_borrador_sin_central(): void
    {
        $ctx     = $this->prepararEscenario();
        $partido = $this->crearPartidoBorrador($ctx);
        $gestor  = $this->crearGestorTorneos($ctx['colegio']);

        $respuesta = $this->actingAs($gestor)->put(
            route('partidos.estado', ['torneoId' => $ctx['torneo']->idTorneo, 'id' => $partido->idPartido]),
            ['estadoNuevo' => 'programado'],
        );

        $respuesta->assertSessionHas('error');
        $this->assertSame(Partido::ESTADO_BORRADOR, $partido->fresh()->estadoPartido);
    }

    // ── Candidatos: estados de disponibilidad ─────────────────

    public function test_franja_reportada_que_no_cubre_el_partido_se_marca_otra_franja(): void
    {
        $ctx     = $this->prepararEscenario();
        $partido = $this->crearPartidoBorrador($ctx); // 15:00 → franja pm

        DisponibilidadArbitro::create([
            'idArbitro'           => $ctx['arbitro']->idArbitro,
            'fechaDisponibilidad' => $partido->fechaPartido->toDateString(),
            'franjaHoraria'       => DisponibilidadArbitro::FRANJA_AM,
        ]);

        $datos = $this->datosCandidato($ctx, $partido);

        $this->assertSame('otra_franja', $datos['disponibilidad'], 'Reportó AM y el partido es PM: no es "sin reporte".');
        $this->assertSame('AM', $datos['franjaLabel']);
    }

    public function test_franja_que_cubre_el_partido_se_marca_disponible(): void
    {
        $ctx     = $this->prepararEscenario();
        $partido = $this->crearPartidoBorrador($ctx); // 15:00 → pm

        DisponibilidadArbitro::create([
            'idArbitro'           => $ctx['arbitro']->idArbitro,
            'fechaDisponibilidad' => $partido->fechaPartido->toDateString(),
            'franjaHoraria'       => DisponibilidadArbitro::FRANJA_TODO_DIA,
        ]);

        $datos = $this->datosCandidato($ctx, $partido);

        $this->assertSame('disponible', $datos['disponibilidad']);
    }

    public function test_no_disponible_explicito_queda_fuera_de_los_candidatos(): void
    {
        $ctx     = $this->prepararEscenario();
        $partido = $this->crearPartidoBorrador($ctx);

        DisponibilidadArbitro::create([
            'idArbitro'           => $ctx['arbitro']->idArbitro,
            'fechaDisponibilidad' => $partido->fechaPartido->toDateString(),
            'franjaHoraria'       => DisponibilidadArbitro::FRANJA_NO_DISPONIBLE,
        ]);

        $this->assertNull($this->datosCandidato($ctx, $partido), 'Un árbitro que se declaró no disponible no debe listarse.');
    }

    public function test_indisponibilidad_extraordinaria_queda_fuera_de_los_candidatos(): void
    {
        $ctx     = $this->prepararEscenario();
        $partido = $this->crearPartidoBorrador($ctx);

        IndisponibilidadExtraordinaria::create([
            'idArbitro'         => $ctx['arbitro']->idArbitro,
            'idColegio'         => $ctx['colegio']->idColegio,
            'fechaAfectada'     => $partido->fechaPartido->toDateString(),
            'franjaAfectada'    => DisponibilidadArbitro::FRANJA_TODO_DIA,
            'motivo'            => 'Calamidad doméstica',
            'idUsuarioRegistro' => $ctx['designador']->idUsuario,
        ]);

        $this->assertNull($this->datosCandidato($ctx, $partido));
    }

    public function test_sin_registro_para_esa_fecha_sigue_siendo_sin_reporte(): void
    {
        $ctx     = $this->prepararEscenario();
        $partido = $this->crearPartidoBorrador($ctx);

        // Reportó OTRO día — para la fecha del partido no hay registro
        DisponibilidadArbitro::create([
            'idArbitro'           => $ctx['arbitro']->idArbitro,
            'fechaDisponibilidad' => $partido->fechaPartido->copy()->addDays(2)->toDateString(),
            'franjaHoraria'       => DisponibilidadArbitro::FRANJA_TODO_DIA,
        ]);

        $datos = $this->datosCandidato($ctx, $partido);

        $this->assertSame('sin_reporte', $datos['disponibilidad']);
    }

    // ── calcularAdvertencias (ruta de asignación real) ────────

    public function test_advertencias_al_asignar_detectan_otra_franja_y_fecha_correcta(): void
    {
        $ctx     = $this->prepararEscenario();
        $partido = $this->crearPartidoBorrador($ctx); // 15:00 → pm

        // Disponibilidad de OTRO día (no debe contar) + la del día en franja am
        DisponibilidadArbitro::create([
            'idArbitro'           => $ctx['arbitro']->idArbitro,
            'fechaDisponibilidad' => $partido->fechaPartido->copy()->subDays(3)->toDateString(),
            'franjaHoraria'       => DisponibilidadArbitro::FRANJA_TODO_DIA,
        ]);
        DisponibilidadArbitro::create([
            'idArbitro'           => $ctx['arbitro']->idArbitro,
            'fechaDisponibilidad' => $partido->fechaPartido->toDateString(),
            'franjaHoraria'       => DisponibilidadArbitro::FRANJA_AM,
        ]);

        $resultado = app(DesignacionService::class)->asignarArbitro(
            $partido,
            $ctx['arbitro']->idArbitro,
            $this->idRolPorNombre('Central'),
            $ctx['colegio']->idColegio,
            $ctx['designador']->idUsuario,
        );

        $this->assertFalse($resultado['advertencias']['sinDisponibilidad']);
        $this->assertTrue($resultado['advertencias']['otraFranja']);
        $this->assertSame('AM', $resultado['advertencias']['franjaReportada']);
    }

    public function test_advertencias_al_asignar_detectan_extraordinaria_de_ese_dia(): void
    {
        $ctx     = $this->prepararEscenario();
        $partido = $this->crearPartidoBorrador($ctx);

        IndisponibilidadExtraordinaria::create([
            'idArbitro'         => $ctx['arbitro']->idArbitro,
            'idColegio'         => $ctx['colegio']->idColegio,
            'fechaAfectada'     => $partido->fechaPartido->toDateString(),
            'franjaAfectada'    => DisponibilidadArbitro::FRANJA_TODO_DIA,
            'motivo'            => 'Viaje familiar',
            'idUsuarioRegistro' => $ctx['designador']->idUsuario,
        ]);

        $resultado = app(DesignacionService::class)->asignarArbitro(
            $partido,
            $ctx['arbitro']->idArbitro,
            $this->idRolPorNombre('Central'),
            $ctx['colegio']->idColegio,
            $ctx['designador']->idUsuario,
        );

        $this->assertTrue(
            $resultado['advertencias']['tieneExtraordinaria'],
            'La extraordinaria del mismo día debe detectarse (antes se comparaba Carbon contra string y nunca coincidía).'
        );
    }

    // ── Helpers ───────────────────────────

    private function prepararEscenario(): array
    {
        $colegio    = $this->crearColegio($this->crearPlan(['modulosJSON' => ['arbitros', 'torneos', 'designaciones']]));
        $designador = $this->crearDesignador($colegio);

        $this->crearRolesPartido();

        return [
            'colegio'    => $colegio,
            'designador' => $designador,
            'torneo'     => $this->crearTorneo($colegio, $designador),
            'formato'    => $this->crearFormatoDupla(),
            'arbitro'    => $this->crearArbitro($colegio),
        ];
    }

    private function crearGestorTorneos(\App\Models\Colegio $colegio): \App\Models\User
    {
        $this->crearRolSpatie('ejecutivo', ['ver-torneos', 'crear-torneos', 'editar-torneos']);

        $usuario = \App\Models\User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);
        $usuario->assignRole('ejecutivo');

        return $usuario;
    }

    private function crearPartidoBorrador(array $ctx): Partido
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
            'fechaPartido'    => today()->addDay()->format('Y-m-d'),
            'horaPartido'     => '15:00',
            'observaciones'   => null,
        ], $ctx['designador']->idUsuario);
    }

    /** Datos del árbitro del escenario dentro de la lista de candidatos, o null si fue excluido. */
    private function datosCandidato(array $ctx, Partido $partido): ?array
    {
        $respuesta = $this->actingAs($ctx['designador'])
            ->getJson(route('api.partidos.arbitros-disponibles', $partido->idPartido));

        $respuesta->assertOk();

        return collect($respuesta->json())->firstWhere('idArbitro', $ctx['arbitro']->idArbitro);
    }
}
