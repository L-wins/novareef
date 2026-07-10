<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Arbitro;
use App\Models\Colegio;
use App\Models\User;
use App\Services\FinanzasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class ComprobantePagoTest extends TestCase
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

    /**
     * Nómina de 80000 + multa de 20000 compensada → neto 60000, con lote.
     */
    private function pagarAcumulado(Colegio $colegio, Arbitro $arbitro, User $tesorero): string
    {
        Queue::fake();
        $finanzas = app(FinanzasService::class);

        $nomina = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'egreso', 'categoria' => 'nomina_arbitro',
            'concepto' => 'Partido de prueba', 'montoTotal' => 80000,
            'fechaMovimiento' => today()->format('Y-m-d'), 'idArbitro' => $arbitro->idArbitro,
        ], null);

        $multa = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'multa',
            'concepto' => 'Multa de prueba', 'montoTotal' => 20000,
            'fechaMovimiento' => today()->format('Y-m-d'), 'idArbitro' => $arbitro->idArbitro,
            'tipoOrigenMulta' => 'manual',
        ], null);

        $resultado = $finanzas->pagarAcumuladoArbitro(
            $arbitro,
            [$nomina->idMovimiento],
            [$multa->idMovimiento],
            ['fecha' => today()->format('Y-m-d'), 'metodoPago' => 'transferencia', 'referencia' => 'REF-123'],
            $tesorero,
        );

        return $resultado['idLotePago'];
    }

    public function test_el_tesorero_descarga_el_comprobante_de_su_colegio(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);

        $lote = $this->pagarAcumulado($colegio, $arbitro, $tesorero);

        $respuesta = $this->actingAs($tesorero)
            ->get("/finanzas/pagos-arbitro/comprobante/{$lote}");

        $respuesta->assertOk();
        $this->assertSame('application/pdf', $respuesta->headers->get('Content-Type'));
    }

    public function test_un_tesorero_de_otro_colegio_no_puede_descargar_el_comprobante(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $lote     = $this->pagarAcumulado($colegio, $arbitro, $tesorero);

        $otroColegio  = $this->crearColegioConFinanzas();
        $otroTesorero = $this->crearTesorero($otroColegio);

        $this->actingAs($otroTesorero)
            ->get("/finanzas/pagos-arbitro/comprobante/{$lote}")
            ->assertNotFound();
    }

    public function test_el_arbitro_descarga_su_propio_comprobante(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $lote     = $this->pagarAcumulado($colegio, $arbitro, $tesorero);

        $respuesta = $this->actingAs($arbitro->usuario)
            ->get("/mi-estado-cuenta/comprobante/{$lote}");

        $respuesta->assertOk();
        $this->assertSame('application/pdf', $respuesta->headers->get('Content-Type'));
    }

    public function test_un_arbitro_no_descarga_comprobantes_ajenos(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $otro     = $this->crearArbitro($colegio);
        $lote     = $this->pagarAcumulado($colegio, $arbitro, $tesorero);

        $this->actingAs($otro->usuario)
            ->get("/mi-estado-cuenta/comprobante/{$lote}")
            ->assertForbidden();
    }

    public function test_el_estado_de_cuenta_enlaza_el_comprobante(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $lote     = $this->pagarAcumulado($colegio, $arbitro, $tesorero);

        $this->actingAs($arbitro->usuario)
            ->get('/mi-estado-cuenta')
            ->assertOk()
            ->assertSee("/mi-estado-cuenta/comprobante/{$lote}", false);
    }
}
