@if ($confiabilidad->isEmpty())
    <div class="empty-state"><p class="empty-state__sub">No hay suficientes designaciones en este rango (mínimo 3 por árbitro).</p></div>
@else
    <div class="disp-table-wrap">
        <table class="disp-table">
            <thead>
                <tr>
                    <th>Árbitro</th>
                    <th>% Rechazo</th>
                    <th>Designaciones</th>
                    <th>Confirmación promedio</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($confiabilidad as $fila)
                    @php
                        $claseBarra = $fila['porcentajeRechazo'] > 30 ? 'is-low' : ($fila['porcentajeRechazo'] > 10 ? 'is-mid' : '');
                        $mins = $fila['minutosPromedioConfirmacion'];
                        $textoConfirmacion = $mins === null
                            ? '—'
                            : ($mins < 60 ? ((int) $mins) . ' min' : sprintf('%dh %dmin', intdiv((int) $mins, 60), ((int) $mins) % 60));
                    @endphp
                    <tr>
                        <td>{{ $fila['arbitro']->usuario?->nombreUsuario ?? '—' }}</td>
                        <td>
                            <div class="est-bar-cell">
                                <div class="est-bar">
                                    <div class="est-bar__fill {{ $claseBarra }}" style="width: {{ $fila['porcentajeRechazo'] }}%"></div>
                                </div>
                                <span class="est-bar-value">{{ $fila['porcentajeRechazo'] }}%</span>
                            </div>
                        </td>
                        <td>{{ $fila['total'] }} <span class="est-inline-muted">({{ $fila['rechazadas'] }} rechazadas)</span></td>
                        <td>{{ $textoConfirmacion }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
