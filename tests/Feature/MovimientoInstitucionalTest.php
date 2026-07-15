<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\MovimientoFinanciero;
use App\Models\User;
use App\Services\FinanzasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class MovimientoInstitucionalTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function crearTesorero(Colegio $colegio): User
    {
        foreach (['ver-finanzas', 'crear-finanzas', 'editar-finanzas'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $rol = Role::firstOrCreate(['name' => 'tesorero', 'guard_name' => 'web']);
        $rol->syncPermissions(['ver-finanzas', 'crear-finanzas', 'editar-finanzas']);

        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'tesorero']);
        $usuario->assignRole('tesorero');

        return $usuario;
    }

    private function crearColegioConFinanzas(): Colegio
    {
        return $this->crearColegio($this->crearPlan(['modulosJSON' => ['arbitros', 'torneos', 'designaciones', 'finanzas']]));
    }

    public function test_registra_un_gasto_fijo_ya_pagado(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $this->actingAs($tesorero)->post('/finanzas/gastos-ingresos', [
            'tipoMovimiento'  => 'egreso',
            'categoria'       => 'gasto_fijo',
            'concepto'        => 'Arriendo julio',
            'montoTotal'      => 300000,
            'fechaMovimiento' => today()->format('Y-m-d'),
            'metodoPago'      => 'transferencia',
        ])->assertRedirect();

        $movimiento = MovimientoFinanciero::where('categoria', 'gasto_fijo')->first();
        $this->assertNotNull($movimiento);
        $this->assertNull($movimiento->idArbitro);
        $this->assertSame('pagado', $movimiento->estadoMovimiento);
        $this->assertSame(0.0, $movimiento->saldoPendiente());

        $abono = $movimiento->abonos->first();
        $this->assertNotNull($abono);
        $this->assertSame('transferencia', $abono->metodoPago);
        $this->assertSame(300000.0, (float) $abono->monto);
    }

    public function test_registra_un_ingreso_de_torneo_vinculado_ya_pagado(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $torneo   = $this->crearTorneo($colegio, $tesorero);

        $this->actingAs($tesorero)->post('/finanzas/gastos-ingresos', [
            'tipoMovimiento'  => 'ingreso',
            'categoria'       => 'ingreso_torneo',
            'concepto'        => 'Pago organizador',
            'montoTotal'      => 500000,
            'fechaMovimiento' => today()->format('Y-m-d'),
            'idTorneo'        => $torneo->idTorneo,
            'metodoPago'      => 'consignacion',
        ])->assertRedirect();

        $movimiento = MovimientoFinanciero::where('categoria', 'ingreso_torneo')->first();
        $this->assertSame($torneo->idTorneo, $movimiento->idTorneo);
        $this->assertSame('pagado', $movimiento->estadoMovimiento);
    }

    public function test_falta_metodo_de_pago_falla_validacion(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $this->actingAs($tesorero)->post('/finanzas/gastos-ingresos', [
            'tipoMovimiento'  => 'egreso',
            'categoria'       => 'gasto_vario',
            'concepto'        => 'Sin método',
            'montoTotal'      => 10000,
            'fechaMovimiento' => today()->format('Y-m-d'),
        ])->assertSessionHasErrors('metodoPago');

        $this->assertSame(0, MovimientoFinanciero::count());
    }

    public function test_no_se_puede_registrar_mensualidad_ni_nomina_desde_este_formulario(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        // mensualidad/multa/nómina son movimientos de árbitro — quedan fuera
        // de la lista blanca institucional a propósito, siguen viviendo en
        // la ficha del árbitro.
        $this->actingAs($tesorero)->post('/finanzas/gastos-ingresos', [
            'tipoMovimiento'  => 'ingreso',
            'categoria'       => 'mensualidad',
            'concepto'        => 'Intento inválido',
            'montoTotal'      => 50000,
            'fechaMovimiento' => today()->format('Y-m-d'),
            'metodoPago'      => 'efectivo',
        ])->assertSessionHasErrors('categoria');

        $this->assertSame(0, MovimientoFinanciero::count());
    }

    public function test_categoria_de_egreso_con_tipo_ingreso_falla_validacion(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $this->actingAs($tesorero)->post('/finanzas/gastos-ingresos', [
            'tipoMovimiento'  => 'ingreso',
            'categoria'       => 'gasto_fijo',
            'concepto'        => 'Combinación inválida',
            'montoTotal'      => 50000,
            'fechaMovimiento' => today()->format('Y-m-d'),
            'metodoPago'      => 'efectivo',
        ])->assertSessionHasErrors('categoria');

        $this->assertSame(0, MovimientoFinanciero::count());
    }

    public function test_el_listado_no_muestra_movimientos_de_arbitro(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $finanzas = app(FinanzasService::class);

        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento'  => 'ingreso',
            'categoria'       => 'mensualidad',
            'concepto'        => 'Cuota árbitro',
            'montoTotal'      => 50000,
            'fechaMovimiento' => today()->format('Y-m-d'),
            'idArbitro'       => $arbitro->idArbitro,
        ], $tesorero);

        $finanzas->registrarMovimientoPagado($colegio->idColegio, [
            'tipoMovimiento'  => 'egreso',
            'categoria'       => 'gasto_fijo',
            'concepto'        => 'Arriendo',
            'montoTotal'      => 200000,
            'fechaMovimiento' => today()->format('Y-m-d'),
            'metodoPago'      => 'efectivo',
        ], $tesorero);

        $response = $this->actingAs($tesorero)->get('/finanzas/gastos-ingresos');

        $response->assertOk();
        $response->assertSee('Arriendo');
        $response->assertDontSee('Cuota árbitro');
    }

    public function test_un_colegio_no_ve_movimientos_institucionales_de_otro(): void
    {
        $colegioA  = $this->crearColegioConFinanzas();
        $colegioB  = $this->crearColegioConFinanzas();
        $tesoreroB = $this->crearTesorero($colegioB);
        $finanzas  = app(FinanzasService::class);

        $finanzas->registrarMovimientoPagado($colegioA->idColegio, [
            'tipoMovimiento'  => 'egreso',
            'categoria'       => 'gasto_fijo',
            'concepto'        => 'Arriendo colegio A',
            'montoTotal'      => 200000,
            'fechaMovimiento' => today()->format('Y-m-d'),
            'metodoPago'      => 'efectivo',
        ], $tesoreroB);

        $response = $this->actingAs($tesoreroB)->get('/finanzas/gastos-ingresos');

        $response->assertOk();
        $response->assertDontSee('Arriendo colegio A');
    }
}
