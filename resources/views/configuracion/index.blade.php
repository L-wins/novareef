@extends('layouts.app')

@section('titulo', 'Configuración')
@section('seccion', 'Configuración')

@push('scripts')
    @vite(['resources/js/configuracion/configuracion.js'])
@endpush

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Configuración del colegio</h1>
            <p class="page-subheading">Ajusta los parámetros del módulo de designaciones</p>
        </div>
    </div>

    {{-- ── Identidad del colegio (logo) ──────── --}}
    <div class="detail-card" style="max-width:640px;margin-bottom:1.5rem;">
        <div class="detail-card-header">
            <div class="detail-card-title">
                <i class="fa-solid fa-shield-halved" style="color:var(--accent);margin-right:0.5rem;"></i>
                Identidad del colegio
            </div>
        </div>
        <div class="detail-card-body">

            <div class="logo-colegio-row">
                <div class="logo-colegio-preview">
                    @if ($colegio?->logoUrl)
                        <img src="{{ $colegio->logoUrl }}" alt="Logo de {{ $colegio->nombreColegio }}">
                    @else
                        <i class="fa-solid fa-building-columns"></i>
                    @endif
                </div>
                <div class="logo-colegio-info">
                    <p class="td-primary" style="margin:0;">{{ $colegio?->nombreColegio }}</p>
                    <p class="td-secondary" style="margin:0.25rem 0 0;">
                        El logo aparece en la barra superior para todos los usuarios del colegio.
                    </p>
                </div>
            </div>

            <div class="logo-colegio-actions" style="margin-top:1rem;display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                <form method="POST" action="{{ route('configuracion.logo.actualizar') }}" enctype="multipart/form-data" style="display:flex;gap:0.75rem;align-items:center;">
                    @csrf
                    <label class="btn btn-secondary" style="cursor:pointer;margin:0;">
                        <i class="fa-solid fa-image"></i>
                        {{ $colegio?->logoUrl ? 'Cambiar logo' : 'Subir logo' }}
                        <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp"
                               style="display:none;" data-submit-on-change>
                    </label>
                </form>
                @if ($colegio?->logoUrl)
                    <form method="POST" action="{{ route('configuracion.logo.eliminar') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-secondary" style="color:var(--nv-danger);">
                            <i class="fa-solid fa-trash-can"></i>
                            Quitar logo
                        </button>
                    </form>
                @endif
            </div>
            @error('logo')
                <span class="form-error" style="display:block;margin-top:0.5rem;">{{ $message }}</span>
            @enderror

            <div class="form-note form-note--info" style="margin-top:1rem;">
                <i class="fa-solid fa-circle-info"></i>
                <span>Formatos: JPG, PNG o WebP · máximo 2 MB. Se recomienda una imagen cuadrada.</span>
            </div>

        </div>
    </div>

    <form method="POST" action="{{ route('configuracion.update') }}" data-edit-mode>
        @csrf
        @method('PUT')

        {{-- ── Día de reporte de disponibilidad ──── --}}
        <div class="detail-card" style="max-width:640px;">
            <div class="detail-card-header">
                <div class="detail-card-title">
                    <i class="fa-solid fa-calendar-days" style="color:var(--accent);margin-right:0.5rem;"></i>
                    Día de reporte de disponibilidad
                </div>
            </div>
            <div class="detail-card-body">

                <div class="form-group">
                    <label class="form-label" for="dia_disponibilidad">
                        ¿Qué día deben reportar los árbitros su disponibilidad?
                    </label>
                    <select name="dia_disponibilidad"
                            id="dia_disponibilidad"
                            class="form-select"
                            data-nova-select
                            data-placeholder="Selecciona un día">
                        @foreach ($diasSemana as $num => $nombre)
                            <option value="{{ $num }}"
                                {{ $diaDisponibilidad === $num ? 'selected' : '' }}>
                                {{ $nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('dia_disponibilidad')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-note form-note--info" style="margin-top:0.75rem;">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>
                        Los árbitros deberán reportar su disponibilidad cada
                        <strong>{{ $diasSemana[$diaDisponibilidad] }}</strong>.
                        Una vez guardada no podrán modificarla hasta el siguiente ciclo semanal.
                    </span>
                </div>

            </div>
        </div>

        {{-- ── Confirmación de designaciones ─────── --}}
        <div class="detail-card" style="max-width:640px;margin-top:1.5rem;">
            <div class="detail-card-header">
                <div class="detail-card-title">
                    <i class="fa-solid fa-stopwatch" style="color:var(--accent);margin-right:0.5rem;"></i>
                    Confirmación de designaciones
                </div>
            </div>
            <div class="detail-card-body">

                <div class="form-group">
                    <label class="form-label" for="horas_limite_confirmacion">
                        Horas límite para confirmar
                    </label>
                    <input type="number"
                           name="horas_limite_confirmacion"
                           id="horas_limite_confirmacion"
                           class="form-input"
                           min="1"
                           max="72"
                           value="{{ old('horas_limite_confirmacion', $horasLimiteConfirmacion) }}"
                           style="max-width:140px">
                    @error('horas_limite_confirmacion')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-note form-note--info" style="margin-top:0.75rem;">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>
                        El árbitro tiene este tiempo para confirmar una designación
                        antes de que el partido pase a <strong>CRÍTICO</strong>.
                    </span>
                </div>

            </div>
        </div>

        <div style="margin-top:1.5rem;display:flex;gap:0.75rem;">
            <button type="button" class="btn btn-primary" data-edit-btn>
                <i class="fa-solid fa-pen-to-square"></i>
                Editar
            </button>
            <button type="submit" class="btn btn-primary" data-edit-save hidden>
                <i class="fa-solid fa-floppy-disk"></i>
                Guardar configuración
            </button>
            <button type="button" class="btn btn-secondary" data-edit-cancel hidden>
                <i class="fa-solid fa-xmark"></i>
                Cancelar
            </button>
        </div>

    </form>

</div>
@endsection
