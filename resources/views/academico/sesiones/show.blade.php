@extends('layouts.app')

@section('titulo', $sesion->tema)
@section('seccion', 'Académico')

@push('styles')
    @vite(['resources/css/academico/academico.css'])
@endpush

@php
    [$estadoLabel, $estadoColor] = \App\Models\SesionAcademica::ETIQUETAS_ESTADO[$sesion->estadoSesion] ?? ['—', 'gray'];

    $etiquetasAsistencia = [
        'presente'                 => ['Presente', 'green'],
        'ausente'                  => ['Ausente', 'red'],
        'justificacion_pendiente'  => ['Justificación pendiente', 'amber'],
        'justificado'              => ['Justificado', 'blue'],
        'justificacion_rechazada'  => ['Justificación rechazada', 'gray'],
    ];

    // "Sin marcar" = registradoPor='sistema', el valor que se genera solo al
    // crear la sesión — nadie tomó una decisión real todavía. No se debe
    // confundir con un 'ausente' explícito, aunque comparten el mismo valor
    // en estadoAsistencia (ver AsistenciaAcademica::REGISTRADO_SISTEMA).
    // Una vez la sesión se cierra (o se cancela), ya no hay nada "pendiente"
    // — lo que quedó sin marcar se lee como ausente definitivo, igual que
    // en el historial del árbitro.
    $sesionDefinitiva = in_array($sesion->estadoSesion, ['finalizada', 'cancelada'], true);

    $totalEsperados   = $asistencias->count();
    $totalPresentes   = $asistencias->where('estadoAsistencia', 'presente')->count();
    $totalAusentes    = $asistencias->when(! $sesionDefinitiva, fn ($q) => $q->where('registradoPor', '!=', 'sistema'))
        ->whereIn('estadoAsistencia', ['ausente', 'justificacion_rechazada'])->count();
    $totalJustificados = $asistencias->where('estadoAsistencia', 'justificado')->count();
    $pctAsistencia    = $totalEsperados > 0 ? (int) round(($totalPresentes / $totalEsperados) * 100) : 0;
@endphp

