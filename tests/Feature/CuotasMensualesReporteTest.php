<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\User;
use App\Services\FinanzasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class CuotasMensualesReporteTest extends TestCase
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

    public function test_arbitro_sin_movimiento_del_mes_aparece_como_no_generada(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $this->crearArbitro($colegio);

        $response = $this->actingAs($tesorero)->get('/finanzas/cuotas');

        $response->assertOk();
        $response->assertSee('No generada');
    }

    public function test_arbitro_con_mensualidad_pagada_muestra_estado_pagado(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $finanzas = app(FinanzasService::class);

        $movimiento = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'mensualidad',
            'concepto' => 'Mensualidad', 'montoTotal' => 20000,
            'fechaMovimiento' => today()->format('Y-m-d'), 'idArbitro' => $arbitro->idArbitro,
        ], null);
        $finanzas->registrarAbono($movimiento, ['monto' => 20000, 'fechaAbono' => today()->format('Y-m-d'), 'metodoPago' => 'efectivo'], $tesorero);

        $response = $this->actingAs($tesorero)->get('/finanzas/cuotas?mes=' . today()->format('Y-m'));

        $response->assertOk();
        $response->assertSee('Pagado');
    }
}
