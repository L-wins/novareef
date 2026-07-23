<div class="est-section">
    <h2 class="est-section__title"><i class="fa-solid fa-layer-group"></i> Árbitros por categoría</h2>
    <p class="est-section__desc">Distribución del cuerpo arbitral por categoría del colegio.</p>

    @if ($categorias->isEmpty())
        <div class="empty-state"><p class="empty-state__sub">No hay categorías registradas.</p></div>
    @else
        <div class="disp-table-wrap">
            <table class="disp-table">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Activos</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categorias as $cat)
                        <tr>
                            <td>{{ $cat->nombreCategoria }}</td>
                            <td>{{ $cat->activos_count }}</td>
                            <td>{{ $cat->arbitros_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
