<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Partido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Aislamiento multi-tenant (un colegio no puede ver/tocar partidos de otro)
 * y reglas de autorización específicas de la state machine (ejecutivo-only
 * para cancelar, designador/ejecutivo para finalizar/aplazar, Central-only
 * para finalizar vía el endpoint del árbitro).
 */
class PartidoTenantPermisosTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_un_colegio_no_puede_ver_el_partido_de_otro_colegio(): void
    {
        $datos = $this->prepararPartidoPublicado();

        $colegioB    = $this->crearColegio();
        $designadorB = $this->crearDesignador($colegioB);

        $this->actingAs($designadorB)
            ->get("/designaciones/{$datos['partido']->idPartido}")
            ->assertNotFound();
    }

    public function test_un_colegio_no_puede_publicar_el_partido_de_otro_colegio(): void
    {
        $colegioB    = $this->crearColegio();
        $designadorB = $this->crearDesignador($colegioB);

        $colegioA    = $this->crearColegio();
        $designadorA = $this->crearDesignador($colegioA);

        $this->crearRolesPartido();
        $formato  = $this->crearFormatoDupla();
        $torneo   = $this->crearTorneo($colegioA, $designadorA);
        $division = $this->crearDivision($torneo);
        $sede     = $this->crearSede($torneo);

        $servicio = app(\App\Services\DesignacionService::class);
        $partido  = $servicio->crearPartido($colegioA->idColegio, [
            'idTorneo' => $torneo->idTorneo, 'idDivision' => $division->idDivision,
            'idSede' => $sede->idSede, 'idFormato' => $formato->idFormato,
            'equipoLocal' => 'A', 'equipoVisitante' => 'B',
            'fechaPartido' => today()->format('Y-m-d'), 'horaPartido' => '15:00',
            'observaciones' => null,
        ], $designadorA->idUsuario);

        $this->actingAs($designadorB)
            ->postJson("/designaciones/partido/{$partido->idPartido}/publicar")
            ->assertNotFound();

        $partido->refresh();
        $this->assertSame(Partido::ESTADO_BORRADOR, $partido->estadoPartido);
    }

    public function test_solo_ejecutivo_puede_cancelar_un_partido(): void
    {
        $datos = $this->prepararPartidoPublicado();

        // El designador (sin rol ejecutivo) NO puede cancelar directamente
        $this->actingAs($datos['designador'])
            ->putJson("/designaciones/{$datos['partido']->idPartido}/estado", [
                'estadoNuevo' => 'cancelado',
            ])
            ->assertForbidden();

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_PROGRAMADO, $datos['partido']->estadoPartido);

        // Un ejecutivo del mismo colegio sí puede
        $ejecutivo = $this->crearEjecutivo($datos['colegio']);

        $this->actingAs($ejecutivo)
            ->putJson("/designaciones/{$datos['partido']->idPartido}/estado", [
                'estadoNuevo' => 'cancelado',
            ])
            ->assertJson(['success' => true]);

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_CANCELADO, $datos['partido']->estadoPartido);
    }

    public function test_designador_si_puede_aplazar_un_partido(): void
    {
        $datos = $this->prepararPartidoPublicado();

        $this->actingAs($datos['designador'])
            ->putJson("/designaciones/{$datos['partido']->idPartido}/estado", [
                'estadoNuevo' => 'aplazado',
            ])
            ->assertJson(['success' => true]);

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_APLAZADO, $datos['partido']->estadoPartido);
    }

    public function test_solo_el_arbitro_central_puede_finalizar_via_su_endpoint(): void
    {
        $datos = $this->prepararPartidoPublicado();

        // finalizarPartido() exige que la designación del Central esté confirmada
        $this->actingAs($datos['designacionCentral']->arbitro->usuario)
            ->postJson("/mis-partidos/{$datos['designacionCentral']->idDesignacion}/confirmar");
        $this->actingAs($datos['designacionAsistente']->arbitro->usuario)
            ->postJson("/mis-partidos/{$datos['designacionAsistente']->idDesignacion}/confirmar");

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_CONFIRMADO, $datos['partido']->estadoPartido);

        // El Asistente (no Central) intenta finalizar → debe rechazarse
        $this->actingAs($datos['designacionAsistente']->arbitro->usuario)
            ->postJson("/designaciones/partido/{$datos['partido']->idPartido}/finalizar")
            ->assertForbidden();

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_CONFIRMADO, $datos['partido']->estadoPartido);

        // El Central sí puede
        $this->actingAs($datos['designacionCentral']->arbitro->usuario)
            ->postJson("/designaciones/partido/{$datos['partido']->idPartido}/finalizar")
            ->assertJson(['success' => true]);

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_FINALIZADO, $datos['partido']->estadoPartido);
    }

    public function test_no_se_puede_establecer_critico_manualmente(): void
    {
        $datos = $this->prepararPartidoPublicado();

        $this->actingAs($datos['designador'])
            ->putJson("/designaciones/{$datos['partido']->idPartido}/estado", [
                'estadoNuevo' => 'critico',
            ])
            ->assertStatus(422);

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_PROGRAMADO, $datos['partido']->estadoPartido);
    }

    public function test_no_se_puede_establecer_confirmado_manualmente(): void
    {
        $datos = $this->prepararPartidoPublicado();

        $this->actingAs($datos['designador'])
            ->putJson("/designaciones/{$datos['partido']->idPartido}/estado", [
                'estadoNuevo' => 'confirmado',
            ])
            ->assertStatus(422);

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_PROGRAMADO, $datos['partido']->estadoPartido);
    }

    public function test_no_se_puede_revertir_a_programado_desde_confirmado(): void
    {
        $datos = $this->prepararPartidoPublicado();

        foreach ([$datos['designacionCentral'], $datos['designacionAsistente']] as $designacion) {
            $this->actingAs($designacion->arbitro->usuario)
                ->postJson("/mis-partidos/{$designacion->idDesignacion}/confirmar");
        }

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_CONFIRMADO, $datos['partido']->estadoPartido);

        $this->actingAs($datos['designador'])
            ->putJson("/designaciones/{$datos['partido']->idPartido}/estado", [
                'estadoNuevo' => 'programado',
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_CONFIRMADO, $datos['partido']->estadoPartido);
    }

    public function test_si_se_puede_reactivar_un_partido_aplazado_a_programado(): void
    {
        $datos = $this->prepararPartidoPublicado();

        $datos['partido']->update(['estadoPartido' => Partido::ESTADO_APLAZADO]);

        $this->actingAs($datos['designador'])
            ->putJson("/designaciones/{$datos['partido']->idPartido}/estado", [
                'estadoNuevo' => 'programado',
            ])
            ->assertJson(['success' => true]);

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_PROGRAMADO, $datos['partido']->estadoPartido);
    }
}
