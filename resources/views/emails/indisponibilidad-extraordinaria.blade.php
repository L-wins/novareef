<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Indisponibilidad extraordinaria — NovaReef</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', Arial, sans-serif; background: #f0f4f8; color: #1a202c; }

        .wrapper  { max-width: 600px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.10); }

        .header   { background: #1a1f2e; padding: 28px 32px; text-align: center; }
        .header-logo { font-size: 1.35rem; font-weight: 800; color: #ffffff; letter-spacing: -0.02em; }
        .header-logo span { color: #4f8ef7; }

        .alert-banner { background: #fef9c3; border-left: 4px solid #eab308; padding: 14px 24px; font-size: 0.9rem; color: #713f12; }
        .alert-banner strong { display: block; font-size: 1rem; margin-bottom: 2px; }

        .body     { padding: 28px 32px; }

        .section-title { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: #64748b; margin-bottom: 12px; }

        .info-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px; }
        .info-row  { display: flex; justify-content: space-between; align-items: flex-start; padding: 6px 0; border-bottom: 1px solid #e2e8f0; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-size: 0.82rem; color: #64748b; flex: 0 0 140px; }
        .info-value { font-size: 0.88rem; color: #1e293b; font-weight: 500; text-align: right; }

        .motivo-block { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 14px 16px; margin-bottom: 20px; font-size: 0.88rem; color: #9a3412; line-height: 1.55; }

        .partidos-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; margin-bottom: 20px; }
        .partidos-table th { background: #f1f5f9; color: #475569; font-weight: 600; padding: 8px 12px; text-align: left; border-bottom: 2px solid #e2e8f0; }
        .partidos-table td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .partidos-table tr:last-child td { border-bottom: none; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 99px; font-size: 0.72rem; font-weight: 600; }
        .badge-warning { background: #fef3c7; color: #92400e; }

        .cta-wrap { text-align: center; margin: 24px 0 4px; }
        .cta-btn  { display: inline-block; background: #4f8ef7; color: #ffffff !important; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; }

        .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 32px; text-align: center; font-size: 0.78rem; color: #94a3b8; }
    </style>
</head>
<body>
<div class="wrapper">

    {{-- Header --}}
    <div class="header">
        <div class="header-logo">Nova<span>Reef</span></div>
    </div>

    {{-- Alerta --}}
    <div class="alert-banner">
        <strong>⚠️ Indisponibilidad extraordinaria reportada</strong>
        Un árbitro ha reportado que no puede atender sus compromisos.
    </div>

    <div class="body">

        {{-- Datos del árbitro --}}
        <p class="section-title">Árbitro</p>
        <div class="info-card">
            <div class="info-row">
                <span class="info-label">Nombre</span>
                <span class="info-value">{{ $arbitro->usuario?->nombreUsuario ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Categoría</span>
                <span class="info-value">{{ $arbitro->categoria?->nombreCategoria ?? '—' }}</span>
            </div>
        </div>

        {{-- Detalle de la indisponibilidad --}}
        <p class="section-title">Detalle</p>
        <div class="info-card">
            <div class="info-row">
                <span class="info-label">Fecha afectada</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($fecha)->translatedFormat('l d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Franja horaria</span>
                <span class="info-value">{{ \App\Models\DisponibilidadArbitro::getFranjas()[$franja] ?? $franja }}</span>
            </div>
        </div>

        {{-- Motivo --}}
        <p class="section-title">Motivo reportado</p>
        <div class="motivo-block">{{ $motivo }}</div>

        {{-- Partidos afectados --}}
        @if ($partidosAfectados->isNotEmpty())
        <p class="section-title">
            Partidos confirmados afectados
            <span class="badge badge-warning">{{ $partidosAfectados->count() }}</span>
        </p>
        <table class="partidos-table">
            <thead>
                <tr>
                    <th>Partido</th>
                    <th>Torneo</th>
                    <th>Fecha / Hora</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($partidosAfectados as $des)
                <tr>
                    <td>{{ $des->partido?->equipoLocal ?? '—' }} vs {{ $des->partido?->equipoVisitante ?? '—' }}</td>
                    <td>{{ $des->partido?->torneo?->nombreTorneo ?? '—' }}</td>
                    <td>{{ $des->partido?->fechaPartido?->format('d/m/Y') }} · {{ \Illuminate\Support\Carbon::parse($des->partido?->horaPartido)->format('H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="cta-wrap">
            <a href="{{ url('/torneos') }}" class="cta-btn">Ver designaciones afectadas</a>
        </div>
        @endif

    </div>

    <div class="footer">
        Este correo fue generado automáticamente por <strong>NovaReef</strong>.<br>
        No respondas a este mensaje.
    </div>

</div>
</body>
</html>
