<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Designación rechazada — NovaReef</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 15px; color: #1e293b; -webkit-text-size-adjust: 100%;
        }
        .wrapper { max-width: 580px; margin: 40px auto; padding: 0 16px 40px; }
        .header { text-align: center; padding: 32px 0 24px; }
        .logo-name { font-size: 22px; font-weight: 700; color: #0f172a; }
        .logo-dot { color: #4f8ef7; }
        .card { background: #ffffff; border-radius: 16px; padding: 40px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .alert-header { background: #fef2f2; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; border-left: 4px solid #dc2626; }
        .alert-header h1 { font-size: 18px; font-weight: 700; color: #dc2626; }
        .alert-header p { font-size: 14px; color: #7f1d1d; margin-top: 4px; }
        .match-info { background: #f8fafc; border-radius: 10px; padding: 16px; margin-bottom: 20px; }
        .match-name { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .match-detail { font-size: 13px; color: #475569; line-height: 1.6; }
        .motivo-box { background: #fff7ed; border: 1px solid #fed7aa; border-left: 4px solid #f97316; border-radius: 10px; padding: 16px; margin-bottom: 24px; }
        .motivo-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #9a3412; margin-bottom: 6px; }
        .motivo-text { font-size: 14px; color: #431407; line-height: 1.6; font-style: italic; }
        .btn-gestionar {
            display: block; background: #4f8ef7; color: #ffffff;
            text-align: center; text-decoration: none; font-size: 15px; font-weight: 700;
            padding: 16px; border-radius: 10px;
        }
        .footer { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 24px; line-height: 1.6; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div class="logo-name">Nova<span class="logo-dot">Reef</span></div>
    </div>
    <div class="card">
        @php
            $partido = $designacion->partido;
            $arbitro = $designacion->arbitro?->usuario?->nombreUsuario ?? 'El árbitro';
            $fecha   = $partido->fechaPartido?->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
        @endphp

        <div class="alert-header">
            <h1>🔴 Designación rechazada</h1>
            <p><strong>{{ $arbitro }}</strong> ha rechazado su designación para el siguiente partido.</p>
        </div>

        <div class="match-info">
            <div class="match-name">{{ $partido->equipoLocal }} vs {{ $partido->equipoVisitante }}</div>
            <div class="match-detail">
                {{ $partido->torneo?->nombreTorneo }} — {{ $partido->division?->nombreDivision }}<br>
                📅 {{ ucfirst($fecha ?? '—') }} · 🕐 {{ $partido->horaPartido ?? '—' }}<br>
                📍 {{ $partido->sede?->nombreSede ?? '—' }}, {{ $partido->sede?->ciudad ?? '' }}<br>
                Rol: {{ $designacion->rol?->nombre ?? 'Árbitro' }}
            </div>
        </div>

        @if($designacion->motivoRechazo)
        <div class="motivo-box">
            <div class="motivo-label">Motivo del rechazo</div>
            <div class="motivo-text">"{{ $designacion->motivoRechazo }}"</div>
        </div>
        @endif

        <a href="{{ route('designaciones.show', $partido->idPartido) }}" class="btn-gestionar">
            Gestionar designaciones del partido →
        </a>
    </div>
    <div class="footer">
        Notificación automática de NovaReef · Sistema de gestión de árbitros
    </div>
</div>
</body>
</html>
