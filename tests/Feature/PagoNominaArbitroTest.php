<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\PagoArbitroRealizadoMail;
use App\Models\Colegio;
use App\Models\User;
use App\Services\FinanzasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class PagoNominaArbitroTest extends TestCase
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

    public function test_paga_un_solo_partido_de_nomina(): void
    {
        Mail::fake();

        $colegio  = $this->crearColegioConFinanzas();
        $arbitro  = $this->crearArbitro($colegio);
        $tesorero = $this->crearTesorero($colegio);
        $finanzas = app(FinanzasService::class);

        $mov = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'egreso', 'categoria' => 'nomina_arbitro',
            'concepto' => 'Partido 1', 'montoTotal' => 60000,
            'fechaMovimiento' => today()->format('Y-m-d'), 'idArbitro' => $arbitro->idArbitro,
        ], null);

        $this->actingAs($tesorero)->post("/finanzas/arbitro/{$arbitro->idArbitro}/nomina/pagar", [
            'idsMovimientos' => [$mov->idMovimiento],
            'fecha'          => today()->format('Y-m-d'),
            'metodoPago'     => 'pago_digital',
        ])->assertRedirect();

        $mov->refresh();
        $this->assertSame('pagado', $mov->estadoMovimiento);
        $this->assertSame(0.0, $mov->saldoPendiente());

        Mail::assertSent(PagoArbitroRealizadoMail::class, fn ($mail) => $mail->netoDesembolsado === 60000.0 && $mail->totalDeudasNeteadas === 0.0);
    }

    public function test_paga_varios_partidos_de_nomina_en_un_solo_lote(): void
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

        $this->actingAs($tesorero)->post("/finanzas/arbitro/{$arbitro->idArbitro}/nomina/pagar", [
            'idsMovimientos' => [$mov1->idMovimiento, $mov2->idMovimiento],
            'fecha'          => today()->format('Y-m-d'),
            'metodoPago'     => 'efectivo',
        ])->assertRedirect();

        $mov1->refresh();
        $mov2->refresh();
        $this->assertSame('pagado', $mov1->estadoMovimiento);
        $this->assertSame('pagado', $mov2->estadoMovimiento);

        // Mismo idLotePago para ambos — un solo comprobante cubre el lote completo.
        $lote1 = $mov1->abonos()->first()->idLotePago;
        $lote2 = $mov2->abonos()->first()->idLotePago;
        $this->assertNotNull($lote1);
        $this->assertSame($lote1, $lote2);

        Mail::assertSent(PagoArbitroRealizadoMail::class, fn ($mail) => $mail->netoDesembolsado === 100000.0);
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

        $this->actingAs($tesoreroA)
            ->post("/finanzas/arbitro/{$arbitroB->idArbitro}/nomina/pagar", [
                'idsMovimientos' => [$movB->idMovimiento],
                'fecha'          => today()->format('Y-m-d'),
                'metodoPago'     => 'efectivo',
            ])
            ->assertNotFound();

        $movB->refresh();
        $this->assertSame('pendiente', $movB->estadoMovimiento);
    }
}
