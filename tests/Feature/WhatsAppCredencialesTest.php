<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Services\ArbitroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * ArbitroService::registrarConCredenciales() envía WhatsApp además del
 * correo (best-effort, nunca bloquea la creación de la cuenta) — ver
 * WhatsAppService::enviarPlantilla(). Cubre: envío correcto con plantilla
 * configurada, no-op sin plantilla configurada, no-op sin teléfono, y que
 * un fallo de WhatsApp no impide que la cuenta se cree ni que el correo
 * se envíe.
 */
class WhatsAppCredencialesTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'arbitro', 'guard_name' => 'web']);
    }

    private function colegioConCategoria(): Colegio
    {
        return $this->crearColegio();
    }

    public function test_envia_whatsapp_con_los_datos_correctos_cuando_hay_plantilla_configurada(): void
    {
        config(['services.whatsapp.token' => 'token-fake', 'services.whatsapp.phone_number_id' => '123', 'services.whatsapp.plantilla_credenciales' => 'credenciales_acceso']);
        Mail::fake();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.fake']]], 200)]);

        $colegio = $this->colegioConCategoria();

        app(ArbitroService::class)->registrarConCredenciales(
            idColegio: $colegio->idColegio,
            nombre: 'Juan Pérez',
            email: 'juan@test.com',
            telefono: '3001234567',
            rol: 'arbitro',
            nombreColegio: $colegio->nombreColegio,
            urlAcceso: 'http://localhost/login',
        );

        Http::assertSent(function ($request) use ($colegio) {
            $parametros = collect($request['template']['components'][0]['parameters'])
                ->keyBy('parameter_name');

            return str_contains($request->url(), 'graph.facebook.com')
                && $request['to'] === '573001234567'
                && $request['type'] === 'template'
                && $request['template']['name'] === 'credenciales_acceso'
                && $parametros['colegio']['text'] === $colegio->nombreColegio
                && $parametros['usuario']['text'] === 'juan@test.com'
                && $parametros['url_acceso']['text'] === 'http://localhost/login';
        });
    }

    public function test_no_envia_whatsapp_sin_plantilla_configurada(): void
    {
        config(['services.whatsapp.plantilla_credenciales' => null]);
        Mail::fake();
        Http::fake();

        $colegio = $this->colegioConCategoria();

        app(ArbitroService::class)->registrarConCredenciales(
            idColegio: $colegio->idColegio,
            nombre: 'Juan Pérez',
            email: 'juan2@test.com',
            telefono: '3001234567',
            rol: 'arbitro',
            nombreColegio: $colegio->nombreColegio,
            urlAcceso: 'http://localhost/login',
        );

        Http::assertNothingSent();
    }

    public function test_no_envia_whatsapp_sin_telefono(): void
    {
        config(['services.whatsapp.plantilla_credenciales' => 'credenciales_acceso']);
        Mail::fake();
        Http::fake();

        $colegio = $this->colegioConCategoria();

        app(ArbitroService::class)->registrarConCredenciales(
            idColegio: $colegio->idColegio,
            nombre: 'Juan Pérez',
            email: 'juan3@test.com',
            telefono: '',
            rol: 'arbitro',
            nombreColegio: $colegio->nombreColegio,
            urlAcceso: 'http://localhost/login',
        );

        Http::assertNothingSent();
    }

    /**
     * WhatsAppService::post() no lanza excepción ante una respuesta HTTP de
     * error (solo la loguea y devuelve la Response tal cual — ver
     * WhatsAppService) — el try/catch de ArbitroService cubre errores de
     * transporte (timeout, DNS, credenciales ausentes), no un 4xx/5xx de la
     * API. Este test confirma lo que realmente importa: pase lo que pase
     * con WhatsApp, la cuenta se crea y el correo se envía igual.
     */
    public function test_un_error_de_la_api_de_whatsapp_no_impide_crear_la_cuenta_ni_enviar_el_correo(): void
    {
        config(['services.whatsapp.token' => 'token-fake', 'services.whatsapp.phone_number_id' => '123', 'services.whatsapp.plantilla_credenciales' => 'credenciales_acceso']);
        Mail::fake();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);

        $colegio = $this->colegioConCategoria();

        $usuario = app(ArbitroService::class)->registrarConCredenciales(
            idColegio: $colegio->idColegio,
            nombre: 'Juan Pérez',
            email: 'juan4@test.com',
            telefono: '3001234567',
            rol: 'arbitro',
            nombreColegio: $colegio->nombreColegio,
            urlAcceso: 'http://localhost/login',
        );

        $this->assertNotNull($usuario->idUsuario);
        Mail::assertSent(\App\Mail\CredencialesColegioMail::class);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.facebook.com'));
    }

    /**
     * A diferencia del caso anterior (error HTTP de la API), esto sí es un
     * fallo real de PHP (config ausente en medio del envío, credenciales
     * inválidas que WhatsAppService valida antes del request) — confirma
     * que el try/catch de ArbitroService también cubre ese camino.
     */
    public function test_un_error_de_configuracion_de_whatsapp_no_impide_crear_la_cuenta_ni_enviar_el_correo(): void
    {
        // Sin token/phone_number_id — WhatsAppService::post() lanza
        // RuntimeException antes de intentar el request HTTP.
        config(['services.whatsapp.token' => null, 'services.whatsapp.phone_number_id' => null, 'services.whatsapp.plantilla_credenciales' => 'credenciales_acceso']);
        Mail::fake();
        Log::spy();

        $colegio = $this->colegioConCategoria();

        $usuario = app(ArbitroService::class)->registrarConCredenciales(
            idColegio: $colegio->idColegio,
            nombre: 'Juan Pérez',
            email: 'juan5@test.com',
            telefono: '3001234567',
            rol: 'arbitro',
            nombreColegio: $colegio->nombreColegio,
            urlAcceso: 'http://localhost/login',
        );

        $this->assertNotNull($usuario->idUsuario);
        Mail::assertSent(\App\Mail\CredencialesColegioMail::class);
        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(fn (string $mensaje) => $mensaje === 'No se pudo enviar WhatsApp de credenciales');
    }
}
