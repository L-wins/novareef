@extends('layouts.app')

@section('titulo', 'Panel de control')

@section('contenido')
<div class="container">

    <x-dashboard.welcome subtitulo="Aquí tienes el estado financiero del colegio, al día de hoy." />

    <x-dashboard.section label="Bolsillos">
        <div class="stats-grid">
            <x-dashboard.stat-card icon="fa-vault" color="emerald" :value="'$' . number_format($bolsillos['saldoEnCaja'], 0, ',', '.')" label="Caja en banco" href="{{ route('finanzas.balance.index') }}" sub="Dinero realmente cobrado menos realmente pagado" />
            <x-dashboard.stat-card class="stat-card--hero" icon="fa-sack-dollar" color="teal" :value="'$' . number_format($bolsillos['disponibleReal'], 0, ',', '.')" label="Disponible real" href="{{ route('finanzas.balance.index') }}" sub="Caja menos lo que aún falta pagar" />
            <x-dashboard.stat-card icon="fa-arrow-down" color="blue" :value="'$' . number_format($bolsillos['pendientePorCobrar'], 0, ',', '.')" label="Por cobrar" href="{{ route('finanzas.balance.index') }}" />
            <x-dashboard.stat-card icon="fa-arrow-up" color="amber" :value="'$' . number_format($bolsillos['pendientePorPagar'], 0, ',', '.')" label="Por pagar" href="{{ route('finanzas.balance.index') }}" />
        </div>
    </x-dashboard.section>

    <x-dashboard.section label="Acciones frecuentes">
        <div class="modules-grid">
            <a href="{{ route('finanzas.reportes.index') }}" class="module-card module-card--link">
                <div class="mod-icon-box ic-blue"><i class="fa-solid fa-chart-column"></i></div>
                <div class="mod-info">
                    <div class="mod-name">Reportes</div>
                    <div class="mod-desc">Ingresos y egresos por período</div>
                </div>
            </a>
        </div>
    </x-dashboard.section>

    <x-dashboard.widget-card titulo="Árbitros con mayor deuda" icono="fa-users" color="blue"
        href="{{ route('finanzas.balance.index') }}" cta="Ver balance completo">
        @if ($topDeudas->isEmpty())
            <div class="widget-empty"><i class="fa-solid fa-circle-check"></i><span>No hay saldos pendientes con ningún árbitro en este momento.</span></div>
        @else
            <div class="widget-list">
                @foreach ($topDeudas as $fila)
                    <div class="widget-list-item">
                        <div class="widget-list-item__main">
                            <span class="widget-list-item__title">{{ $fila['arbitro']->usuario->nombreUsuario ?? 'Árbitro #' . $fila['arbitro']->idArbitro }}</span>
                            @if ($fila['nosDebe'] > 0)
                                <span class="widget-list-item__meta">Nos debe ${{ number_format($fila['nosDebe'], 0, ',', '.') }}</span>
                            @endif
                        </div>
                        @if ($fila['leDebemos'] > 0)
                            <span class="dash-pill" data-color="amber">${{ number_format($fila['leDebemos'], 0, ',', '.') }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-dashboard.widget-card>

</div>
@endsection
