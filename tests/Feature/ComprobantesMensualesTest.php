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

class ComprobantesMensualesTest extends TestCase
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

    public function test_lista_un_cobro_de_mensualidad_del_mes(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $finanzas = app(FinanzasService::class);

        $finanzas->registrarCobroMasivo($colegio->idColegio, [
            'categoria'       => 'mensualidad',
            'concepto'        => 'Mensualidad',
            'fechaMovimiento' => today()->format('Y-m-d'),
            'montoTotal'      => 20000,
            'cargos' => [
                ['idArbitro' => $arbitro->idArbitro, 'incluir' => true, 'yaPago' => true, 'metodoPago' => 'efectivo'],
            ],
        ], $tesorero);

        $response = $this->actingAs($tesorero)->get('/finanzas/comprobantes?mes=' . today()->format('Y-m'));

        $response->assertOk();
        $response->assertSee('Cobro de mensualidad');
    }

    public function test_lista_un_pago_de_nomina_del_mes(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $finanzas = app(FinanzasService::class);

        $nomina = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'egreso', 'categoria' => 'nomina_arbitro',
            'concepto' => 'Partido', 'montoTotal' => 60000,
            'fechaMovimiento' => today()->format('Y-m-d'), 'idArbitro' => $arbitro->idArbitro,
        ], null);

        $finanzas->pagarNominaArbitro($arbitro, [$nomina->idMovimiento], [
            'fecha' => today()->format('Y-m-d'), 'metodoPago' => 'efectivo',
        ], $tesorero);

        $response = $this->actingAs($tesorero)->get('/finanzas/comprobantes?mes=' . today()->format('Y-m'));

        $response->assertOk();
        $response->assertSee('Pago de nómina');
    }

    public function test_mes_sin_comprobantes_muestra_estado_vacio(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $response = $this->actingAs($tesorero)->get('/finanzas/comprobantes?mes=' . today()->subYear()->format('Y-m'));

        $response->assertOk();
        $response->assertSee('No hay comprobantes registrados en este mes.');
    }
}
