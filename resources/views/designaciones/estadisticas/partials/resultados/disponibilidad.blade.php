@if ($rankingDisponibilidad->isEmpty())
    <div class="empty-state"><p class="empty-state__sub">No hay árbitros activos para mostrar.</p></div>
@else
    <div class="disp-table-wrap">
        <table class="disp-table">
            <thead>
                <tr>
                    <th>Árbitro</th>
                    <th>% Disponible</th>
                    <th>Días reportados</th>
                    <th>No disponible</th>
                    <th>Sin reportar</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rankingDisponibilidad as $fila)
                    @php
                        $claseBarra = $fila['porcentaje'] < 40 ? 'is-low' : ($fila['porcentaje'] < 70 ? 'is-mid' : '');
                    @endphp
                    <tr>
                        <td>{{ $fila['arbitro']->usuario?->nombreUsuario ?? '—' }}</td>
                        <td>
                            <div class="est-bar-cell">
                                <div class="est-bar">
                                    <div class="est-bar__fill {{ $claseBarra }}" style="width: {{ $fila['porcentaje'] }}%"></div>
                                </div>
                                <span class="est-bar-value">{{ $fila['porcentaje'] }}%</span>
                            </div>
                        </td>
                        <td>{{ $fila['diasReportados'] }}</td>
                        <td>{{ $fila['diasNoDisponible'] }}</td>
                        <td>{{ $fila['diasSinReportar'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
