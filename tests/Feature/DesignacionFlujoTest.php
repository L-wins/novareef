<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\NotificarCriticoJob;
use App\Models\Designacion;
use App\Models\Partido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Camino crítico: rechazo de designación, timeout de confirmación,
 * reasignación de árbitro sobre un partido ya publicado, y el regreso
 * a 'confirmado' una vez se cubre el rol de nuevo.
 */
class DesignacionFlujoTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_rechazo_de_designacion_escala_el_partido_a_critico(): void
    {
        Queue::fake();

        $datos = $this->prepararPartidoPublicado();

        $usuarioAsistente = $datos['designacionAsistente']->arbitro->usuario;

        $this->actingAs($usuarioAsistente)
            ->postJson("/mis-partidos/{$datos['designacionAsistente']->idDesignacion}/rechazar", [
                'motivo' => 'Tengo un compromiso familiar ese día.',
            ])
            ->assertJson(['success' => true]);

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_CRITICO, $datos['partido']->estadoPartido);

        $designacionRechazada = Designacion::find($datos['designacionAsistente']->idDesignacion);
        $this->assertSame(Designacion::ESTADO_RECHAZADA, $designacionRechazada->estadoDesignacion);

        Queue::assertPushed(NotificarCriticoJob::class);
    }

    public function test_timeout_de_confirmacion_escala_a_critico_incluso_con_partido_confirmado(): void
    {
        Queue::fake();

        $datos = $this->prepararPartidoPublicado();

        // Ambos confirman → el partido queda "confirmado"
        $this->confirmarTodasLasDesignaciones($datos['partido']);
        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_CONFIRMADO, $datos['partido']->estadoPartido);

        // El designador reasigna un rol (deja una nueva designación pendiente
        // sin tocar el estado del partido — ver reasignarArbitro())
        $arbitroSuplente = $this->crearArbitro($datos['colegio']);

        $this->actingAs($datos['designador'])
            ->putJson("/designaciones/designacion/{$datos['designacionAsistente']->idDesignacion}/reasignar", [
                'idArbitro' => $arbitroSuplente->idArbitro,
            ])
            ->assertJson(['success' => true]);

        $datos['partido']->refresh();
        $this->assertSame(
            Partido::ESTADO_CONFIRMADO,
            $datos['partido']->estadoPartido,
            'La reasignación no debe cambiar el estado del partido.'
        );

        // Han pasado 5 horas desde la publicación (límite default: 4h) sin que
        // el suplente confirme → VerificarConfirmacionesJob debe escalar a crítico,
        // incluso viniendo de estado "confirmado" (no solo "programado").
        $this->travel(5)->hours();
        (new \App\Jobs\VerificarConfirmacionesJob())->handle();

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_CRITICO, $datos['partido']->estadoPartido);
    }

    public function test_reasignar_arbitro_sobre_partido_critico_crea_designacion_pendiente_sin_cambiar_estado(): void
    {
        $datos = $this->prepararPartidoPublicado();

        $usuarioAsistente = $datos['designacionAsistente']->arbitro->usuario;
        $this->actingAs($usuarioAsistente)
            ->postJson("/mis-partidos/{$datos['designacionAsistente']->idDesignacion}/rechazar", [
                'motivo' => 'No podré asistir ese día por un imprevisto médico.',
            ]);

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_CRITICO, $datos['partido']->estadoPartido);

        // El Central (el otro rol) confirma su designación normalmente
        $this->actingAs($datos['designacionCentral']->arbitro->usuario)
            ->postJson("/mis-partidos/{$datos['designacionCentral']->idDesignacion}/confirmar")
            ->assertJson(['success' => true]);

        $arbitroSuplente = $this->crearArbitro($datos['colegio']);

        $respuesta = $this->actingAs($datos['designador'])
            ->putJson("/designaciones/designacion/{$datos['designacionAsistente']->idDesignacion}/reasignar", [
                'idArbitro' => $arbitroSuplente->idArbitro,
            ]);

        $respuesta->assertJson(['success' => true]);

        // La designación vieja (rechazada) fue reemplazada por una nueva pendiente
        $this->assertNull(Designacion::find($datos['designacionAsistente']->idDesignacion));

        $nuevaDesignacion = Designacion::where('idPartido', $datos['partido']->idPartido)
            ->where('idArbitro', $arbitroSuplente->idArbitro)
            ->firstOrFail();

        $this->assertSame(Designacion::ESTADO_PENDIENTE, $nuevaDesignacion->estadoDesignacion);

        $datos['partido']->refresh();
        $this->assertSame(
            Partido::ESTADO_CRITICO,
            $datos['partido']->estadoPartido,
            'Reasignar no debe "arreglar" el estado del partido por sí solo.'
        );

        // Al confirmar el suplente y ya estando el resto confirmado, el partido
        // debe poder volver a 'confirmado' (transición critico -> confirmado).
        $this->actingAs($arbitroSuplente->usuario)
            ->postJson("/mis-partidos/{$nuevaDesignacion->idDesignacion}/confirmar")
            ->assertJson(['success' => true, 'partidoCompleto' => true]);

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_CONFIRMADO, $datos['partido']->estadoPartido);
    }

    public function test_no_se_puede_reasignar_un_partido_en_borrador(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);

        $this->crearRolesPartido();
        $formato  = $this->crearFormatoDupla();
        $torneo   = $this->crearTorneo($colegio, $designador);
        $division = $this->crearDivision($torneo);
        $sede     = $this->crearSede($torneo);
        $arbitro  = $this->crearArbitro($colegio);

        $servicio = app(\App\Services\DesignacionService::class);
        $partido  = $servicio->crearPartido($colegio->idColegio, [
            'idTorneo' => $torneo->idTorneo, 'idDivision' => $division->idDivision,
            'idSede' => $sede->idSede, 'idFormato' => $formato->idFormato,
            'equipoLocal' => 'A', 'equipoVisitante' => 'B',
            'fechaPartido' => today()->format('Y-m-d'), 'horaPartido' => '15:00',
            'observaciones' => null,
        ], $designador->idUsuario);

        $resultado = $servicio->asignarArbitro(
            $partido, $arbitro->idArbitro, $this->idRolPorNombre('Central'),
            $colegio->idColegio, $designador->idUsuario
        );

        $otroArbitro = $this->crearArbitro($colegio);

        $this->actingAs($designador)
            ->putJson("/designaciones/designacion/{$resultado['designacion']->idDesignacion}/reasignar", [
                'idArbitro' => $otroArbitro->idArbitro,
            ])
            ->assertStatus(422);
    }

    /**
     * Confirma todas las designaciones pendientes del partido, actuando como
     * el usuario asociado a cada árbitro designado.
     */
    private function confirmarTodasLasDesignaciones(Partido $partido): void
    {
        foreach ($partido->designaciones()->get() as $designacion) {
            $this->actingAs($designacion->arbitro->usuario)
                ->postJson("/mis-partidos/{$designacion->idDesignacion}/confirmar");
        }
    }
}
