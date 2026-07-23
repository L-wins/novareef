<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\Partido;
use App\Models\User;
use App\Services\EstadoCuentaArbitroService;
use App\Services\FinanzasService;
use App\StateMachines\PartidoStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Cada rol tiene su propio dashboard (vista + payload de DashboardService) —
 * confirma que /dashboard resuelve la vista correcta por rol y que no truena
 * al renderizar con datos reales.
 */
class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_ejecutivo_ve_su_dashboard(): void
    {
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivo($colegio);

        $this->actingAs($ejecutivo)->get('/dashboard')
            ->assertOk()
            ->assertViewIs('dashboard.ejecutivo');
    }

    public function test_designador_ve_su_dashboard(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);

        $this->actingAs($designador)->get('/dashboard')
            ->assertOk()
            ->assertViewIs('dashboard.designador');
    }

    public function test_tesorero_ve_su_dashboard(): void
    {
        $colegio  = $this->crearColegio();
        $tesorero = $this->crearUsuarioConRol($colegio, 'tesorero', ['ver-finanzas', 'crear-finanzas']);

        $this->actingAs($tesorero)->get('/dashboard')
            ->assertOk()
            ->assertViewIs('dashboard.tesorero');
    }

    public function test_sanciones_ve_su_dashboard(): void
    {
        $colegio = $this->crearColegio();
        $usuario = $this->crearUsuarioConRol($colegio, 'sanciones', ['ver-sanciones', 'ver-academico', 'editar-academico']);

        $this->actingAs($usuario)->get('/dashboard')
            ->assertOk()
            ->assertViewIs('dashboard.sanciones');
    }

    public function test_tecnico_ve_su_dashboard(): void
    {
        $colegio = $this->crearColegio();
        $usuario = $this->crearUsuarioConRol($colegio, 'tecnico', ['ver-academico', 'crear-academico']);

        $this->actingAs($usuario)->get('/dashboard')
            ->assertOk()
            ->assertViewIs('dashboard.tecnico');
    }

    public function test_veedor_ve_su_dashboard(): void
    {
        $colegio = $this->crearColegio();
        $usuario = $this->crearUsuarioConRol($colegio, 'veedor', ['ver-designaciones', 'crear-calificaciones']);

        $this->actingAs($usuario)->get('/dashboard')
            ->assertOk()
            ->assertViewIs('dashboard.veedor');
    }

    public function test_arbitro_ve_su_dashboard_con_su_saldo_pendiente(): void
    {
        $colegio  = $this->crearColegio();
        $arbitro  = $this->crearArbitro($colegio);
        $finanzas = app(FinanzasService::class);

        $finanzas->registrarMovimiento($colegio->idColegio, [
            'tipoMovimiento'  => 'egreso',
            'categoria'       => 'nomina_arbitro',
            'concepto'        => 'Nómina partido #1',
            'montoTotal'      => 60000,
            'fechaMovimiento' => today()->format('Y-m-d'),
            'idArbitro'       => $arbitro->idArbitro,
        ], null);

        $response = $this->actingAs($arbitro->usuario)->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('dashboard.arbitro');
        $response->assertSee('$60.000'); // formato COP sin decimales
        $this->assertSame(
            app(EstadoCuentaArbitroService::class)->saldoPendienteArbitro($arbitro->fresh()),
            $response->viewData('saldoPendienteCobrar'),
        );
    }

    /**
     * Hallazgo de la auditoría de carga (docs/auditoria-carga-2026-07.md):
     * un partido finalizado en modalidad nómina cuyas designaciones nunca se
     * confirmaron no genera ningún egreso — antes de este contador, eso
     * quedaba invisible para el ejecutivo salvo por un Log::warning() en
     * servidor. El caso real más común (tarifa faltante) exigiría montar
     * TarifaTorneo también; confirmar via designaciones sin confirmar basta
     * para ejercitar el mismo contador con menos fixture.
     */
    public function test_ejecutivo_ve_alerta_de_partidos_sin_nomina_generada(): void
    {
        $colegio = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivo($colegio);

        $partido = $this->prepararPartidoPublicado($colegio, $this->crearDesignador($colegio));
        Partido::where('idPartido', $partido['partido']->idPartido)->update(['modalidadPago' => 'nomina']);

        // Designaciones deliberadamente sin confirmar — el partido se lleva
        // a 'finalizado' con la state machine directamente, como haría un
        // job/comando que no pase por confirmarDesignacion() de cada árbitro.
        PartidoStateMachine::transicionarCon($partido['partido']->fresh(), 'confirmado', $ejecutivo);
        PartidoStateMachine::transicionarCon($partido['partido']->fresh(), 'finalizado', $ejecutivo);

        app(FinanzasService::class); // asegura el binding antes del dashboard
        $response = $this->actingAs($ejecutivo)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Hay partidos finalizados sin nómina generada');
        $this->assertSame(1, $response->viewData('partidosNominaSinGenerar'));
    }

    public function test_ejecutivo_sin_partidos_sin_nomina_no_ve_alerta(): void
    {
        $colegio = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivo($colegio);

        $response = $this->actingAs($ejecutivo)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('Hay partidos finalizados sin nómina generada');
    }

    /**
     * 'superadmin' es un valor válido del enum rolUsuario pero no debería
     * existir ahí (vive en la tabla admins — ver LimpiarUsuariosFantasma) — si
     * por alguna inconsistencia aparece, el dashboard no debe tronar con un 500.
     */
    public function test_un_rol_sin_dashboard_propio_cae_al_generico_sin_error(): void
    {
        $colegio = $this->crearColegio();
        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'superadmin']);

        $this->actingAs($usuario)->get('/dashboard')
            ->assertOk()
            ->assertViewIs('dashboard.generico');
    }

    private function crearUsuarioConRol(Colegio $colegio, string $rol, array $permisos): User
    {
        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        $role->syncPermissions($permisos);

        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => $rol]);
        $usuario->assignRole($rol);

        return $usuario;
    }
}
