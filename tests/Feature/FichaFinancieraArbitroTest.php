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

class FichaFinancieraArbitroTest extends TestCase
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

    public function test_el_tesorero_ve_la_ficha_de_un_arbitro_de_su_colegio(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);

        $this->actingAs($tesorero)
            ->get("/finanzas/arbitro/{$arbitro->idArbitro}")
            ->assertOk();
    }

    public function test_registra_un_cargo_de_mensualidad_al_arbitro(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);

        $this->actingAs($tesorero)->post("/finanzas/arbitro/{$arbitro->idArbitro}/cargos", [
            'categoria'       => 'mensualidad',
            'concepto'        => 'Mensualidad julio',
            'montoTotal'      => 50000,
            'fechaMovimiento' => today()->format('Y-m-d'),
        ])->assertRedirect();

        $movimiento = MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)->first();
        $this->assertNotNull($movimiento);
        $this->assertSame('mensualidad', $movimiento->categoria);
        $this->assertSame('pendiente', $movimiento->estadoMovimiento);
    }

    public function test_no_se_puede_registrar_nomina_como_cargo_manual(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);

        // nomina_arbitro no está en la lista blanca del Request — es
        // automática (finalización de partido) o vía pago acumulado, nunca
        // un cargo suelto creado a mano desde la ficha.
        $this->actingAs($tesorero)->post("/finanzas/arbitro/{$arbitro->idArbitro}/cargos", [
            'categoria'       => 'nomina_arbitro',
            'concepto'        => 'Intento inválido',
            'montoTotal'      => 50000,
            'fechaMovimiento' => today()->format('Y-m-d'),
        ])->assertSessionHasErrors('categoria');

        $this->assertSame(0, MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)->count());
    }

    public function test_abonar_un_cargo_reduce_el_saldo_pendiente(): void
    {
        $colegio    = $this->crearColegioConFinanzas();
        $tesorero   = $this->crearTesorero($colegio);
        $arbitro    = $this->crearArbitro($colegio);
        $finanzas   = app(FinanzasService::class);

        $movimiento = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento'  => 'ingreso',
            'categoria'       => 'mensualidad',
            'concepto'        => 'Mensualidad julio',
            'montoTotal'      => 50000,
            'fechaMovimiento' => today()->format('Y-m-d'),
            'idArbitro'       => $arbitro->idArbitro,
        ], $tesorero);

        $this->actingAs($tesorero)->post("/finanzas/arbitro/{$arbitro->idArbitro}/cargos/{$movimiento->idMovimiento}/abonos", [
            'monto'      => 50000,
            'fechaAbono' => today()->format('Y-m-d'),
            'metodoPago' => 'efectivo',
        ])->assertRedirect();

        $movimiento->refresh();
        $this->assertSame('pagado', $movimiento->estadoMovimiento);
        $this->assertSame(0.0, $movimiento->saldoPendiente());
    }

    public function test_anular_un_cargo_sin_abonos(): void
    {
        $colegio    = $this->crearColegioConFinanzas();
        $tesorero   = $this->crearTesorero($colegio);
        $arbitro    = $this->crearArbitro($colegio);
        $finanzas   = app(FinanzasService::class);

        $movimiento = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento'  => 'ingreso',
            'categoria'       => 'otro_ingreso',
            'concepto'        => 'Duplicado por error',
            'montoTotal'      => 5000,
            'fechaMovimiento' => today()->format('Y-m-d'),
            'idArbitro'       => $arbitro->idArbitro,
        ], $tesorero);

        $this->actingAs($tesorero)
            ->put("/finanzas/arbitro/{$arbitro->idArbitro}/cargos/{$movimiento->idMovimiento}/anular")
            ->assertRedirect();

        $movimiento->refresh();
        $this->assertSame('anulado', $movimiento->estadoMovimiento);
    }

    public function test_un_colegio_no_puede_ver_ni_actuar_sobre_un_arbitro_de_otro_colegio(): void
    {
        $colegioA  = $this->crearColegioConFinanzas();
        $colegioB  = $this->crearColegioConFinanzas();
        $tesoreroB = $this->crearTesorero($colegioB);
        $arbitroA  = $this->crearArbitro($colegioA);

        $this->actingAs($tesoreroB)
            ->get("/finanzas/arbitro/{$arbitroA->idArbitro}")
            ->assertNotFound();

        $this->actingAs($tesoreroB)->post("/finanzas/arbitro/{$arbitroA->idArbitro}/cargos", [
            'categoria'       => 'otro_ingreso',
            'concepto'        => 'Intento cruzado',
            'montoTotal'      => 1000,
            'fechaMovimiento' => today()->format('Y-m-d'),
        ])->assertNotFound();
    }
}
