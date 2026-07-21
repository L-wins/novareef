<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MovimientoFinanciero;
use App\Services\FinanzasService;
use App\Services\ReporteFinanzasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class EstadoCuentaArbitroTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_el_arbitro_puede_ver_su_estado_de_cuenta_sin_permiso_ver_finanzas(): void
    {
        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);

        // El rol 'arbitro' del seeder real no tiene ver-finanzas — confirmamos
        // que la ruta /mi-estado-cuenta no depende de ese permiso.
        $this->assertFalse($arbitro->usuario->can('ver-finanzas'));

        $this->actingAs($arbitro->usuario)->get('/mi-estado-cuenta')->assertOk();
    }

    public function test_muestra_el_saldo_pendiente_por_cobrar_de_partidos_en_nomina(): void
    {
        $colegio  = $this->crearColegio();
        $arbitro  = $this->crearArbitro($colegio);
        $finanzas = app(FinanzasService::class);

        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento'  => 'egreso',
            'categoria'       => 'nomina_arbitro',
            'concepto'        => 'Nómina partido #1',
            'montoTotal'      => 60000,
            'fechaMovimiento' => today()->format('Y-m-d'),
            'idArbitro'       => $arbitro->idArbitro,
        ], null);

        $response = $this->actingAs($arbitro->usuario)->get('/mi-estado-cuenta');

        $response->assertOk();
        $response->assertSee('$60.000'); // formato COP sin decimales
    }

    public function test_un_pago_recibido_reduce_el_saldo_y_aparece_en_el_historial(): void
    {
        $colegio  = $this->crearColegio();
        $arbitro  = $this->crearArbitro($colegio);
        $finanzas = app(FinanzasService::class);
        $tesorero = $this->crearArbitro($colegio)->usuario; // solo para tener un User cualquiera como registrador

        $movimiento = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento'  => 'egreso',
            'categoria'       => 'nomina_arbitro',
            'concepto'        => 'Nómina partido #1',
            'montoTotal'      => 60000,
            'fechaMovimiento' => today()->format('Y-m-d'),
            'idArbitro'       => $arbitro->idArbitro,
        ], null);

        $finanzas->registrarAbono($movimiento, [
            'monto'      => 60000,
            'fechaAbono' => today()->format('Y-m-d'),
            'metodoPago' => 'pago_digital',
        ], $tesorero);

        $estadoCuenta = app(ReporteFinanzasService::class)->estadoCuentaArbitro($arbitro->fresh());

        $this->assertSame(0.0, $estadoCuenta['saldoPendienteCobrar']);
        $this->assertCount(1, $estadoCuenta['historialPagos']);
    }

    public function test_las_multas_del_arbitro_aparecen_en_su_historial(): void
    {
        $colegio  = $this->crearColegio();
        $arbitro  = $this->crearArbitro($colegio);
        $finanzas = app(FinanzasService::class);

        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento'  => 'ingreso',
            'categoria'       => 'multa',
            'concepto'        => 'Inasistencia injustificada',
            'montoTotal'      => 15000,
            'fechaMovimiento' => today()->format('Y-m-d'),
            'idArbitro'       => $arbitro->idArbitro,
            'tipoOrigenMulta' => MovimientoFinanciero::ORIGEN_MULTA_MANUAL,
        ], null);

        $estadoCuenta = app(ReporteFinanzasService::class)->estadoCuentaArbitro($arbitro->fresh());

        $this->assertCount(1, $estadoCuenta['historialMultas']);
        $this->assertSame('multa', $estadoCuenta['historialMultas']->first()->categoria);
    }

    public function test_un_descuento_de_compensacion_no_cuenta_como_pago_recibido(): void
    {
        $colegio  = $this->crearColegio();
        $arbitro  = $this->crearArbitro($colegio);
        $finanzas = app(FinanzasService::class);
        $usuario  = $this->crearArbitro($colegio)->usuario;

        $movimiento = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento'  => 'egreso',
            'categoria'       => 'nomina_arbitro',
            'concepto'        => 'Nómina partido #1',
            'montoTotal'      => 60000,
            'fechaMovimiento' => today()->format('Y-m-d'),
            'idArbitro'       => $arbitro->idArbitro,
        ], null);

        $finanzas->registrarAbono($movimiento, [
            'monto'      => 60000,
            'fechaAbono' => today()->format('Y-m-d'),
            'metodoPago' => 'nomina',
        ], $usuario);

        $estadoCuenta = app(ReporteFinanzasService::class)->estadoCuentaArbitro($arbitro->fresh());

        $this->assertCount(0, $estadoCuenta['historialPagos']);
        $this->assertCount(1, $estadoCuenta['descuentosNomina']);
    }
}
