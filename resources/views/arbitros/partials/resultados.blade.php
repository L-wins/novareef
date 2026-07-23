@if ($arbitros->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fa-solid fa-user-slash"></i>
        </div>
        @if (request('buscar') || request('estado') || request('categoria'))
            <h2>No encontramos coincidencias</h2>
            <p>Prueba con otro nombre, documento, carné o limpia los filtros activos.</p>
            <a href="{{ route('arbitros.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-rotate-left"></i>
                Ver todos
            </a>
        @else
            <h2>Aún no hay árbitros registrados</h2>
            <p>No hay árbitros registrados todavía.</p>
            @can('crear-arbitros')
            <a href="{{ route('arbitros.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-user-plus"></i>
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
                    <th>Perfil</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($arbitros as $arbitro)
                <tr>
                    <td class="td-code">
                        <span class="code-chip">{{ $arbitro->codigoCarnet }}</span>
                    </td>
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
                                <a href="{{ route('arbitros.show', $arbitro->idArbitro) }}" class="td-primary td-link">
                                    {{ $arbitro->usuario->nombreUsuario }}
                                </a>
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
                        @php
                            $perfil = $arbitro->porcentajePerfil;
                            $perfilColor = $arbitro->colorPerfil;
                        @endphp
                        <div class="table-profile-meter" title="Perfil completo al {{ $perfil }}%">
                            <div class="table-profile-meter__head">
                                <span>{{ $perfil }}%</span>
                                <span>{{ $perfil === 100 ? 'Completo' : 'Pendiente' }}</span>
                            </div>
                            <div class="table-profile-meter__bar" aria-hidden="true">
                                <span data-color="{{ $perfilColor }}" style="width: {{ $perfil }}%;"></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ route('arbitros.show', $arbitro->idArbitro) }}"
                               class="row-action row-action--primary"
                               title="Ver detalle"
                               aria-label="Ver detalle de {{ $arbitro->usuario->nombreUsuario }}">
                                <i class="fa-solid fa-eye"></i>
                                <span>Ver</span>
                            </a>
                            @can('editar-arbitros')
                            <a href="{{ route('arbitros.edit', $arbitro->idArbitro) }}"
                               class="row-action"
                               title="Editar"
                               aria-label="Editar {{ $arbitro->usuario->nombreUsuario }}">
                                <i class="fa-solid fa-pen-to-square"></i>
                                <span>Editar</span>
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
