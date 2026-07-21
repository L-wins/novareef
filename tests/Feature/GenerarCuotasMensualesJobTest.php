<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\GenerarCuotasMensualesJob;
use App\Models\ConfiguracionColegio;
use App\Models\MovimientoFinanciero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Día de vencimiento fijado en 1 en todos los casos — así el job siempre
 * "toca" generar sin importar qué día corran los tests (hoy->day >= 1 es
 * siempre cierto), sin necesidad de congelar el reloj.
 */
class GenerarCuotasMensualesJobTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_genera_una_cuota_pendiente_por_arbitro_activo(): void
    {
        $colegio = $this->crearColegioConFinanzas();
        $arbitroA = $this->crearArbitro($colegio);
        $arbitroB = $this->crearArbitro($colegio);

        ConfiguracionColegio::set($colegio->idColegio, ConfiguracionColegio::MONTO_MENSUALIDAD, '20000');
        ConfiguracionColegio::set($colegio->idColegio, ConfiguracionColegio::DIA_VENCIMIENTO_MENSUALIDAD, '1');

        (new GenerarCuotasMensualesJob())->handle(app(\App\Services\FinanzasService::class));

        $this->assertSame(2, MovimientoFinanciero::where('idColegio', $colegio->idColegio)
            ->where('categoria', MovimientoFinanciero::CATEGORIA_MENSUALIDAD)
            ->count());

        $movimiento = MovimientoFinanciero::where('idArbitro', $arbitroA->idArbitro)->first();
        $this->assertSame('pendiente', $movimiento->estadoMovimiento);
        $this->assertSame(20000.0, (float) $movimiento->montoTotal);

        $this->assertNotNull(MovimientoFinanciero::where('idArbitro', $arbitroB->idArbitro)->first());
    }

    public function test_no_genera_cuota_para_arbitro_retirado(): void
    {
        $colegio = $this->crearColegioConFinanzas();
        $retirado = $this->crearArbitro($colegio, ['arbitro' => ['estadoArbitro' => 'retirado']]);

        ConfiguracionColegio::set($colegio->idColegio, ConfiguracionColegio::MONTO_MENSUALIDAD, '20000');
        ConfiguracionColegio::set($colegio->idColegio, ConfiguracionColegio::DIA_VENCIMIENTO_MENSUALIDAD, '1');

        (new GenerarCuotasMensualesJob())->handle(app(\App\Services\FinanzasService::class));

        $this->assertNull(MovimientoFinanciero::where('idArbitro', $retirado->idArbitro)->first());
    }

    public function test_no_genera_nada_si_el_monto_es_cero(): void
    {
        $colegio = $this->crearColegioConFinanzas();
        $this->crearArbitro($colegio);

        // Sin configurar MONTO_MENSUALIDAD — default 0, cobro automático desactivado.

        (new GenerarCuotasMensualesJob())->handle(app(\App\Services\FinanzasService::class));

        $this->assertSame(0, MovimientoFinanciero::where('idColegio', $colegio->idColegio)
            ->where('categoria', MovimientoFinanciero::CATEGORIA_MENSUALIDAD)
            ->count());
    }

    public function test_no_duplica_si_corre_dos_veces_el_mismo_mes(): void
    {
        $colegio = $this->crearColegioConFinanzas();
        $this->crearArbitro($colegio);

        ConfiguracionColegio::set($colegio->idColegio, ConfiguracionColegio::MONTO_MENSUALIDAD, '20000');
        ConfiguracionColegio::set($colegio->idColegio, ConfiguracionColegio::DIA_VENCIMIENTO_MENSUALIDAD, '1');

        $finanzas = app(\App\Services\FinanzasService::class);
        (new GenerarCuotasMensualesJob())->handle($finanzas);
        (new GenerarCuotasMensualesJob())->handle($finanzas);

        $this->assertSame(1, MovimientoFinanciero::where('idColegio', $colegio->idColegio)
            ->where('categoria', MovimientoFinanciero::CATEGORIA_MENSUALIDAD)
            ->count());
    }

    private function crearColegioConFinanzas(): \App\Models\Colegio
    {
        return $this->crearColegio($this->crearPlan(['modulosJSON' => ['arbitros', 'torneos', 'designaciones', 'finanzas']]));
    }
}
