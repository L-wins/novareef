@extends('admin.layouts.app')

@section('titulo', 'Planes')

@section('contenido')

<div class="admin-page-header">
    <h1>Planes</h1>
    <p>Gestión de planes de suscripción disponibles para los colegios.</p>
</div>

<div class="admin-card" style="padding:3rem;text-align:center;">
    <div style="width:64px;height:64px;border-radius:20px;background:rgba(79,142,247,0.1);
                border:1px solid rgba(79,142,247,0.2);display:flex;align-items:center;
                justify-content:center;margin:0 auto 1.25rem;">
        <i data-feather="credit-card" style="width:28px;height:28px;color:var(--primary);"></i>
    </div>
    <h2 style="font-size:1.125rem;font-weight:700;color:var(--text-bright);margin:0 0 0.5rem;">
        Módulo en construcción
    </h2>
    <p style="font-size:0.875rem;color:var(--text-muted);margin:0;">
        Este módulo estará disponible próximamente.
    </p>
</div>

@endsection
