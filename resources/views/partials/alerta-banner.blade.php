{{--
    Banner de alerta genérico — mismo lenguaje visual que limite-plan-banner
    pero sin acoplarse al concepto de "plan" (usado por ejemplo para avisar
    de nómina sin generar). Reutiliza las clases .plan-limite-banner* porque
    ya tienen los estilos correctos; solo agrega la variante sin CTA.

    Variables esperadas:
    - $titulo string
    - $texto  string
    - $href   ?string  Si viene, se muestra un botón "Ver" enlazando ahí.
    - $nivel  string   'advertencia' | 'critico' (por defecto 'advertencia')
--}}
@php $nivel = $nivel ?? 'advertencia'; @endphp
<div class="plan-limite-banner plan-limite-banner--{{ $nivel }}">
    <div class="plan-limite-banner__icon">
        <i class="fa-solid {{ $nivel === 'critico' ? 'fa-circle-exclamation' : 'fa-triangle-exclamation' }}"></i>
    </div>
    <div class="plan-limite-banner__body">
        <p class="plan-limite-banner__title">{{ $titulo }}</p>
        <p class="plan-limite-banner__text">{{ $texto }}</p>
    </div>
    @if (! empty($href))
        <a href="{{ $href }}" class="btn btn-secondary plan-limite-banner__cta">Ver</a>
    @endif
</div>
