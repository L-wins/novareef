@extends('layouts.app')

@section('titulo', 'Aceptar política de datos')
@section('seccion', 'Privacidad')

@section('contenido')
<div class="container" style="max-width:600px;">

    <div style="text-align:center;margin-bottom:2rem;">
        <div style="width:56px;height:56px;border-radius:50%;background:rgba(79,142,247,.15);
                    border:2px solid rgba(79,142,247,.3);display:flex;align-items:center;
                    justify-content:center;margin:0 auto 1rem;">
            <i class="fa-solid fa-shield-halved" style="font-size:22px;color:#4f8ef7;"></i>
        </div>
        <h1 class="page-heading" style="font-size:1.35rem;margin-bottom:0.4rem;">
            Antes de continuar
        </h1>
        <p style="font-size:0.9rem;color:var(--text-muted);max-width:440px;margin:0 auto;">
            Actualizamos nuestra política de tratamiento de datos personales. Léela y acéptala para
            seguir usando NovaReef.
        </p>
    </div>

    @if($errors->any())
        <div class="flash-error" style="margin-bottom:1.25rem;">
            @foreach($errors->all() as $error)
                <p style="font-size:0.82rem;margin:0;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="form-card">
        <form method="POST" action="{{ route('privacidad.aceptar.guardar') }}" novalidate>
            @csrf

            <p style="font-size:0.88rem;color:var(--text-muted);margin-bottom:1rem;">
                <a href="{{ route('privacidad.politica') }}" target="_blank" rel="noopener">
                    Leer la política completa <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:0.7em;"></i>
                </a>
            </p>

            <div class="form-check" style="margin-bottom:1.5rem;">
                <input type="checkbox" id="acepto" name="acepto" value="1" class="form-check-input">
                <label for="acepto" class="form-check-label">
                    He leído y acepto la política de tratamiento de datos personales de NovaReef.
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;">
                <i class="fa-solid fa-check"></i> Aceptar y continuar
            </button>
        </form>
    </div>

</div>
@endsection
