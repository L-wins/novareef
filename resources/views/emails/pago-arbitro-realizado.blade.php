<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago de nómina — NovaReef</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 15px; color: #1e293b; }
        .wrapper { max-width: 580px; margin: 40px auto; padding: 0 16px 40px; }
        .header { text-align: center; padding: 32px 0 24px; }
        .logo-name { font-size: 22px; font-weight: 700; color: #0f172a; }
        .logo-dot { color: #4f8ef7; }
        .card { background: #fff; border-radius: 16px; padding: 40px; border: 1px solid #e2e8f0; }
        .banner { background: #f0fdf4; border-radius: 10px; padding: 20px; text-align: center; margin-bottom: 28px; border: 2px solid #bbf7d0; }
        .banner-icon { font-size: 32px; margin-bottom: 8px; }
        .banner-title { font-size: 20px; font-weight: 800; color: #16a34a; }
        .match-box { background: #f8fafc; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .match-meta { font-size: 13px; color: #475569; line-height: 1.9; }
        .footer { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 24px; line-height: 1.6; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header"><div class="logo-name">Nova<span class="logo-dot">Reef</span></div></div>
    <div class="card">
        <div class="banner">
            <div class="banner-icon">💰</div>
            <div class="banner-title">¡Tu pago de nómina fue registrado!</div>
        </div>

        <div class="match-box">
            <div class="match-meta">
                Hola {{ $arbitro->usuario->nombreUsuario }},<br><br>
                El colegio registró un pago acumulado a tu favor.<br><br>
                💵 Neto desembolsado: <strong>${{ number_format($netoDesembolsado, 2) }}</strong><br>
                @if ($totalDeudasNeteadas > 0)
                    📉 Deudas compensadas: <strong>${{ number_format($totalDeudasNeteadas, 2) }}</strong><br>
                @endif
                🧾 Referencia de lote: {{ $idLotePago }}
            </div>
        </div>
    </div>
    <div class="footer">Notificación automática de NovaReef · Sistema de gestión de árbitros</div>
</div>
</body>
</html>
