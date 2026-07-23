<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\NotificarDesignacionJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * NotificarDesignacionJob envía WhatsApp además de correo/SMS al avisar una
 * nueva designación (best-effort, nunca bloquea el correo) — ver
 * NotificarDesignacionJob::enviarWhatsApp(). Cubre: envío correcto con
 * plantilla configurada, no-op sin plantilla, no-op sin teléfono, y que un
 * fallo de WhatsApp no impide que el correo se marque como enviado.
 */
class NotificarDesignacionWhatsAppTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_envia_whatsapp_con_los_datos_correctos_cuando_hay_plantilla_configurada(): void
    {
        config(['services.whatsapp.token' => 'token-fake', 'services.whatsapp.phone_number_id' => '123', 'services.whatsapp.plantilla_designacion' => 'designacion_partido']);
        Mail::fake();
        Queue::fake();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.fake']]], 200)]);

        $colegio = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);
        $datos = $this->prepararPartidoPublicado($colegio, $designador);

        $designacion = $datos['designacionCentral'];
        $designacion->arbitro->usuario->update(['telefonoUsuario' => '3001234567']);

        (new NotificarDesignacionJob($designacion))->handle();

        Http::assertSent(function ($request) {
            $parametros = collect($request['template']['components'][0]['parameters'])
                ->keyBy('parameter_name');

            return str_contains($request->url(), 'graph.facebook.com')
                && $request['to'] === '573001234567'
                && $request['type'] === 'template'
                && $request['template']['name'] === 'designacion_partido'
                && $parametros['equipos']['text'] === 'Local FC vs Visitante FC'
                && $parametros['rol']['text'] === 'Central';
        });

        $this->assertTrue($designacion->fresh()->notificacionEnviada);
    }

    public function test_no_envia_whatsapp_sin_plantilla_configurada(): void
    {
        config(['services.whatsapp.plantilla_designacion' => null]);
        Mail::fake();
        Queue::fake();
        Http::fake();

        $colegio = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);
        $datos = $this->prepararPartidoPublicado($colegio, $designador);

        $designacion = $datos['designacionCentral'];
        $designacion->arbitro->usuario->update(['telefonoUsuario' => '3001234567']);

        (new NotificarDesignacionJob($designacion))->handle();

        Http::assertNothingSent();
    }

    public function test_no_envia_whatsapp_sin_telefono(): void
    {
        config(['services.whatsapp.plantilla_designacion' => 'designacion_partido']);
        Mail::fake();
        Queue::fake();
        Http::fake();

        $colegio = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);
        $datos = $this->prepararPartidoPublicado($colegio, $designador);

        $designacion = $datos['designacionCentral'];
        $designacion->arbitro->usuario->update(['telefonoUsuario' => null]);

        (new NotificarDesignacionJob($designacion))->handle();

        Http::assertNothingSent();
    }

    public function test_un_error_de_la_api_de_whatsapp_no_impide_marcar_la_notificacion_como_enviada(): void
    {
        config(['services.whatsapp.token' => 'token-fake', 'services.whatsapp.phone_number_id' => '123', 'services.whatsapp.plantilla_designacion' => 'designacion_partido']);
        Mail::fake();
        Queue::fake();
        Log::spy();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);

        $colegio = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);
        $datos = $this->prepararPartidoPublicado($colegio, $designador);

        $designacion = $datos['designacionCentral'];
        $designacion->arbitro->usuario->update(['telefonoUsuario' => '3001234567']);

        (new NotificarDesignacionJob($designacion))->handle();

        $this->assertTrue($designacion->fresh()->notificacionEnviada);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.facebook.com'));
    }
}
