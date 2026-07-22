<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * CuentaAdminController::update() bloquea que un ejecutivo cambie su propio
 * rolUsuario (mismo principio que la auto-revocación) — pero el <select> de
 * Rol en cuentas-admin/edit.blade.php nunca se deshabilita para la propia
 * cuenta, así que visualmente parece permitirlo. Confirma que el guard del
 * controller sí frena el cambio en runtime aunque la UI no lo comunique.
 */
class CuentaAdminEditarPropioRolTest extends TestCase
{
    use CreaColegioDePrueba;
    use RefreshDatabase;

    public function test_un_ejecutivo_no_puede_cambiar_su_propio_rol(): void
    {
        $colegio = $this->crearColegio($this->crearPlan(['limiteCuentasAdmin' => 5]));
        $this->crearRolSpatie('ejecutivo', ['ver-arbitros', 'gestionar-cuentas-admin']);
        $this->crearRolSpatie('tesorero', ['ver-arbitros']);

        $ejecutivo = User::factory()->create([
            'idColegio' => $colegio->idColegio,
            'rolUsuario' => 'ejecutivo',
        ]);
        $ejecutivo->assignRole('ejecutivo');

        $this->actingAs($ejecutivo, 'web')
            ->put(route('configuracion.cuentas-admin.update', $ejecutivo->idUsuario), [
                'nombreUsuario' => $ejecutivo->nombreUsuario,
                'usernameUsuario' => $ejecutivo->usernameUsuario ?? 'ejecutivo1',
                'emailUsuario' => $ejecutivo->emailUsuario,
                'rolUsuario' => 'tesorero',
            ])
            ->assertSessionHas('error');

        $this->assertSame('ejecutivo', $ejecutivo->fresh()->rolUsuario);
    }

    public function test_un_ejecutivo_si_puede_cambiar_el_rol_de_otra_cuenta(): void
    {
        $colegio = $this->crearColegio($this->crearPlan(['limiteCuentasAdmin' => 5]));
        $this->crearRolSpatie('ejecutivo', ['ver-arbitros', 'gestionar-cuentas-admin']);
        $this->crearRolSpatie('tesorero', ['ver-arbitros']);

        $ejecutivo = User::factory()->create([
            'idColegio' => $colegio->idColegio,
            'rolUsuario' => 'ejecutivo',
        ]);
        $ejecutivo->assignRole('ejecutivo');
        $otraCuenta = User::factory()->create([
            'idColegio' => $colegio->idColegio,
            'rolUsuario' => 'ejecutivo',
        ]);

        $this->actingAs($ejecutivo, 'web')
            ->put(route('configuracion.cuentas-admin.update', $otraCuenta->idUsuario), [
                'nombreUsuario' => $otraCuenta->nombreUsuario,
                'usernameUsuario' => $otraCuenta->usernameUsuario ?? 'otracuenta1',
                'emailUsuario' => $otraCuenta->emailUsuario,
                'rolUsuario' => 'tesorero',
            ])
            ->assertSessionHas('success');

        $this->assertSame('tesorero', $otraCuenta->fresh()->rolUsuario);
    }
}
