<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CalificacionArbitro;
use App\Models\Colegio;
use App\Models\Partido;
use App\Services\DesignacionService;
use App\StateMachines\PartidoStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Cobertura de CalificacionController — calificar árbitros de un partido
 * finalizado (rol veedor). Sin tests de Feature previos.
 */
class CalificacionControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    /**
     * Deja un partido finalizado con ambas designaciones confirmadas, listo
     * para calificar — parte de prepararPartidoPublicado() (pendiente) y
     * avanza confirmando cada rol antes de finalizar.
     */
    private function prepararPartidoFinalizado(): array
    {
        $datos = $this->prepararPartidoPublicado();

        $servicio = app(DesignacionService::class);
        $servicio->confirmarDesignacion($datos['designacionCentral'], $datos['arbitroCentral'], $datos['designador']);
        $servicio->confirmarDesignacion($datos['designacionAsistente'], $datos['arbitroAsistente'], $datos['designador']);

        $partido = $datos['partido']->fresh(['formato']);
        PartidoStateMachine::transicionarCon($partido, Partido::ESTADO_FINALIZADO, $datos['designador']);

        $datos['partido'] = $partido->fresh();
        $datos['designacionCentral']   = $datos['designacionCentral']->fresh();
        $datos['designacionAsistente'] = $datos['designacionAsistente']->fresh();

        return $datos;
    }

    private function crearVeedor(Colegio $colegio)
    {
        return $this->crearUsuarioConRol($colegio, 'veedor', ['ver-designaciones', 'crear-calificaciones']);
    }

    private function crearUsuarioConRol(Colegio $colegio, string $rol, array $permisos)
    {
        foreach ($permisos as $permiso) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        $role->syncPermissions($permisos);

        $usuario = \App\Models\User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => $rol]);
        $usuario->assignRole($rol);

        return $usuario;
    }

    public function test_lista_las_designaciones_confirmadas_del_partido(): void
    {
        $datos  = $this->prepararPartidoFinalizado();
        $veedor = $this->crearVeedor($datos['colegio']);

        $this->actingAs($veedor)->get(route('designaciones.calificaciones.index', $datos['partido']->idPartido))
            ->assertOk()
            ->assertViewHas('partido', fn ($p) => $p->designaciones->count() === 2);
    }

    public function test_registra_una_calificacion_y_recalcula_el_score(): void
    {
        $datos  = $this->prepararPartidoFinalizado();
        $veedor = $this->crearVeedor($datos['colegio']);

        $this->actingAs($veedor)->postJson(route('calificaciones.store', $datos['designacionCentral']->idDesignacion), [
            'nota'       => 4.5,
            'comentario' => 'Buen manejo del partido, decisiones acertadas.',
        ])->assertOk()->assertJson(['success' => true, 'nuevaScore' => 4.5]);

        $this->assertDatabaseHas('calificaciones_arbitro', [
            'idDesignacion' => $datos['designacionCentral']->idDesignacion,
            'nota'          => 4.5,
        ]);
        $this->assertEquals(4.5, $datos['arbitroCentral']->fresh()->scoreDesempeno);
    }

    public function test_no_se_puede_calificar_un_partido_no_finalizado(): void
    {
        $datos  = $this->prepararPartidoPublicado();
        $veedor = $this->crearVeedor($datos['colegio']);

        $servicio = app(DesignacionService::class);
        $servicio->confirmarDesignacion($datos['designacionCentral'], $datos['arbitroCentral'], $datos['designador']);

        $this->actingAs($veedor)->postJson(route('calificaciones.store', $datos['designacionCentral']->fresh()->idDesignacion), [
            'nota'       => 5,
            'comentario' => 'Intento de calificar antes de tiempo.',
        ])->assertStatus(422);
    }

    public function test_calificar_de_nuevo_actualiza_en_vez_de_duplicar(): void
    {
        $datos  = $this->prepararPartidoFinalizado();
        $veedor = $this->crearVeedor($datos['colegio']);

        $this->actingAs($veedor)->postJson(route('calificaciones.store', $datos['designacionCentral']->idDesignacion), [
            'nota' => 3, 'comentario' => 'Primera calificación de prueba.',
        ]);
        $this->actingAs($veedor)->postJson(route('calificaciones.store', $datos['designacionCentral']->idDesignacion), [
            'nota' => 5, 'comentario' => 'Calificación corregida tras revisión.',
        ]);

        $this->assertSame(1, CalificacionArbitro::where('idDesignacion', $datos['designacionCentral']->idDesignacion)->count());
        $this->assertEquals(5, CalificacionArbitro::where('idDesignacion', $datos['designacionCentral']->idDesignacion)->value('nota'));
    }

    public function test_un_colegio_no_puede_calificar_partido_de_otro(): void
    {
        $datos       = $this->prepararPartidoFinalizado();
        $otroColegio = $this->crearColegio();
        $veedorOtro  = $this->crearVeedor($otroColegio);

        $this->actingAs($veedorOtro)->get(route('designaciones.calificaciones.index', $datos['partido']->idPartido))
            ->assertNotFound();
    }
}
