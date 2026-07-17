@extends('layouts.app')

@section('titulo', 'Importar desde Word')
@section('seccion', 'Designaciones')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@section('contenido')
<div class="container">

    <div class="breadcrumb">
        <a href="{{ route('designaciones.index') }}">Designaciones</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>Importar desde Word</span>
    </div>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Importar partidos desde Word</h1>
            <p class="page-subheading">Sube el .docx que envía la asociación y crea todos los partidos de un torneo de una sola vez.</p>
        </div>
    </div>

    @if(session('error'))
        <div class="flash-error" style="margin-bottom:1.25rem;">{{ session('error') }}</div>
    @endif

@if(is_null($importacion))
{{-- ══════════ ESTADO 1: formulario de subida ══════════ --}}
    <form method="POST" action="{{ route('designaciones.importar.procesar') }}" enctype="multipart/form-data" class="form-card">
        @csrf

        <div class="form-section">
            <p class="form-section-title">Datos de la importación</p>

            <div class="form-group">
                <label class="form-label">Torneo <span class="req">*</span></label>
                <select name="idTorneo" class="form-input" data-nova-select data-searchable="true"
                        data-placeholder="Selecciona un torneo" required>
                    <option value="">Selecciona un torneo</option>
                    @foreach($torneos as $t)
                    <option value="{{ $t->idTorneo }}" {{ old('idTorneo') == $t->idTorneo ? 'selected' : '' }}>
                        {{ $t->nombreTorneo }} ({{ $t->temporada }})
                    </option>
                    @endforeach
                </select>
                <p class="field-hint">Las divisiones y sedes del Word se comparan contra las que ya existen en este torneo — créalas antes si faltan.</p>
                @error('idTorneo')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Formato de designación por defecto <span class="req">*</span></label>
                <select name="idFormato" class="form-input" data-nova-select data-placeholder="Selecciona formato" required>
                    <option value="">Selecciona formato</option>
                    @foreach($formatos as $f)
                    <option value="{{ $f->idFormato }}" {{ old('idFormato') == $f->idFormato ? 'selected' : '' }}>
                        {{ $f->nombre }} ({{ $f->maxArbitros }} árbitro{{ $f->maxArbitros > 1 ? 's' : '' }})
                    </option>
                    @endforeach
                </select>
                <p class="field-hint">Se aplica a todos los partidos importados; puedes cambiarlo por fila en el paso siguiente.</p>
                @error('idFormato')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Archivo .docx <span class="req">*</span></label>
                <input type="file" name="archivoWord" class="form-input" accept=".docx" required>
                <p class="field-hint">Solo .docx. Si el archivo es .doc (Word 97-2003), ábrelo en Word y usa Archivo &gt; Guardar como &gt; Word (.docx).</p>
                @error('archivoWord')<div class="form-error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('designaciones.index') }}" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-upload"></i>
                Subir y previsualizar
            </button>
        </div>
    </form>

