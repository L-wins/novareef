<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\GenerarPagosJob;
use App\Jobs\NotificarPublicacionJob;
use App\Models\Partido;
use App\StateMachines\PartidoStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Ciclo de vida feliz completo de un partido, end-to-end vía HTTP:
 * borrador → programado → confirmado → finalizado. No existe estado
 * "en_curso" — el partido queda en 'confirmado' hasta que el árbitro
 * Central o el designador lo finalizan manualmente.
 */
class PartidoTransicionesTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_ciclo_completo_borrador_hasta_finalizado_por_arbitro_central(): void
    {
        Queue::fake();

        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);

        $this->crearRolesPartido();
        $formato  = $this->crearFormatoDupla();
        $torneo   = $this->crearTorneo($colegio, $designador);
        $division = $this->crearDivision($torneo);
        $sede     = $this->crearSede($torneo);

        $arbitroCentral   = $this->crearArbitro($colegio);
        $arbitroAsistente = $this->crearArbitro($colegio);

        // 1. Crear partido en borrador (POST /designaciones)
        $respuestaCrear = $this->actingAs($designador)->post('/designaciones', [
            'idTorneo'        => $torneo->idTorneo,
            'idDivision'      => $division->idDivision,
            'idSede'          => $sede->idSede,
            'idFormato'       => $formato->idFormato,
            'equipoLocal'     => 'Local FC',
            'equipoVisitante' => 'Visitante FC',
            'fechaPartido'    => today()->format('Y-m-d'),
            'horaPartido'     => '15:00',
        ]);

        $respuestaCrear->assertRedirect();
        $partido = Partido::where('idColegio', $colegio->idColegio)->firstOrFail();
        $this->assertSame(Partido::ESTADO_BORRADOR, $partido->estadoPartido);

        // 2. Asignar árbitros a los dos slots (Central + Asistente)
        $this->actingAs($designador)->postJson("/designaciones/{$partido->idPartido}/asignar", [
            'idArbitro' => $arbitroCentral->idArbitro,
            'idRol'     => $this->idRolPorNombre('Central'),
        ])->assertJson(['success' => true]);

        $this->actingAs($designador)->postJson("/designaciones/{$partido->idPartido}/asignar", [
            'idArbitro' => $arbitroAsistente->idArbitro,
            'idRol'     => $this->idRolPorNombre('Asistente'),
        ])->assertJson(['success' => true]);

        $this->assertSame(2, $partido->designaciones()->count());

        // 3. Publicar (borrador → programado)
        $this->actingAs($designador)->postJson("/designaciones/partido/{$partido->idPartido}/publicar")
            ->assertJson(['success' => true]);

        $partido->refresh();
        $this->assertSame(Partido::ESTADO_PROGRAMADO, $partido->estadoPartido);
        Queue::assertPushed(NotificarPublicacionJob::class);

        // 4. Ambos árbitros confirman su designación → el partido pasa a confirmado
        foreach ($partido->designaciones as $designacion) {
            $usuarioArbitro = $designacion->arbitro->usuario;

            $this->actingAs($usuarioArbitro)
                ->postJson("/mis-partidos/{$designacion->idDesignacion}/confirmar")
                ->assertJson(['success' => true]);
        }

        $partido->refresh();
        $this->assertSame(Partido::ESTADO_CONFIRMADO, $partido->estadoPartido);

        // 5. El árbitro Central finaliza manualmente (confirmado → finalizado)
        $this->actingAs($arbitroCentral->usuario)
            ->postJson("/designaciones/partido/{$partido->idPartido}/finalizar")
            ->assertJson(['success' => true]);

        $partido->refresh();
        $this->assertSame(Partido::ESTADO_FINALIZADO, $partido->estadoPartido);

        // modalidadPago por defecto es 'campo' → no debe generarse job de pagos
        Queue::assertNotPushed(GenerarPagosJob::class);
    }

    public function test_el_designador_tambien_puede_finalizar_un_partido_confirmado(): void
    {
        $datos = $this->prepararPartidoPublicado();

        foreach ([$datos['designacionCentral'], $datos['designacionAsistente']] as $designacion) {
            $this->actingAs($designacion->arbitro->usuario)
                ->postJson("/mis-partidos/{$designacion->idDesignacion}/confirmar");
        }

        $this->actingAs($datos['designador'])
            ->putJson("/designaciones/{$datos['partido']->idPartido}/estado", [
                'estadoNuevo' => 'finalizado',
            ])
            ->assertJson(['success' => true]);

        $datos['partido']->refresh();
        $this->assertSame(Partido::ESTADO_FINALIZADO, $datos['partido']->estadoPartido);
    }

    public function test_finalizacion_con_modalidad_nomina_despacha_generar_pagos(): void
    {
        Queue::fake();

        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);
        $formato    = $this->crearFormatoDupla();
        $torneo     = $this->crearTorneo($colegio, $designador);
        $division   = $this->crearDivision($torneo);

        $partido = Partido::create([
            'idColegio'       => $colegio->idColegio,
            'idTorneo'        => $torneo->idTorneo,
            'idDivision'      => $division->idDivision,
            'idFormato'       => $formato->idFormato,
            'equipoLocal'     => 'Local FC',
            'equipoVisitante' => 'Visitante FC',
            'fechaPartido'    => today(),
            'horaPartido'     => '10:00',
            'estadoPartido'   => Partido::ESTADO_CONFIRMADO,
            'modalidadPago'   => 'nomina',
            'version'         => 0,
        ]);

        PartidoStateMachine::transicionarCon($partido, Partido::ESTADO_FINALIZADO, $designador);

        $partido->refresh();
        $this->assertSame(Partido::ESTADO_FINALIZADO, $partido->estadoPartido);
        Queue::assertPushed(GenerarPagosJob::class);
    }
}
