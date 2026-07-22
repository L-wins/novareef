<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\SolicitudArcoMail;
use App\Models\Colegio;
use App\Models\User;
use App\Services\PoliticaPrivacidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Ley 1581 de 2012 (Habeas Data): consentimiento previo/expreso/informado
 * antes de tratar datos, uno separado para datos sensibles (RH/EPS, salud),
 * y un canal para ejercer los derechos ARCO. Motivado por auditar el
 * proyecto y encontrar que no existía nada de esto — cero consentimiento en
 * ningún formulario.
 */
class PoliticaPrivacidadTest extends TestCase
{
    use CreaColegioDePrueba;
    use RefreshDatabase;

    public function test_un_usuario_sin_aceptar_la_politica_es_redirigido_al_gate(): void
    {
        $colegio = $this->crearColegio();
        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);

        $this->actingAsSinAceptarPolitica($usuario, 'web')
            ->get('/dashboard')
            ->assertRedirect(route('privacidad.aceptar'));
    }

    public function test_aceptar_la_politica_permite_continuar(): void
    {
        $colegio = $this->crearColegio();
        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);

        $this->actingAs($usuario, 'web')
            ->post(route('privacidad.aceptar.guardar'), ['acepto' => '1'])
            ->assertRedirect();

        $this->assertDatabaseHas('aceptaciones_politica_privacidad', [
            'idUsuario' => $usuario->idUsuario,
            'tipo' => 'politica_general',
            'version' => PoliticaPrivacidadService::VERSION_ACTUAL,
        ]);

        $this->actingAs($usuario, 'web')
            ->get('/dashboard')
            ->assertOk();
    }

    /**
     * Bug real: un colegio recién creado tiene must_change_password=true Y
     * la política sin aceptar al mismo tiempo. VerificarCambioContrasena
     * redirige a password.change; ExigirAceptacionPolitica redirige esa
     * misma página a privacidad.aceptar; VerificarCambioContrasena la
     * rebota de vuelta a password.change — ERR_TOO_MANY_REDIRECTS. Cada
     * middleware exime las rutas del otro para romper el ciclo.
     */
    public function test_usuario_con_password_pendiente_y_politica_sin_aceptar_no_entra_en_loop(): void
    {
        $colegio = $this->crearColegio();
        $usuario = User::factory()->create([
            'idColegio' => $colegio->idColegio,
            'rolUsuario' => 'ejecutivo',
            'must_change_password' => true,
        ]);

        $this->actingAsSinAceptarPolitica($usuario, 'web')
            ->get('/dashboard')
            ->assertRedirect(route('password.change'));

        $this->actingAsSinAceptarPolitica($usuario, 'web')
            ->get(route('password.change'))
            ->assertOk();
    }

    public function test_no_se_puede_aceptar_sin_marcar_el_checkbox(): void
    {
        $colegio = $this->crearColegio();
        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);

        $this->actingAsSinAceptarPolitica($usuario, 'web')
            ->post(route('privacidad.aceptar.guardar'), [])
            ->assertSessionHasErrors('acepto');

        $this->assertDatabaseMissing('aceptaciones_politica_privacidad', ['idUsuario' => $usuario->idUsuario]);
    }

    public function test_guardar_rh_o_eps_sin_consentimiento_falla_la_validacion(): void
    {
        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);
        $this->aceptarPoliticaGeneral($arbitro->usuario);

        $this->actingAs($arbitro->usuario, 'web')
            ->put(route('arbitros.mi-perfil.update'), ['rhArbitro' => 'O+'])
            ->assertSessionHasErrors('consentimientoDatosSensibles');

        $this->assertNull($arbitro->fresh()->rhArbitro);
    }

    public function test_guardar_rh_con_consentimiento_lo_registra_y_guarda_el_dato(): void
    {
        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);
        $this->aceptarPoliticaGeneral($arbitro->usuario);

        $this->actingAs($arbitro->usuario, 'web')
            ->put(route('arbitros.mi-perfil.update'), [
                'rhArbitro' => 'O+',
                'consentimientoDatosSensibles' => '1',
            ])
            ->assertRedirect(route('arbitros.mi-perfil'));

        $this->assertSame('O+', $arbitro->fresh()->rhArbitro);
        $this->assertDatabaseHas('aceptaciones_politica_privacidad', [
            'idUsuario' => $arbitro->usuario->idUsuario,
            'tipo' => 'datos_sensibles',
        ]);
    }

    public function test_una_vez_consentido_no_se_vuelve_a_exigir_en_ediciones_futuras(): void
    {
        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);
        $this->aceptarPoliticaGeneral($arbitro->usuario);
        app(PoliticaPrivacidadService::class)->registrarAceptacionDatosSensibles($arbitro->usuario, '127.0.0.1');

        $this->actingAs($arbitro->usuario, 'web')
            ->put(route('arbitros.mi-perfil.update'), ['rhArbitro' => 'A-'])
            ->assertRedirect(route('arbitros.mi-perfil'));

        $this->assertSame('A-', $arbitro->fresh()->rhArbitro);
    }

    public function test_solicitud_arco_se_registra_y_notifica_al_ejecutivo_del_colegio(): void
    {
        Mail::fake();

        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);
        $ejecutivo = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);
        $this->aceptarPoliticaGeneral($arbitro->usuario);

        $this->actingAs($arbitro->usuario, 'web')
            ->post(route('privacidad.solicitud.store'), [
                'tipo' => 'rectificacion',
                'mensaje' => 'Mi EPS está desactualizada, quiero corregirla.',
            ])
            ->assertRedirect(route('privacidad.politica'));

        $this->assertDatabaseHas('solicitudes_arco', [
            'idUsuario' => $arbitro->usuario->idUsuario,
            'idColegio' => $colegio->idColegio,
            'tipo' => 'rectificacion',
        ]);

        Mail::assertSent(SolicitudArcoMail::class, fn ($mail) => $mail->hasTo($ejecutivo->emailUsuario));
    }

    private function aceptarPoliticaGeneral(User $usuario): void
    {
        app(PoliticaPrivacidadService::class)->registrarAceptacionGeneral($usuario, '127.0.0.1');
    }
}
