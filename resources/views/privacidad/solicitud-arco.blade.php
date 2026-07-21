@extends('layouts.app')

@section('titulo', 'Derechos sobre mis datos')
@section('seccion', 'Privacidad')

@section('contenido')
<div class="container" style="max-width:640px;">

    <h1 class="page-heading" style="margin-bottom:0.4rem;">Ejercer mis derechos sobre mis datos</h1>
    <p style="font-size:0.9rem;color:var(--text-muted);margin-bottom:1.5rem;">
        Puedes pedir acceso, corrección, eliminación u oponerte al tratamiento de tus datos personales
        (derechos ARCO — Ley 1581 de 2012). Tu colegio recibirá esta solicitud y debe atenderla dentro
        de los plazos que establece la ley.
    </p>

    @if($errors->any())
        <div class="flash-error" style="margin-bottom:1.25rem;">
            @foreach($errors->all() as $error)
                <p style="font-size:0.82rem;margin:0;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="form-card">
        <form method="POST" action="{{ route('privacidad.solicitud.store') }}" novalidate>
            @csrf

            <div class="form-group" style="margin-bottom:1rem;">
                <label for="tipo" class="form-label">Tipo de solicitud</label>
                <select id="tipo" name="tipo" data-nova-select data-placeholder="Selecciona…"
                        class="form-select {{ $errors->has('tipo') ? 'is-invalid' : '' }}">
                    <option value="">— Selecciona —</option>
                    @foreach($tipos as $tipoOpcion)
                        <option value="{{ $tipoOpcion }}" {{ old('tipo') === $tipoOpcion ? 'selected' : '' }}>
                            @switch($tipoOpcion)
                                @case('acceso') Acceso a mis datos @break
                                @case('rectificacion') Rectificación (corregir datos) @break
                                @case('cancelacion') Cancelación (eliminar datos) @break
                                @case('oposicion') Oposición a un tratamiento @break
                            @endswitch
                        </option>
                    @endforeach
                </select>
                @error('tipo') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label for="mensaje" class="form-label">Detalle tu solicitud</label>
                <textarea id="mensaje" name="mensaje" rows="5" maxlength="2000"
                          placeholder="Ej. Quiero que corrijan mi número de EPS, está desactualizado."
                          class="form-input {{ $errors->has('mensaje') ? 'is-invalid' : '' }}">{{ old('mensaje') }}</textarea>
                @error('mensaje') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-paper-plane"></i> Enviar solicitud
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
