<div class="est-section">
    <h2 class="est-section__title"><i class="fa-solid fa-futbol"></i> Partidos finalizados por árbitro</h2>
    <p class="est-section__desc">
        Solo cuenta partidos en estado Finalizado, desglosado por el rol que tuvo cada árbitro.
        Deja el filtro vacío para ver todos los torneos.
    </p>

    <form method="GET" action="{{ route('designaciones.estadisticas.partidos-arbitro') }}"
          class="desi-filter-bar" data-auto-filter data-auto-filter-ajax>
        <div class="desi-filter-item desi-filter-item--wide">
            <label class="desi-filter-label">Torneos</label>
            <select name="torneos[]" multiple class="filter-select"
                    data-nova-select data-searchable="true" data-placeholder="Todos los torneos">
                @foreach ($torneos as $torneo)
                    <option value="{{ $torneo->idTorneo }}" {{ in_array($torneo->idTorneo, $idsTorneos) ? 'selected' : '' }}>
                        {{ $torneo->nombreTorneo }} ({{ $torneo->temporada }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="desi-filter-actions">
            <button type="submit" class="btn btn-primary btn-sm" data-auto-filter-hide>
                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
            </button>
        </div>
    </form>

    <div data-auto-filter-region="partidosArbitro">
        @include('designaciones.estadisticas.partials.resultados.partidos-arbitro')
    </div>
</div>
