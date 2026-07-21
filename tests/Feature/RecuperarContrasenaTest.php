<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\RecuperarContrasenaMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * El flujo de "olvidé mi contraseña" responde con el mismo mensaje genérico
 * exista o no la cuenta (o esté revocada) — no debe filtrar esa información
 * a un tercero, mismo criterio de seguridad que LoginDualTest. El token es
 * de un solo uso, hasheado por el broker estándar de Laravel, y expira según
 * config('auth.passwords.users.expire').
 */
class RecuperarContrasenaTest extends TestCase
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

    public function test_solicitar_recuperacion_con_email_existente_envia_correo_y_muestra_mensaje_generico(): void
    {
        Mail::fake();
        $usuario = $this->crearUsuario();

        $response = $this->post('/recuperar-contrasena', ['email' => $usuario->emailUsuario]);

        Mail::assertSent(RecuperarContrasenaMail::class);
        $response->assertSessionHas('status');
        $this->assertDatabaseCount('password_reset_tokens', 1);
    }

    public function test_solicitar_recuperacion_con_email_inexistente_no_envia_correo_pero_muestra_mismo_mensaje(): void
    {
        Mail::fake();
        $this->crearUsuario();

        $respuestaExistente = $this->post('/recuperar-contrasena', ['email' => 'usuario.prueba@novareef.test']);

        Mail::fake();
        $respuestaInexistente = $this->post('/recuperar-contrasena', ['email' => 'no.existe@novareef.test']);

        Mail::assertNotSent(RecuperarContrasenaMail::class);
        $this->assertSame(
            $respuestaExistente->getSession()->get('status'),
            $respuestaInexistente->getSession()->get('status'),
        );
    }

    public function test_cuenta_revocada_no_recibe_enlace_pero_mensaje_es_generico(): void
    {
        Mail::fake();
        $this->crearUsuario(['estadoUsuario' => 'inactivo']);

        $response = $this->post('/recuperar-contrasena', ['email' => 'usuario.prueba@novareef.test']);

        Mail::assertNotSent(RecuperarContrasenaMail::class);
        $response->assertSessionHas('status');
    }

    public function test_solicitud_repetida_activa_bloqueo_por_rate_limit(): void
    {
        Mail::fake();
        $usuario = $this->crearUsuario();

        for ($i = 0; $i < 3; $i++) {
            $this->post('/recuperar-contrasena', ['email' => $usuario->emailUsuario]);
        }

        $response = $this->post('/recuperar-contrasena', ['email' => $usuario->emailUsuario]);

        $response->assertSessionHasErrors('email');
    }

    public function test_restablecer_con_token_valido_cambia_la_contrasena(): void
    {
        $usuario = $this->crearUsuario();
        $tokenAnterior = $usuario->remember_token;
        $token = Password::broker('users')->createToken($usuario);

        $response = $this->post('/recuperar-contrasena/restablecer', [
            'token'                 => $token,
            'email'                 => $usuario->emailUsuario,
            'password'              => 'NuevaPass123!',
            'password_confirmation' => 'NuevaPass123!',
        ]);

        $response->assertRedirect(route('login'));
        $usuario->refresh();
        $this->assertTrue(Hash::check('NuevaPass123!', $usuario->passwordUsuario));
        $this->assertNotSame($tokenAnterior, $usuario->remember_token);
    }

    public function test_restablecer_con_token_invalido_falla(): void
    {
        $usuario = $this->crearUsuario();

        $response = $this->post('/recuperar-contrasena/restablecer', [
            'token'                 => 'token-invalido',
            'email'                 => $usuario->emailUsuario,
            'password'              => 'NuevaPass123!',
            'password_confirmation' => 'NuevaPass123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('Prueba123!', $usuario->fresh()->passwordUsuario));
    }

    public function test_restablecer_con_token_expirado_falla(): void
    {
        $usuario = $this->crearUsuario();
        $token = Password::broker('users')->createToken($usuario);

        DB::table('password_reset_tokens')
            ->where('email', $usuario->emailUsuario)
            ->update(['created_at' => now()->subMinutes(120)]);

        $response = $this->post('/recuperar-contrasena/restablecer', [
            'token'                 => $token,
            'email'                 => $usuario->emailUsuario,
            'password'              => 'NuevaPass123!',
            'password_confirmation' => 'NuevaPass123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('Prueba123!', $usuario->fresh()->passwordUsuario));
    }

    public function test_password_antigua_deja_de_funcionar_tras_reset(): void
    {
        $usuario = $this->crearUsuario();
        $token = Password::broker('users')->createToken($usuario);

        $this->post('/recuperar-contrasena/restablecer', [
            'token'                 => $token,
            'email'                 => $usuario->emailUsuario,
            'password'              => 'NuevaPass123!',
            'password_confirmation' => 'NuevaPass123!',
        ]);

        $this->post('/login', [
            'identificador'   => $usuario->emailUsuario,
            'passwordUsuario' => 'Prueba123!',
        ]);
        $this->assertGuest('web');

        $this->post('/login', [
            'identificador'   => $usuario->emailUsuario,
            'passwordUsuario' => 'NuevaPass123!',
        ]);
        $this->assertAuthenticated('web');
    }
}
