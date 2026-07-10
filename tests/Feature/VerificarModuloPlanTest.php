<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class VerificarModuloPlanTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function crearUsuarioConPermiso(int $idColegio, string $permiso): User
    {
        Permission::create(['name' => $permiso, 'guard_name' => 'web']);

        $usuario = User::factory()->create(['idColegio' => $idColegio, 'rolUsuario' => 'ejecutivo']);
        $usuario->givePermissionTo($permiso);

        return $usuario;
    }

    public function test_bloquea_un_modulo_no_incluido_en_el_plan(): void
    {
        $colegio = $this->crearColegio($this->crearPlan(['modulosJSON' => ['arbitros']])); // sin 'torneos'
        $usuario = $this->crearUsuarioConPermiso($colegio->idColegio, 'ver-torneos');

        $response = $this->actingAs($usuario)->get('/torneos');

        $response->assertForbidden();
    }

    public function test_permite_un_modulo_incluido_en_el_plan(): void
    {
        $colegio = $this->crearColegio($this->crearPlan(['modulosJSON' => ['arbitros', 'torneos']]));
        $usuario = $this->crearUsuarioConPermiso($colegio->idColegio, 'ver-torneos');

        $response = $this->actingAs($usuario)->get('/torneos');

        $response->assertOk();
    }

    public function test_cambiar_el_plan_desbloquea_el_modulo_sin_relogin(): void
    {
        $plan    = $this->crearPlan(['modulosJSON' => ['arbitros']]);
        $colegio = $this->crearColegio($plan);
        $usuario = $this->crearUsuarioConPermiso($colegio->idColegio, 'ver-torneos');

        $this->actingAs($usuario)->get('/torneos')->assertForbidden();

        $plan->update(['modulosJSON' => ['arbitros', 'torneos']]);

        $this->actingAs($usuario)->get('/torneos')->assertOk();
    }

    public function test_tener_el_permiso_no_alcanza_sin_el_modulo_en_el_plan(): void
    {
        // El permiso de Spatie por sí solo no debe bastar — hacen falta ambas cosas.
        $colegio = $this->crearColegio($this->crearPlan(['modulosJSON' => []]));
        $usuario = $this->crearUsuarioConPermiso($colegio->idColegio, 'ver-torneos');

        $this->assertTrue($usuario->can('ver-torneos'));
        $this->actingAs($usuario)->get('/torneos')->assertForbidden();
    }
}
