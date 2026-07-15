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

class CobroMasivoTest extends TestCase
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

    public function test_cobra_mensualidad_a_varios_arbitros(): void
    {
        $colegio   = $this->crearColegioConFinanzas();
        $tesorero  = $this->crearTesorero($colegio);
        $arbitroA  = $this->crearArbitro($colegio);
        $arbitroB  = $this->crearArbitro($colegio);

        $resultado = app(FinanzasService::class)->registrarCobroMasivo($colegio->idColegio, [
            'categoria'       => 'mensualidad',
            'concepto'        => 'Mensualidad julio',
            'fechaMovimiento' => today()->format('Y-m-d'),
            'montoTotal'      => 50000,
            'cargos' => [
                ['idArbitro' => $arbitroA->idArbitro, 'incluir' => true],
                ['idArbitro' => $arbitroB->idArbitro, 'incluir' => true],
            ],
        ], $tesorero);

        $this->assertSame(2, $resultado['totalCreados']);
        $this->assertSame(0, $resultado['totalOmitidos']);
        $this->assertSame(2, MovimientoFinanciero::where('categoria', 'mensualidad')->count());
    }

    public function test_no_duplica_el_cobro_del_mismo_mes_aunque_el_concepto_este_escrito_distinto(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $finanzas = app(FinanzasService::class);

        $finanzas->registrarCobroMasivo($colegio->idColegio, [
            'categoria'       => 'mensualidad',
            'concepto'        => 'Mensualidad julio',
            'fechaMovimiento' => today()->format('Y-m-d'),
            'montoTotal'      => 50000,
            'cargos' => [
                ['idArbitro' => $arbitro->idArbitro, 'incluir' => true],
            ],
        ], $tesorero);

        // Mismo árbitro, misma categoría, mismo mes — pero el texto del
        // concepto es distinto. Antes del fix, esto pasaba de largo y
        // generaba un segundo cobro; ahora debe omitirse por duplicado.
        $resultado = $finanzas->registrarCobroMasivo($colegio->idColegio, [
            'categoria'       => 'mensualidad',
            'concepto'        => 'Mensualidad Julio 2026 (segundo intento)',
            'fechaMovimiento' => today()->format('Y-m-d'),
            'montoTotal'      => 50000,
            'cargos' => [
                ['idArbitro' => $arbitro->idArbitro, 'incluir' => true],
            ],
        ], $tesorero);

        $this->assertSame(0, $resultado['totalCreados']);
        $this->assertSame(1, $resultado['totalOmitidos']);
        $this->assertSame(1, MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)->where('categoria', 'mensualidad')->count());
    }

    public function test_permite_el_mismo_concepto_en_meses_distintos(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $finanzas = app(FinanzasService::class);

        $finanzas->registrarCobroMasivo($colegio->idColegio, [
            'categoria'       => 'mensualidad',
            'concepto'        => 'Mensualidad',
            'fechaMovimiento' => today()->subMonth()->format('Y-m-d'),
            'montoTotal'      => 50000,
            'cargos' => [['idArbitro' => $arbitro->idArbitro, 'incluir' => true]],
        ], $tesorero);

        $resultado = $finanzas->registrarCobroMasivo($colegio->idColegio, [
            'categoria'       => 'mensualidad',
            'concepto'        => 'Mensualidad',
            'fechaMovimiento' => today()->format('Y-m-d'),
            'montoTotal'      => 50000,
            'cargos' => [['idArbitro' => $arbitro->idArbitro, 'incluir' => true]],
        ], $tesorero);

        $this->assertSame(1, $resultado['totalCreados']);
        $this->assertSame(2, MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)->where('categoria', 'mensualidad')->count());
    }

    public function test_la_ruta_de_cobro_masivo_responde_ok_solo_con_ver_finanzas(): void
    {
        // El permiso de escritura solo debe exigirse en el store, no en el
        // index — antes exigía crear-finanzas para todo el grupo. rolUsuario
        // es un enum de BD fijo, así que se usa un valor válido existente
        // ("tesorero") y se le quita crear-finanzas vía Spatie, que es lo
        // que realmente controla la ruta.
        foreach (['ver-finanzas', 'crear-finanzas'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }
        $rol = Role::firstOrCreate(['name' => 'solo-ver-finanzas', 'guard_name' => 'web']);
        $rol->syncPermissions(['ver-finanzas']);

        $colegio = $this->crearColegioConFinanzas();
        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'tesorero']);
        $usuario->assignRole('solo-ver-finanzas');

        $this->actingAs($usuario)->get('/finanzas/cobro-masivo')->assertOk();
    }
}
