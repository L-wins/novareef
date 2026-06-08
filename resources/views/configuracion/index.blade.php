@extends('layouts.app')

@section('titulo', 'Configuración')
@section('seccion', 'Configuración')

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Configuración del colegio</h1>
            <p class="page-subheading">Ajusta los parámetros del módulo de designaciones</p>
        </div>
    </div>

    <form method="POST" action="{{ route('configuracion.update') }}">
        @csrf
        @method('PUT')

        {{-- ── Día de reporte de disponibilidad ──────────────────────────── --}}
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

        <div style="margin-top:1.5rem;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                Guardar configuración
            </button>
        </div>

    </form>

</div>
@endsection
