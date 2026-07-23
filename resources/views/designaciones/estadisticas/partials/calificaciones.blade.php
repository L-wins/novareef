<div class="est-section">
    <h2 class="est-section__title"><i class="fa-solid fa-star"></i> Calificación promedio</h2>
    <p class="est-section__desc">
        Promedio de nota del veedor (0.0–5.0) — solo árbitros con 3 o más calificaciones registradas.
    </p>

    @if ($calificaciones->isEmpty())
        <div class="empty-state"><p class="empty-state__sub">Todavía no hay suficientes calificaciones registradas.</p></div>
    @else
        <div class="disp-table-wrap">
            <table class="disp-table">
                <thead>
                    <tr>
                        <th>Árbitro</th>
                        <th>Promedio</th>
                        <th>Calificaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($calificaciones as $fila)
                        <tr>
                            <td>{{ $fila['arbitro']->usuario?->nombreUsuario ?? '—' }}</td>
                            <td>
                                <div class="est-bar-cell">
                                    <div class="est-bar">
                                        <div class="est-bar__fill" style="width: {{ $fila['promedio'] / 5 * 100 }}%"></div>
                                    </div>
                                    <span class="est-bar-value">{{ number_format($fila['promedio'], 1) }}</span>
                                </div>
                            </td>
                            <td>{{ $fila['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
