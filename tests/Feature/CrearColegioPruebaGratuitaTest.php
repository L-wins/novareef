<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\VencerSuscripcionesJob;
use App\Models\Admin;
use App\Models\Suscripcion;
use App\Services\ColegioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Al crear un colegio desde el panel admin, el superadmin puede marcar
 * "Iniciar como prueba gratuita" — la suscripción nace en estado=trial con
 * vencimiento a 7 días, en vez de activa de inmediato. El estado 'trial' ya
 * existía en el enum y en el dashboard (AdminDashboardMetrics), pero nada
 * en el código lo asignaba nunca antes de esto.
 *
 * En trial el colegio no elige plan comercial: la vista oculta la grilla de
 * planes y el backend asigna automáticamente el de mayor jerarquía (orden
 * más alto), para que pueda evaluar todos los módulos sin límites.
 */
class CrearColegioPruebaGratuitaTest extends TestCase
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

    private function datosFormulario(array $overrides = []): array
    {
        $plan = $this->crearPlan();

        return array_merge([
            'nombreColegio' => 'Colegio Prueba',
            'codigoColegio' => 'CP-'.uniqid(),
            'emailColegio' => 'contacto@'.uniqid().'.test',
            'paisColegio' => 'Colombia',
            'idPlan' => $plan->idPlan,
            'nombreAdmin' => 'Admin Prueba',
            'emailAdmin' => 'admin@'.uniqid().'.test',
        ], $overrides);
    }

    public function test_marcar_el_checkbox_crea_la_suscripcion_en_trial_a_7_dias(): void
    {
        $admin = $this->crearAdmin();
        $this->crearRolSpatie('ejecutivo', ['ver-arbitros']);

        $this->actingAs($admin, 'admin')
            ->post('/novareef-panel/colegios', $this->datosFormulario(['iniciarComoTrial' => '1']))
            ->assertRedirect();

        $suscripcion = Suscripcion::latest('idSuscripcion')->first();

        $this->assertSame('trial', $suscripcion->estado);
        $this->assertSame(
            today()->addDays(ColegioService::DIAS_PRUEBA_GRATUITA)->toDateString(),
            $suscripcion->fechaVencimiento->toDateString(),
        );
    }

    public function test_sin_marcar_el_checkbox_la_suscripcion_sigue_naciendo_activa(): void
    {
        $admin = $this->crearAdmin();
        $this->crearRolSpatie('ejecutivo', ['ver-arbitros']);

        $this->actingAs($admin, 'admin')
            ->post('/novareef-panel/colegios', $this->datosFormulario())
            ->assertRedirect();

        $suscripcion = Suscripcion::latest('idSuscripcion')->first();

        $this->assertSame('activa', $suscripcion->estado);
    }

    public function test_el_trial_vencido_pasa_a_vencida_con_el_job_existente(): void
    {
        $admin = $this->crearAdmin();
        $this->crearRolSpatie('ejecutivo', ['ver-arbitros']);

        $this->actingAs($admin, 'admin')
            ->post('/novareef-panel/colegios', $this->datosFormulario(['iniciarComoTrial' => '1']))
            ->assertRedirect();

        $suscripcion = Suscripcion::latest('idSuscripcion')->first();
        $suscripcion->update(['fechaVencimiento' => today()->subDay()]);

        (new VencerSuscripcionesJob)->handle();

        $this->assertSame('vencida', $suscripcion->fresh()->estado);
    }

    public function test_trial_sin_elegir_plan_asigna_el_de_mayor_jerarquia(): void
    {
        $admin = $this->crearAdmin();
        $this->crearRolSpatie('ejecutivo', ['ver-arbitros']);

        $planBajo = $this->crearPlan(['nombre' => 'Rookie', 'orden' => 1]);
        $planAlto = $this->crearPlan(['nombre' => 'GodMode', 'orden' => 4]);

        $datos = $this->datosFormulario(['iniciarComoTrial' => '1']);
        unset($datos['idPlan']);

        $this->actingAs($admin, 'admin')
            ->post('/novareef-panel/colegios', $datos)
            ->assertRedirect();

        $suscripcion = Suscripcion::latest('idSuscripcion')->first();

        $this->assertSame($planAlto->idPlan, $suscripcion->idPlan);
        $this->assertNotSame($planBajo->idPlan, $suscripcion->idPlan);
    }

    public function test_trial_sin_plan_no_falla_la_validacion(): void
    {
        $admin = $this->crearAdmin();
        $this->crearRolSpatie('ejecutivo', ['ver-arbitros']);

        $datos = $this->datosFormulario(['iniciarComoTrial' => '1']);
        unset($datos['idPlan']);

        $this->actingAs($admin, 'admin')
            ->post('/novareef-panel/colegios', $datos)
            ->assertSessionDoesntHaveErrors('idPlan');
    }

    public function test_sin_trial_y_sin_plan_falla_la_validacion(): void
    {
        $admin = $this->crearAdmin();

        $datos = $this->datosFormulario();
        unset($datos['idPlan']);

        $this->actingAs($admin, 'admin')
            ->post('/novareef-panel/colegios', $datos)
            ->assertSessionHasErrors('idPlan');
    }
}
