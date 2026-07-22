<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Colegio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * El listado transversal de suscripciones (todos los colegios) era de solo
 * lectura y sin ninguna forma de priorizar a quién hacerle seguimiento —
 * esto cubre las mejoras: orden por urgencia, búsqueda, filtro "vence
 * pronto", resumen de stat-cards y export CSV.
 */
class AdminSuscripcionesListadoTest extends TestCase
{
    use CreaColegioDePrueba;
    use RefreshDatabase;

    private function crearAdmin(): Admin
    {
        return Admin::create([
            'nombre' => 'Super', 'email' => 'super@test.com',
            'password' => Hash::make('password'), 'activo' => true,
        ]);
    }

    private function crearColegioConVencimiento(string $nombre, string $estado, Carbon $fechaVencimiento): Colegio
    {
        $colegio = $this->crearColegio();
        $colegio->update(['nombreColegio' => $nombre]);
        $colegio->suscripcionActiva()->update(['estado' => $estado, 'fechaVencimiento' => $fechaVencimiento]);

        return $colegio;
    }

    public function test_el_orden_por_defecto_es_por_vencimiento_mas_urgente_primero(): void
    {
        $admin = $this->crearAdmin();

        $this->crearColegioConVencimiento('Lejano', 'activa', today()->addDays(60));
        $this->crearColegioConVencimiento('Vencido', 'vencida', today()->subDays(10));
        $this->crearColegioConVencimiento('Pronto', 'activa', today()->addDays(3));

        $response = $this->actingAs($admin, 'admin')->get('/novareef-panel/suscripciones');

        $nombres = $response->viewData('suscripciones')->pluck('colegio.nombreColegio')->all();

        $this->assertSame(['Vencido', 'Pronto', 'Lejano'], $nombres);
    }

    public function test_busqueda_filtra_por_nombre_de_colegio(): void
    {
        $admin = $this->crearAdmin();
        $this->crearColegioConVencimiento('Colegio de Cundinamarca', 'activa', today()->addMonth());
        $this->crearColegioConVencimiento('Colegio de Boyacá', 'activa', today()->addMonth());

        $response = $this->actingAs($admin, 'admin')->get('/novareef-panel/suscripciones?q=Cundinamarca');

        $nombres = $response->viewData('suscripciones')->pluck('colegio.nombreColegio')->all();

        $this->assertSame(['Colegio de Cundinamarca'], $nombres);
    }

    public function test_filtro_vencimiento_solo_trae_vigentes_dentro_de_la_ventana(): void
    {
        $admin = $this->crearAdmin();
        $this->crearColegioConVencimiento('Vence pronto', 'activa', today()->addDays(3));
        $this->crearColegioConVencimiento('Vence lejos', 'activa', today()->addDays(60));
        $this->crearColegioConVencimiento('Ya vencido', 'vencida', today()->subDays(3));

        $response = $this->actingAs($admin, 'admin')->get('/novareef-panel/suscripciones?vencimiento=7');

        $nombres = $response->viewData('suscripciones')->pluck('colegio.nombreColegio')->all();

        $this->assertSame(['Vence pronto'], $nombres);
    }

    public function test_el_resumen_cuenta_correctamente_cada_categoria(): void
    {
        $admin = $this->crearAdmin();
        $this->crearColegioConVencimiento('A', 'activa', today()->addMonth());
        $this->crearColegioConVencimiento('B', 'trial', today()->addDays(5));
        $this->crearColegioConVencimiento('C', 'vencida', today()->subDays(1));

        $response = $this->actingAs($admin, 'admin')->get('/novareef-panel/suscripciones');

        $resumen = $response->viewData('resumen');

        $this->assertSame(1, $resumen['activas']);
        $this->assertSame(1, $resumen['trial']);
        $this->assertSame(1, $resumen['vencidas']);
        $this->assertSame(1, $resumen['vencenPronto']); // B: trial, vence en 5 días
    }

    public function test_exportar_csv_respeta_los_filtros_aplicados(): void
    {
        $admin = $this->crearAdmin();
        $this->crearColegioConVencimiento('Incluido', 'vencida', today()->subDays(2));
        $this->crearColegioConVencimiento('Excluido', 'activa', today()->addMonth());

        $response = $this->actingAs($admin, 'admin')->get('/novareef-panel/suscripciones/exportar?estado=vencida');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $contenido = $response->streamedContent();
        $this->assertStringContainsString('Incluido', $contenido);
        $this->assertStringNotContainsString('Excluido', $contenido);
    }
}
