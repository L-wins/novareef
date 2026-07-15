<div class="fin-subnav">
    <a href="{{ route('finanzas.balance.index') }}" class="fin-subnav-link {{ request()->routeIs('finanzas.balance.*') ? 'active' : '' }}">
        <i class="fa-solid fa-scale-balanced"></i> Balance
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
