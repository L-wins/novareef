@extends('layouts.app')

@section('titulo', $arbitro->usuario->nombreUsuario)
@section('seccion', 'Árbitros')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
<div class="container">

    @if (session('success'))
        <div id="flash-msg" class="flash-success">{{ session('success') }}</div>
    @elseif (session('error'))
        <div id="flash-msg" class="flash-error">{{ session('error') }}</div>
    @endif

    <a href="{{ route('arbitros.index') }}" class="back-link">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;">
            <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/>
        </svg>
        Volver a árbitros
    </a>

    <div class="detail-hero">
        <div class="detail-hero-left">
            <div class="detail-avatar">{{ strtoupper(substr($arbitro->usuario->nombreUsuario, 0, 2)) }}</div>
            <div>
                <h1 class="detail-hero-name">{{ $arbitro->usuario->nombreUsuario }}</h1>
                <div class="detail-hero-meta">
                    <span class="cat-badge">{{ $arbitro->categoria->nombreCategoria }}</span>
                    <span class="status-badge status-{{ $arbitro->estadoArbitro }}">
                        {{ ucfirst(str_replace('_', ' ', $arbitro->estadoArbitro)) }}
                    </span>
                    <span class="td-code" style="font-size:0.78rem;">{{ $arbitro->codigoCarnet }}</span>
                </div>
            </div>
        </div>
        <div class="detail-hero-actions">
            @can('editar-arbitros')
            <a href="{{ route('arbitros.edit', $arbitro->idArbitro) }}" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;">
                    <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z"/>
                    <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z"/>
                </svg>
                Editar
            </a>
            <form method="POST" action="{{ route('arbitros.toggleEstado', $arbitro->idArbitro) }}">
                @csrf
                @method('PUT')
                <button type="button" class="btn btn-warning"
                        data-confirm="¿Avanzar el estado de {{ $arbitro->usuario->nombreUsuario }}?">
                    Cambiar estado
                </button>
            </form>
            @endcan
        </div>
    </div>

    <div class="detail-body">

        {{-- Datos del usuario --}}
        <div class="detail-card">
            <p class="detail-section-title">Datos del usuario</p>
            <div class="detail-grid">
                <div class="detail-field">
                    <span class="detail-label">Nombre completo</span>
                    <span class="detail-value">{{ $arbitro->usuario->nombreUsuario }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Correo electrónico</span>
                    <span class="detail-value">{{ $arbitro->usuario->emailUsuario }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Teléfono</span>
                    <span class="detail-value">{{ $arbitro->usuario->telefonoUsuario ?? '—' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Colegio</span>
                    <span class="detail-value">{{ $arbitro->colegio->nombreColegio }}</span>
                </div>
            </div>
        </div>

        {{-- Identificación --}}
        <div class="detail-card">
            <p class="detail-section-title">Identificación</p>
            <div class="detail-grid">
                <div class="detail-field">
                    <span class="detail-label">Número de documento</span>
                    <span class="detail-value">{{ $arbitro->numeroDocumento }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Tipo de documento</span>
                    <span class="detail-value">{{ ucfirst($arbitro->tipoDocumento) }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Lugar de expedición</span>
                    <span class="detail-value">{{ $arbitro->lugarExpedicionCC ?? '—' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Código de carné</span>
                    <span class="detail-value td-code">{{ $arbitro->codigoCarnet }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Categoría</span>
                    <span class="detail-value">{{ $arbitro->categoria->nombreCategoria }}</span>
                </div>
            </div>
        </div>

        {{-- Información física y de salud --}}
        <div class="detail-card">
            <p class="detail-section-title">Información física y de salud</p>
            <div class="detail-grid">
                <div class="detail-field">
                    <span class="detail-label">Peso</span>
                    <span class="detail-value">{{ $arbitro->pesoArbitro ? $arbitro->pesoArbitro . ' kg' : '—' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Estatura</span>
                    <span class="detail-value">{{ $arbitro->estaturaArbitro ? $arbitro->estaturaArbitro . ' m' : '—' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">RH</span>
                    <span class="detail-value">{{ $arbitro->rhArbitro ?? '—' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">EPS</span>
                    <span class="detail-value">{{ $arbitro->epsArbitro ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- Información profesional y administrativa --}}
        <div class="detail-card">
            <p class="detail-section-title">Información profesional y administrativa</p>
            <div class="detail-grid">
                <div class="detail-field">
                    <span class="detail-label">Profesión</span>
                    <span class="detail-value">{{ $arbitro->profesionArbitro ?? '—' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Fecha de ingreso al colegio</span>
                    <span class="detail-value">
                        {{ $arbitro->fechaIngresoColegio ? $arbitro->fechaIngresoColegio->format('d/m/Y') : '—' }}
                    </span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Dirección</span>
                    <span class="detail-value">{{ $arbitro->direccionArbitro ?? '—' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Barrio</span>
                    <span class="detail-value">{{ $arbitro->barrioArbitro ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- Vehículo --}}
        <div class="detail-card">
            <p class="detail-section-title">Vehículo</p>
            @if ($arbitro->tieneVehiculo)
                <div class="detail-grid">
                    <div class="detail-field">
                        <span class="detail-label">Tipo de vehículo</span>
                        <span class="detail-value">{{ ucfirst($arbitro->tipoVehiculo) }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">Marca</span>
                        <span class="detail-value">{{ $arbitro->marcaVehiculo ?? '—' }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">Placa</span>
                        <span class="detail-value">{{ $arbitro->placaVehiculo ?? '—' }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">Color</span>
                        <span class="detail-value">{{ $arbitro->colorVehiculo ?? '—' }}</span>
                    </div>
                </div>
            @else
                <p class="detail-empty">El árbitro no registra vehículo.</p>
            @endif
        </div>

        {{-- Documentos --}}
        <div class="detail-card">
            <p class="detail-section-title">Documentos</p>
            @forelse ($arbitro->documentos as $documento)
                <div class="docs-list">
                    <div class="doc-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-4.12-4.122A1.5 1.5 0 0 0 10.88 2H4.5Zm6 4.5a1 1 0 0 0 1 1h3l-4-4v3Z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ $documento->nombreDocumento }}</span>
                        @if ($documento->obligatorio)
                            <span class="cat-badge" style="margin-left:auto;">Obligatorio</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="detail-empty">No hay documentos cargados para este árbitro.</p>
            @endforelse
        </div>

    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/arbitros/arbitros.js'])
@endpush
