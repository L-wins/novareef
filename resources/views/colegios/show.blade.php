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
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
             style="width:14px;height:14px;">
            <path fill-rule="evenodd"
                  d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75
                     0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z"
                  clip-rule="evenodd"/>
        </svg>
        Volver a colegios
    </a>

    {{-- Flash --}}
    @if (session('success'))
        <div class="flash flash-success" id="flash-msg">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                 style="width:16px;height:16px;flex-shrink:0;">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483
                         4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                      clip-rule="evenodd"/>
            </svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.75" stroke="currentColor" style="width:15px;height:15px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9"/>
                    </svg>
                    {{ $esSuspender ? 'Suspender' : 'Activar' }}
                </button>
            </form>

            {{-- Editar --}}
            <a href="{{ route('colegios.edit', $colegio->idColegio) }}" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                     stroke-width="1.75" stroke="currentColor" style="width:15px;height:15px;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5
                             0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5
                             7.125"/>
                </svg>
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
