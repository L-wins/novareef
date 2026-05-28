@extends('layouts.app')

@section('titulo', 'Sin permiso')
@section('seccion', 'Acceso denegado')

@section('contenido')
<div style="
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    min-height:60vh;
    text-align:center;
    padding:2rem;
">

    <div style="
        width:72px;height:72px;
        border-radius:50%;
        background:rgba(239,68,68,0.12);
        display:flex;align-items:center;justify-content:center;
        margin-bottom:1.5rem;
    ">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="#ef4444" stroke-width="1.8" style="width:34px;height:34px;">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
    </div>

    <h1 style="font-size:1.5rem;font-weight:700;color:#f9fafb;margin:0 0 0.5rem;">
        Acceso denegado
    </h1>

    <p style="color:#9ca3af;font-size:0.95rem;max-width:400px;margin:0 0 2rem;line-height:1.6;">
        No tienes permiso para acceder a esta sección.<br>
        Contacta al administrador de tu colegio si crees que esto es un error.
    </p>

    <a href="{{ route('dashboard') }}" style="
        display:inline-flex;align-items:center;gap:0.5rem;
        background:#2563eb;color:#fff;
        padding:0.6rem 1.4rem;border-radius:8px;
        font-size:0.875rem;font-weight:500;
        text-decoration:none;
        transition:background 0.15s;
    ">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
             style="width:15px;height:15px;">
            <path fill-rule="evenodd"
                  d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v6a1 1 0 0 1-1
                     1h-2a1 1 0 0 1-1-1v-3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3a1 1 0 0 1-1
                     1H5a1 1 0 0 1-1-1v-6H3a1 1 0 0 1-.707-1.707l7-7Z"
                  clip-rule="evenodd"/>
        </svg>
        Volver al panel
    </a>

</div>
@endsection
