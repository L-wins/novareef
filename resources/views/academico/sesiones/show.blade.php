@extends('layouts.app')

@section('titulo', $sesion->tema)
@section('seccion', 'Académico')

@push('styles')
    @vite(['resources/css/academico/academico.css'])
@endpush

@php
    $etiquetasEstadoSesion = [
        'programada' => ['Programada', 'gray'],
        'en_curso'   => ['En curso', 'amber'],
        'finalizada' => ['Finalizada', 'green'],
        'cancelada'  => ['Cancelada', 'red'],
    ];
    [$estadoLabel, $estadoColor] = $etiquetasEstadoSesion[$sesion->estadoSesion] ?? ['—', 'gray'];

    $etiquetasAsistencia = [
        'presente'                 => ['Presente', 'green'],
        'ausente'                  => ['Ausente', 'red'],
        'justificacion_pendiente'  => ['Justificación pendiente', 'amber'],
        'justificado'              => ['Justificado', 'blue'],
        'justificacion_rechazada'  => ['Justificación rechazada', 'gray'],
    ];
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
            <p class="page-subheading">
                <span class="badge badge-{{ $estadoColor }}">{{ $estadoLabel }}</span>
                {{ $sesion->tipo->etiqueta ?? '—' }}
                @if ($sesion->esOficial)
                    <span class="badge badge-blue"><i class="fa-solid fa-star"></i> Oficial FCF</span>
                @endif
            </p>
        </div>
        <div style="display:flex; gap:0.75rem;">
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
        </div>
    </div>

    @if (session('success'))
        <div class="flash-success" style="margin-bottom:1.25rem;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error" style="margin-bottom:1.25rem;">{{ session('error') }}</div>
    @endif

    <div class="form-card">
        <div class="form-section" style="border-bottom:none;">
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label">Fecha y hora</label>
                    <span>{{ $sesion->fechaSesion->format('d/m/Y') }} — {{ \Illuminate\Support\Carbon::parse($sesion->horaSesion)->format('H:i') }} ({{ $sesion->duracionMinutos }} min)</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Lugar / modalidad</label>
                    <span>
                        {{ ucfirst($sesion->modalidad) }}
                        @if ($sesion->modalidad === 'virtual' && $sesion->urlVirtual)
                            — <a href="{{ $sesion->urlVirtual }}" target="_blank" rel="noopener">enlace de la sesión</a>
                        @elseif ($sesion->lugar)
                            — {{ $sesion->lugar }}
                        @endif
                    </span>
                </div>
                <div class="form-group">
                    <label class="form-label">Dirigida a</label>
                    <span>{{ $sesion->dirigidaA === 'categoria' ? ($sesion->categoria->nombreCategoria ?? 'Categoría') : 'Todos los árbitros' }}</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Instructor</label>
                    <span>{{ $sesion->instructor->nombreUsuario ?? '—' }}</span>
                </div>
                @if ($sesion->descripcion)
                    <div class="form-group span-2">
                        <label class="form-label">Descripción</label>
                        <span>{{ $sesion->descripcion }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($sesion->modoAsistencia === 'scanner')
        <div class="scanner-panel">
            <div class="scanner-panel__icono"><i class="fa-solid fa-id-card"></i></div>
            <p class="page-subheading" style="margin-bottom:1rem;">Escanea el carné o escribe el código manualmente</p>
            <input type="text" id="scanner-input" class="scanner-input" placeholder="Código de carné"
                   {{ $sesion->sesionAbierta ? '' : 'disabled' }} autocomplete="off">
            <p id="scanner-feedback" class="scanner-feedback"></p>
        </div>

        <p class="aca-section-title">Registrados</p>
        <div id="scanner-lista" class="scanner-lista">
            @foreach ($asistencias->where('estadoAsistencia', 'presente') as $a)
                <div class="scanner-item">
                    <span>{{ $a->arbitro->usuario->nombreUsuario ?? '—' }}</span>
                    <span class="asistencia-hora">{{ $a->horaMarca?->format('H:i') }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <p class="aca-section-title" style="margin-top:1.5rem;">Lista de asistencia ({{ $asistencias->count() }})</p>
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Árbitro</th>
                    <th>Estado</th>
                    <th>Hora</th>
                    @if ($sesion->estadoSesion === 'en_curso' && $sesion->modoAsistencia === 'manual')
                        <th class="text-right">Corregir</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($asistencias as $a)
                    @php [$aLabel, $aColor] = $etiquetasAsistencia[$a->estadoAsistencia] ?? ['—', 'gray']; @endphp
                    <tr class="asistencia-row" data-asistencia="{{ $a->idAsistencia }}" data-estado="{{ $a->estadoAsistencia }}">
                        <td class="td-primary">{{ $a->arbitro->usuario->nombreUsuario ?? '—' }}</td>
                        <td class="asistencia-estado"><span class="badge badge-{{ $aColor }}">{{ $aLabel }}</span></td>
                        <td class="asistencia-hora">{{ $a->horaMarca?->format('H:i') ?? '—' }}</td>
                        @if ($sesion->estadoSesion === 'en_curso' && $sesion->modoAsistencia === 'manual')
                            <td class="text-right">
                                <div class="asistencia-acciones">
                                    <button type="button" class="btn btn-secondary btn-sm" data-corregir-asistencia="{{ $a->idAsistencia }}" data-estado-nuevo="presente">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" data-corregir-asistencia="{{ $a->idAsistencia }}" data-estado-nuevo="ausente">
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
    window.colegioId  = {{ auth()->user()->idColegio }};
    window.sesionId   = {{ $sesion->idSesion }};
    window.csrfToken  = "{{ csrf_token() }}";
    window.scannerUrl = "{{ route('academico.scanner') }}";
    window.corregirBase = "{{ url('academico/asistencias') }}";
</script>
@endsection

@push('scripts')
    @vite(['resources/js/academico/academico.js'])
@endpush
