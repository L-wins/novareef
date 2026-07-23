<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\MovimientoFinanciero;
use App\Models\User;
use App\Services\FinanzasService;
use App\Services\BalanceFinanzasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class BalanceFinancieroTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function crearTesorero(Colegio $colegio): User
    {
        foreach (['ver-finanzas', 'crear-finanzas'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $rol = Role::firstOrCreate(['name' => 'tesorero', 'guard_name' => 'web']);
        $rol->syncPermissions(['ver-finanzas', 'crear-finanzas']);

        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'tesorero']);
        $usuario->assignRole('tesorero');

        return $usuario;
    }

    private function crearColegioConFinanzas(): Colegio
    {
        return $this->crearColegio($this->crearPlan(['modulosJSON' => ['arbitros', 'torneos', 'designaciones', 'finanzas']]));
    }

    public function test_calcula_el_saldo_en_caja_excluyendo_compensaciones(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $finanzas = app(FinanzasService::class);

        $ingreso = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'mensualidad',
            'concepto' => 'Cuota', 'montoTotal' => 50000, 'fechaMovimiento' => today()->format('Y-m-d'),
        ], null);
        $finanzas->registrarAbono($ingreso, ['monto' => 50000, 'fechaAbono' => today()->format('Y-m-d'), 'metodoPago' => 'efectivo'], $tesorero);

        $egreso = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'egreso', 'categoria' => 'gasto_fijo',
            'concepto' => 'Arriendo', 'montoTotal' => 20000, 'fechaMovimiento' => today()->format('Y-m-d'),
        ], null);
        $finanzas->registrarAbono($egreso, ['monto' => 20000, 'fechaAbono' => today()->format('Y-m-d'), 'metodoPago' => 'pago_digital'], $tesorero);

        // Movimiento pendiente (sin abonar) — no debe afectar el saldo en caja.
        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'otro_ingreso',
            'concepto' => 'Pendiente', 'montoTotal' => 99999, 'fechaMovimiento' => today()->format('Y-m-d'),
        ], null);

        $balance = app(BalanceFinanzasService::class)->balanceGeneral($colegio->idColegio);

        $this->assertSame(30000.0, $balance['saldoEnCaja']);
    }

    public function test_calcula_le_debemos_y_nos_debe_por_arbitro_e_incluye_arbitros_sin_saldo(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $arbitroA = $this->crearArbitro($colegio);
        $arbitroB = $this->crearArbitro($colegio); // sin movimientos — debe aparecer en ceros, es el acceso a su ficha
        $finanzas = app(FinanzasService::class);

        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'egreso', 'categoria' => 'nomina_arbitro',
            'concepto' => 'Partido 1', 'montoTotal' => 60000, 'fechaMovimiento' => today()->format('Y-m-d'),
            'idArbitro' => $arbitroA->idArbitro,
        ], null);

        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'multa',
            'concepto' => 'Inasistencia', 'montoTotal' => 15000, 'fechaMovimiento' => today()->format('Y-m-d'),
            'idArbitro' => $arbitroA->idArbitro, 'tipoOrigenMulta' => MovimientoFinanciero::ORIGEN_MULTA_MANUAL,
        ], null);

        $balance = app(BalanceFinanzasService::class)->balanceGeneral($colegio->idColegio);

        $this->assertCount(2, $balance['porArbitro']);

        $fila = $balance['porArbitro']->first();
        $this->assertSame($arbitroA->idArbitro, $fila['arbitro']->idArbitro);
        $this->assertSame(60000.0, $fila['leDebemos']);
        $this->assertSame(15000.0, $fila['nosDebe']);

        $filaB = $balance['porArbitro']->first(fn ($f) => $f['arbitro']->idArbitro === $arbitroB->idArbitro);
        $this->assertSame(0.0, $filaB['leDebemos']);
        $this->assertSame(0.0, $filaB['nosDebe']);

        $this->assertSame(60000.0, $balance['totalLeDebemos']);
        $this->assertSame(15000.0, $balance['totalNosDeben']);
    }

    public function test_la_ruta_de_balance_responde_ok(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $this->actingAs($tesorero)->get('/finanzas/balance')->assertOk();
    }
}
