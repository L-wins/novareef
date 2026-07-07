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
 * distinto de 'activo' (revocadas). El mensaje depende de si la contraseña
 * ingresada es correcta:
 * - Password correcta + cuenta revocada → mensaje específico ("tu cuenta fue
 *   desactivada"), porque acertar la contraseña ya es una señal fuerte de que
 *   quien la ingresa es el dueño legítimo de la cuenta.
 * - Password incorrecta (exista o no la cuenta) → mensaje genérico idéntico,
 *   para no revelarle a un tercero que adivina credenciales si la cuenta existe.
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

    public function test_cuenta_revocada_con_password_correcta_muestra_mensaje_especifico(): void
    {
        $this->crearUsuario(['estadoUsuario' => 'inactivo']);

        $this->post('/login', [
            'identificador'   => 'usuario_prueba',
            'passwordUsuario' => 'Prueba123!',
        ]);

        $this->assertStringContainsString(
            'desactivada',
            (string) session('errors')->first('identificador'),
        );
        $this->assertGuest('web');
    }

    public function test_cuenta_revocada_con_password_incorrecta_no_revela_su_existencia(): void
    {
        // Con password incorrecta, una cuenta revocada debe dar el mismo mensaje
        // genérico que un identificador que no existe — sin filtrar que la cuenta existe.
        $this->crearUsuario(['estadoUsuario' => 'inactivo']);

        $this->post('/login', [
            'identificador'   => 'usuario_prueba',
            'passwordUsuario' => 'password-equivocado',
        ]);
        $revocadaPasswordMala = session('errors')->first('identificador');

        $this->post('/login', [
            'identificador'   => 'no-existe-nadie-con-este-usuario',
            'passwordUsuario' => 'lo-que-sea',
        ]);
        $noExiste = session('errors')->first('identificador');

        $this->assertSame($noExiste, $revocadaPasswordMala);
    }
}
