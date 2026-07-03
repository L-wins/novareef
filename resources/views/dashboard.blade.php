@extends('layouts.app')

@section('titulo', 'Panel de control')

@php
    $dias   = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    $meses  = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto',
                'septiembre','octubre','noviembre','diciembre'];
    $hoy    = $dias[now()->dayOfWeek]
            . ', ' . now()->day
            . ' de ' . $meses[now()->month - 1]
            . ' de ' . now()->year;

    $modulos = [
        ['key' => 'arbitros',       'permiso' => 'ver-arbitros',       'ruta' => 'arbitros.index',   'icono' => 'fa-users',           'color' => 'ic-teal',    'nombre' => 'Árbitros',       'desc' => 'Expedientes, categorías y estadísticas',  'activo' => true],
        ['key' => 'torneos',        'permiso' => 'ver-torneos',        'ruta' => null,               'icono' => 'fa-trophy',          'color' => 'ic-amber',   'nombre' => 'Torneos',        'desc' => 'Competencias, equipos y partidos',         'activo' => false],
        ['key' => 'designaciones',  'permiso' => 'ver-designaciones',  'ruta' => null,               'icono' => 'fa-clipboard-list',  'color' => 'ic-emerald', 'nombre' => 'Designaciones',  'desc' => 'Asignación de árbitros a partidos',        'activo' => false],
        ['key' => 'finanzas',       'permiso' => 'ver-finanzas',       'ruta' => null,               'icono' => 'fa-money-bill-wave', 'color' => 'ic-blue',    'nombre' => 'Finanzas',       'desc' => 'Pagos, cuotas e ingresos del colegio',     'activo' => false],
        ['key' => 'academico',      'permiso' => 'ver-academico',      'ruta' => null,               'icono' => 'fa-graduation-cap',  'color' => 'ic-purple',  'nombre' => 'Académico',      'desc' => 'Cursos, evaluaciones y formación',         'activo' => false],
        ['key' => 'sanciones',      'permiso' => 'ver-sanciones',      'ruta' => null,               'icono' => 'fa-gavel',           'color' => 'ic-red',     'nombre' => 'Sanciones',      'desc' => 'Gestión disciplinaria de árbitros',        'activo' => false],
    ];
@endphp

@section('contenido')
<div class="container">

    {{-- === BIENVENIDA === --}}
    <section class="welcome-card">
        <div class="welcome-badge">
            <span class="badge-dot"></span>
            Sesión activa
        </div>
        <h1 class="welcome-title">Bienvenido a NovaReef</h1>
        <p class="welcome-sub">
            Hola, <strong>{{ Auth::user()->nombreUsuario }}</strong>. Desde aquí gestionas
            las operaciones de tu colegio de árbitros. Selecciona un módulo para comenzar.
        </p>
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

    {{-- === ESTADÍSTICAS === --}}
    <div class="section-head">
        <span class="section-label">Resumen del colegio</span>
        <div class="section-rule"></div>
    </div>

    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon-box ic-emerald">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-value val-emerald">{{ $arbitrosRegistrados }}</div>
            <div class="stat-label">Árbitros registrados</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon-box ic-teal">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="stat-value val-teal">{{ $arbitrosActivos }}</div>
            <div class="stat-label">Árbitros activos</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon-box ic-amber">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div class="stat-value val-amber">{{ $arbitrosProceso }}</div>
            <div class="stat-label">En proceso de ingreso</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon-box ic-purple">
                <i class="fa-solid fa-user-group"></i>
            </div>
            <div class="stat-value val-purple">{{ $totalUsuarios }}</div>
            <div class="stat-label">Usuarios del colegio</div>
        </div>

    </div>

    {{-- === MÓDULOS === --}}
    <section class="modules-section">
        <div class="section-head">
            <span class="section-label">Módulos disponibles</span>
            <div class="section-rule"></div>
        </div>
        <div class="modules-grid">

            @foreach ($modulos as $modulo)
                @php $incluidoEnPlan = in_array($modulo['key'], $modulosPlan ?? [], true); @endphp
                @can($modulo['permiso'])
                    @if (! $incluidoEnPlan)
                        <div class="module-card module-card--locked">
                            <div class="mod-icon-box {{ $modulo['color'] }}" style="opacity:0.35;">
                                <i class="fa-solid {{ $modulo['icono'] }}"></i>
                            </div>
                            <div class="mod-info">
                                <div class="mod-name" style="opacity:0.4;">{{ $modulo['nombre'] }}</div>
                                <div class="mod-desc" style="opacity:0.3;">{{ $modulo['desc'] }}</div>
                            </div>
                            <span class="mod-badge badge-locked">No incluido en tu plan</span>
                        </div>
                    @elseif ($modulo['activo'] && $modulo['ruta'])
                        <a href="{{ route($modulo['ruta']) }}" class="module-card module-card--link">
                            <div class="mod-icon-box {{ $modulo['color'] }}">
                                <i class="fa-solid {{ $modulo['icono'] }}"></i>
                            </div>
                            <div class="mod-info">
                                <div class="mod-name">{{ $modulo['nombre'] }}</div>
                                <div class="mod-desc">{{ $modulo['desc'] }}</div>
                            </div>
                        </a>
                    @else
                        <div class="module-card">
                            <div class="mod-icon-box {{ $modulo['color'] }}">
                                <i class="fa-solid {{ $modulo['icono'] }}"></i>
                            </div>
                            <div class="mod-info">
                                <div class="mod-name">{{ $modulo['nombre'] }}</div>
                                <div class="mod-desc">{{ $modulo['desc'] }}</div>
                            </div>
                            <span class="mod-badge badge-soon">Pronto</span>
                        </div>
                    @endif
                @else
                    <div class="module-card module-card--locked">
                        <div class="mod-icon-box {{ $modulo['color'] }}" style="opacity:0.35;">
                            <i class="fa-solid {{ $modulo['icono'] }}"></i>
                        </div>
                        <div class="mod-info">
                            <div class="mod-name" style="opacity:0.4;">{{ $modulo['nombre'] }}</div>
                            <div class="mod-desc" style="opacity:0.3;">{{ $modulo['desc'] }}</div>
                        </div>
                        <span class="mod-badge badge-locked">Sin acceso</span>
                    </div>
                @endcan
            @endforeach

        </div>
    </section>

</div>
@endsection
