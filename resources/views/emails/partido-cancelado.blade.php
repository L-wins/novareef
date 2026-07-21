<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partido cancelado — NovaReef</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 15px; color: #1e293b; }
        .wrapper { max-width: 580px; margin: 40px auto; padding: 0 16px 40px; }
        .header { text-align: center; padding: 32px 0 24px; }
        .logo-name { font-size: 22px; font-weight: 700; color: #0f172a; }
        .logo-dot { color: #4f8ef7; }
        .card { background: #fff; border-radius: 16px; padding: 40px; border: 1px solid #e2e8f0; }
        .banner { background: #fef2f2; border-radius: 10px; padding: 20px; text-align: center; margin-bottom: 28px; border: 2px solid #fecaca; }
        .banner-icon { font-size: 32px; margin-bottom: 8px; }
        .banner-title { font-size: 20px; font-weight: 800; color: #dc2626; }
        .banner-sub { font-size: 14px; color: #7f1d1d; margin-top: 4px; }
        .match-box { background: #f8fafc; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .match-name { font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .match-meta { font-size: 13px; color: #475569; line-height: 1.7; }
        .note { font-size: 13px; color: #475569; background: #f1f5f9; border-radius: 8px; padding: 14px; margin-top: 16px; line-height: 1.6; }
        .footer { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 24px; line-height: 1.6; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header"><div class="logo-name">Nova<span class="logo-dot">Reef</span></div></div>
    <div class="card">
        @php
            $fecha = $partido->fechaPartido?->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
        @endphp

        <div class="banner">
            <div class="banner-icon">❌</div>
            <div class="banner-title">Partido cancelado</div>
            <div class="banner-sub">Tu designación para este partido ha quedado sin efecto.</div>
        </div>

        <div class="match-box">
            <div class="match-name">{{ $partido->equipoLocal }} vs {{ $partido->equipoVisitante }}</div>
            <div class="match-meta">
                📋 {{ $partido->torneo?->nombreTorneo }}<br>
                📅 {{ ucfirst($fecha ?? '—') }} · 🕐 {{ $partido->horaPartido ?? '—' }}<br>
                📍 {{ $partido->sede?->nombreSede ?? '—' }}, {{ $partido->sede?->ciudad ?? '' }}<br>
                Tu rol: {{ $designacion->rol?->nombre ?? 'Árbitro' }}
            </div>
        </div>

        <div class="note">
            ℹ️ Este partido ha sido cancelado por el equipo administrativo de tu colegio. Si tienes dudas sobre la cancelación, comunícate directamente con tu designador.
        </div>
    </div>
    <div class="footer">Notificación automática de NovaReef · Sistema de gestión de árbitros</div>
</div>
</body>
</html>