@section('contenido')
<div class="container">

    <a href="{{ route('academico.sesiones.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a académico
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">{{ $sesion->tema }}</h1>

            <div class="sesion-chips">
                <span class="badge badge-{{ $estadoColor }}">{{ $estadoLabel }}</span>

                <span class="sesion-chip"><i class="fa-solid fa-tag"></i>{{ $sesion->tipo->etiqueta ?? '—' }}</span>

                @if ($sesion->esOficial)
                    <span class="sesion-chip"><i class="fa-solid fa-star"></i>Oficial FCF</span>
                @endif

                <span class="sesion-chip"><i class="fa-regular fa-calendar"></i>{{ $sesion->fechaSesion->format('d/m/Y') }}</span>
                <span class="sesion-chip"><i class="fa-regular fa-clock"></i>{{ \Illuminate\Support\Carbon::parse($sesion->horaSesion)->format('H:i') }} · {{ $sesion->duracionMinutos }} min</span>

                @if ($sesion->modalidad === 'virtual')
                    <span class="sesion-chip">
                        <i class="fa-solid fa-video"></i>
                        @if ($sesion->urlVirtual)
                            <a href="{{ $sesion->urlVirtual }}" target="_blank" rel="noopener">Enlace virtual</a>
                        @else
                            Virtual
                        @endif
                    </span>
                @else
                    <span class="sesion-chip"><i class="fa-solid fa-location-dot"></i>{{ $sesion->lugar ?? 'Presencial' }}</span>
                @endif

                <span class="sesion-chip">
                    <i class="fa-solid fa-users"></i>
                    {{ $sesion->dirigidaA === 'categoria' ? ($sesion->categoria->nombreCategoria ?? 'Categoría') : 'Todos los árbitros' }}
                </span>

                <span class="sesion-chip"><i class="fa-solid fa-chalkboard-user"></i>{{ $sesion->instructor->nombreUsuario ?? '—' }}</span>

                @if ($sesion->esObligatoria)
                    <span class="badge badge-amber"><i class="fa-solid fa-circle-exclamation"></i> Obligatoria</span>
                @else
                    <span class="badge badge-gray"><i class="fa-regular fa-circle"></i> Opcional</span>
                @endif
            </div>

            @if ($sesion->descripcion)
                <p class="page-subheading" style="margin-top:0.75rem; max-width:640px;">{{ $sesion->descripcion }}</p>
            @endif
        </div>

        <div style="display:flex; gap:0.75rem; flex-shrink:0;">
            @if ($sesion->estadoSesion === 'programada')
                <a href="{{ route('academico.sesiones.edit', $sesion->idSesion) }}" class="btn btn-secondary">
                    <i class="fa-solid fa-pen"></i> Editar
                </a>
                <form method="POST" action="{{ route('academico.sesiones.abrir', $sesion->idSesion) }}">
                    @csrf @method('PUT')
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-play"></i> Abrir sesión
                    </button>
                </form>
                <form method="POST" action="{{ route('academico.sesiones.destroy', $sesion->idSesion) }}"
                      data-confirm-submit
                      data-confirm-title="Eliminar sesión"
                      data-confirm-text="¿Eliminar «{{ $sesion->tema }}»? Esta acción no se puede deshacer.">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-trash"></i> Eliminar
                    </button>
                </form>
            @endif
            @if ($sesion->estadoSesion === 'en_curso')
                <form method="POST" action="{{ route('academico.sesiones.cerrar', $sesion->idSesion) }}"
                      data-confirm-submit
                      data-confirm-title="¿Confirmar y cerrar la sesión?"
                      data-confirm-text="La lista de asistencia quedará confirmada como definitiva.">
                    @csrf @method('PUT')
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check-double"></i> Confirmar y cerrar
                    </button>
                </form>
            @endif
            @if (in_array($sesion->estadoSesion, ['programada', 'en_curso'], true))
                <form method="POST" action="{{ route('academico.sesiones.cancelar', $sesion->idSesion) }}"
                      data-confirm-submit
                      data-confirm-title="Cancelar sesión"
                      data-confirm-text="¿Cancelar «{{ $sesion->tema }}»? Quedará marcada como cancelada en el historial.">
                    @csrf @method('PUT')
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-ban"></i> Cancelar
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error">{{ session('error') }}</div>
    @endif

    <div class="aca-stat-row">
        <div class="aca-stat aca-stat--rico">
            <div class="aca-stat__head">
                <div>
                    <div class="aca-stat__label">Esperados</div>
                    <div class="aca-stat__value" id="stat-esperados">{{ $totalEsperados }}</div>
                </div>
                <i class="fa-solid fa-users aca-stat__icon"></i>
            </div>
        </div>
        <div class="aca-stat aca-stat--rico">
            <div class="aca-stat__head">
                <div>
                    <div class="aca-stat__label">Presentes</div>
                    <div class="aca-stat__value aca-stat__value--green" id="stat-presentes">{{ $totalPresentes }}</div>
                </div>
                <i class="fa-solid fa-circle-check aca-stat__icon"></i>
            </div>
        </div>
        <div class="aca-stat aca-stat--rico">
            <div class="aca-stat__head">
                <div>
                    <div class="aca-stat__label">Ausentes</div>
                    <div class="aca-stat__value aca-stat__value--red" id="stat-ausentes">{{ $totalAusentes }}</div>
                </div>
                <i class="fa-solid fa-circle-xmark aca-stat__icon"></i>
            </div>
        </div>
        <div class="aca-stat aca-stat--rico">
            <div class="aca-stat__head">
                <div>
                    <div class="aca-stat__label">Justificados</div>
                    <div class="aca-stat__value aca-stat__value--amber" id="stat-justificados">{{ $totalJustificados }}</div>
                </div>
                <i class="fa-solid fa-file-circle-check aca-stat__icon"></i>
            </div>
        </div>
        <div class="aca-stat aca-stat--rico">
            <div class="aca-stat__head">
                <div>
                    <div class="aca-stat__label">% Asistencia</div>
                    <div class="aca-stat__value" id="stat-pct">{{ $pctAsistencia }}%</div>
                </div>
                <i class="fa-solid fa-chart-simple aca-stat__icon"></i>
            </div>
            <div class="aca-stat__bar">
                <div class="aca-stat__bar-fill" id="stat-pct-bar" style="width:{{ $pctAsistencia }}%;"></div>
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section" style="border-bottom:none;">
            <p class="form-section-title">Material de clase</p>

            @if ($sesion->materiales->isEmpty())
                <p class="field-hint" style="margin-bottom:1rem;">Aún no hay material adjunto — se puede subir antes, durante o después de la sesión.</p>
            @else
                <div class="aca-materiales-lista">
                    @foreach ($sesion->materiales as $material)
                        <div class="aca-material-item">
                            <a href="{{ route('academico.materiales.descargar', $material->idMaterial) }}" class="aca-material-link">
                                <i class="fa-solid {{ $material->icono }}"></i>
                                <span>{{ $material->titulo }}</span>
                            </a>
                            <span class="aca-material-meta">{{ $material->tamanoLegible }}</span>
                            <form method="POST" action="{{ route('academico.materiales.destroy', $material->idMaterial) }}"
                                  data-confirm-submit
                                  data-confirm-title="¿Eliminar material?"
                                  data-confirm-text="Se eliminará el archivo permanentemente para todos los árbitros.">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('academico.materiales.store', $sesion->idSesion) }}" enctype="multipart/form-data" style="margin-top:1rem;">
                @csrf
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="material-titulo">Título</label>
                        <input type="text" id="material-titulo" name="titulo" class="form-input {{ $errors->has('titulo') ? 'is-invalid' : '' }}" placeholder="Ej. Presentación reglas 2026">
                        @error('titulo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="material-archivo">Archivo</label>
                        <input type="file" id="material-archivo" name="archivo" class="form-input {{ $errors->has('archivo') ? 'is-invalid' : '' }}">
                        <p class="field-hint">PDF, Word, PowerPoint, Excel o imagen — máx. 20 MB.</p>
                        @error('archivo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-secondary btn-sm" style="margin-top:0.5rem;">
                    <i class="fa-solid fa-paperclip"></i> Adjuntar material
                </button>
            </form>
        </div>
    </div>

    @if ($sesion->modoAsistencia === 'scanner')
        <div class="scanner-panel">
            <div class="scanner-panel__icono"><i class="fa-solid fa-id-card"></i></div>
            <p class="page-subheading" style="margin-bottom:1rem;">Escanea el carné o escribe el código manualmente</p>
            <input type="text" id="scanner-input" class="scanner-input" placeholder="Código de carné"
                   {{ $sesion->sesionAbierta ? '' : 'disabled' }} autocomplete="off">
            <p id="scanner-feedback" class="scanner-feedback"></p>

            <div class="scanner-last" id="scanner-last">
                <div class="scanner-last__avatar" id="scanner-last-avatar">?</div>
                <div class="scanner-last__texto">
                    <div class="scanner-last__nombre" id="scanner-last-nombre">—</div>
                    <div class="scanner-last__hora" id="scanner-last-hora">—</div>
                </div>
            </div>
        </div>

        <p class="aca-section-title">Registrados recientemente</p>
        <div id="scanner-lista" class="scanner-lista" style="margin-bottom:2rem;">
            @foreach ($asistencias->where('estadoAsistencia', 'presente')->sortByDesc('horaMarca') as $a)
                <div class="scanner-item">
                    <div class="aca-arbitro-cell">
                        <div class="aca-avatar aca-avatar--sm">{{ strtoupper(substr($a->arbitro->usuario->nombreUsuario ?? '?', 0, 1)) }}</div>
                        <span>{{ $a->arbitro->usuario->nombreUsuario ?? '—' }}</span>
                    </div>
                    <span class="asistencia-hora">{{ $a->horaMarca?->format('H:i') }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="aca-table-toolbar">
        <p class="aca-section-title" style="margin:0;">Lista de asistencia</p>
        <div style="display:flex; align-items:center; gap:1rem;">
            <span class="aca-table-count"><span id="aca-count-visible">{{ $totalEsperados }}</span> de {{ $totalEsperados }}</span>
            <div class="aca-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="aca-buscador" placeholder="Buscar árbitro...">
            </div>
        </div>
    </div>

    <div class="table-card">
        <table class="data-table data-table--asistencia">
            <thead>
                <tr>
                    <th class="col-arbitro">Árbitro</th>
                    <th class="col-estado">Estado</th>
                    <th class="col-hora">Hora</th>
                    @if ($sesion->estadoSesion === 'en_curso' && $sesion->modoAsistencia === 'manual')
                        <th class="text-right col-acciones">Acciones</th>
                    @endif
                </tr>
            </thead>
            <tbody id="aca-tabla-body">
                @foreach ($asistencias as $a)
                    @php
                        $sinMarcar = $a->registradoPor === 'sistema' && ! $sesionDefinitiva;
                        [$aLabel, $aColor] = $sinMarcar ? ['Sin marcar', 'pendiente'] : ($etiquetasAsistencia[$a->estadoAsistencia] ?? ['—', 'gray']);
                        $nombreArbitro = $a->arbitro->usuario->nombreUsuario ?? '—';
                    @endphp
                    <tr class="asistencia-row" data-asistencia="{{ $a->idAsistencia }}" data-estado="{{ $a->estadoAsistencia }}" data-sin-marcar="{{ $sinMarcar ? '1' : '0' }}" data-nombre="{{ strtolower($nombreArbitro) }}">
                        <td class="col-arbitro">
                            <div class="aca-arbitro-cell">
                                <div class="aca-avatar">{{ strtoupper(substr($nombreArbitro, 0, 1)) }}</div>
                                <span class="td-primary">{{ $nombreArbitro }}</span>
                            </div>
                        </td>
                        <td class="asistencia-estado col-estado">
                            <span class="badge {{ $aColor === 'pendiente' ? 'badge-pendiente' : 'badge-' . $aColor }}">{{ $aLabel }}</span>
                        </td>
                        <td class="asistencia-hora col-hora">{{ $a->horaMarca?->format('H:i') ?? '—' }}</td>
                        @if ($sesion->estadoSesion === 'en_curso' && $sesion->modoAsistencia === 'manual')
                            <td class="text-right col-acciones">
                                <div class="asistencia-acciones" data-locked="{{ $sinMarcar ? '0' : '1' }}">
                                    <button type="button" class="btn btn-secondary btn-sm btn-editar-marca" data-editar-asistencia="{{ $a->idAsistencia }}" title="Editar marca">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-marca {{ $a->estadoAsistencia === 'presente' && ! $sinMarcar ? 'is-activo' : 'btn-secondary' }}" data-corregir-asistencia="{{ $a->idAsistencia }}" data-estado-nuevo="presente" title="Marcar presente">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-marca {{ $a->estadoAsistencia === 'ausente' && ! $sinMarcar ? 'is-activo' : 'btn-secondary' }}" data-corregir-asistencia="{{ $a->idAsistencia }}" data-estado-nuevo="ausente" title="Marcar ausente">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

<script>
    window.colegioId    = {{ auth()->user()->idColegio }};
    window.sesionId     = {{ $sesion->idSesion }};
    window.csrfToken    = "{{ csrf_token() }}";
    window.scannerUrl   = "{{ route('academico.scanner') }}";
    window.corregirBase = "{{ url('academico/asistencias') }}";
</script>
@endsection

@push('scripts')
    @vite(['resources/js/academico/academico.js'])
@endpush
