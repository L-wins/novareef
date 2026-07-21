@extends('layouts.app')

@section('titulo', 'Configuración — General')
@section('seccion', 'Configuración')

@push('styles')
    @vite(['resources/css/configuracion/configuracion.css'])
@endpush

@push('scripts')
    @vite(['resources/js/configuracion/configuracion.js'])
@endpush

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Configuración</h1>
            <p class="page-subheading">Perfil del colegio y reglas de operación.</p>
        </div>
    </div>

    @include('configuracion.partials.subnav')

    <div class="cfg-grid">

    {{-- ── Identidad del colegio (logo) ──────── --}}
    <div class="detail-card">
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

    {{-- ── Datos de la cuenta (solo lectura) ──── --}}
    <div class="detail-card">
        <div class="detail-card-header">
            <div class="detail-card-title">
                <i class="fa-solid fa-id-card" style="color:var(--accent);margin-right:0.5rem;"></i>
                Datos de la cuenta
            </div>
        </div>
        <div class="detail-card-body">

            <div class="cfg-perfil-grid">
                <div class="cfg-perfil-item">
                    <span class="cfg-perfil-item__label">Nombre</span>
                    <span class="cfg-perfil-item__valor">{{ $colegio?->nombreColegio ?? '—' }}</span>
                </div>
                <div class="cfg-perfil-item">
                    <span class="cfg-perfil-item__label">Código</span>
                    <span class="cfg-perfil-item__valor">{{ $colegio?->codigoColegio ?? '—' }}</span>
                </div>
                <div class="cfg-perfil-item">
                    <span class="cfg-perfil-item__label">Correo</span>
                    <span class="cfg-perfil-item__valor">{{ $colegio?->emailColegio ?? '—' }}</span>
                </div>
                <div class="cfg-perfil-item">
                    <span class="cfg-perfil-item__label">Teléfono</span>
                    <span class="cfg-perfil-item__valor">{{ $colegio?->telefonoColegio ?? '—' }}</span>
                </div>
                <div class="cfg-perfil-item">
                    <span class="cfg-perfil-item__label">Ciudad</span>
                    <span class="cfg-perfil-item__valor">{{ $colegio?->ciudadColegio ?? '—' }}</span>
                </div>
                <div class="cfg-perfil-item">
                    <span class="cfg-perfil-item__label">País</span>
                    <span class="cfg-perfil-item__valor">{{ $colegio?->paisColegio ?? '—' }}</span>
                </div>
            </div>

            <div class="form-note form-note--info" style="margin-top:1.25rem;">
                <i class="fa-solid fa-circle-info"></i>
                <span>Estos datos los administra NovaReef — si necesitas corregir alguno, contacta a soporte.</span>
            </div>

        </div>
    </div>

    </div>

</div>
@endsection
