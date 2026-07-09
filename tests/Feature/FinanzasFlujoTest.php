<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\MovimientoFinanciero;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class FinanzasFlujoTest extends TestCase
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

    public function test_lista_movimientos_financieros_del_colegio(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $this->actingAs($tesorero)->get('/finanzas')->assertOk();
    }

    public function test_registra_un_movimiento_de_mensualidad(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $response = $this->actingAs($tesorero)->post('/finanzas', [
            'tipoMovimiento'  => 'ingreso',
            'categoria'       => 'mensualidad',
            'concepto'        => 'Mensualidad julio',
            'montoTotal'      => 50000,
            'fechaMovimiento' => today()->format('Y-m-d'),
        ]);

        $response->assertRedirect();

        $movimiento = MovimientoFinanciero::where('idColegio', $colegio->idColegio)->first();
        $this->assertNotNull($movimiento);
        $this->assertSame('pendiente', $movimiento->estadoMovimiento);
        $this->assertSame(50000.0, (float) $movimiento->montoTotal);
    }

    public function test_no_se_puede_registrar_una_categoria_que_no_corresponde_al_tipo(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $response = $this->actingAs($tesorero)->post('/finanzas', [
            'tipoMovimiento'  => 'ingreso',
            'categoria'       => 'nomina_arbitro', // categoría de egreso, no de ingreso
            'concepto'        => 'Inválido',
            'montoTotal'      => 1000,
            'fechaMovimiento' => today()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $this->assertSame(0, MovimientoFinanciero::where('idColegio', $colegio->idColegio)->count());
    }

    public function test_registrar_un_abono_reduce_el_saldo_pendiente_y_actualiza_el_estado(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $this->actingAs($tesorero)->post('/finanzas', [
            'tipoMovimiento'  => 'egreso',
            'categoria'       => 'gasto_fijo',
            'concepto'        => 'Arriendo julio',
            'montoTotal'      => 100000,
            'fechaMovimiento' => today()->format('Y-m-d'),
        ]);

        $movimiento = MovimientoFinanciero::where('idColegio', $colegio->idColegio)->firstOrFail();

        $this->actingAs($tesorero)->post("/finanzas/{$movimiento->idMovimiento}/abonos", [
            'monto'      => 40000,
            'fechaAbono' => today()->format('Y-m-d'),
            'metodoPago' => 'transferencia',
        ])->assertRedirect();

        $movimiento->refresh();
        $this->assertSame('parcial', $movimiento->estadoMovimiento);
        $this->assertSame(60000.0, $movimiento->saldoPendiente());

        $this->actingAs($tesorero)->post("/finanzas/{$movimiento->idMovimiento}/abonos", [
            'monto'      => 60000,
            'fechaAbono' => today()->format('Y-m-d'),
            'metodoPago' => 'efectivo',
        ])->assertRedirect();

        $movimiento->refresh();
        $this->assertSame('pagado', $movimiento->estadoMovimiento);
        $this->assertSame(0.0, $movimiento->saldoPendiente());
    }

    public function test_no_se_puede_abonar_mas_del_saldo_pendiente(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $this->actingAs($tesorero)->post('/finanzas', [
            'tipoMovimiento'  => 'ingreso',
            'categoria'       => 'otro_ingreso',
            'concepto'        => 'Donación',
            'montoTotal'      => 10000,
            'fechaMovimiento' => today()->format('Y-m-d'),
        ]);

        $movimiento = MovimientoFinanciero::where('idColegio', $colegio->idColegio)->firstOrFail();

        $this->actingAs($tesorero)->post("/finanzas/{$movimiento->idMovimiento}/abonos", [
            'monto'      => 20000,
            'fechaAbono' => today()->format('Y-m-d'),
            'metodoPago' => 'efectivo',
        ])->assertRedirect();

        $movimiento->refresh();
        $this->assertSame(0, $movimiento->abonos()->count());
        $this->assertSame('pendiente', $movimiento->estadoMovimiento);
    }

    public function test_anular_un_movimiento_sin_abonos(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $this->actingAs($tesorero)->post('/finanzas', [
            'tipoMovimiento'  => 'egreso',
            'categoria'       => 'gasto_vario',
            'concepto'        => 'Duplicado por error',
            'montoTotal'      => 5000,
            'fechaMovimiento' => today()->format('Y-m-d'),
        ]);

        $movimiento = MovimientoFinanciero::where('idColegio', $colegio->idColegio)->firstOrFail();

        $this->actingAs($tesorero)->put("/finanzas/{$movimiento->idMovimiento}/anular")->assertRedirect();

        $movimiento->refresh();
        $this->assertSame('anulado', $movimiento->estadoMovimiento);
    }

    public function test_no_se_puede_anular_un_movimiento_que_ya_tiene_abonos(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $this->actingAs($tesorero)->post('/finanzas', [
            'tipoMovimiento'  => 'egreso',
            'categoria'       => 'gasto_vario',
            'concepto'        => 'Con abono',
            'montoTotal'      => 5000,
            'fechaMovimiento' => today()->format('Y-m-d'),
        ]);

        $movimiento = MovimientoFinanciero::where('idColegio', $colegio->idColegio)->firstOrFail();

        $this->actingAs($tesorero)->post("/finanzas/{$movimiento->idMovimiento}/abonos", [
            'monto'      => 5000,
            'fechaAbono' => today()->format('Y-m-d'),
            'metodoPago' => 'efectivo',
        ]);

        $this->actingAs($tesorero)->put("/finanzas/{$movimiento->idMovimiento}/anular")->assertRedirect();

        $movimiento->refresh();
        $this->assertSame('pagado', $movimiento->estadoMovimiento);
    }

    public function test_un_colegio_no_puede_ver_movimientos_de_otro_colegio(): void
    {
        $colegioA  = $this->crearColegioConFinanzas();
        $colegioB  = $this->crearColegioConFinanzas();
        $tesoreroA = $this->crearTesorero($colegioA);
        $tesoreroB = $this->crearTesorero($colegioB);

        $this->actingAs($tesoreroA)->post('/finanzas', [
            'tipoMovimiento'  => 'ingreso',
            'categoria'       => 'otro_ingreso',
            'concepto'        => 'Solo de A',
            'montoTotal'      => 1000,
            'fechaMovimiento' => today()->format('Y-m-d'),
        ]);

        $movimiento = MovimientoFinanciero::where('idColegio', $colegioA->idColegio)->firstOrFail();

        $this->actingAs($tesoreroB)->get("/finanzas/{$movimiento->idMovimiento}")->assertForbidden();
    }
}
