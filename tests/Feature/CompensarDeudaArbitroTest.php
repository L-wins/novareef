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

/**
 * Compensar una deuda (mensualidad/multa) contra la nómina pendiente del
 * árbitro — reemplaza el neteo de la vieja vista de "pago acumulado".
 * A diferencia de esa, nunca lanza error por falta de nómina: compensa lo
 * que hay disponible y deja el resto de la deuda pendiente.
 */
class CompensarDeudaArbitroTest extends TestCase
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

    public function test_compensa_completo_cuando_la_nomina_alcanza(): void
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

        $this->actingAs($tesorero)
            ->post("/finanzas/arbitro/{$arbitro->idArbitro}/cargos/{$multa->idMovimiento}/compensar")
            ->assertRedirect();

        $nomina->refresh();
        $multa->refresh();

        $this->assertSame('pagado', $multa->estadoMovimiento);
        $this->assertSame('parcial', $nomina->estadoMovimiento);
        $this->assertSame(45000.0, $nomina->saldoPendiente());

        $abonoCompensacion = $nomina->abonos()->where('metodoPago', 'compensacion_nomina')->first();
        $this->assertNotNull($abonoCompensacion);
        $this->assertSame(15000.0, (float) $abonoCompensacion->monto);

        Mail::assertSent(PagoArbitroRealizadoMail::class, fn ($mail) => $mail->netoDesembolsado === 0.0 && $mail->totalDeudasNeteadas === 15000.0);
    }

    public function test_compensa_parcial_sin_lanzar_error_cuando_la_nomina_no_alcanza(): void
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

        // No lanza error aunque la deuda (50000) supere la nómina disponible (10000).
        $this->actingAs($tesorero)
            ->post("/finanzas/arbitro/{$arbitro->idArbitro}/cargos/{$multa->idMovimiento}/compensar")
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $nomina->refresh();
        $multa->refresh();

        $this->assertSame('pagado', $nomina->estadoMovimiento);
        $this->assertSame(0.0, $nomina->saldoPendiente());

        // La multa queda parcial — se compensaron 10000 de los 50000, el resto sigue pendiente.
        $this->assertSame('parcial', $multa->estadoMovimiento);
        $this->assertSame(40000.0, $multa->saldoPendiente());
    }

    public function test_se_puede_volver_a_compensar_cuando_aparece_mas_nomina(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $arbitro  = $this->crearArbitro($colegio);
        $tesorero = $this->crearTesorero($colegio);
        $finanzas = app(FinanzasService::class);

        $nomina1 = $finanzas->registrarMovimiento($colegio->idColegio, [
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

        $this->actingAs($tesorero)
            ->post("/finanzas/arbitro/{$arbitro->idArbitro}/cargos/{$multa->idMovimiento}/compensar");

        $multa->refresh();
        $this->assertSame(40000.0, $multa->saldoPendiente());

        // Llega otro partido de nómina — se puede compensar de nuevo contra el resto de la multa.
        $nomina2 = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'egreso', 'categoria' => 'nomina_arbitro',
            'concepto' => 'Partido 2', 'montoTotal' => 25000,
            'fechaMovimiento' => today()->format('Y-m-d'), 'idArbitro' => $arbitro->idArbitro,
        ], null);

        $this->actingAs($tesorero)
            ->post("/finanzas/arbitro/{$arbitro->idArbitro}/cargos/{$multa->idMovimiento}/compensar")
            ->assertRedirect();

        $multa->refresh();
        $nomina2->refresh();
        $this->assertSame(15000.0, $multa->saldoPendiente());
        $this->assertSame('pagado', $nomina2->estadoMovimiento);
    }

    public function test_compensar_sin_nomina_pendiente_lanza_error(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $arbitro  = $this->crearArbitro($colegio);
        $tesorero = $this->crearTesorero($colegio);
        $finanzas = app(FinanzasService::class);

        $multa = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'multa',
            'concepto' => 'Inasistencia', 'montoTotal' => 50000,
            'fechaMovimiento' => today()->format('Y-m-d'), 'idArbitro' => $arbitro->idArbitro,
            'tipoOrigenMulta' => MovimientoFinanciero::ORIGEN_MULTA_MANUAL,
        ], null);

        $this->actingAs($tesorero)
            ->post("/finanzas/arbitro/{$arbitro->idArbitro}/cargos/{$multa->idMovimiento}/compensar")
            ->assertRedirect()
            ->assertSessionHas('error');

        $multa->refresh();
        $this->assertSame('pendiente', $multa->estadoMovimiento);
    }

    public function test_un_tesorero_no_puede_compensar_una_deuda_de_otro_colegio(): void
    {
        $colegioA  = $this->crearColegioConFinanzas();
        $colegioB  = $this->crearColegioConFinanzas();
        $tesoreroA = $this->crearTesorero($colegioA);
        $arbitroB  = $this->crearArbitro($colegioB);
        $finanzas  = app(FinanzasService::class);

        $multaB = $finanzas->registrarMovimiento($colegioB->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'multa',
            'concepto' => 'Multa de B', 'montoTotal' => 10000,
            'fechaMovimiento' => today()->format('Y-m-d'), 'idArbitro' => $arbitroB->idArbitro,
            'tipoOrigenMulta' => MovimientoFinanciero::ORIGEN_MULTA_MANUAL,
        ], null);

        $this->actingAs($tesoreroA)
            ->post("/finanzas/arbitro/{$arbitroB->idArbitro}/cargos/{$multaB->idMovimiento}/compensar")
            ->assertNotFound();
    }
}
