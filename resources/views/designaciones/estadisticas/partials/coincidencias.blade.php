<div class="est-section">
    <h2 class="est-section__title"><i class="fa-solid fa-people-group"></i> Coincidencias entre árbitros</h2>
    <p class="est-section__desc">
        Elige 2 o más árbitros para ver cuántos partidos han compartido juntos (cualquier rol,
        sin contar designaciones rechazadas).
    </p>

    <form method="GET" action="{{ route('designaciones.estadisticas.coincidencias') }}"
          class="desi-filter-bar" data-auto-filter data-auto-filter-ajax>
        <div class="desi-filter-item desi-filter-item--wide">
            <label class="desi-filter-label">Árbitros</label>
            <select name="arbitros[]" multiple class="filter-select"
                    data-nova-select data-searchable="true" data-placeholder="Elige 2 o más árbitros">
                @foreach ($arbitrosOpciones as $arb)
                    <option value="{{ $arb->idArbitro }}" {{ in_array($arb->idArbitro, $idsArbitrosSeleccionados) ? 'selected' : '' }}>
                        {{ $arb->usuario?->nombreUsuario ?? '—' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="desi-filter-actions">
            <button type="submit" class="btn btn-primary btn-sm" data-auto-filter-hide>
                <i class="fa-solid fa-magnifying-glass"></i> Comparar
            </button>
        </div>
    </form>

    <div data-auto-filter-region="coincidencias">
        @include('designaciones.estadisticas.partials.resultados.coincidencias')
    </div>
</div>
