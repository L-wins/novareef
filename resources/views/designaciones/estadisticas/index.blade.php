@extends('layouts.app')

@section('titulo', 'Estadísticas')
@section('seccion', 'Designaciones')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@section('contenido')
<div class="container">

    <div class="est-header">
        <a href="{{ route('disponibilidad.general') }}" class="est-back-link">
            <i class="fa-solid fa-arrow-left"></i> Volver a disponibilidad
        </a>
        <div class="page-header">
            <div class="page-header-left">
                <h1 class="page-heading">Estadísticas</h1>
                <p class="page-subheading">
                    Indicadores del cuerpo arbitral: disponibilidad, carga de partidos, confiabilidad y calificación.
                </p>
            </div>
        </div>
    </div>

    @include('designaciones.estadisticas.partials.resumen')

    <div class="est-sections">
        @include('designaciones.estadisticas.partials.categorias')
        @include('designaciones.estadisticas.partials.disponibilidad')
        @include('designaciones.estadisticas.partials.partidos-arbitro')
        @include('designaciones.estadisticas.partials.confiabilidad')
        @include('designaciones.estadisticas.partials.calificaciones')
        @include('designaciones.estadisticas.partials.coincidencias')
    </div>

</div>
@endsection
