{{-- $modulosPlan se recibe explícito: los componentes anónimos NO heredan el
     scope de variables de la vista que los incluye (solo props/slots), aunque
     el composer lo inyecte en dashboard.ejecutivo. --}}
@props(['modulos', 'modulosPlan' => [], 'compact' => false])

@if ($compact)
    {{-- Barra de accesos rápidos: solo lo que el usuario ya puede usar hoy —
         un botón deshabilitado hasta arriba de la página no ayuda a nadie. --}}
    <div class="quick-actions">
        @foreach ($modulos as $modulo)
            @php($incluidoEnPlan = in_array($modulo['key'], $modulosPlan, true))
            @can($modulo['permiso'])
                @if ($incluidoEnPlan)
                    <a href="{{ route($modulo['ruta']) }}" class="quick-action">
                        <span class="quick-action__icon {{ $modulo['color'] }}"><i class="fa-solid {{ $modulo['icono'] }}"></i></span>
                        <span>{{ $modulo['nombre'] }}</span>
                    </a>
                @endif
            @endcan
        @endforeach
    </div>
@else
    <section class="modules-section">
        <div class="section-head">
            <span class="section-label">Accesos rápidos</span>
            <div class="section-rule"></div>
        </div>
        <div class="modules-grid">
            @foreach ($modulos as $modulo)
                @php($incluidoEnPlan = in_array($modulo['key'], $modulosPlan, true))
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
                    @else
                        <a href="{{ route($modulo['ruta']) }}" class="module-card module-card--link">
                            <div class="mod-icon-box {{ $modulo['color'] }}">
                                <i class="fa-solid {{ $modulo['icono'] }}"></i>
                            </div>
                            <div class="mod-info">
                                <div class="mod-name">{{ $modulo['nombre'] }}</div>
                                <div class="mod-desc">{{ $modulo['desc'] }}</div>
                            </div>
                        </a>
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
@endif
