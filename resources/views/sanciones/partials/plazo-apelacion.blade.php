@php
    $diasRestantes = $sancion->diasRestantesApelacion();
    $vencido = $sancion->vencioPlazoApelacion();
    $urgencia = $vencido ? 'vencido' : ($diasRestantes <= 1 ? 'critica' : ($diasRestantes <= 2 ? 'media' : 'normal'));
    // Barra: proporción de plazo consumido sobre el total (DIAS_LIMITE_APELACION).
    $porcentaje = $vencido ? 100 : max(0, min(100, (int) round((1 - $diasRestantes / \App\Models\Sancion::DIAS_LIMITE_APELACION) * 100)));
@endphp

<div class="plazo-card {{ $vencido ? 'plazo-card--vencido' : '' }}" data-urgencia="{{ $urgencia }}">
    <div class="plazo-card__icon">
        <i class="fa-solid {{ $vencido ? 'fa-lock' : 'fa-hourglass-half' }}"></i>
    </div>
    <div class="plazo-card__body">
        <p class="plazo-card__title">
            @if ($vencido)
                Plazo de apelación vencido
            @else
                {{ $esArbitro ? 'Puedes apelar esta sanción' : 'El árbitro puede apelar esta sanción' }}
            @endif
        </p>
        <p class="plazo-card__subtitle">
            @if ($vencido)
                Venció el {{ $sancion->fechaLimiteApelacion()->format('d/m/Y') }} — la sanción ya no puede apelarse.
            @else
                Plazo hasta el {{ $sancion->fechaLimiteApelacion()->format('d/m/Y') }} ({{ \App\Models\Sancion::DIAS_LIMITE_APELACION }} días desde el registro).
            @endif
        </p>
        @unless ($vencido)
            <div class="plazo-card__bar">
                <div class="plazo-card__bar-fill" style="width: {{ $porcentaje }}%;"></div>
            </div>
        @endunless
    </div>
    @unless ($vencido)
        <div class="plazo-card__days">
            {{ $diasRestantes }}
            <span>{{ $diasRestantes === 1 ? 'día restante' : 'días restantes' }}</span>
        </div>
    @endunless
</div>
