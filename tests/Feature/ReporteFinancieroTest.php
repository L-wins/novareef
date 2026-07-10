<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\User;
use App\Services\FinanzasService;
use App\Services\ReporteFinanzasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class ReporteFinancieroTest extends TestCase
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

    public function test_calcula_totales_y_desglose_por_categoria_dentro_del_rango(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $finanzas = app(FinanzasService::class);

        // Dentro del rango
        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'mensualidad',
            'concepto' => 'Cuota', 'montoTotal' => 50000, 'fechaMovimiento' => '2026-06-10',
        ], null);
        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'egreso', 'categoria' => 'gasto_fijo',
            'concepto' => 'Arriendo', 'montoTotal' => 30000, 'fechaMovimiento' => '2026-06-15',
        ], null);

        // Fuera del rango (mes siguiente) — no debe contarse
        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'otro_ingreso',
            'concepto' => 'Fuera de rango', 'montoTotal' => 99999, 'fechaMovimiento' => '2026-07-05',
        ], null);

        // Anulado dentro del rango — no debe contarse
        $anulado = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'egreso', 'categoria' => 'gasto_vario',
            'concepto' => 'Anulado', 'montoTotal' => 15000, 'fechaMovimiento' => '2026-06-20',
        ], null);
        $tesorero = $this->crearTesorero($colegio);
        $finanzas->anularMovimiento($anulado, $tesorero);

        $reporte = app(ReporteFinanzasService::class)->reporte($colegio->idColegio, '2026-06-01', '2026-06-30');

        $this->assertSame(50000.0, $reporte['totalIngresos']);
        $this->assertSame(30000.0, $reporte['totalEgresos']);
        $this->assertSame(20000.0, $reporte['neto']);
        $this->assertCount(2, $reporte['porCategoria']);
    }

    public function test_la_ruta_de_reportes_usa_el_mes_actual_por_defecto(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $this->actingAs($tesorero)->get('/finanzas/reportes')->assertOk();
    }

    public function test_rechaza_un_rango_con_hasta_anterior_a_desde(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $response = $this->actingAs($tesorero)->get('/finanzas/reportes?desde=2026-06-15&hasta=2026-06-01');

        $response->assertRedirect();
        $response->assertSessionHasErrors('hasta');
    }
}
