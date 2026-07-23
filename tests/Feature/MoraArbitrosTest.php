<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\User;
use App\Services\FinanzasService;
use App\Services\BalanceFinanzasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class MoraArbitrosTest extends TestCase
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

    public function test_arbitro_sin_deuda_no_aparece_en_la_mora(): void
    {
        $colegio = $this->crearColegioConFinanzas();
        $this->crearArbitro($colegio);

        $balance = app(BalanceFinanzasService::class)->balanceGeneral($colegio->idColegio);
        $mora    = app(BalanceFinanzasService::class)->moraDesdeBalance($balance);

        $this->assertCount(0, $mora);
    }

    public function test_deuda_de_45_dias_cae_en_el_bucket_31_60(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $arbitro  = $this->crearArbitro($colegio);
        $finanzas = app(FinanzasService::class);

        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento'  => 'ingreso',
            'categoria'       => 'mensualidad',
            'concepto'        => 'Mensualidad',
            'montoTotal'      => 20000,
            'fechaMovimiento' => today()->subDays(45)->format('Y-m-d'),
            'idArbitro'       => $arbitro->idArbitro,
        ], null);

        $balance = app(BalanceFinanzasService::class)->balanceGeneral($colegio->idColegio);
        $mora    = app(BalanceFinanzasService::class)->moraDesdeBalance($balance);

        $this->assertCount(1, $mora);
        $this->assertSame(20000.0, $mora->first()['nosDebe']);
        $this->assertSame('31-60', $mora->first()['bucket']);
        $this->assertSame(45, $mora->first()['diasMora']);
    }

    public function test_ordena_descendente_por_antiguedad(): void
    {
        $colegio    = $this->crearColegioConFinanzas();
        $arbitroA   = $this->crearArbitro($colegio);
        $arbitroB   = $this->crearArbitro($colegio);
        $finanzas   = app(FinanzasService::class);

        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'mensualidad', 'concepto' => 'Mensualidad',
            'montoTotal' => 20000, 'fechaMovimiento' => today()->subDays(10)->format('Y-m-d'),
            'idArbitro' => $arbitroA->idArbitro,
        ], null);

        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'multa', 'concepto' => 'Multa',
            'montoTotal' => 10000, 'fechaMovimiento' => today()->subDays(95)->format('Y-m-d'),
            'idArbitro' => $arbitroB->idArbitro,
        ], null);

        $balance = app(BalanceFinanzasService::class)->balanceGeneral($colegio->idColegio);
        $mora    = app(BalanceFinanzasService::class)->moraDesdeBalance($balance);

        $this->assertCount(2, $mora);
        $this->assertSame($arbitroB->idArbitro, $mora->first()['arbitro']->idArbitro);
        $this->assertSame('90+', $mora->first()['bucket']);
        $this->assertSame('0-30', $mora->last()['bucket']);
    }

    public function test_la_ruta_de_mora_responde_ok(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $this->actingAs($tesorero)->get('/finanzas/mora')->assertOk();
    }
}
