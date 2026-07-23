<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\VencerSancionesJob;
use App\Models\Sancion;
use App\Models\TipoSancion;
use App\Models\User;
use App\Services\SancionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class VencerSancionesJobTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_cierra_sanciones_activas_con_fecha_fin_vencida(): void
    {
        $colegio  = $this->crearColegio();
        $arbitro  = $this->crearArbitro($colegio);
        $usuario  = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);
        $servicio = app(SancionService::class);

        $tipo = TipoSancion::create([
            'idColegio' => $colegio->idColegio, 'etiqueta' => 'Falta',
            'severidad' => 'leve', 'esActivo' => true,
        ]);

        $vencida = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitro->idArbitro, 'idTipoSancion' => $tipo->idTipoSancion,
            'motivoSancion' => 'Vencida', 'fechaHecho' => today()->subDays(10)->format('Y-m-d'),
            'fechaInicioSancion' => today()->subDays(10)->format('Y-m-d'),
            'fechaFinSancion' => today()->subDay()->format('Y-m-d'),
            'tieneMultaEconomica' => false,
        ], $usuario);

        $vigente = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitro->idArbitro, 'idTipoSancion' => $tipo->idTipoSancion,
            'motivoSancion' => 'Vigente', 'fechaHecho' => today()->format('Y-m-d'),
            'fechaInicioSancion' => today()->format('Y-m-d'),
            'fechaFinSancion' => today()->addDays(10)->format('Y-m-d'),
            'tieneMultaEconomica' => false,
        ], $usuario);

        $indefinida = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitro->idArbitro, 'idTipoSancion' => $tipo->idTipoSancion,
            'motivoSancion' => 'Indefinida', 'fechaHecho' => today()->format('Y-m-d'),
            'fechaInicioSancion' => today()->format('Y-m-d'),
            'tieneMultaEconomica' => false,
        ], $usuario);

        (new VencerSancionesJob())->handle($servicio);

        $this->assertSame('cumplida', $vencida->fresh()->estadoSancion);
        $this->assertSame('activa', $vigente->fresh()->estadoSancion);
        $this->assertSame('activa', $indefinida->fresh()->estadoSancion);

        $historialCumplida = $vencida->fresh()->historial()->where('tipoAccion', 'cumplida')->first();
        $this->assertNotNull($historialCumplida);
        $this->assertNull($historialCumplida->idUsuarioAccion);
    }
}
