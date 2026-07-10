{{--
    Banner de uso de plan — reutilizable en cualquier vista que reporte
    consumo contra un límite de plan (árbitros, cuentas admin, etc.).

    Variables esperadas:
    - $recurso    string  Nombre del recurso en plural minúscula ("árbitros", "cuentas admin")
    - $usados     int     Cantidad actualmente usada
    - $limite     int|null Límite del plan (null = ilimitado, no se muestra nada)
    - $porcentaje float   Porcentaje de uso (0 si ilimitado)

    Solo se renderiza a partir de 80% de uso — por debajo de eso el
    contador simple en el encabezado de cada vista ya es suficiente.
--}}
@if ($limite !== null && $porcentaje >= 80)
    @php $critico = $porcentaje >= 100; @endphp
    <div class="plan-limite-banner {{ $critico ? 'plan-limite-banner--critico' : 'plan-limite-banner--advertencia' }}">
        <div class="plan-limite-banner__icon">
            <i class="fa-solid {{ $critico ? 'fa-circle-exclamation' : 'fa-triangle-exclamation' }}"></i>
        </div>
        <div class="plan-limite-banner__body">
            <p class="plan-limite-banner__title">
                @if ($critico)
                    Alcanzaste el límite de {{ $recurso }} de tu plan
                @else
                    Estás cerca del límite de {{ $recurso }} de tu plan
                @endif
            </p>
            <p class="plan-limite-banner__text">
                {{ $usados }} de {{ $limite }} {{ $recurso }} usados ({{ $porcentaje }}%).
                @if ($critico)
                    Actualiza tu plan para seguir registrando.
                @else
                    Considera actualizar tu plan antes de alcanzar el tope.
                @endif
            </p>
        </div>
        <a href="#" class="btn btn-primary plan-limite-banner__cta" data-plan-upgrade-cta>
            <i class="fa-solid fa-arrow-up-right-dots"></i>
            Actualizar plan
        </a>
    </div>
@endif
