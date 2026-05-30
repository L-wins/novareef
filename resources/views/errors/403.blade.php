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
        <i class="fa-solid fa-lock" style="font-size:34px;color:#ef4444;"></i>
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
        <i class="fa-solid fa-house"></i>
        Volver al panel
    </a>

</div>
@endsection
