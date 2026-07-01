<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva designación — NovaReef</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 15px;
            color: #1e293b;
            -webkit-text-size-adjust: 100%;
        }
        .wrapper { max-width: 580px; margin: 40px auto; padding: 0 16px 40px; }
        .header { text-align: center; padding: 32px 0 24px; }
        .logo-name { font-size: 22px; font-weight: 700; color: #0f172a; letter-spacing: -0.5px; }
        .logo-dot { color: #4f8ef7; }
        .card { background: #ffffff; border-radius: 16px; padding: 40px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        h1 { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .sub { color: #475569; line-height: 1.6; margin-bottom: 24px; }
        .match-title {
            font-size: 22px; font-weight: 800; color: #0f172a;
            text-align: center; margin: 20px 0 24px;
            padding: 20px; background: #f8fafc; border-radius: 12px;
            border: 2px solid #e2e8f0; letter-spacing: -0.5px;
        }
        .match-title span { color: #94a3b8; font-size: 14px; font-weight: 500; display: block; margin-top: 4px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
        .info-item { background: #f8fafc; border-radius: 10px; padding: 14px 16px; }
        .info-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; margin-bottom: 4px; }
        .info-value { font-size: 14px; font-weight: 600; color: #1e293b; }
        .rol-badge {
            display: inline-block; background: #dbeafe; color: #1e40af;
            font-size: 13px; font-weight: 700; padding: 6px 14px;
            border-radius: 20px; margin-bottom: 24px;
        }
        .reminder-box {
            background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b;
            border-radius: 10px; padding: 14px 16px; margin-bottom: 24px;
            font-size: 13px; color: #92400e; line-height: 1.55;
        }
        .maps-link {
            display: inline-block; background: #f1f5f9; color: #4f8ef7;
            font-size: 13px; font-weight: 600; padding: 8px 14px;
            border-radius: 8px; text-decoration: none; margin-bottom: 24px;
        }
        .btn-row { display: flex; gap: 12px; margin-top: 8px; }
        .btn-confirm {
            flex: 1; display: block; background: #16a34a; color: #ffffff;
            text-align: center; text-decoration: none; font-size: 15px; font-weight: 700;
            padding: 16px; border-radius: 10px;
        }
        .btn-reject {
            flex: 1; display: block; background: #dc2626; color: #ffffff;
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
        <h1>¡Tienes una nueva designación!</h1>
        <p class="sub">Hola <strong>{{ $designacion->arbitro?->usuario?->nombreUsuario ?? 'Árbitro' }}</strong>, has sido designado para el siguiente partido:</p>

        <div class="match-title">
            {{ $designacion->partido->equipoLocal }} vs {{ $designacion->partido->equipoVisitante }}
            <span>{{ $designacion->partido->torneo?->nombreTorneo }} — {{ $designacion->partido->division?->nombreDivision }}</span>
        </div>

        @php
            $partido = $designacion->partido;
            $fecha   = $partido->fechaPartido?->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');
            $hora    = $partido->horaPartido;
            $sede    = $partido->sede;
        @endphp

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Fecha</div>
                <div class="info-value">{{ ucfirst($fecha ?? '—') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Hora</div>
                <div class="info-value">{{ $hora ?? '—' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Sede</div>
                <div class="info-value">{{ $sede?->nombreSede ?? '—' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Municipio</div>
                <div class="info-value">{{ $sede?->municipio ?? '—' }}</div>
            </div>
        </div>

        <div>Tu rol: <span class="rol-badge">{{ $designacion->rol?->nombre ?? 'Árbitro' }}</span></div>

        @if($sede?->urlMaps)
        <a href="{{ $sede->urlMaps }}" class="maps-link" target="_blank">📍 Ver ubicación en Google Maps</a>
        @endif

        @if($partido->torneo?->tipoTorneo === 'oficial')
        <div class="reminder-box">
            <strong>⚠️ RECORDATORIO:</strong> Este es un partido de torneo oficial. Recuerda reportarte en el sistema COMET antes del partido según los protocolos de tu colegio.
        </div>
        @endif

        <div class="btn-row">
            <a href="{{ route('mis-partidos.confirmar', $designacion->idDesignacion) }}" class="btn-confirm">✅ Confirmar designación</a>
            <a href="{{ route('mis-partidos.index') }}#rechazar-{{ $designacion->idDesignacion }}" class="btn-reject">❌ No puedo asistir</a>
        </div>
    </div>
    <div class="footer">
        Este correo fue enviado automáticamente por NovaReef.<br>
        Sistema de gestión para colegios de árbitros de fútbol.
    </div>
</div>
</body>
</html>
