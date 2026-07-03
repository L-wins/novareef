<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Torneo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * estadoTorneo era 100% manual — nada lo recalculaba según fechaInicio/
 * fechaFin, así que un torneo podía quedar "próximo" para siempre aunque ya
 * estuviera en curso, o "activo" para siempre aunque ya hubiera terminado.
 */
class ActualizarEstadosTorneoCommandTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function crearTorneo(string $estado, string $fechaInicio, string $fechaFin): Torneo
    {
        $colegio = $this->crearColegio($this->crearPlan());
        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);

        return Torneo::create([
            'idColegio'         => $colegio->idColegio,
            'idUsuarioCreador'  => $usuario->idUsuario,
            'organizadorNombre' => 'Organizador de prueba',
            'nombreTorneo'      => 'Torneo de prueba',
            'temporada'         => 2026,
            'fechaInicio'       => $fechaInicio,
            'fechaFin'          => $fechaFin,
            'estadoTorneo'      => $estado,
        ]);
    }

    public function test_pasa_de_proximo_a_activo_si_ya_empezo(): void
    {
        $torneo = $this->crearTorneo('proximo', today()->subDay()->toDateString(), today()->addMonth()->toDateString());

        $this->artisan('novareef:actualizar-estados-torneo')->run();

        $this->assertSame('activo', $torneo->fresh()->estadoTorneo);
    }

    public function test_pasa_de_activo_a_finalizado_si_ya_termino(): void
    {
        $torneo = $this->crearTorneo('activo', today()->subMonth()->toDateString(), today()->subDay()->toDateString());

        $this->artisan('novareef:actualizar-estados-torneo')->run();

        $this->assertSame('finalizado', $torneo->fresh()->estadoTorneo);
    }

    public function test_no_toca_un_torneo_cancelado(): void
    {
        $torneo = $this->crearTorneo('cancelado', today()->subMonth()->toDateString(), today()->subDay()->toDateString());

        $this->artisan('novareef:actualizar-estados-torneo')->run();

        $this->assertSame('cancelado', $torneo->fresh()->estadoTorneo);
    }

    public function test_no_toca_un_proximo_que_de_verdad_no_ha_empezado(): void
    {
        $torneo = $this->crearTorneo('proximo', today()->addWeek()->toDateString(), today()->addMonth()->toDateString());

        $this->artisan('novareef:actualizar-estados-torneo')->run();

        $this->assertSame('proximo', $torneo->fresh()->estadoTorneo);
    }
}
