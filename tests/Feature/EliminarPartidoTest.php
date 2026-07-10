<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Designacion;
use App\Models\HistorialDesignacion;
use App\Models\Partido;
use App\Models\SlotDesignacion;
use App\Services\DesignacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Eliminación de un partido en borrador: debe borrar en cascada designaciones,
 * slots e historial. Una vez publicado, debe quedar bloqueada (se cancela o
 * aplaza en su lugar, nunca se borra el rastro).
 */
class EliminarPartidoTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_elimina_partido_en_borrador_con_cascada(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);

        $this->crearRolesPartido();
        $formato  = $this->crearFormatoDupla();
        $torneo   = $this->crearTorneo($colegio, $designador);
        $division = $this->crearDivision($torneo);
        $sede     = $this->crearSede($torneo);
        $arbitro  = $this->crearArbitro($colegio);

        $servicio = app(DesignacionService::class);
        $partido  = $servicio->crearPartido($colegio->idColegio, [
            'idTorneo' => $torneo->idTorneo, 'idDivision' => $division->idDivision,
            'idSede' => $sede->idSede, 'idFormato' => $formato->idFormato,
            'equipoLocal' => 'A', 'equipoVisitante' => 'B',
            'fechaPartido' => today()->format('Y-m-d'), 'horaPartido' => '15:00',
            'observaciones' => null,
        ], $designador->idUsuario);

        $servicio->asignarArbitro(
            $partido, $arbitro->idArbitro, $this->idRolPorNombre('Central'),
            $colegio->idColegio, $designador->idUsuario
        );

        $this->assertSame(1, Designacion::where('idPartido', $partido->idPartido)->count());
        $this->assertGreaterThan(0, HistorialDesignacion::where('idPartido', $partido->idPartido)->count());
        $this->assertSame(2, SlotDesignacion::where('idPartido', $partido->idPartido)->count());

        $this->actingAs($designador)
            ->deleteJson("/designaciones/partido/{$partido->idPartido}")
            ->assertJson(['success' => true]);

        $this->assertNull(Partido::find($partido->idPartido));
        $this->assertSame(0, Designacion::where('idPartido', $partido->idPartido)->count());
        $this->assertSame(0, HistorialDesignacion::where('idPartido', $partido->idPartido)->count());
        // slots_designacion tiene onDelete cascade sobre idPartido
        $this->assertSame(0, SlotDesignacion::where('idPartido', $partido->idPartido)->count());
    }

    public function test_no_se_puede_eliminar_un_partido_ya_publicado(): void
    {
        $datos = $this->prepararPartidoPublicado();

        $this->actingAs($datos['designador'])
            ->deleteJson("/designaciones/partido/{$datos['partido']->idPartido}")
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertNotNull(Partido::find($datos['partido']->idPartido));
        $this->assertSame(
            Partido::ESTADO_PROGRAMADO,
            Partido::find($datos['partido']->idPartido)->estadoPartido
        );
    }

    public function test_un_usuario_sin_permiso_no_puede_eliminar_partido(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);

        $this->crearRolesPartido();
        $formato  = $this->crearFormatoDupla();
        $torneo   = $this->crearTorneo($colegio, $designador);
        $division = $this->crearDivision($torneo);
        $sede     = $this->crearSede($torneo);

        $servicio = app(DesignacionService::class);
        $partido  = $servicio->crearPartido($colegio->idColegio, [
            'idTorneo' => $torneo->idTorneo, 'idDivision' => $division->idDivision,
            'idSede' => $sede->idSede, 'idFormato' => $formato->idFormato,
            'equipoLocal' => 'A', 'equipoVisitante' => 'B',
            'fechaPartido' => today()->format('Y-m-d'), 'horaPartido' => '15:00',
            'observaciones' => null,
        ], $designador->idUsuario);

        // Un árbitro (sin permiso crear-designaciones) no debe poder eliminar
        $arbitro = $this->crearArbitro($colegio);

        $this->actingAs($arbitro->usuario)
            ->deleteJson("/designaciones/partido/{$partido->idPartido}")
            ->assertStatus(403);

        $this->assertNotNull(Partido::find($partido->idPartido));
    }
}
