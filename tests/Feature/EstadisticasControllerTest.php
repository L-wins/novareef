<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Rutas/permisos/respuestas AJAX del módulo de Estadísticas de Designaciones
 * — la lógica de agregación en sí se cubre en EstadisticasServiceTest.
 */
class EstadisticasControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_designador_puede_ver_la_pagina_de_estadisticas(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);
        $this->crearArbitro($colegio);

        $this->actingAs($designador)
            ->get(route('designaciones.estadisticas'))
            ->assertOk()
            ->assertViewHas('resumen')
            ->assertViewHas('categorias')
            ->assertViewHas('rankingDisponibilidad')
            ->assertViewHas('coincidencias');
    }

    public function test_arbitro_sin_permiso_no_puede_acceder(): void
    {
        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);

        $this->actingAs($arbitro->usuario)
            ->get(route('designaciones.estadisticas'))
            ->assertForbidden();
    }

    public function test_endpoint_ajax_disponibilidad_responde_json_con_region(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);
        $this->crearArbitro($colegio);

        $respuesta = $this->actingAs($designador)
            ->getJson(route('designaciones.estadisticas.disponibilidad'), ['X-Requested-With' => 'XMLHttpRequest']);

        $respuesta->assertOk()->assertJsonStructure(['regions' => ['disponibilidad']]);
    }

    public function test_endpoint_ajax_sin_cabecera_ajax_redirige_al_index(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);

        $this->actingAs($designador)
            ->get(route('designaciones.estadisticas.confiabilidad'))
            ->assertRedirect(route('designaciones.estadisticas'));
    }

    public function test_coincidencias_con_menos_de_2_arbitros_no_explota(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);
        $arbitro    = $this->crearArbitro($colegio);

        $respuesta = $this->actingAs($designador)->getJson(
            route('designaciones.estadisticas.coincidencias', ['arbitros' => [$arbitro->idArbitro]]),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $respuesta->assertOk()->assertJsonStructure(['regions' => ['coincidencias']]);
    }

    public function test_coincidencias_ajax_con_dos_arbitros_devuelve_el_conteo(): void
    {
        Queue::fake();

        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);
        $datos      = $this->prepararPartidoPublicado($colegio, $designador);

        $respuesta = $this->actingAs($designador)->getJson(
            route('designaciones.estadisticas.coincidencias', [
                'arbitros' => [$datos['arbitroCentral']->idArbitro, $datos['arbitroAsistente']->idArbitro],
            ]),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $respuesta->assertOk();
        $this->assertStringContainsString('1', $respuesta->json('regions.coincidencias'));
    }

    public function test_un_colegio_no_ve_arbitros_de_otro_en_el_select_de_coincidencias(): void
    {
        $colegioA    = $this->crearColegio();
        $designadorA = $this->crearDesignador($colegioA);

        $colegioB = $this->crearColegio();
        $arbitroB = $this->crearArbitro($colegioB);

        $respuesta = $this->actingAs($designadorA)->get(route('designaciones.estadisticas'));

        $respuesta->assertOk();
        $opciones = $respuesta->viewData('arbitrosOpciones');
        $this->assertFalse($opciones->contains('idArbitro', $arbitroB->idArbitro));
    }
}
