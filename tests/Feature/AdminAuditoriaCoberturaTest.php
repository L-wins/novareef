<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Colegio;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Motivado por el incidente de ASOCAFA: admin_action_logs solo auditaba
 * 'impersonar' — cambiar de plan, extender, cancelar, editar/suspender un
 * colegio, editar/activar un plan y suspender una cuenta de usuario corrían
 * en silencio, sin dejar quién ni cuándo. No es un detalle cosmético: fue
 * la falta de este rastro lo que impidió responder "¿quién canceló esto y
 * cuándo?" con certeza en ese momento.
 */
class AdminAuditoriaCoberturaTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function crearAdmin(): Admin
    {
        return Admin::create([
            'nombre' => 'Super', 'email' => 'super@test.com',
            'password' => Hash::make('password'), 'activo' => true,
        ]);
    }

    public function test_cambiar_plan_extender_y_cancelar_suscripcion_quedan_auditados(): void
    {
        $admin   = $this->crearAdmin();
        $colegio = $this->crearColegio();
        $planNuevo = $this->crearPlan(['nombre' => 'GodMode']);

        $this->actingAs($admin, 'admin')
            ->put("/novareef-panel/suscripciones/colegio/{$colegio->idColegio}/plan", ['idPlan' => $planNuevo->idPlan])
            ->assertRedirect();

        $this->assertDatabaseHas('admin_action_logs', [
            'idAdmin' => $admin->idAdmin, 'accion' => 'cambiar_plan', 'entidad' => 'suscripcion', 'entidadId' => $colegio->idColegio,
        ]);

        $this->actingAs($admin, 'admin')
            ->put("/novareef-panel/suscripciones/colegio/{$colegio->idColegio}/extender", ['dias' => 30])
            ->assertRedirect();

        $this->assertDatabaseHas('admin_action_logs', [
            'idAdmin' => $admin->idAdmin, 'accion' => 'extender', 'entidad' => 'suscripcion', 'entidadId' => $colegio->idColegio,
        ]);

        $this->actingAs($admin, 'admin')
            ->put("/novareef-panel/suscripciones/colegio/{$colegio->idColegio}/cancelar")
            ->assertRedirect();

        $this->assertDatabaseHas('admin_action_logs', [
            'idAdmin' => $admin->idAdmin, 'accion' => 'cancelar', 'entidad' => 'suscripcion', 'entidadId' => $colegio->idColegio,
        ]);
    }

    public function test_crear_editar_y_suspender_colegio_quedan_auditados(): void
    {
        $admin = $this->crearAdmin();
        $plan  = $this->crearPlan();
        $this->crearRolSpatie('ejecutivo', ['ver-arbitros']);

        $this->actingAs($admin, 'admin')
            ->post('/novareef-panel/colegios', [
                'nombreColegio' => 'Nuevo Colegio', 'codigoColegio' => 'NC-' . uniqid(),
                'emailColegio'  => 'nuevo@' . uniqid() . '.test', 'paisColegio' => 'Colombia',
                'idPlan'        => $plan->idPlan,
                'nombreAdmin'   => 'Admin Nuevo', 'emailAdmin' => 'admin@' . uniqid() . '.test',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('admin_action_logs', ['idAdmin' => $admin->idAdmin, 'accion' => 'crear', 'entidad' => 'colegio']);

        $colegio = $this->crearColegio();

        $this->actingAs($admin, 'admin')
            ->put("/novareef-panel/colegios/{$colegio->idColegio}/estado", ['estado' => 'suspendido'])
            ->assertRedirect();

        $this->assertDatabaseHas('admin_action_logs', [
            'idAdmin' => $admin->idAdmin, 'accion' => 'cambiar_estado', 'entidad' => 'colegio', 'entidadId' => $colegio->idColegio,
        ]);
    }

    public function test_activar_y_suspender_cuenta_de_usuario_queda_auditado(): void
    {
        $admin   = $this->crearAdmin();
        $colegio = $this->crearColegio();
        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);

        $this->actingAs($admin, 'admin')
            ->put("/novareef-panel/usuarios/{$usuario->idUsuario}/estado")
            ->assertRedirect();

        $this->assertDatabaseHas('admin_action_logs', [
            'idAdmin' => $admin->idAdmin, 'accion' => 'cambiar_estado', 'entidad' => 'usuario', 'entidadId' => $usuario->idUsuario,
        ]);
    }

    public function test_activar_y_desactivar_plan_queda_auditado(): void
    {
        $admin = $this->crearAdmin();
        $plan  = $this->crearPlan();

        $this->actingAs($admin, 'admin')
            ->put("/novareef-panel/planes/{$plan->idPlan}/activo")
            ->assertRedirect();

        $this->assertDatabaseHas('admin_action_logs', [
            'idAdmin' => $admin->idAdmin, 'accion' => 'cambiar_estado', 'entidad' => 'plan', 'entidadId' => $plan->idPlan,
        ]);
    }
}
