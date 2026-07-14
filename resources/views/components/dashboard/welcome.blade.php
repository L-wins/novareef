@props(['subtitulo' => 'Selecciona una opción del menú para comenzar.'])

@php
    $dias  = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $hoy   = $dias[now()->dayOfWeek] . ', ' . now()->day . ' de ' . $meses[now()->month - 1] . ' de ' . now()->year;
@endphp

<section class="welcome-card">
    <div class="welcome-badge">
        <span class="badge-dot"></span>
        Sesión activa
    </div>
    <h1 class="welcome-title">Hola, {{ Auth::user()->nombreUsuario }}</h1>
    <p class="welcome-sub">{{ $subtitulo }}</p>
    <div class="welcome-meta">
        <div class="meta-item">
            <i class="fa-solid fa-calendar-days"></i>
            {{ $hoy }}
        </div>
        <div class="meta-item">
            <i class="fa-solid fa-clock"></i>
            {{ now()->format('H:i') }} (hora local)
        </div>
    </div>
</section>
