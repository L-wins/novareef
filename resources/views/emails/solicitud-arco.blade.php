<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva solicitud de derechos ARCO</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 15px;
            color: #1e293b;
        }
        .wrapper { max-width: 580px; margin: 40px auto; padding: 0 16px 40px; }
        .card {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            border: 1px solid #e2e8f0;
        }
        h1 { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        p { color: #475569; line-height: 1.6; margin-bottom: 16px; }
        .campo { margin-bottom: 16px; }
        .campo strong { display: block; color: #0f172a; margin-bottom: 4px; }
        .mensaje {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 16px;
            white-space: pre-wrap;
        }
        .footer { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <h1>Nueva solicitud de derechos ARCO</h1>
            <p>Un árbitro de tu colegio ejerció uno de sus derechos sobre datos personales (Ley 1581 de 2012).</p>

            <div class="campo">
                <strong>Solicitante</strong>
                {{ $nombreSolicitante }}
            </div>
            <div class="campo">
                <strong>Tipo de solicitud</strong>
                {{ ucfirst($tipo) }}
            </div>
            <div class="campo">
                <strong>Mensaje</strong>
                <div class="mensaje">{{ $mensaje }}</div>
            </div>

            <p>Atiéndela desde el panel de NovaReef, dentro de los plazos que establece la ley.</p>
        </div>
        <div class="footer">NovaReef — Este correo fue generado automáticamente.</div>
    </div>
</body>
</html>
