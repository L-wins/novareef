<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tus credenciales de acceso a NovaReef</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 15px;
            color: #1e293b;
            -webkit-text-size-adjust: 100%;
        }
        .wrapper {
            max-width: 580px;
            margin: 40px auto;
            padding: 0 16px 40px;
        }
        .header {
            text-align: center;
            padding: 32px 0 24px;
        }
        .logo-img {
            height: 40px;
        }
        .card {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        h1 {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .welcome-text {
            color: #475569;
            line-height: 1.6;
            margin-bottom: 28px;
        }
        .section-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .credential-row {
            margin-bottom: 16px;
        }
        .credential-value {
            font-size: 15px;
            font-weight: 500;
            color: #0f172a;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            word-break: break-all;
        }
        .credential-value.mono {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 17px;
            letter-spacing: 0.03em;
        }
        .cta-btn {
            display: block;
            margin: 28px 0;
            padding: 14px 24px;
            background-color: #4f8ef7;
            color: #ffffff !important;
            text-align: center;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            border-radius: 10px;
        }
        .alert {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 10px;
            padding: 14px 16px;
            margin-top: 24px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .alert-icon {
            font-size: 18px;
            line-height: 1;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .alert-text {
            font-size: 13px;
            color: #92400e;
            line-height: 1.5;
        }
        .alert-text strong {
            color: #78350f;
        }
        .tip {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 14px 16px;
            margin-top: 14px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .tip-icon {
            font-size: 16px;
            line-height: 1;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .tip-text {
            font-size: 13px;
            color: #1e40af;
            line-height: 1.5;
        }
        .tip-text strong {
            color: #1e3a8a;
        }
        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 32px 0 24px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.6;
        }
        .footer strong {
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="wrapper">

        {{-- Logo --}}
        <div class="header">
            <img src="{{ asset('images/logo/novareef-logo-light.png') }}" alt="NovaReef" class="logo-img">
        </div>

        {{-- Card --}}
        <div class="card">

            <h1>¡Bienvenido a NovaReef!</h1>
            <p class="welcome-text">
                El colegio <strong>{{ $nombreColegio }}</strong> ha sido registrado exitosamente
                en la plataforma. A continuación encontrarás tus credenciales de acceso como
                administrador.
            </p>

            {{-- URL de acceso --}}
            <div class="credential-row">
                <p class="section-label">URL de acceso</p>
                <p class="credential-value">
                    <a href="{{ $urlAcceso }}" style="color:#4f8ef7;text-decoration:none;">
                        {{ $urlAcceso }}
                    </a>
                </p>
            </div>

            {{-- Usuario --}}
            <div class="credential-row">
                <p class="section-label">Usuario ({{ $usernameUsuario ? 'nombre de usuario' : 'correo electrónico' }})</p>
                <p class="credential-value">{{ $usernameUsuario ?? $emailAdmin }}</p>
            </div>

            {{-- Contraseña — {!! !!} evita que & se convierta en &amp; en el HTML del email --}}
            <div class="credential-row">
                <p class="section-label">Contraseña temporal</p>
                <p class="credential-value mono">{!! $passwordGenerado !!}</p>
            </div>

            <a href="{{ $urlAcceso }}" class="cta-btn">Ingresar al panel →</a>

            {{-- Aviso --}}
            <div class="alert">
                <span class="alert-icon">⚠️</span>
                <div class="alert-text">
                    <strong>Acción requerida:</strong> Por seguridad, debes cambiar esta contraseña
                    la primera vez que inicies sesión. No compartas estas credenciales con nadie.
                </div>
            </div>

            @unless($usernameUsuario)
            {{-- Tip --}}
            <div class="tip">
                <span class="tip-icon">💡</span>
                <div class="tip-text">
                    <strong>¿Prefieres entrar con un usuario en vez del correo?</strong>
                    Puedes configurarlo cuando quieras desde
                    <strong>Configuración → Cuentas Admin</strong> dentro del panel.
                </div>
            </div>
            @endunless

        </div>

        {{-- Footer --}}
        <hr class="divider">
        <div class="footer">
            <strong>NovaReef</strong> — Sistema de Gestión de Árbitros<br>
            Este correo fue generado automáticamente. Por favor no respondas a este mensaje.<br>
            Si tienes dudas, escríbenos a
            <a href="mailto:soporte@novareef.com" style="color:#4f8ef7;">soporte@novareef.com</a>
        </div>

    </div>
</body>
</html>
