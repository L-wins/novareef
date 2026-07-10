<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\PagoArbitroRealizadoMail;
use App\Models\Colegio;
use App\Models\MovimientoFinanciero;
use App\Models\User;
use App\Services\FinanzasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class PagoAcumuladoArbitroTest extends TestCase
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

    public function test_paga_dos_pendientes_de_nomina_sin_deudas_a_netear(): void
    {
        Mail::fake();

        $colegio  = $this->crearColegioConFinanzas();
        $arbitro  = $this->crearArbitro($colegio);
        $tesorero = $this->crearTesorero($colegio);
        $finanzas = app(FinanzasService::class);

        $mov1 = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'egreso', 'categoria' => 'nomina_arbitro',
            'concepto' => 'Partido 1', 'montoTotal' => 60000,
            'fechaMovimiento' => today()->format('Y-m-d'), 'idArbitro' => $arbitro->idArbitro,
        ], null);
        $mov2 = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'egreso', 'categoria' => 'nomina_arbitro',
            'concepto' => 'Partido 2', 'montoTotal' => 40000,
            'fechaMovimiento' => today()->format('Y-m-d'), 'idArbitro' => $arbitro->idArbitro,
        ], null);

        $response = $this->actingAs($tesorero)->post('/finanzas/pagos-arbitro', [
            'idArbitro'             => $arbitro->idArbitro,
            'idsMovimientosNomina'  => [$mov1->idMovimiento, $mov2->idMovimiento],
            'fecha'                 => today()->format('Y-m-d'),
            'metodoPago'            => 'transferencia',
        ]);

        $response->assertRedirect();

        $mov1->refresh();
        $mov2->refresh();
        $this->assertSame('pagado', $mov1->estadoMovimiento);
        $this->assertSame('pagado', $mov2->estadoMovimiento);
        $this->assertSame(0.0, $mov1->saldoPendiente());
        $this->assertSame(0.0, $mov2->saldoPendiente());

        Mail::assertSent(PagoArbitroRealizadoMail::class, fn ($mail) => $mail->netoDesembolsado === 100000.0);
    }

    public function test_neteo_de_una_multa_reduce_el_efectivo_pagado_al_arbitro(): void
    {
        Mail::fake();

        $colegio  = $this->crearColegioConFinanzas();
        $arbitro  = $this->crearArbitro($colegio);
        $tesorero = $this->crearTesorero($colegio);
        $finanzas = app(FinanzasService::class);

        $nomina = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'egreso', 'categoria' => 'nomina_arbitro',
            'concepto' => 'Partido 1', 'montoTotal' => 60000,
            'fechaMovimiento' => today()->format('Y-m-d'), 'idArbitro' => $arbitro->idArbitro,
        ], null);
        $multa = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'multa',
            'concepto' => 'Inasistencia', 'montoTotal' => 15000,
            'fechaMovimiento' => today()->format('Y-m-d'), 'idArbitro' => $arbitro->idArbitro,
            'tipoOrigenMulta' => MovimientoFinanciero::ORIGEN_MULTA_MANUAL,
        ], null);

        $response = $this->actingAs($tesorero)->post('/finanzas/pagos-arbitro', [
            'idArbitro'             => $arbitro->idArbitro,
            'idsMovimientosNomina'  => [$nomina->idMovimiento],
            'idsDeudasNetear'       => [$multa->idMovimiento],
            'fecha'                 => today()->format('Y-m-d'),
            'metodoPago'            => 'efectivo',
        ]);

        $response->assertRedirect();

        $nomina->refresh();
        $multa->refresh();

        $this->assertSame('pagado', $nomina->estadoMovimiento);
        $this->assertSame('pagado', $multa->estadoMovimiento);

        // De los 60000 de nómina, 15000 se compensaron contra la multa y 45000 se pagaron en efectivo real.
        $abonoEfectivo = $nomina->abonos()->where('metodoPago', 'efectivo')->first();
        $abonoCompensacion = $nomina->abonos()->where('metodoPago', 'compensacion_nomina')->first();

        $this->assertNotNull($abonoEfectivo);
        $this->assertNotNull($abonoCompensacion);
        $this->assertSame(45000.0, (float) $abonoEfectivo->monto);
        $this->assertSame(15000.0, (float) $abonoCompensacion->monto);

        Mail::assertSent(PagoArbitroRealizadoMail::class, fn ($mail) => $mail->netoDesembolsado === 45000.0 && $mail->totalDeudasNeteadas === 15000.0);
    }

    public function test_no_se_puede_netear_mas_deuda_que_el_saldo_de_nomina_seleccionado(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $arbitro  = $this->crearArbitro($colegio);
        $tesorero = $this->crearTesorero($colegio);
        $finanzas = app(FinanzasService::class);

        $nomina = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'egreso', 'categoria' => 'nomina_arbitro',
            'concepto' => 'Partido 1', 'montoTotal' => 10000,
            'fechaMovimiento' => today()->format('Y-m-d'), 'idArbitro' => $arbitro->idArbitro,
        ], null);
        $multa = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'multa',
            'concepto' => 'Inasistencia', 'montoTotal' => 50000,
            'fechaMovimiento' => today()->format('Y-m-d'), 'idArbitro' => $arbitro->idArbitro,
            'tipoOrigenMulta' => MovimientoFinanciero::ORIGEN_MULTA_MANUAL,
        ], null);

        $response = $this->actingAs($tesorero)->post('/finanzas/pagos-arbitro', [
            'idArbitro'             => $arbitro->idArbitro,
            'idsMovimientosNomina'  => [$nomina->idMovimiento],
            'idsDeudasNetear'       => [$multa->idMovimiento],
            'fecha'                 => today()->format('Y-m-d'),
            'metodoPago'            => 'efectivo',
        ]);

        $response->assertRedirect();

        $nomina->refresh();
        $multa->refresh();
        $this->assertSame('pendiente', $nomina->estadoMovimiento);
        $this->assertSame('pendiente', $multa->estadoMovimiento);
    }

    public function test_un_tesorero_no_puede_pagar_a_un_arbitro_de_otro_colegio(): void
    {
        $colegioA  = $this->crearColegioConFinanzas();
        $colegioB  = $this->crearColegioConFinanzas();
        $tesoreroA = $this->crearTesorero($colegioA);
        $arbitroB  = $this->crearArbitro($colegioB);
        $finanzas  = app(FinanzasService::class);

        $movB = $finanzas->registrarMovimiento($colegioB->idColegio, [
            'tipoMovimiento' => 'egreso', 'categoria' => 'nomina_arbitro',
            'concepto' => 'Partido de B', 'montoTotal' => 10000,
            'fechaMovimiento' => today()->format('Y-m-d'), 'idArbitro' => $arbitroB->idArbitro,
        ], null);

        $response = $this->actingAs($tesoreroA)->post('/finanzas/pagos-arbitro', [
            'idArbitro'             => $arbitroB->idArbitro,
            'idsMovimientosNomina'  => [$movB->idMovimiento],
            'fecha'                 => today()->format('Y-m-d'),
            'metodoPago'            => 'efectivo',
        ]);

        $response->assertNotFound();

        $movB->refresh();
        $this->assertSame('pendiente', $movB->estadoMovimiento);
    }
}
