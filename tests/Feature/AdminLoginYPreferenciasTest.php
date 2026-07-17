<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Colegio;
use App\Models\Plan;
use App\Models\Suscripcion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Cobertura HTTP de AdminLoginController, Admin2FAController y
 * AdminPreferenciaController/AdminSuscripcionController — antes de este
 * archivo solo AdminAccessTest probaba acceso ya autenticado (GET), nunca
 * el propio flujo de login/2FA ni las rutas PUT/PATCH de estos
 * controladores. También es la regresión de su paso a FormRequest.
 */
class AdminLoginYPreferenciasTest extends TestCase
{
    use RefreshDatabase;

    private function crearAdmin(array $overrides = []): Admin
    {
        return Admin::create(array_merge([
            'nombre'   => 'Super',
            'email'    => 'super@test.com',
            'password' => Hash::make('password-correcta'),
            'activo'   => true,
        ], $overrides));
    }

    public function test_login_con_credenciales_correctas_autentica(): void
    {
        $admin = $this->crearAdmin();

        $this->post(route('admin.login.post'), [
            'email'    => 'super@test.com',
            'password' => 'password-correcta',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_login_con_password_incorrecta_no_autentica(): void
    {
        $this->crearAdmin();

        $this->post(route('admin.login.post'), [
            'email'    => 'super@test.com',
            'password' => 'password-mala',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_login_valida_campos_requeridos(): void
    {
        $this->post(route('admin.login.post'), [])
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_flujo_completo_de_2fa_activar_login_y_desactivar(): void
    {
        $admin    = $this->crearAdmin();
        $google2fa = app(Google2FA::class);
        $secret    = $google2fa->generateSecretKey();
        $admin->forceFill(['google2fa_secret' => $secret])->saveQuietly();

        // Login real (sin 2FA todavía) para dejar una sesión genuina del guard
        // admin — actingAs() no sirve aquí porque fuerza la autenticación sin
        // pasar por el controlador, lo que dejaría la sesión "pegada" y
        // arruinaría la aserción de invitado más abajo.
        $this->post(route('admin.login.post'), [
            'email'    => 'super@test.com',
            'password' => 'password-correcta',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post(route('admin.2fa.enable'), ['codigo' => $google2fa->getCurrentOtp($secret)])
            ->assertRedirect(route('admin.2fa.config'));

        $this->assertTrue($admin->fresh()->two_factor_enabled);

        $this->post(route('admin.logout'));
        $this->assertGuest('admin');

        // Login ahora exige 2FA
        $this->post(route('admin.login.post'), [
            'email'    => 'super@test.com',
            'password' => 'password-correcta',
        ])->assertRedirect(route('admin.2fa'));

        $this->assertGuest('admin');

        // Código incorrecto no pasa
        $this->post(route('admin.2fa.post'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        // Código correcto sí
        $this->post(route('admin.2fa.post'), ['code' => $google2fa->getCurrentOtp($secret)])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin->fresh(), 'admin');

        // Desactivar exige la contraseña
        $this->post(route('admin.2fa.disable'), ['password' => 'password-mala'])
            ->assertSessionHasErrors('password');

        $this->post(route('admin.2fa.disable'), ['password' => 'password-correcta'])
            ->assertRedirect(route('admin.2fa.config'));

        $this->assertFalse($admin->fresh()->two_factor_enabled);
    }

    public function test_verificar_2fa_bloquea_tras_varios_codigos_incorrectos(): void
    {
        $admin     = $this->crearAdmin();
        $google2fa = app(Google2FA::class);
        $secret    = $google2fa->generateSecretKey();
        $admin->forceFill(['google2fa_secret' => $secret, 'two_factor_enabled' => true])->saveQuietly();

        $this->post(route('admin.login.post'), [
            'email'    => 'super@test.com',
            'password' => 'password-correcta',
        ])->assertRedirect(route('admin.2fa'));

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('admin.2fa.post'), ['code' => '000000'])
                ->assertSessionHasErrors('code');
        }

        // El 4º intento ya no evalúa el código — lo bloquea el throttle.
        $this->post(route('admin.2fa.post'), ['code' => $google2fa->getCurrentOtp($secret)])
            ->assertSessionHasErrors('code');

        $this->assertGuest('admin');
    }

    public function test_actualizar_tema_valida_valores_permitidos(): void
    {
        $admin = $this->crearAdmin();

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.preferencias.tema'), ['tema' => 'dark'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('dark', $admin->fresh()->temaPreferencia);

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.preferencias.tema'), ['tema' => 'no-existe'])
            ->assertStatus(422);
    }

    public function test_cambiar_plan_y_extender_suscripcion_validan_datos(): void
    {
        $admin = $this->crearAdmin();

        $tenantId = 'test-' . uniqid();
        DB::table('tenants')->insert(['id' => $tenantId, 'created_at' => now(), 'updated_at' => now()]);

        $planViejo = Plan::create(['nombre' => 'Viejo', 'precio' => 0, 'periodicidad' => 'mensual', 'modulosJSON' => ['arbitros'], 'orden' => 1]);
        $planNuevo = Plan::create(['nombre' => 'Nuevo', 'precio' => 0, 'periodicidad' => 'mensual', 'modulosJSON' => ['arbitros'], 'orden' => 2]);

        $colegio = Colegio::create([
            'tenantId'      => $tenantId,
            'nombreColegio' => 'Colegio de prueba',
            'codigoColegio' => 'T-' . uniqid(),
            'emailColegio'  => 'contacto@' . uniqid() . '.test',
            'paisColegio'   => 'Colombia',
        ]);

        Suscripcion::create([
            'idColegio'        => $colegio->idColegio,
            'idPlan'           => $planViejo->idPlan,
            'fechaInicio'      => today(),
            'fechaVencimiento' => today()->addMonth(),
            'estado'           => 'activa',
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.suscripciones.cambiarPlan', $colegio->idColegio), ['idPlan' => 999999])
            ->assertSessionHasErrors('idPlan');

        $this->actingAs($admin, 'admin')
            ->put(route('admin.suscripciones.cambiarPlan', $colegio->idColegio), ['idPlan' => $planNuevo->idPlan])
            ->assertRedirect();

        $this->assertSame($planNuevo->idPlan, $colegio->fresh()->suscripcionActiva->idPlan);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.suscripciones.extender', $colegio->idColegio), ['dias' => 0])
            ->assertSessionHasErrors('dias');

        $this->actingAs($admin, 'admin')
            ->put(route('admin.suscripciones.extender', $colegio->idColegio), ['dias' => 30])
            ->assertRedirect();
    }
}
