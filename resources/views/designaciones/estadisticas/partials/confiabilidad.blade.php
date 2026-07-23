<div class="est-section">
    <h2 class="est-section__title"><i class="fa-solid fa-shield-halved"></i> Confiabilidad</h2>
    <p class="est-section__desc">
        % de designaciones rechazadas y tiempo promedio de confirmación — solo árbitros con 3 o más
        designaciones en el rango, ordenado de mayor a menor tasa de rechazo.
    </p>

    <form method="GET" action="{{ route('designaciones.estadisticas.confiabilidad') }}"
          class="desi-filter-bar" data-auto-filter data-auto-filter-ajax>
        <div class="desi-filter-item">
            <label class="desi-filter-label">Desde</label>
            <input type="text" name="confDesde" value="{{ $confDesde }}" class="filter-input" data-nova-date>
        </div>
        <div class="desi-filter-item">
            <label class="desi-filter-label">Hasta</label>
            <input type="text" name="confHasta" value="{{ $confHasta }}" class="filter-input" data-nova-date>
        </div>
        <div class="desi-filter-actions">
            <button type="submit" class="btn btn-primary btn-sm" data-auto-filter-hide>
                <i class="fa-solid fa-magnifying-glass"></i> Aplicar
            </button>
        </div>
    </form>

    <div data-auto-filter-region="confiabilidad">
        @include('designaciones.estadisticas.partials.resultados.confiabilidad')
    </div>
</div>
