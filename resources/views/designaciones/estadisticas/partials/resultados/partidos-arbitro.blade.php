@if ($partidosPorArbitro->isEmpty())
    <div class="empty-state"><p class="empty-state__sub">No hay partidos finalizados que coincidan con el filtro.</p></div>
@else
    <div class="disp-table-wrap">
        <table class="disp-table">
            <thead>
                <tr>
                    <th>Árbitro</th>
                    <th>Total finalizados</th>
                    <th>Desglose por rol</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($partidosPorArbitro as $fila)
                    <tr>
                        <td>{{ $fila['arbitro']->usuario?->nombreUsuario ?? '—' }}</td>
                        <td>{{ $fila['total'] }}</td>
                        <td>
                            <div class="est-rol-chips">
                                @foreach ($fila['porRol'] as $rol => $cantidad)
                                    <span class="est-rol-chip">{{ $rol }}: {{ $cantidad }}</span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
