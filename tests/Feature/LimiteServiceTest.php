<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\Plan;
use App\Services\LimiteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class LimiteServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private LimiteService $limites;

    protected function setUp(): void
    {
        parent::setUp();
        $this->limites = app(LimiteService::class);
    }

    // ── Árbitros ──

    public function test_plan_ilimitado_siempre_permite_crear_arbitros(): void
    {
        $colegio = $this->crearColegio($this->crearPlan(['limiteArbitros' => null]));

        for ($i = 0; $i < 5; $i++) {
            $this->crearArbitro($colegio);
        }

        $this->assertNull($this->limites->limiteArbitros($colegio->idColegio));
        $this->assertTrue($this->limites->puedeCrearArbitro($colegio->idColegio));
    }

    public function test_bloquea_crear_arbitro_al_alcanzar_el_limite(): void
    {
        $colegio = $this->crearColegio($this->crearPlan(['limiteArbitros' => 2]));

        $this->crearArbitro($colegio);
        $this->crearArbitro($colegio);

        $this->assertSame(2, $this->limites->arbitrosActivos($colegio->idColegio));
        $this->assertFalse($this->limites->puedeCrearArbitro($colegio->idColegio));

        $this->expectException(\RuntimeException::class);
        $this->limites->asegurarPuedeCrearArbitro($colegio->idColegio);
    }

    public function test_arbitro_archivado_no_cuenta_para_el_limite(): void
    {
        $colegio = $this->crearColegio($this->crearPlan(['limiteArbitros' => 1]));

        $arbitro = $this->crearArbitro($colegio);
        $this->assertFalse($this->limites->puedeCrearArbitro($colegio->idColegio));

        $arbitro->delete(); // soft delete = archivar

        $this->assertSame(0, $this->limites->arbitrosActivos($colegio->idColegio));
        $this->assertTrue($this->limites->puedeCrearArbitro($colegio->idColegio));
    }

    public function test_sin_plan_resoluble_el_limite_es_cero_fail_safe(): void
    {
        $tenantId = 'test-sinplan-' . uniqid();
        DB::table('tenants')->insert(['id' => $tenantId, 'created_at' => now(), 'updated_at' => now()]);

        $colegio = Colegio::create([
            'tenantId'      => $tenantId,
            'nombreColegio' => 'Sin suscripción',
            'codigoColegio' => 'SIN-' . uniqid(),
            'emailColegio'  => 'sin@' . uniqid() . '.test',
            'paisColegio'   => 'Colombia',
        ]);

        $this->assertSame(0, $this->limites->limiteArbitros($colegio->idColegio));
        $this->assertFalse($this->limites->puedeCrearArbitro($colegio->idColegio));
    }

    // ── Cuentas admin ─────────────────────

    public function test_cuenta_admin_activas_no_incluye_arbitros_ni_cuentas_revocadas(): void
    {
        $colegio = $this->crearColegio($this->crearPlan(['limiteCuentasAdmin' => 5]));

        $this->crearArbitro($colegio);
        $this->crearCuentaAdmin($colegio, 'ejecutivo');
        $this->crearCuentaAdmin($colegio, 'designador');
        $this->crearCuentaAdmin($colegio, 'tesorero', estado: 'inactivo'); // revocada

        $this->assertSame(2, $this->limites->cuentasAdminActivas($colegio->idColegio));
    }

    public function test_bloquea_crear_cuenta_admin_al_alcanzar_el_limite(): void
    {
        $colegio = $this->crearColegio($this->crearPlan(['limiteCuentasAdmin' => 1]));

        $this->crearCuentaAdmin($colegio, 'ejecutivo');

        $this->assertFalse($this->limites->puedeCrearCuentaAdmin($colegio->idColegio));
        $this->expectException(\RuntimeException::class);
        $this->limites->asegurarPuedeCrearCuentaAdmin($colegio->idColegio);
    }

    public function test_revocar_una_cuenta_libera_cupo_para_crear_otra(): void
    {
        $colegio = $this->crearColegio($this->crearPlan(['limiteCuentasAdmin' => 1]));

        $cuenta = $this->crearCuentaAdmin($colegio, 'designador');
        $this->assertFalse($this->limites->puedeCrearCuentaAdmin($colegio->idColegio));

        $cuenta->update(['estadoUsuario' => 'inactivo']);

        $this->assertTrue($this->limites->puedeCrearCuentaAdmin($colegio->idColegio));
    }

    // ── Módulos por plan ──────────────────

    public function test_modulo_habilitado_respeta_el_catalogo_del_plan(): void
    {
        $colegio = $this->crearColegio($this->crearPlan(['modulosJSON' => ['arbitros', 'torneos']]));

        $this->assertTrue($this->limites->moduloHabilitado($colegio->idColegio, 'torneos'));
        $this->assertFalse($this->limites->moduloHabilitado($colegio->idColegio, 'sanciones'));
    }

    public function test_cambiar_el_plan_en_caliente_aplica_de_inmediato(): void
    {
        $plan    = $this->crearPlan(['limiteArbitros' => 1]);
        $colegio = $this->crearColegio($plan);

        $this->crearArbitro($colegio);
        $this->assertFalse($this->limites->puedeCrearArbitro($colegio->idColegio));

        $plan->update(['limiteArbitros' => 10]);

        $this->assertTrue($this->limites->puedeCrearArbitro($colegio->idColegio), 'El cambio de plan debe reflejarse sin caché.');
    }
}
