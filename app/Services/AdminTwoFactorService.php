<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Admin;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

final class AdminTwoFactorService
{
    private const QR_SIZE   = 220;
    private const QR_ISSUER = 'NovaReef';

    public function __construct(private readonly Google2FA $google2fa) {}

    /**
     * Devuelve el secret TOTP existente del admin o genera uno nuevo y lo persiste.
     * saveQuietly() evita disparar eventos de modelo durante la configuración inicial.
     */
    public function obtenerOCrearSecret(Admin $admin): string
    {
        $secret = $admin->getRawOriginal('google2fa_secret');

        if ($secret) {
            return $secret;
        }

        $secret = $this->google2fa->generateSecretKey();
        $admin->forceFill(['google2fa_secret' => $secret])->saveQuietly();

        return $secret;
    }

    /**
     * Genera el SVG del QR para escanear con la app de autenticación.
     * Backend SVG: no depende de extensiones de imagen de PHP (gd/imagick).
     */
    public function generarQrSvg(string $email, string $secret): string
    {
        $otpUrl   = $this->google2fa->getQRCodeUrl(self::QR_ISSUER, $email, $secret);
        $renderer = new ImageRenderer(new RendererStyle(self::QR_SIZE), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($otpUrl);
    }

    /**
     * Verifica un código TOTP de 6 dígitos contra el secreto guardado del admin.
     * Única fuente de verdad: la usan tanto la activación de 2FA como el login.
     */
    public function verificarCodigo(Admin $admin, string $codigo): bool
    {
        $secret = $admin->getRawOriginal('google2fa_secret');

        return $secret !== null && $this->google2fa->verifyKey($secret, $codigo);
    }

    /**
     * Activa 2FA para el admin tras confirmar el código de su app de autenticación.
     *
     * @throws \RuntimeException si no hay secreto pendiente o el código es inválido.
     */
    public function activar(Admin $admin, string $codigo): void
    {
        if (! $admin->getRawOriginal('google2fa_secret')) {
            throw new \RuntimeException('No se encontró clave secreta. Recarga la página.');
        }

        if (! $this->verificarCodigo($admin, $codigo)) {
            throw new \RuntimeException('Código inválido. Verifica tu aplicación de autenticación e inténtalo de nuevo.');
        }

        $admin->update(['two_factor_enabled' => true]);
    }

    /**
     * Desactiva 2FA para el admin tras confirmar su contraseña.
     *
     * @throws \RuntimeException si la contraseña no coincide.
     */
    public function desactivar(Admin $admin, string $password): void
    {
        if (! Hash::check($password, $admin->getAuthPassword())) {
            throw new \RuntimeException('Contraseña incorrecta.');
        }

        $admin->update([
            'two_factor_enabled' => false,
            'google2fa_secret'   => null,
        ]);
    }
}
