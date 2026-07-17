<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Cobertura de ColegioController (guard web, middleware solo.superadmin) —
 * registro de colegios y cambio de estado. Sin tests de Feature previos
 * (ver auditoría de plataforma, punto 1.1 sobre este mismo "superadmin"
 * cross-tenant del guard web).
 */
class ColegioControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function crearSuperadmin(): User
    {
        return User::factory()->create(['rolUsuario' => 'superadmin']);
    }

    private function datosNuevoColegio(Plan $plan): array
    {
        return [
            'nombreColegio' => 'Colegio Nuevo',
            'codigoColegio' => 'CN-' . uniqid(),
            'emailColegio'  => 'contacto@' . uniqid() . '.test',
            'paisColegio'   => 'Colombia',
            'idPlan'        => $plan->idPlan,
            'nombreAdmin'   => 'Admin Nuevo',
            'emailAdmin'    => 'admin.' . uniqid() . '@test.com',
        ];
    }

    public function test_un_usuario_normal_no_puede_acceder(): void
    {
        $colegio  = $this->crearColegio();
        $ejecutivo = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);

        $this->actingAs($ejecutivo)->get(route('colegios.index'))->assertForbidden();
    }

    public function test_el_superadmin_lista_colegios(): void
    {
        $superadmin = $this->crearSuperadmin();
        $this->crearColegio();
        $this->crearColegio();

        $this->actingAs($superadmin)->get(route('colegios.index'))
            ->assertOk()
            ->assertViewHas('colegios', fn ($colegios) => $colegios->total() === 2);
    }

    public function test_registra_un_colegio_nuevo_con_su_admin(): void
    {
        Role::firstOrCreate(['name' => 'ejecutivo', 'guard_name' => 'web']);
        $superadmin = $this->crearSuperadmin();
        $plan       = $this->crearPlan();

        $this->actingAs($superadmin)->post(route('colegios.store'), $this->datosNuevoColegio($plan))
            ->assertRedirect(route('colegios.index'));

        $this->assertDatabaseHas('colegios', ['nombreColegio' => 'Colegio Nuevo']);

        $colegio = Colegio::where('nombreColegio', 'Colegio Nuevo')->firstOrFail();
        $this->assertDatabaseHas('usuarios', [
            'idColegio'  => $colegio->idColegio,
            'rolUsuario' => 'ejecutivo',
        ]);
        $this->assertNotNull($colegio->suscripcionActiva);
    }

    public function test_no_permite_codigo_de_colegio_duplicado(): void
    {
        Role::firstOrCreate(['name' => 'ejecutivo', 'guard_name' => 'web']);
        $superadmin = $this->crearSuperadmin();
        $plan       = $this->crearPlan();
        $existente  = $this->crearColegio($plan);

        $datos = $this->datosNuevoColegio($plan);
        $datos['codigoColegio'] = $existente->codigoColegio;

        $this->actingAs($superadmin)->post(route('colegios.store'), $datos)
            ->assertSessionHasErrors('codigoColegio');
    }

    public function test_cambia_el_estado_del_colegio(): void
    {
        $superadmin = $this->crearSuperadmin();
        $colegio    = $this->crearColegio();
        // estadoColegio tiene default 'activo' a nivel de BD, no reflejado en
        // la instancia en memoria que devuelve create() — hay que refrescar.
        $this->assertSame('activo', $colegio->fresh()->estadoColegio);

        $this->actingAs($superadmin)->put(route('colegios.toggleEstado', $colegio->idColegio))
            ->assertRedirect();

        $this->assertSame('suspendido', $colegio->fresh()->estadoColegio);
    }
}
