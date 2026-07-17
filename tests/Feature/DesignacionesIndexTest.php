<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Cobertura de DesignacionController::index() — sin ?torneo= muestra el grid
 * de torneos con conteos, con ?torneo= el listado filtrado del torneo. Antes
 * de este archivo la ruta no tenía ningún test propio; sirve además de
 * regresión para el paso de esta lógica a ReporteDesignacionesService.
 */
class DesignacionesIndexTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_sin_torneo_muestra_el_grid_con_conteos(): void
    {
        $datos = $this->prepararPartidoPublicado();

        $this->actingAs($datos['designador'])->get(route('designaciones.index'))
            ->assertOk()
            ->assertViewIs('designaciones.index')
            ->assertViewHas('torneos')
            ->assertViewHas('criticosCount', 0);
    }

    public function test_con_torneo_muestra_el_listado_filtrado(): void
    {
        $datos  = $this->prepararPartidoPublicado();
        $torneo = $datos['partido']->torneo;

        $this->actingAs($datos['designador'])
            ->get(route('designaciones.index', ['torneo' => $torneo->idTorneo]))
            ->assertOk()
            ->assertViewIs('designaciones.partidos-torneo')
            ->assertViewHas('partidos', fn ($partidos) => $partidos->total() === 1)
            ->assertViewHas('criticosCount', 0);
    }

    public function test_el_filtro_de_estado_excluye_partidos_de_otro_estado(): void
    {
        $datos  = $this->prepararPartidoPublicado();
        $torneo = $datos['partido']->torneo;

        $this->actingAs($datos['designador'])
            ->get(route('designaciones.index', ['torneo' => $torneo->idTorneo, 'estado' => 'finalizado']))
            ->assertOk()
            ->assertViewHas('partidos', fn ($partidos) => $partidos->total() === 0);
    }

    public function test_un_colegio_no_puede_ver_torneos_de_otro_colegio(): void
    {
        $datos      = $this->prepararPartidoPublicado();
        $otroColegio = $this->crearColegio();
        $otroDesignador = $this->crearDesignador($otroColegio);

        $this->actingAs($otroDesignador)
            ->get(route('designaciones.index', ['torneo' => $datos['partido']->torneo->idTorneo]))
            ->assertNotFound();
    }
}
