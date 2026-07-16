<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Tests\TestCase;

/**
 * Un POST con token CSRF vencido (ej. logout tras dejar la pestaña abierta,
 * o el navegador reenviando un formulario viejo al volver atrás) lanzaba
 * TokenMismatchException y Laravel mostraba la página 419 cruda. El handler
 * global en bootstrap/app.php la convierte en un redirect al login
 * correspondiente con un mensaje amable, para ambos guards.
 *
 * Laravel desactiva el middleware CSRF automáticamente en tests
 * (VerifyCsrfToken::runningUnitTests()), así que no es posible reproducir
 * el 419 real vía una petición HTTP de test. En su lugar se invoca el
 * render handler registrado en bootstrap/app.php directamente, tal como
 * lo haría el ExceptionHandler ante una TokenMismatchException real.
 */
class SesionExpiradaTest extends TestCase
{
    public function test_token_mismatch_en_ruta_de_usuario_redirige_al_login_de_usuario(): void
    {
        $request = Request::create('/logout', 'POST');

        $response = $this->invocarHandler($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login'), $response->headers->get('Location'));
        $this->assertStringContainsString('sesión expiró', $response->getSession()->get('error'));
    }

    public function test_token_mismatch_en_ruta_admin_redirige_al_login_admin(): void
    {
        $prefix = config('admin.prefix', 'novareef-panel');
        $request = Request::create("/{$prefix}/logout", 'POST');

        $response = $this->invocarHandler($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('admin.login'), $response->headers->get('Location'));
        $this->assertStringContainsString('sesión expiró', $response->getSession()->get('error'));
    }

    /**
     * Las peticiones fetch/AJAX que mandan Accept: application/json (ej. el
     * scanner de académico) deben seguir recibiendo el 419 real, no un
     * redirect — academico.js ya distingue ese status explícitamente
     * (leerRespuestaJson) y un redirect sería seguido en silencio por
     * fetch, entregando HTML donde se esperaba JSON.
     */
    public function test_token_mismatch_en_peticion_json_no_se_redirige(): void
    {
        $request = Request::create('/academico/scanner', 'POST');
        $request->headers->set('Accept', 'application/json');

        $response = $this->invocarHandler($request);

        $this->assertNotSame(302, $response->getStatusCode());
    }

    private function invocarHandler(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $request->setLaravelSession($this->app['session.store']);

        $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);

        return $handler->render($request, new TokenMismatchException('CSRF token mismatch.'));
    }
}
