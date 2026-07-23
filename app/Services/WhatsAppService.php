<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envío de mensajes vía WhatsApp Cloud API (Meta), credenciales en
 * config('services.whatsapp'). Dos formas de envío:
 *
 * - enviarTexto(): mensaje de texto libre. Solo funciona dentro de la
 *   "ventana de servicio" de 24h desde el último mensaje que el destinatario
 *   le envió al número — Meta lo rechaza fuera de esa ventana. Útil para
 *   pruebas manuales (en modo prueba, respondiendo al número de prueba) pero
 *   NO sirve para iniciar conversación con un usuario nuevo (ej. avisarle
 *   sus credenciales apenas se crea la cuenta).
 * - enviarPlantilla(): usa una plantilla pre-aprobada por Meta — es el único
 *   tipo de mensaje que puede iniciar conversación fuera de esa ventana.
 *   Necesaria para el flujo de credenciales.
 *
 * En modo prueba (WHATSAPP_MODO_PRUEBA=true) el número solo puede escribirle
 * a destinatarios agregados manualmente en el panel de Meta — cualquier otro
 * número devuelve error de la API, no un fallo silencioso de este servicio.
 */
final class WhatsAppService
{
    private const API_VERSION = 'v21.0';

    /**
     * @throws \RuntimeException  Si faltan credenciales configuradas.
     */
    public function enviarTexto(string $telefono, string $mensaje): Response
    {
        return $this->post([
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizarTelefono($telefono),
            'type'              => 'text',
            'text'              => ['body' => $mensaje],
        ]);
    }

    /**
     * Envía un mensaje de plantilla. $parametros es un array asociativo
     * nombre_variable => valor — Meta exige plantillas con variables con
     * nombre (parameter_format: NAMED, ej. {{colegio}}, {{password}}) desde
     * que dejó de aceptar el formato posicional {{1}}/{{2}} para plantillas
     * nuevas; la clave de cada entrada debe coincidir exactamente con el
     * nombre de variable configurado en la plantilla aprobada en Meta.
     *
     * @param  array<string, string>  $parametros
     *
     * @throws \RuntimeException  Si faltan credenciales configuradas.
     */
    public function enviarPlantilla(string $telefono, string $nombrePlantilla, array $parametros = [], string $idioma = 'es'): Response
    {
        $componentes = [];

        if ($parametros !== []) {
            $componentes[] = [
                'type'       => 'body',
                'parameters' => array_map(
                    fn (string $valor, string $nombre) => ['type' => 'text', 'parameter_name' => $nombre, 'text' => $valor],
                    array_values($parametros),
                    array_keys($parametros),
                ),
            ];
        }

        return $this->post([
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizarTelefono($telefono),
            'type'              => 'template',
            'template'          => [
                'name'     => $nombrePlantilla,
                'language' => ['code' => $idioma],
                'components' => $componentes,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws \RuntimeException  Si faltan credenciales configuradas.
     */
    private function post(array $payload): Response
    {
        $token         = config('services.whatsapp.token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        if (! $token || ! $phoneNumberId) {
            throw new \RuntimeException(
                'WhatsApp no está configurado — faltan WHATSAPP_TOKEN o WHATSAPP_PHONE_NUMBER_ID en .env.'
            );
        }

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/" . self::API_VERSION . "/{$phoneNumberId}/messages", $payload);

        if ($response->failed()) {
            Log::error('WhatsApp: envío fallido', [
                'to'     => $payload['to'] ?? null,
                'type'   => $payload['type'] ?? null,
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);
        }

        return $response;
    }

    /**
     * Meta exige el número en formato E.164 sin "+" ni espacios (ej.
     * 573202029121 para Colombia). Acepta el número tal como suele venir
     * guardado en `usuarios.telefonoUsuario` (solo dígitos, con o sin
     * indicativo) y antepone el indicativo de Colombia (57) si el número
     * no lo trae — asunción válida hoy porque NovaReef solo opera en
     * Colombia; revisar si el proyecto se expande a otro país.
     */
    private function normalizarTelefono(string $telefono): string
    {
        $soloDigitos = preg_replace('/\D+/', '', $telefono) ?? '';

        if (strlen($soloDigitos) === 10) {
            return '57' . $soloDigitos;
        }

        return $soloDigitos;
    }
}
