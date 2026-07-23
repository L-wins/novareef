<div class="est-section">
    <h2 class="est-section__title"><i class="fa-solid fa-calendar-check"></i> Ranking de disponibilidad</h2>
    <p class="est-section__desc">
        Días reportados como disponible sobre el total de días de la ventana — solo árbitros activos.
    </p>

    <form method="GET" action="{{ route('designaciones.estadisticas.disponibilidad') }}"
          class="desi-filter-bar" data-auto-filter data-auto-filter-ajax>
        <div class="desi-filter-item">
            <label class="desi-filter-label">Árbitro</label>
            <input type="text" name="dispNombre" value="{{ $dispNombre }}" class="filter-input" placeholder="Buscar por nombre...">
        </div>
        <div class="desi-filter-item">
            <label class="desi-filter-label">Desde</label>
            <input type="text" name="dispDesde" value="{{ $dispDesde }}" class="filter-input" data-nova-date>
        </div>
        <div class="desi-filter-item">
            <label class="desi-filter-label">Hasta</label>
            <input type="text" name="dispHasta" value="{{ $dispHasta }}" class="filter-input" data-nova-date>
        </div>
        <div class="desi-filter-actions">
            <button type="submit" class="btn btn-primary btn-sm" data-auto-filter-hide>
                <i class="fa-solid fa-magnifying-glass"></i> Aplicar
            </button>
        </div>
    </form>

    <div data-auto-filter-region="disponibilidad">
        @include('designaciones.estadisticas.partials.resultados.disponibilidad')
    </div>
</div>
