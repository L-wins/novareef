<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recupera tu contraseña de NovaReef</title>
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
            margin-bottom: 4px;
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
        .cta-btn {
            display: block;
            margin: 28px 0;
            padding: 14px 24px;
            background-color: #10b981;
            color: #ffffff !important;
            text-align: center;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            border-radius: 10px;
        }
        .fallback-url {
            font-size: 13px;
            color: #64748b;
            word-break: break-all;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 24px;
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

            <h1>Recupera tu contraseña</h1>
            <p class="welcome-text">
                Hola <strong>{{ $nombreUsuario }}</strong>, recibimos una solicitud para restablecer
                la contraseña de tu cuenta en NovaReef. Haz clic en el botón de abajo para elegir una
                nueva contraseña. Este enlace expira en {{ $minutosExpiracion }} minutos.
            </p>

            <a href="{{ $urlRestablecimiento }}" class="cta-btn">Restablecer contraseña →</a>

            <p class="fallback-url">
                Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                {{ $urlRestablecimiento }}
            </p>

            {{-- Aviso --}}
            <div class="alert">
                <span class="alert-icon">⚠️</span>
                <div class="alert-text">
                    <strong>¿No fuiste tú?</strong> Si no solicitaste este cambio, ignora este correo —
                    tu contraseña actual sigue funcionando y no se realizó ningún cambio en tu cuenta.
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <hr class="divider">
        <div class="footer">
            <strong>NovaReef</strong> — Sistema de Gestión de Árbitros<br>
            Este correo fue generado automáticamente. Por favor no respondas a este mensaje.<br>
            Si tienes dudas, escríbenos a
            <a href="mailto:soporte@novareef.com" style="color:#10b981;">soporte@novareef.com</a>
        </div>

    </div>
</body>
</html>
