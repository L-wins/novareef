<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CalificacionArbitro;
use App\Models\Colegio;
use App\Models\Designacion;
use App\Models\DisponibilidadArbitro;
use App\Models\Partido;
use App\Services\DesignacionService;
use App\Services\EstadisticasService;
use App\StateMachines\PartidoStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * EstadisticasService — cobertura de las 7 agregaciones del módulo de
 * Estadísticas de Designaciones. Cada test llama al Service directamente
 * (no HTTP) para poder controlar fechas/estados con precisión — ver
 * EstadisticasControllerTest para la cobertura de rutas/permisos/AJAX.
 */
class EstadisticasServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function servicio(): EstadisticasService
    {
        return app(EstadisticasService::class);
    }

    /** Deja un partido finalizado con ambas designaciones confirmadas — mismo patrón que CalificacionControllerTest. */
    private function finalizarPartido(array $datos): array
    {
        Queue::fake();

        $servicio = app(DesignacionService::class);
        $servicio->confirmarDesignacion($datos['designacionCentral'], $datos['arbitroCentral'], $datos['designador']);
        $servicio->confirmarDesignacion($datos['designacionAsistente'], $datos['arbitroAsistente'], $datos['designador']);

        $partido = $datos['partido']->fresh(['formato']);
        PartidoStateMachine::transicionarCon($partido, Partido::ESTADO_FINALIZADO, $datos['designador']);

        $datos['partido']              = $partido->fresh();
        $datos['designacionCentral']   = $datos['designacionCentral']->fresh();
        $datos['designacionAsistente'] = $datos['designacionAsistente']->fresh();

        return $datos;
    }

    // ── Partidos finalizados por árbitro ──

    public function test_partidos_finalizados_por_arbitro_solo_cuenta_finalizado_y_desglosa_rol(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);

        $datos = $this->prepararPartidoPublicado($colegio, $designador);
        $datos = $this->finalizarPartido($datos);

        // Un segundo partido para el mismo árbitro Central, publicado pero
        // sin finalizar — no debe contar en el reporte.
        Queue::fake();
        $servicioDesignaciones = app(DesignacionService::class);
        $formato  = $this->crearFormatoDupla();
        $torneo   = $this->crearTorneo($colegio, $designador);
        $division = $this->crearDivision($torneo);
        $sede     = $this->crearSede($torneo);
        $partido2 = $servicioDesignaciones->crearPartido($colegio->idColegio, [
            'idTorneo' => $torneo->idTorneo, 'idDivision' => $division->idDivision, 'idSede' => $sede->idSede,
            'idFormato' => $formato->idFormato, 'equipoLocal' => 'Local 2', 'equipoVisitante' => 'Visitante 2',
            'fechaPartido' => today()->addDay()->format('Y-m-d'), 'horaPartido' => '10:00', 'observaciones' => null,
        ], $designador->idUsuario);
        $servicioDesignaciones->asignarArbitro($partido2, $datos['arbitroCentral']->idArbitro, $this->idRolPorNombre('Central'), $colegio->idColegio, $designador->idUsuario);

        $ranking = $this->servicio()->partidosFinalizadosPorArbitro($colegio->idColegio);

        $filaCentral = $ranking->firstWhere(fn ($f) => $f['arbitro']->idArbitro === $datos['arbitroCentral']->idArbitro);

        $this->assertNotNull($filaCentral);
        $this->assertSame(1, $filaCentral['total'], 'Solo el partido finalizado debe contar, no el publicado.');
        $this->assertSame(['Central' => 1], $filaCentral['porRol']);
    }

    public function test_partidos_finalizados_por_arbitro_filtra_por_torneo(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);

        $datos = $this->prepararPartidoPublicado($colegio, $designador);
        $datos = $this->finalizarPartido($datos);

        $otroTorneo = $this->crearTorneo($colegio, $designador);

        $ranking = $this->servicio()->partidosFinalizadosPorArbitro($colegio->idColegio, [$otroTorneo->idTorneo]);

        $this->assertTrue($ranking->isEmpty(), 'Filtrar por un torneo sin partidos finalizados debe devolver vacío.');
    }

    // ── Coincidencias entre árbitros ──────

    public function test_coincidencias_cuenta_partidos_donde_coinciden_todos_los_seleccionados(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);

        $datos = $this->prepararPartidoPublicado($colegio, $designador);

        $resultado = $this->servicio()->coincidencias($colegio->idColegio, [
            $datos['arbitroCentral']->idArbitro,
            $datos['arbitroAsistente']->idArbitro,
        ]);

        $this->assertCount(1, $resultado['partidos']);
        $this->assertSame($datos['partido']->idPartido, $resultado['partidos']->first()->idPartido);
        $this->assertSame(
            'Central',
            $resultado['roles'][$datos['partido']->idPartido][$datos['arbitroCentral']->idArbitro]
        );
    }

    public function test_coincidencias_excluye_partidos_donde_falta_uno_de_los_seleccionados(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);

        $datos       = $this->prepararPartidoPublicado($colegio, $designador);
        $arbitroSolo = $this->crearArbitro($colegio);

        $resultado = $this->servicio()->coincidencias($colegio->idColegio, [
            $datos['arbitroCentral']->idArbitro,
            $arbitroSolo->idArbitro,
        ]);

        $this->assertTrue($resultado['partidos']->isEmpty(), 'No hay ningún partido donde coincidan ambos.');
    }

    public function test_coincidencias_calcula_desglose_por_pares(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);

        $this->crearRolesPartido();
        $formato  = $this->crearFormatoDupla();
        $torneo   = $this->crearTorneo($colegio, $designador);
        $division = $this->crearDivision($torneo);
        $sede     = $this->crearSede($torneo);

        $arbitroA = $this->crearArbitro($colegio);
        $arbitroB = $this->crearArbitro($colegio);
        $arbitroC = $this->crearArbitro($colegio);

        $servicio = app(DesignacionService::class);

        // Partido 1: A + B. Partido 2: B + C. A y C nunca coinciden.
        $partido1 = $servicio->crearPartido($colegio->idColegio, [
            'idTorneo' => $torneo->idTorneo, 'idDivision' => $division->idDivision, 'idSede' => $sede->idSede,
            'idFormato' => $formato->idFormato, 'equipoLocal' => 'Local 1', 'equipoVisitante' => 'Visitante 1',
            'fechaPartido' => today()->format('Y-m-d'), 'horaPartido' => '15:00', 'observaciones' => null,
        ], $designador->idUsuario);
        $servicio->asignarArbitro($partido1, $arbitroA->idArbitro, $this->idRolPorNombre('Central'), $colegio->idColegio, $designador->idUsuario);
        $servicio->asignarArbitro($partido1, $arbitroB->idArbitro, $this->idRolPorNombre('Asistente'), $colegio->idColegio, $designador->idUsuario);

        $partido2 = $servicio->crearPartido($colegio->idColegio, [
            'idTorneo' => $torneo->idTorneo, 'idDivision' => $division->idDivision, 'idSede' => $sede->idSede,
            'idFormato' => $formato->idFormato, 'equipoLocal' => 'Local 2', 'equipoVisitante' => 'Visitante 2',
            'fechaPartido' => today()->addDay()->format('Y-m-d'), 'horaPartido' => '15:00', 'observaciones' => null,
        ], $designador->idUsuario);
        $servicio->asignarArbitro($partido2, $arbitroB->idArbitro, $this->idRolPorNombre('Central'), $colegio->idColegio, $designador->idUsuario);
        $servicio->asignarArbitro($partido2, $arbitroC->idArbitro, $this->idRolPorNombre('Asistente'), $colegio->idColegio, $designador->idUsuario);

        $resultado = $this->servicio()->coincidencias($colegio->idColegio, [
            $arbitroA->idArbitro, $arbitroB->idArbitro, $arbitroC->idArbitro,
        ]);

        $this->assertTrue($resultado['partidos']->isEmpty(), 'Nunca coinciden los 3 a la vez.');
        $this->assertCount(2, $resultado['pares'], 'Solo A-B y B-C comparten partido, A-C no.');

        $claves = $resultado['pares']->map(fn ($p) => [$p['a']->idArbitro, $p['b']->idArbitro])->toArray();
        $contieneParABoBA = fn ($x, $y) => in_array([$x, $y], $claves) || in_array([$y, $x], $claves);

        $this->assertTrue($contieneParABoBA($arbitroA->idArbitro, $arbitroB->idArbitro));
        $this->assertTrue($contieneParABoBA($arbitroB->idArbitro, $arbitroC->idArbitro));
        $this->assertFalse($contieneParABoBA($arbitroA->idArbitro, $arbitroC->idArbitro));
    }

    public function test_coincidencias_con_un_solo_arbitro_no_calcula_nada(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);
        $datos      = $this->prepararPartidoPublicado($colegio, $designador);

        $resultado = $this->servicio()->coincidencias($colegio->idColegio, [$datos['arbitroCentral']->idArbitro]);

        $this->assertTrue($resultado['partidos']->isEmpty());
    }

    // ── Árbitros por categoría ────────────

    public function test_arbitros_por_categoria_cuenta_activos_y_total(): void
    {
        $colegio = $this->crearColegio();
        $activo  = $this->crearArbitro($colegio);
        $retirado = $this->crearArbitro($colegio, ['arbitro' => ['estadoArbitro' => 'retirado']]);

        $categorias = $this->servicio()->arbitrosPorCategoria($colegio->idColegio);

        $categoria = $categorias->firstWhere('idCategoria', $activo->idCategoria);
        $this->assertSame(2, $categoria->arbitros_count);
        $this->assertSame(1, $categoria->activos_count);
    }

    // ── Ranking de disponibilidad ─────────

    public function test_ranking_disponibilidad_calcula_porcentaje_y_dias_sin_reportar(): void
    {
        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);

        $desde = Carbon::parse('2026-01-05'); // lunes
        $hasta = Carbon::parse('2026-01-11'); // domingo — 7 días de ventana

        DisponibilidadArbitro::create(['idArbitro' => $arbitro->idArbitro, 'fechaDisponibilidad' => '2026-01-05', 'franjaHoraria' => DisponibilidadArbitro::FRANJA_TODO_DIA]);
        DisponibilidadArbitro::create(['idArbitro' => $arbitro->idArbitro, 'fechaDisponibilidad' => '2026-01-06', 'franjaHoraria' => DisponibilidadArbitro::FRANJA_NO_DISPONIBLE]);
        // El resto de la semana (5 días) queda sin reportar.

        $ranking = $this->servicio()->rankingDisponibilidad($colegio->idColegio, $desde, $hasta);
        $fila    = $ranking->firstWhere(fn ($f) => $f['arbitro']->idArbitro === $arbitro->idArbitro);

        $this->assertSame(2, $fila['diasReportados']);
        $this->assertSame(1, $fila['diasNoDisponible']);
        $this->assertSame(5, $fila['diasSinReportar']);
        // 1 día disponible de 7 días de ventana = 14.3%
        $this->assertEqualsWithDelta(14.3, $fila['porcentaje'], 0.1);
    }

    public function test_ranking_disponibilidad_filtra_por_nombre(): void
    {
        $colegio = $this->crearColegio();
        $this->crearArbitro($colegio, ['usuario' => ['nombreUsuario' => 'Carlos Andrés Pérez']]);
        $this->crearArbitro($colegio, ['usuario' => ['nombreUsuario' => 'María Fernanda Gómez']]);

        $desde = Carbon::parse('2026-01-05');
        $hasta = Carbon::parse('2026-01-11');

        $ranking = $this->servicio()->rankingDisponibilidad($colegio->idColegio, $desde, $hasta, 'carlos');

        $this->assertCount(1, $ranking);
        $this->assertSame('Carlos Andrés Pérez', $ranking->first()['arbitro']->usuario->nombreUsuario);
    }

    // ── Confiabilidad ──────────────────────

    public function test_confiabilidad_calcula_tasa_de_rechazo(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);
        $arbitro    = $this->crearArbitro($colegio);

        $this->crearRolesPartido();
        $formato  = $this->crearFormatoDupla();
        $torneo   = $this->crearTorneo($colegio, $designador);
        $division = $this->crearDivision($torneo);

        // 4 designaciones del mismo árbitro en 4 partidos distintos: 1 rechazada, 3 confirmadas.
        for ($i = 1; $i <= 4; $i++) {
            $partido = Partido::create([
                'idColegio' => $colegio->idColegio, 'idTorneo' => $torneo->idTorneo, 'idDivision' => $division->idDivision,
                'idFormato' => $formato->idFormato, 'equipoLocal' => "Local {$i}", 'equipoVisitante' => "Visitante {$i}",
                'fechaPartido' => today()->addDays($i), 'horaPartido' => '15:00', 'estadoPartido' => Partido::ESTADO_PROGRAMADO,
            ]);

            Designacion::create([
                'idPartido' => $partido->idPartido, 'idArbitro' => $arbitro->idArbitro,
                'idRol' => $this->idRolPorNombre('Central'), 'idColegio' => $colegio->idColegio,
                'estadoDesignacion' => $i === 1 ? Designacion::ESTADO_RECHAZADA : Designacion::ESTADO_CONFIRMADA,
                'fechaConfirmacion' => $i === 1 ? null : now(),
                'idUsuarioDesignador' => $designador->idUsuario,
            ]);
        }

        $resultado = $this->servicio()->confiabilidad($colegio->idColegio, now()->subDay(), now()->addWeek());
        $fila      = $resultado->firstWhere(fn ($f) => $f['arbitro']->idArbitro === $arbitro->idArbitro);

        $this->assertSame(4, $fila['total']);
        $this->assertSame(1, $fila['rechazadas']);
        $this->assertSame(25.0, $fila['porcentajeRechazo']);
    }

    // ── Multi-tenancy ──────────────────────

    public function test_los_reportes_no_mezclan_colegios(): void
    {
        $colegioA    = $this->crearColegio();
        $designadorA = $this->crearDesignador($colegioA);
        $datosA      = $this->prepararPartidoPublicado($colegioA, $designadorA);
        $datosA      = $this->finalizarPartido($datosA);

        $colegioB = $this->crearColegio();
        $this->crearArbitro($colegioB);

        $rankingB = $this->servicio()->partidosFinalizadosPorArbitro($colegioB->idColegio);
        $this->assertTrue($rankingB->isEmpty(), 'El colegio B no debe ver partidos finalizados del colegio A.');

        $categoriasB = $this->servicio()->arbitrosPorCategoria($colegioB->idColegio);
        $this->assertSame(1, $categoriasB->sum('arbitros_count'));
    }
}
