<div class="cfg-subnav">
    <a href="{{ route('configuracion.index') }}" class="cfg-subnav-link {{ request()->routeIs('configuracion.index') ? 'active' : '' }}">
        <i class="fa-solid fa-building-columns"></i> General
    </a>
    <a href="{{ route('configuracion.colegio') }}" class="cfg-subnav-link {{ request()->routeIs('configuracion.colegio') ? 'active' : '' }}">
        <i class="fa-solid fa-sliders"></i> Colegio
    </a>
</div>
