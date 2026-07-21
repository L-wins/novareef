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
        <div class="page-header-right">
            <a href="{{ route('designaciones.importar.historial') }}" class="btn btn-ghost">
                <i class="fa-solid fa-clock-rotate-left"></i>
                Historial de importaciones
            </a>
        </div>
    </div>

    @if(session('error'))
        <div class="flash-error" style="margin-bottom:1.25rem;">{{ session('error') }}</div>
    @endif

@if(is_null($importacion))
{{-- ══════════ ESTADO 1: formulario de subida ══════════ --}}
    <details class="importar-guia">
        <summary>
            <i class="fa-solid fa-circle-info"></i>
            ¿Qué formato debe tener el archivo .docx?
        </summary>

        <div class="importar-guia__contenido">
            <p class="importar-guia__intro">
                No todas las asociaciones envían el Word con exactamente la misma estructura de tabla —
                el importador tolera algunas variaciones, pero necesita estas dos partes en cada partido:
            </p>

            <div class="importar-guia__bloque">
                <p class="importar-guia__titulo-bloque">1. Un bloque de texto con el contexto del grupo</p>
                <p class="importar-guia__texto">
                    Antes de la tabla de cada partido debe haber una línea de texto con, en este orden:
                    <strong>grupo</strong> (opcional), <strong>categoría/división</strong> y <strong>fecha</strong>,
                    separados por tabulaciones. Ejemplo: <code>GRUPO 15&nbsp;&nbsp;&nbsp;SUB 15&nbsp;&nbsp;&nbsp;01 MARZO 07/08</code>
                </p>
            </div>

            <div class="importar-guia__bloque">
                <p class="importar-guia__titulo-bloque">2. Una tabla de 4 filas por partido, con estas etiquetas</p>
                <p class="importar-guia__texto">
                    El importador busca el texto exacto de cada etiqueta en la tabla (no importa en qué celda esté)
                    y toma el valor de la celda siguiente. Las etiquetas obligatorias son:
                </p>
                <table class="importar-guia__tabla">
                    <thead>
                        <tr><th>Columna izquierda</th><th>Columna derecha</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>PARTIDO</strong> — equipo local, equipo visitante</td><td><strong>ARBITRO</strong> — nombre del árbitro central</td></tr>
                        <tr><td><strong>ESTADIO</strong> — nombre de la sede</td><td><strong>LINEA UNO</strong> — nombre del asistente 1</td></tr>
                        <tr><td><strong>DIA</strong> / <strong>HORA</strong> — fecha y hora del partido</td><td><strong>LINEA DOS</strong> — nombre del asistente 2</td></tr>
                        <tr><td><strong>CIUDAD</strong> — municipio donde se juega</td><td><strong>EMERGENTE</strong> — nombre del cuarto árbitro</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="importar-guia__bloque">
                <p class="importar-guia__titulo-bloque">Lo que SÍ tolera el importador</p>
                <ul class="importar-guia__lista importar-guia__lista--ok">
                    <li>Que la etiqueta y su valor vengan en la misma celda (ej. una celda que diga <code>ESTADIO&nbsp;&nbsp;&nbsp;Centro Deportivo 1</code>) o en celdas separadas — ambos casos funcionan.</li>
                    <li>Celdas de más al final de una fila (restos de una plantilla reciclada) — se ignoran.</li>
                    <li>Que el nombre del árbitro de cada rol (ARBITRO/LINEA UNO/LINEA DOS/EMERGENTE) pertenezca a otra asociación — si no existe en este colegio, ese rol queda sin asignar con una advertencia, pero el partido se importa igual.</li>
                    <li>Mayúsculas/minúsculas y tildes en categoría y sede — el matching contra las divisiones/sedes del torneo las ignora.</li>
                </ul>
            </div>

            <div class="importar-guia__bloque">
                <p class="importar-guia__titulo-bloque">Lo que NO tolera</p>
                <ul class="importar-guia__lista importar-guia__lista--error">
                    <li>Archivos <code>.doc</code> (Word 97-2003) — deben guardarse como <code>.docx</code> primero.</li>
                    <li>Una tabla sin la etiqueta <strong>PARTIDO</strong> — esa fila queda con error y no se puede incluir sin corregirla manualmente en el paso de revisión.</li>
                    <li>Fechas fuera del año del torneo, o que no tengan el formato "día + nombre del mes" (ej. "7 marzo") y "HH:MM" para la hora.</li>
                </ul>
            </div>

            <p class="importar-guia__nota">
                Cualquier fila que no se pueda interpretar queda marcada en rojo en el paso de revisión, con el detalle
                del problema — puedes corregirla ahí mismo antes de confirmar, no hace falta que el Word sea perfecto.
            </p>
        </div>
    </details>

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
        $totalErrores      = $filas->filter(fn ($f) => $f->tieneErrores())->count();
        $totalAdvertencias = $filas->filter(fn ($f) => ! $f->tieneErrores() && ($f->advertencias ?? []) !== [])->count();
        $totalDuplicados   = $filas->filter(fn ($f) => $f->esPosibleDuplicado)->count();
        $porCategoria      = $filas->groupBy('categoriaTexto');
    @endphp

    <div class="importar-resumen">
        <div class="importar-resumen__item">
            <span class="importar-resumen__valor">{{ $filas->count() }}</span>
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
        @if($totalDuplicados > 0)
        <div class="importar-resumen__item importar-resumen__item--warn">
            <span class="importar-resumen__valor">{{ $totalDuplicados }}</span>
            <span class="importar-resumen__label">Posibles duplicados</span>
        </div>
        @endif
        <div class="importar-resumen__archivo">
            <i class="fa-solid fa-file-word"></i>
            {{ $importacion->nombreArchivoOriginal }}
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

            <div class="importar-lista">
                @foreach($filasCategoria as $fila)
                @php
                    $prefijo = "filas[{$fila->clave}]";
                    $errores = $fila->errores ?? [];
                    $advertencias = $fila->advertencias ?? [];
                    $estadoFila = $errores !== [] ? 'error' : ($advertencias !== [] ? 'warn' : 'ok');
                @endphp
                <div class="importar-card importar-card--{{ $estadoFila }}">

                    {{-- ── Cabecera: incluir + partido + estado ── --}}
                    <div class="importar-card__cabecera">
                        <label class="importar-card__incluir">
                            <input type="checkbox" name="{{ $prefijo }}[incluir]" value="1"
                                   {{ $fila->incluir ? 'checked' : '' }} {{ $errores !== [] ? 'disabled' : '' }}>
                            @if($errores !== [])
                                <input type="hidden" name="{{ $prefijo }}[incluir]" value="">
                            @endif
                        </label>

                        <div class="importar-card__partido">
                            <input type="text" class="form-input importar-input-sm importar-input-equipo" name="{{ $prefijo }}[equipoLocal]" value="{{ $fila->equipoLocal }}" placeholder="Equipo local">
                            <span class="importar-card__vs">vs</span>
                            <input type="text" class="form-input importar-input-sm importar-input-equipo" name="{{ $prefijo }}[equipoVisitante]" value="{{ $fila->equipoVisitante }}" placeholder="Equipo visitante">
                        </div>

                        <div class="importar-card__estados">
                            @if($errores !== [])
                                <span class="badge-fila-estado badge-fila-estado--error" title="{{ implode(' — ', $errores) }}">
                                    <i class="fa-solid fa-circle-exclamation"></i> Error
                                </span>
                            @elseif($advertencias !== [])
                                <span class="badge-fila-estado badge-fila-estado--warn" title="{{ implode(' — ', $advertencias) }}">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Aviso
                                </span>
                            @else
                                <span class="badge-fila-estado badge-fila-estado--ok">
                                    <i class="fa-solid fa-circle-check"></i> OK
                                </span>
                            @endif
                            @if($fila->esPosibleDuplicado)
                                <span class="badge-fila-estado badge-fila-estado--warn" title="Ya existe un partido similar en este torneo">
                                    <i class="fa-solid fa-clone"></i> Duplicado
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- ── Cuerpo: campos editables agrupados por bloque lógico ── --}}
                    <div class="importar-card__cuerpo">
                        <div class="importar-campo importar-campo--grupo">
                            <label class="importar-campo__label">Grupo</label>
                            <input type="text" class="form-input importar-input-sm" name="{{ $prefijo }}[grupoTexto]"
                                   value="{{ $fila->grupoTexto }}">
                        </div>

                        <div class="importar-campo importar-campo--division">
                            <label class="importar-campo__label">
                                División
                                <i class="fa-regular fa-circle-question importar-campo__ayuda" title="Word: &quot;{{ $fila->categoriaTexto }}&quot;"></i>
                            </label>
                            <select name="{{ $prefijo }}[idDivision]" class="form-input importar-input-sm importar-select" data-nova-select data-searchable="true" data-placeholder="Sin división">
                                <option value="">— Sin división —</option>
                                @foreach($divisiones as $d)
                                <option value="{{ $d->idDivision }}" {{ $fila->idDivisionMatch == $d->idDivision ? 'selected' : '' }}>
                                    {{ $d->nombreDivision }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="importar-campo importar-campo--sede">
                            <label class="importar-campo__label">
                                Sede
                                <i class="fa-regular fa-circle-question importar-campo__ayuda" title="Word: &quot;{{ $fila->nombreSedeTexto }}&quot;"></i>
                            </label>
                            <select name="{{ $prefijo }}[idSede]" class="form-input importar-input-sm importar-select" data-nova-select data-searchable="true" data-placeholder="Sin sede">
                                <option value="">— Sin sede —</option>
                                @foreach($sedes as $s)
                                <option value="{{ $s->idSede }}" {{ $fila->idSedeMatch == $s->idSede ? 'selected' : '' }}>
                                    {{ $s->nombreSede }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="importar-campo importar-campo--fecha">
                            <label class="importar-campo__label">Fecha</label>
                            <input type="text" class="form-input importar-input-sm" data-nova-date
                                   name="{{ $prefijo }}[fechaPartido]" value="{{ $fila->fechaPartido?->format('Y-m-d') }}" placeholder="dd/mm/aaaa">
                        </div>

                        <div class="importar-campo importar-campo--hora">
                            <label class="importar-campo__label">Hora</label>
                            <input type="text" class="form-input importar-input-sm importar-input-hora"
                                   name="{{ $prefijo }}[horaPartido]" value="{{ $fila->horaPartido ? substr($fila->horaPartido, 0, 5) : '' }}"
                                   placeholder="HH:MM" maxlength="5" pattern="[0-2][0-9]:[0-5][0-9]"
                                   inputmode="numeric" autocomplete="off">
                        </div>

                        <div class="importar-campo importar-campo--formato">
                            <label class="importar-campo__label">Formato</label>
                            <select name="{{ $prefijo }}[idFormato]" class="form-input importar-input-sm importar-select" data-nova-select>
                                @foreach($formatos as $f)
                                <option value="{{ $f->idFormato }}" {{ $fila->idFormato == $f->idFormato ? 'selected' : '' }}>
                                    {{ $f->nombre }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- ── Árbitros designados en el Word ── --}}
                    @if(($fila->designacionesMatch ?? []) !== [])
                    <div class="importar-card__arbitros">
                        <p class="importar-card__arbitros-titulo">Árbitros designados en el Word</p>
                        <div class="importar-card__arbitros-grid">
                            @foreach($fila->designacionesMatch as $rol)
                            @php $idArbitroActual = $rol['idArbitroMatch'] ?? ''; @endphp
                            <div class="importar-rol-linea">
                                <span class="importar-rol-etiqueta">
                                    {{ $rol['rolTexto'] }}
                                    <i class="fa-regular fa-circle-question importar-campo__ayuda" title="Word: &quot;{{ $rol['nombreTexto'] }}&quot;{{ $rol['asociacionTexto'] ? ' ('.$rol['asociacionTexto'].')' : '' }}"></i>
                                </span>
                                <select name="{{ $prefijo }}[designaciones][{{ $rol['idRol'] }}]" class="form-input importar-input-sm importar-select" data-nova-select data-searchable="true" data-placeholder="Sin asignar">
                                    <option value="">— Sin asignar —</option>
                                    @foreach($arbitros as $a)
                                    <option value="{{ $a['id'] }}" {{ (string) $idArbitroActual === (string) $a['id'] ? 'selected' : '' }}>
                                        {{ $a['nombre'] }}
                                    </option>
                                    @endforeach
                                </select>
                                @if(empty($rol['idArbitroMatch']) && !empty($rol['sugerenciaNombre']))
                                <p class="field-hint">¿Quisiste decir <strong>{{ $rol['sugerenciaNombre'] }}</strong>?</p>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </div>
                @endforeach
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
