<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\MovimientoFinanciero;
use App\Models\User;
use App\Services\FinanzasService;
use App\Services\ReporteFinanzasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class ReporteFinanzasTest extends TestCase
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

    public function test_resumen_listado_respeta_filtros_y_descuenta_abonos(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $finanzas = app(FinanzasService::class);
        $reportes = app(ReporteFinanzasService::class);

        $ingreso = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'mensualidad',
            'concepto' => 'Cuota enero', 'montoTotal' => 50000, 'fechaMovimiento' => '2026-06-10',
        ], null);
        $finanzas->registrarAbono($ingreso, ['monto' => 20000, 'fechaAbono' => '2026-06-15', 'metodoPago' => 'efectivo'], $tesorero);

        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'egreso', 'categoria' => 'gasto_fijo',
            'concepto' => 'Arriendo', 'montoTotal' => 30000, 'fechaMovimiento' => '2026-06-20',
        ], null);

        // Fuera del rango filtrado — no debe sumar
        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'otro_ingreso',
            'concepto' => 'Fuera de rango', 'montoTotal' => 99999, 'fechaMovimiento' => '2026-07-05',
        ], null);

        // Anulado — nunca suma
        $anulado = $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'otro_ingreso',
            'concepto' => 'Anulado', 'montoTotal' => 88888, 'fechaMovimiento' => '2026-06-12',
        ], null);
        $finanzas->anularMovimiento($anulado, $tesorero);

        $resumen = $reportes->resumenListado($colegio->idColegio, ['desde' => '2026-06-01', 'hasta' => '2026-06-30']);

        $this->assertSame(50000.0, $resumen['totalIngresos']);
        $this->assertSame(30000.0, $resumen['totalEgresos']);
        $this->assertSame(20000.0, $resumen['neto']);
        $this->assertSame(30000.0, $resumen['pendientePorCobrar']); // 50000 - 20000 abonado
        $this->assertSame(30000.0, $resumen['pendientePorPagar']);
    }

    public function test_serie_mensual_agrupa_por_mes_y_rellena_meses_vacios(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $finanzas = app(FinanzasService::class);
        $reportes = app(ReporteFinanzasService::class);

        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'mensualidad',
            'concepto' => 'Abril', 'montoTotal' => 10000, 'fechaMovimiento' => '2026-04-10',
        ], null);
        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'egreso', 'categoria' => 'gasto_fijo',
            'concepto' => 'Junio', 'montoTotal' => 7000, 'fechaMovimiento' => '2026-06-05',
        ], null);

        $serie = $reportes->serieMensual($colegio->idColegio, '2026-04-01', '2026-06-30');

        $this->assertCount(3, $serie); // abr, may (vacío), jun
        $this->assertSame(['2026-04', '2026-05', '2026-06'], $serie->pluck('mes')->all());
        $this->assertSame(10000.0, $serie[0]['ingresos']);
        $this->assertSame(0.0, $serie[1]['ingresos']);
        $this->assertSame(0.0, $serie[1]['egresos']);
        $this->assertSame(7000.0, $serie[2]['egresos']);
    }

    public function test_reporte_incluye_comparativa_contra_periodo_anterior(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $finanzas = app(FinanzasService::class);
        $reportes = app(ReporteFinanzasService::class);

        // Período actual: junio — 30000 de ingresos
        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'mensualidad',
            'concepto' => 'Junio', 'montoTotal' => 30000, 'fechaMovimiento' => '2026-06-15',
        ], null);

        // Período anterior (mayo, misma duración): 20000 de ingresos
        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'mensualidad',
            'concepto' => 'Mayo', 'montoTotal' => 20000, 'fechaMovimiento' => '2026-05-15',
        ], null);

        $reporte = $reportes->reporte($colegio->idColegio, '2026-06-01', '2026-06-30');

        $this->assertSame(30000.0, $reporte['totalIngresos']);
        $this->assertSame(20000.0, $reporte['comparativa']['totalIngresos']);
        $this->assertSame(50.0, $reporte['comparativa']['variacionIngresos']); // +50%
        $this->assertNull($reporte['comparativa']['variacionEgresos']);        // sin base
    }

    public function test_la_pagina_de_reportes_renderiza_el_grafico(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);
        $finanzas = app(FinanzasService::class);

        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento' => 'ingreso', 'categoria' => 'mensualidad',
            'concepto' => 'Cuota', 'montoTotal' => 50000, 'fechaMovimiento' => today()->format('Y-m-d'),
        ], null);

        $this->actingAs($tesorero)
            ->get('/finanzas/reportes')
            ->assertOk()
            ->assertSee('Tendencia mensual')
            ->assertSee('barra-ingreso', false);
    }

    public function test_el_reporte_pdf_se_descarga(): void
    {
        $colegio  = $this->crearColegioConFinanzas();
        $tesorero = $this->crearTesorero($colegio);

        $respuesta = $this->actingAs($tesorero)->get('/finanzas/reportes/pdf');

        $respuesta->assertOk();
        $this->assertSame('application/pdf', $respuesta->headers->get('Content-Type'));
    }
}
