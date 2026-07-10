<div class="fin-subnav">
    <a href="{{ route('finanzas.index') }}" class="fin-subnav-link {{ request()->routeIs('finanzas.index') ? 'active' : '' }}">
        <i class="fa-solid fa-list"></i> Movimientos
    </a>
    <a href="{{ route('finanzas.balance.index') }}" class="fin-subnav-link {{ request()->routeIs('finanzas.balance.*') ? 'active' : '' }}">
        <i class="fa-solid fa-scale-balanced"></i> Balance
    </a>
    @can('crear-finanzas')
    <a href="{{ route('finanzas.pagos-arbitro.index') }}" class="fin-subnav-link {{ request()->routeIs('finanzas.pagos-arbitro.*') ? 'active' : '' }}">
        <i class="fa-solid fa-hand-holding-dollar"></i> Pago a árbitro
    </a>
    @endcan
    <a href="{{ route('finanzas.reportes.index') }}" class="fin-subnav-link {{ request()->routeIs('finanzas.reportes.*') ? 'active' : '' }}">
        <i class="fa-solid fa-chart-column"></i> Reportes
    </a>
</div>
