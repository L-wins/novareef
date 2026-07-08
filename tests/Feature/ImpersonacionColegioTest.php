<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Impersonación de colegio: el único superadmin puede entrar como el
 * ejecutivo principal de un colegio, sin gating por permiso (no hay otros
 * roles admin que restringir). La sesión del guard 'admin' debe sobrevivir
 * intacta mientras dura la impersonación y después de salir — solo queda
 * rastro en admin_action_logs por transparencia.
 */
class ImpersonacionColegioTest extends TestCase
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

    public function test_el_admin_puede_entrar_como_el_ejecutivo_del_colegio(): void
    {
        $admin      = $this->crearAdmin();
        $colegio    = $this->crearColegio();
        $ejecutivo  = $this->crearCuentaAdmin($colegio, 'ejecutivo');

        $this->actingAs($admin, 'admin')
            ->post("/novareef-panel/colegios/{$colegio->idColegio}/impersonar")
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($ejecutivo, 'web');
        $this->assertAuthenticatedAs($admin, 'admin');

        $this->assertDatabaseHas('admin_action_logs', [
            'idAdmin' => $admin->idAdmin,
            'accion'  => 'impersonar',
            'entidad' => 'colegio',
        ]);
    }

    public function test_colegio_sin_ejecutivo_no_se_puede_impersonar(): void
    {
        $admin   = $this->crearAdmin();
        $colegio = $this->crearColegio();

        $this->actingAs($admin, 'admin')
            ->post("/novareef-panel/colegios/{$colegio->idColegio}/impersonar")
            ->assertRedirect();

        $this->assertGuest('web');
    }

    public function test_salir_termina_solo_la_sesion_web_y_admin_sigue_activo(): void
    {
        $admin     = $this->crearAdmin();
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearCuentaAdmin($colegio, 'ejecutivo');

        $this->actingAs($admin, 'admin')
            ->post("/novareef-panel/colegios/{$colegio->idColegio}/impersonar");

        $this->actingAs($ejecutivo, 'web')
            ->withSession(['impersonacion.idAdmin' => $admin->idAdmin, 'impersonacion.idColegio' => $colegio->idColegio])
            ->post('/impersonacion/salir')
            ->assertRedirect(route('admin.colegios.show', $colegio->idColegio));

        $this->assertGuest('web');
    }
}
