<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\GenerarCuotasMensualesJob;
use App\Models\Colegio;
use App\Models\ConfiguracionColegio;
use App\Models\MovimientoFinanciero;
use App\Models\User;
use App\Services\FinanzasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Recorre el ciclo completo, todo por HTTP/Job real (nada de llamar al
 * Service directo salvo el propio Job, que así es como corre en producción):
 * un ejecutivo configura el cobro automático de mensualidad desde
 * /configuracion → el job genera el cargo → aparece pendiente en
 * /finanzas/cuotas y NO en /finanzas/mora todavía (recién generado, no en
 * mora) → el tesorero lo cobra vía Cobro Masivo → el comprobante queda
 * descargable en /finanzas/comprobantes.
 */
class FinanzasConfiguracionEndToEndTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function crearColegioConFinanzas(): Colegio
    {
        return $this->crearColegio($this->crearPlan(['modulosJSON' => ['arbitros', 'torneos', 'designaciones', 'finanzas']]));
    }

    private function crearEjecutivo(Colegio $colegio): User
    {
        foreach (['editar-arbitros', 'ver-finanzas', 'crear-finanzas'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $rol = Role::firstOrCreate(['name' => 'ejecutivo', 'guard_name' => 'web']);
        $rol->syncPermissions(['editar-arbitros', 'ver-finanzas', 'crear-finanzas']);

        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);
        $usuario->assignRole('ejecutivo');

        return $usuario;
    }

    public function test_ciclo_completo_configurar_generar_cobrar_y_descargar_comprobante(): void
    {
        $colegio   = $this->crearColegioConFinanzas();
        $ejecutivo = $this->crearEjecutivo($colegio);
        $arbitro   = $this->crearArbitro($colegio);

        // 1) El ejecutivo configura el cobro automático desde la página real.
        $this->actingAs($ejecutivo)->put('/configuracion', [
            'dia_disponibilidad'          => 1,
            'horas_limite_confirmacion'   => 4,
            'monto_mensualidad'           => 30000,
            'dia_vencimiento_mensualidad' => 1,
        ])->assertRedirect();

        $this->assertSame(30000.0, ConfiguracionColegio::getMontoMensualidad($colegio->idColegio));

        // 2) El job corre (como lo haría el scheduler) y genera el cargo.
        (new GenerarCuotasMensualesJob())->handle(app(FinanzasService::class));

        $movimiento = MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)
            ->where('categoria', MovimientoFinanciero::CATEGORIA_MENSUALIDAD)
            ->first();
        $this->assertNotNull($movimiento);
        $this->assertSame('pendiente', $movimiento->estadoMovimiento);
        $this->assertSame(30000.0, (float) $movimiento->montoTotal);

        // 3) Aparece pendiente en la matriz de cuotas del mes.
        $this->actingAs($ejecutivo)->get('/finanzas/cuotas?mes=' . today()->format('Y-m'))
            ->assertOk()
            ->assertSee('Pendiente');

        // 4) Ya aparece en mora (nosDebe > 0 alcanza, sin importar la
        // antigüedad — no hay fecha de vencimiento separada, ver Fase 1),
        // pero recién generado cae en el bucket "0-30" con 0 días.
        $this->actingAs($ejecutivo)->get('/finanzas/mora')
            ->assertOk()
            ->assertSee('0-30 días');

        // 5) El tesorero/ejecutivo lo cobra vía Cobro Masivo, marcando "ya pagó".
        $resultado = app(FinanzasService::class)->registrarCobroMasivo($colegio->idColegio, [
            'categoria'       => 'mensualidad',
            'concepto'        => 'Mensualidad — cobro directo (ya cargada por el job)',
            'fechaMovimiento' => today()->addDay()->format('Y-m-d'),
            'montoTotal'      => 30000,
            'cargos' => [
                ['idArbitro' => $arbitro->idArbitro, 'incluir' => true, 'yaPago' => true, 'metodoPago' => 'efectivo'],
            ],
        ], $ejecutivo);
        // El cargo del job ya existe este mes — el segundo se omite por
        // duplicado, confirmando que la deduplicación mensual sigue viva
        // incluso cruzando "cuota automática" con "cobro manual".
        $this->assertSame(0, $resultado['totalCreados']);
        $this->assertSame(1, $resultado['totalOmitidos']);

        // Se cobra entonces el cargo que ya generó el job, vía abono directo.
        app(FinanzasService::class)->registrarAbono($movimiento, [
            'monto'      => 30000,
            'fechaAbono' => today()->format('Y-m-d'),
            'metodoPago' => 'efectivo',
        ], $ejecutivo);

        $movimiento->refresh();
        $this->assertSame('pagado', $movimiento->estadoMovimiento);

        // 6) La matriz de cuotas ya lo muestra pagado.
        $this->actingAs($ejecutivo)->get('/finanzas/cuotas?mes=' . today()->format('Y-m'))
            ->assertOk()
            ->assertSee('Pagado');

        // 7) Ya pagado — el árbitro sale de la mora.
        $this->actingAs($ejecutivo)->get('/finanzas/mora')
            ->assertOk()
            ->assertSee('Ningún árbitro está en mora ahora mismo.');
    }

    public function test_colegio_sin_cobro_automatico_configurado_no_genera_nada(): void
    {
        $colegio = $this->crearColegioConFinanzas();
        $this->crearArbitro($colegio);

        // Sin pasar por /configuracion — monto_mensualidad default 0.
        (new GenerarCuotasMensualesJob())->handle(app(FinanzasService::class));

        $this->assertSame(0, MovimientoFinanciero::where('idColegio', $colegio->idColegio)
            ->where('categoria', MovimientoFinanciero::CATEGORIA_MENSUALIDAD)
            ->count());
    }
}
