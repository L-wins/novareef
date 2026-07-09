@if ($arbitros->isEmpty())
    <div class="empty-state">
        <i class="fa-solid fa-user-slash" style="font-size:48px;margin-bottom:1rem;opacity:.5;"></i>
        @if (request('buscar') || request('estado') || request('categoria'))
            <p>No hay árbitros que coincidan con los filtros aplicados.</p>
            <a href="{{ route('arbitros.index') }}" class="btn btn-secondary" style="margin-top:1rem;">
                Ver todos
            </a>
        @else
            <p>No hay árbitros registrados todavía.</p>
            @can('crear-arbitros')
            <a href="{{ route('arbitros.create') }}" class="btn btn-primary" style="margin-top:1rem;">
                Registrar primer árbitro
            </a>
            @endcan
        @endif
    </div>
@else
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Carné</th>
                    <th>Árbitro</th>
                    <th>Documento</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($arbitros as $arbitro)
                <tr>
                    <td class="td-code">{{ $arbitro->codigoCarnet }}</td>
                    <td>
                        <div class="cell-with-avatar">
                            @if ($arbitro->fotoPerfil)
                                <img src="{{ asset('storage/' . $arbitro->fotoPerfil) }}"
                                     alt="{{ $arbitro->usuario->nombreUsuario }}"
                                     class="avatar avatar-sm">
                            @else
                                <span class="avatar avatar-sm avatar-initials">
                                    {{ strtoupper(substr($arbitro->usuario->nombreUsuario, 0, 1)) }}
                                </span>
                            @endif
                            <div>
                                <span class="td-primary">{{ $arbitro->usuario->nombreUsuario }}</span>
                                <span class="td-secondary">{{ $arbitro->usuario->emailUsuario }}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="td-primary">{{ $arbitro->numeroDocumento }}</span>
                        <span class="td-secondary">{{ ucfirst($arbitro->tipoDocumento) }}</span>
                    </td>
                    <td>
                        <span class="cat-badge">{{ $arbitro->categoria->nombreCategoria }}</span>
                    </td>
                    <td>
                        @php $est = $arbitro->estado; @endphp
                        <span class="estado-pill" data-color="{{ $est->color ?? 'gray' }}">
                            {{ $est->etiqueta ?? ucfirst(str_replace('_', ' ', $arbitro->estadoArbitro)) }}
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ route('arbitros.show', $arbitro->idArbitro) }}"
                               class="btn-icon btn-icon-view" title="Ver detalle">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            @can('editar-arbitros')
                            <a href="{{ route('arbitros.edit', $arbitro->idArbitro) }}"
                               class="btn-icon btn-icon-edit" title="Editar">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($arbitros->hasPages())
        <div class="pagination-wrapper">{{ $arbitros->links() }}</div>
    @endif
@endif
