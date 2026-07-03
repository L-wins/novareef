<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * El login acepta tanto emailUsuario como usernameUsuario en el mismo campo
 * (detección automática por formato), y bloquea cuentas con estadoUsuario
 * distinto de 'activo' (revocadas) con el mismo mensaje genérico que una
 * contraseña incorrecta — sin revelar que la cuenta existe.
 */
class LoginDualTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'emailUsuario'          => 'usuario.prueba@novareef.test',
            'usernameUsuario'       => 'usuario_prueba',
            'passwordUsuario'       => Hash::make('Prueba123!'),
            'rolUsuario'            => 'ejecutivo',
            'estadoUsuario'         => 'activo',
            'must_change_password'  => false,
        ], $overrides));
    }

    public function test_login_con_email_funciona(): void
    {
        $this->crearUsuario();

        $response = $this->post('/login', [
            'identificador'    => 'usuario.prueba@novareef.test',
            'passwordUsuario'  => 'Prueba123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated('web');
    }

    public function test_login_con_username_funciona(): void
    {
        $this->crearUsuario();

        $response = $this->post('/login', [
            'identificador'    => 'usuario_prueba',
            'passwordUsuario'  => 'Prueba123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated('web');
    }

    public function test_login_con_password_incorrecto_falla(): void
    {
        $this->crearUsuario();

        $response = $this->post('/login', [
            'identificador'    => 'usuario_prueba',
            'passwordUsuario'  => 'password-equivocado',
        ]);

        $response->assertSessionHasErrors('identificador');
        $this->assertGuest('web');
    }

    public function test_cuenta_revocada_no_puede_iniciar_sesion(): void
    {
        $this->crearUsuario(['estadoUsuario' => 'inactivo']);

        $response = $this->post('/login', [
            'identificador'    => 'usuario_prueba',
            'passwordUsuario'  => 'Prueba123!',
        ]);

        $response->assertSessionHasErrors('identificador');
        $this->assertGuest('web');
    }

    public function test_cuenta_revocada_y_password_incorrecto_dan_el_mismo_mensaje(): void
    {
        // No debe filtrarse si la cuenta existe pero está revocada, vs. credenciales
        // simplemente inválidas — ambos casos deben dar exactamente el mismo mensaje.
        $this->crearUsuario(['estadoUsuario' => 'inactivo']);

        $this->post('/login', [
            'identificador'   => 'usuario_prueba',
            'passwordUsuario' => 'Prueba123!',
        ]);
        $revocada = session('errors')->first('identificador');

        $this->post('/login', [
            'identificador'   => 'no-existe-nadie-con-este-usuario',
            'passwordUsuario' => 'lo-que-sea',
        ]);
        $noExiste = session('errors')->first('identificador');

        $this->assertSame($noExiste, $revocada);
    }
}
