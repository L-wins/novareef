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
    <details class="importar-guia" id="importar-guia" open>
        <summary>
            <span class="importar-guia__summary-icono"><i class="fa-solid fa-graduation-cap"></i></span>
            <span class="importar-guia__summary-texto">
                <strong>¿Primera vez importando?</strong>
                <span>Haz clic aquí para ver, paso a paso, cómo debe verse tu Word</span>
            </span>
            <i class="fa-solid fa-chevron-down importar-guia__summary-flecha"></i>
        </summary>

        <div class="importar-guia__contenido">

            {{-- Ubicación general: qué va a pasar después de subir el archivo --}}
            <div class="importar-pasos">
                <div class="importar-paso">
                    <span class="importar-paso__numero">1</span>
                    <div>
                        <p class="importar-paso__titulo">Subes el archivo</p>
                        <p class="importar-paso__texto">El .docx que te envía la asociación, tal cual lo recibes.</p>
                    </div>
                </div>
                <i class="fa-solid fa-arrow-right importar-paso__flecha" aria-hidden="true"></i>
                <div class="importar-paso">
                    <span class="importar-paso__numero">2</span>
                    <div>
                        <p class="importar-paso__titulo">Revisas y corriges</p>
                        <p class="importar-paso__texto">Verás cada partido detectado y podrás editar cualquier campo antes de confirmar.</p>
                    </div>
                </div>
                <i class="fa-solid fa-arrow-right importar-paso__flecha" aria-hidden="true"></i>
                <div class="importar-paso">
                    <span class="importar-paso__numero">3</span>
                    <div>
                        <p class="importar-paso__titulo">Confirmas</p>
                        <p class="importar-paso__texto">Se crean todos los partidos del torneo de una sola vez.</p>
                    </div>
                </div>
            </div>

            <p class="importar-guia__intro">
                No todas las asociaciones envían el Word con exactamente la misma estructura —
                el importador tolera bastantes variaciones (más abajo el detalle) — pero necesita
                reconocer <strong>dos partes en cada partido</strong>. Así se ven en un documento real:
            </p>

            {{-- ═══ Ejemplo visual del documento Word ═══ --}}
            <div class="importar-mockup">
                <div class="importar-mockup__etiqueta">
                    <span class="importar-mockup__numero">1</span>
                    Línea de contexto: grupo, categoría y fecha
                </div>
                <div class="importar-mockup__pagina">
                    <p class="importar-mockup__contexto">GRUPO 15&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;SUB 15&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;01 MARZO 07/08</p>

                    <table class="importar-mockup__tabla">
                        <tbody>
                            <tr>
                                <td class="et">PARTIDO</td><td>Santa Fe</td><td colspan="2">Bethel</td>
                                <td class="et">ARBITRO</td><td>Juan Pérez</td>
                            </tr>
                            <tr>
                                <td class="et">ESTADIO</td><td colspan="3">Centro Deportivo 1</td>
                                <td class="et">LINEA UNO</td><td>Carlos Ruiz</td>
                            </tr>
                            <tr>
                                <td class="et">DIA</td><td>Sábado 7 marzo</td>
                                <td class="et">HORA</td><td>09:00</td>
                                <td class="et">LINEA DOS</td><td>Pedro Gómez</td>
                            </tr>
                            <tr>
                                <td class="et">CIUDAD</td><td colspan="3">Bogotá</td>
                                <td class="et">EMERGENTE</td><td>Luis Torres</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="importar-mockup__etiqueta">
                    <span class="importar-mockup__numero">2</span>
                    Tabla de 4 filas con los datos del partido
                </div>
                <p class="importar-mockup__pie">
                    <i class="fa-regular fa-lightbulb"></i>
                    Tu tabla no tiene que verse idéntica a esta — el número de columnas por fila puede
                    variar de una asociación a otra. Lo único que importa es que cada etiqueta
                    (<code>PARTIDO</code>, <code>ESTADIO</code>...) esté escrita tal cual y su valor
                    vaya justo después.
                </p>
            </div>

            <div class="importar-guia__bloque">
                <p class="importar-guia__titulo-bloque">Significado de cada etiqueta</p>
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

            {{-- ═══ Tolera / no tolera, como tarjetas ═══ --}}
            <div class="importar-tolerancia">
                <div class="importar-tolerancia__panel importar-tolerancia__panel--ok">
                    <p class="importar-tolerancia__titulo">
                        <i class="fa-solid fa-circle-check"></i> Esto SÍ lo tolera
                    </p>
                    <ul>
                        <li>La etiqueta y su valor pegados en la misma celda (ej. <code>ESTADIO&nbsp;&nbsp;Centro Deportivo 1</code>) o en celdas separadas — ambos casos funcionan.</li>
                        <li>Celdas de más al final de una fila (restos de una plantilla reciclada) — se ignoran.</li>
                        <li>Un árbitro de otra asociación en cualquier rol — si no existe en tu colegio, ese rol queda sin asignar con una advertencia, pero el partido se importa igual.</li>
                        <li>Mayúsculas/minúsculas y tildes distintas en categoría y sede — el emparejamiento las ignora.</li>
                    </ul>
                </div>
                <div class="importar-tolerancia__panel importar-tolerancia__panel--error">
                    <p class="importar-tolerancia__titulo">
                        <i class="fa-solid fa-circle-xmark"></i> Esto NO lo tolera
                    </p>
                    <ul>
                        <li>Archivos <code>.doc</code> (Word 97-2003) — guárdalo primero como <code>.docx</code> desde Word (<em>Archivo &gt; Guardar como</em>).</li>
                        <li>Una tabla sin la etiqueta <strong>PARTIDO</strong> — esa fila queda con error y hay que corregirla a mano en el paso de revisión.</li>
                        <li>Fechas fuera del año del torneo, o sin el formato "día + nombre del mes" (ej. "7 marzo") y "HH:MM" para la hora.</li>
                    </ul>
                </div>
            </div>

            <p class="importar-guia__nota">
                <i class="fa-solid fa-heart"></i>
                Tranquilo si tu Word no es perfecto: cualquier fila que no se pueda interpretar queda
                marcada en rojo en el paso de revisión, con el detalle exacto del problema — la corriges
                ahí mismo, antes de confirmar.
            </p>
        </div>
    </details>

    <form method="POST" action="{{ route('designaciones.importar.procesar') }}" enctype="multipart/form-data" class="form-card">
        @csrf

        <div class="form-section">
            <p class="form-section-title">Datos de la importación</p>

            <div class="form-grid form-grid-2">
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
            </div>

            <div class="form-group">
                <label class="form-label">Archivo .docx <span class="req">*</span></label>

                <label for="input-archivo-word" class="importar-dropzone" id="importar-dropzone">
                    <input type="file" name="archivoWord" id="input-archivo-word" accept=".docx" required>
                    <span class="importar-dropzone__icono"><i class="fa-solid fa-file-word"></i></span>
                    <span class="importar-dropzone__texto" id="importar-dropzone-texto">
                        <strong>Haz clic para elegir el archivo</strong>
                        <span>o arrástralo aquí — solo .docx</span>
                    </span>
                </label>
                <p class="field-hint">Si el archivo es .doc (Word 97-2003), ábrelo en Word y usa Archivo &gt; Guardar como &gt; Word (.docx).</p>
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