@else
{{-- ══════════ ESTADO 2: preview editable ══════════ --}}
    @php
        $filas = $importacion['partidos'];
        $totalErrores      = collect($filas)->filter(fn ($f) => $f['errores'] !== [])->count();
        $totalAdvertencias = collect($filas)->filter(fn ($f) => $f['errores'] === [] && $f['advertencias'] !== [])->count();
        $porCategoria = collect($filas)->groupBy('categoriaTexto');
    @endphp

    <div class="importar-resumen">
        <div class="importar-resumen__item">
            <span class="importar-resumen__valor">{{ count($filas) }}</span>
            <span class="importar-resumen__label">Partidos detectados</span>
        </div>
        <div class="importar-resumen__item importar-resumen__item--error">
            <span class="importar-resumen__valor">{{ $totalErrores }}</span>
            <span class="importar-resumen__label">Con error</span>
        </div>
        <div class="importar-resumen__item importar-resumen__item--warn">
            <span class="importar-resumen__valor">{{ $totalAdvertencias }}</span>
            <span class="importar-resumen__label">Con advertencia</span>
        </div>
        <div class="importar-resumen__archivo">
            <i class="fa-solid fa-file-word"></i>
            {{ $importacion['nombreArchivoOriginal'] }}
        </div>
    </div>

    <div class="importar-desglose">
        @foreach($porCategoria as $categoria => $filasCategoria)
        <span class="badge-fila-desglose">{{ $categoria ?: 'Sin categoría detectada' }}: {{ $filasCategoria->count() }}</span>
        @endforeach
    </div>

    <form method="POST" action="{{ route('designaciones.importar.revisar') }}" id="form-importar-preview">
        @csrf

        @foreach($porCategoria as $categoria => $filasCategoria)
        <div class="importar-grupo">
            <p class="importar-grupo__titulo">{{ $categoria ?: 'Sin categoría detectada' }}</p>

            <div style="overflow-x:auto;">
                <table class="data-table importar-tabla">
                    <thead>
                        <tr>
                            <th>Incluir</th>
                            <th>Grupo</th>
                            <th>División</th>
                            <th>Local</th>
                            <th>Visitante</th>
                            <th>Sede</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Formato</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($filasCategoria as $fila)
                        @php
                            $prefijo = "filas[{$fila['clave']}]";
                            $estadoFila = $fila['errores'] !== [] ? 'error' : ($fila['advertencias'] !== [] ? 'warn' : 'ok');
                        @endphp
                        <tr class="importar-fila importar-fila--{{ $estadoFila }}">
                            <td>
                                <input type="checkbox" name="{{ $prefijo }}[incluir]" value="1"
                                       {{ $fila['incluir'] ? 'checked' : '' }} {{ $fila['errores'] !== [] ? 'disabled' : '' }}>
                                @if($fila['errores'] !== [])
                                    <input type="hidden" name="{{ $prefijo }}[incluir]" value="">
                                @endif
                            </td>
                            <td>
                                <input type="text" class="form-input importar-input-sm" name="{{ $prefijo }}[grupoTexto]"
                                       value="{{ $fila['grupoTexto'] }}">
                            </td>
                            <td>
                                <select name="{{ $prefijo }}[idDivision]" class="form-input importar-input-sm">
                                    <option value="">— Sin división —</option>
                                    @foreach($divisiones as $d)
                                    <option value="{{ $d->idDivision }}" {{ $fila['idDivisionMatch'] == $d->idDivision ? 'selected' : '' }}>
                                        {{ $d->nombreDivision }}
                                    </option>
                                    @endforeach
                                </select>
                                <p class="field-hint">Word: "{{ $fila['categoriaTexto'] }}"</p>
                            </td>
                            <td><input type="text" class="form-input importar-input-sm" name="{{ $prefijo }}[equipoLocal]" value="{{ $fila['equipoLocal'] }}"></td>
                            <td><input type="text" class="form-input importar-input-sm" name="{{ $prefijo }}[equipoVisitante]" value="{{ $fila['equipoVisitante'] }}"></td>
                            <td>
                                <select name="{{ $prefijo }}[idSede]" class="form-input importar-input-sm">
                                    <option value="">— Sin sede —</option>
                                    @foreach($sedes as $s)
                                    <option value="{{ $s->idSede }}" {{ $fila['idSedeMatch'] == $s->idSede ? 'selected' : '' }}>
                                        {{ $s->nombreSede }}
                                    </option>
                                    @endforeach
                                </select>
                                <p class="field-hint">Word: "{{ $fila['nombreSedeTexto'] }}"</p>
                            </td>
                            <td><input type="text" class="form-input importar-input-sm" data-nova-date
                                       name="{{ $prefijo }}[fechaPartido]" value="{{ $fila['fechaPartido'] }}" placeholder="dd/mm/aaaa"></td>
                            <td><input type="text" class="form-input importar-input-sm" data-nova-date
                                       data-enable-time="true" data-no-calendar="true" data-date-format="H:i" data-alt-format="H:i"
                                       name="{{ $prefijo }}[horaPartido]" value="{{ $fila['horaPartido'] }}" placeholder="HH:MM"></td>
                            <td>
                                <select name="{{ $prefijo }}[idFormato]" class="form-input importar-input-sm">
                                    @foreach($formatos as $f)
                                    <option value="{{ $f->idFormato }}" {{ $fila['idFormato'] == $f->idFormato ? 'selected' : '' }}>
                                        {{ $f->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                @if($fila['errores'] !== [])
                                    <span class="badge-fila-estado badge-fila-estado--error" title="{{ implode(' — ', $fila['errores']) }}">
                                        <i class="fa-solid fa-circle-exclamation"></i> Error
                                    </span>
                                @elseif($fila['advertencias'] !== [])
                                    <span class="badge-fila-estado badge-fila-estado--warn" title="{{ implode(' — ', $fila['advertencias']) }}">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Aviso
                                    </span>
                                @else
                                    <span class="badge-fila-estado badge-fila-estado--ok">
                                        <i class="fa-solid fa-circle-check"></i> OK
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach

        <div class="form-actions">
            <button type="submit" name="accion" value="revisar" class="btn btn-ghost">
                <i class="fa-solid fa-rotate"></i>
                Guardar correcciones
            </button>
            <button type="submit" name="accion" value="confirmar" formaction="{{ route('designaciones.importar.confirmar') }}" class="btn btn-primary" id="btn-confirmar-importacion">
                <i class="fa-solid fa-check"></i>
                Confirmar importación
            </button>
        </div>
    </form>

    <form method="POST" action="{{ route('designaciones.importar.cancelar') }}" id="form-cancelar-importacion" style="display:inline;">
        @csrf
    </form>
    <button type="submit" form="form-cancelar-importacion" class="btn btn-ghost" style="margin-top:.75rem;">
        <i class="fa-solid fa-xmark"></i>
        Cancelar y empezar de nuevo
    </button>

@endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/designaciones/designaciones.js', 'resources/js/designaciones/importar-designaciones.js'])
@endpush
