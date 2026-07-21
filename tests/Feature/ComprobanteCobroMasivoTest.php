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

class ComprobanteCobroMasivoTest extends TestCase
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

    public function test_cobro_masivo_con_ya_pago_genera_idLotePago_descargable(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $finanzas = app(FinanzasService::class);

        $resultado = $finanzas->registrarCobroMasivo($colegio->idColegio, [
            'categoria'       => 'mensualidad',
            'concepto'        => 'Mensualidad julio',
            'fechaMovimiento' => today()->format('Y-m-d'),
            'montoTotal'      => 50000,
            'cargos' => [
                [
                    'idArbitro'  => $arbitro->idArbitro,
                    'incluir'    => true,
                    'yaPago'     => true,
                    'metodoPago' => 'efectivo',
                ],
            ],
        ], $tesorero);

        $this->assertNotEmpty($resultado['idLotePago']);

        $response = $this->actingAs($tesorero)
            ->get("/finanzas/arbitro/{$arbitro->idArbitro}/comprobante-cobro/{$resultado['idLotePago']}");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_un_arbitro_fuera_del_lote_no_puede_ver_el_comprobante(): void
    {
        $colegio   = $this->crearColegioConFinanzas();
        $tesorero  = $this->crearTesorero($colegio);
        $arbitroA  = $this->crearArbitro($colegio);
        $arbitroB  = $this->crearArbitro($colegio);
        $finanzas  = app(FinanzasService::class);

        $resultado = $finanzas->registrarCobroMasivo($colegio->idColegio, [
            'categoria'       => 'mensualidad',
            'concepto'        => 'Mensualidad julio',
            'fechaMovimiento' => today()->format('Y-m-d'),
            'montoTotal'      => 50000,
            'cargos' => [
                [
                    'idArbitro'  => $arbitroA->idArbitro,
                    'incluir'    => true,
                    'yaPago'     => true,
                    'metodoPago' => 'efectivo',
                ],
            ],
        ], $tesorero);

        $response = $this->actingAs($tesorero)
            ->get("/finanzas/arbitro/{$arbitroB->idArbitro}/comprobante-cobro/{$resultado['idLotePago']}");

        $response->assertNotFound();
    }

    public function test_cobro_sin_yapago_no_genera_comprobante(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $finanzas = app(FinanzasService::class);

        $resultado = $finanzas->registrarCobroMasivo($colegio->idColegio, [
            'categoria'       => 'mensualidad',
            'concepto'        => 'Mensualidad julio',
            'fechaMovimiento' => today()->format('Y-m-d'),
            'montoTotal'      => 50000,
            'cargos' => [
                ['idArbitro' => $arbitro->idArbitro, 'incluir' => true],
            ],
        ], $tesorero);

        $response = $this->actingAs($tesorero)
            ->get("/finanzas/arbitro/{$arbitro->idArbitro}/comprobante-cobro/{$resultado['idLotePago']}");

        $response->assertNotFound();
    }
}
