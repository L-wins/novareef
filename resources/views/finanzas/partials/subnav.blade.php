<div class="fin-subnav">
    <a href="{{ route('finanzas.balance.index') }}" class="fin-subnav-link {{ request()->routeIs('finanzas.balance.*') ? 'active' : '' }}">
        <i class="fa-solid fa-scale-balanced"></i> Balance
    </a>
    <a href="{{ route('finanzas.mora.index') }}" class="fin-subnav-link {{ request()->routeIs('finanzas.mora.*') ? 'active' : '' }}">
        <i class="fa-solid fa-triangle-exclamation"></i> Mora
    </a>
    <a href="{{ route('finanzas.cuotas.index') }}" class="fin-subnav-link {{ request()->routeIs('finanzas.cuotas.*') ? 'active' : '' }}">
        <i class="fa-solid fa-calendar-check"></i> Cuotas
    </a>
    <a href="{{ route('finanzas.comprobantes.index') }}" class="fin-subnav-link {{ request()->routeIs('finanzas.comprobantes.*') ? 'active' : '' }}">
        <i class="fa-solid fa-file-invoice"></i> Comprobantes
    </a>
    @can('crear-finanzas')
    <a href="{{ route('finanzas.cobro-masivo.index') }}" class="fin-subnav-link {{ request()->routeIs('finanzas.cobro-masivo.*') ? 'active' : '' }}">
        <i class="fa-solid fa-users"></i> Cobro masivo
    </a>
    @endcan
    <a href="{{ route('finanzas.institucional.index') }}" class="fin-subnav-link {{ request()->routeIs('finanzas.institucional.*') ? 'active' : '' }}">
        <i class="fa-solid fa-building-columns"></i> Gastos e ingresos
    </a>
    <a href="{{ route('finanzas.reportes.index') }}" class="fin-subnav-link {{ request()->routeIs('finanzas.reportes.*') ? 'active' : '' }}">
        <i class="fa-solid fa-chart-column"></i> Reportes
    </a>
</div>
