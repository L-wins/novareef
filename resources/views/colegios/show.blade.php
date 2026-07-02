@extends('layouts.app')

@section('titulo', $colegio->nombreColegio)
@section('seccion', 'Colegios')

@push('styles')
    @vite(['resources/css/colegios/colegios.css'])
@endpush

@section('contenido')
<div class="container">

    {{-- Volver --}}
    <a href="{{ route('colegios.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a colegios
    </a>

    {{-- Flash --}}
    @if (session('success'))
        <div class="flash flash-success" id="flash-msg">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Hero con nombre, código, estado y acciones --}}
    <div class="detail-hero">
        <div>
            <p class="detail-hero-nombre">{{ $colegio->nombreColegio }}</p>
            <p class="detail-hero-code">{{ $colegio->codigoColegio }}</p>
        </div>
        <div class="detail-hero-right">
            <span class="status-badge status-{{ $colegio->estadoColegio }}">
                {{ ucfirst($colegio->estadoColegio) }}
            </span>

            {{-- Toggle estado --}}
            <form method="POST"
                  action="{{ route('colegios.toggleEstado', $colegio->idColegio) }}">
                @csrf
                @method('PUT')
                @php $esSuspender = $colegio->estadoColegio === 'activo'; @endphp
                <button type="submit"
                        class="btn {{ $esSuspender ? 'btn-danger' : 'btn-success' }}"
                        data-confirm="{{ $esSuspender
                            ? '¿Suspender el colegio «' . $colegio->nombreColegio . '»?'
                            : '¿Activar el colegio «' . $colegio->nombreColegio . '»?' }}">
                    <i class="fa-solid fa-power-off"></i>
                    {{ $esSuspender ? 'Suspender' : 'Activar' }}
                </button>
            </form>

            {{-- Editar --}}
            <a href="{{ route('colegios.edit', $colegio->idColegio) }}" class="btn btn-secondary">
                <i class="fa-solid fa-pen-to-square"></i>
                Editar
            </a>
        </div>
    </div>

    {{-- Información del colegio --}}
    <div class="detail-card">

        <div class="detail-section">
            <p class="detail-section-title">Información general</p>
            <div class="detail-grid detail-grid-3">
                <div class="detail-field">
                    <span class="detail-label">Nombre</span>
                    <span class="detail-value">{{ $colegio->nombreColegio }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Código</span>
                    <span class="detail-value detail-value-code">{{ $colegio->codigoColegio }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Correo electrónico</span>
                    <span class="detail-value">{{ $colegio->emailColegio }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Teléfono</span>
                    @if ($colegio->telefonoColegio)
                        <span class="detail-value">{{ $colegio->telefonoColegio }}</span>
                    @else
                        <span class="detail-value-empty">No registrado</span>
                    @endif
                </div>
                <div class="detail-field">
                    <span class="detail-label">Plan</span>
                    <span class="detail-value">
                        <span class="plan-badge plan-{{ $colegio->planColegio }}">
                            {{ ucfirst($colegio->planColegio) }}
                        </span>
                    </span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Tenant ID</span>
                    <span class="detail-value detail-value-code">{{ $colegio->tenantId }}</span>
                </div>
                @if ($colegio->logoColegio)
                    <div class="detail-field span-2">
                        <span class="detail-label">Logo</span>
                        <span class="detail-value">
                            <a href="{{ $colegio->logoColegio }}" target="_blank"
                               style="color:var(--accent);word-break:break-all;">
                                {{ $colegio->logoColegio }}
                            </a>
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <div class="detail-section">
            <p class="detail-section-title">Ubicación</p>
            <div class="detail-grid detail-grid-3">
                <div class="detail-field">
                    <span class="detail-label">País</span>
                    <span class="detail-value">{{ $colegio->paisColegio }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Departamento</span>
                    @if ($colegio->departamentoColegio)
                        <span class="detail-value">{{ $colegio->departamentoColegio }}</span>
                    @else
                        <span class="detail-value-empty">No registrado</span>
                    @endif
                </div>
                <div class="detail-field">
                    <span class="detail-label">Ciudad</span>
                    @if ($colegio->ciudadColegio)
                        <span class="detail-value">{{ $colegio->ciudadColegio }}</span>
                    @else
                        <span class="detail-value-empty">No registrada</span>
                    @endif
                </div>
                <div class="detail-field span-2">
                    <span class="detail-label">Dirección</span>
                    @if ($colegio->direccionColegio)
                        <span class="detail-value">{{ $colegio->direccionColegio }}</span>
                    @else
                        <span class="detail-value-empty">No registrada</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="detail-section">
            <p class="detail-section-title">Suscripción</p>
            <div class="detail-grid detail-grid-3">
                <div class="detail-field">
                    <span class="detail-label">Fecha de suscripción</span>
                    @if ($colegio->fechaSuscripcion)
                        <span class="detail-value">
                            {{ $colegio->fechaSuscripcion->translatedFormat('d \d\e F \d\e Y') }}
                        </span>
                    @else
                        <span class="detail-value-empty">No definida</span>
                    @endif
                </div>
                <div class="detail-field">
                    <span class="detail-label">Fecha de expiración</span>
                    @if ($colegio->fechaExpiracion)
                        <span class="detail-value">
                            {{ $colegio->fechaExpiracion->translatedFormat('d \d\e F \d\e Y') }}
                        </span>
                    @else
                        <span class="detail-value-empty">No definida</span>
                    @endif
                </div>
                <div class="detail-field">
                    <span class="detail-label">Registrado el</span>
                    <span class="detail-value">
                        {{ $colegio->created_at->translatedFormat('d \d\e F \d\e Y') }}
                    </span>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/colegios/colegios.js'])
@endpush
