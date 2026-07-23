@extends('layouts.app')

@section('titulo', 'Disponibilidad general')
@section('seccion', 'Disponibilidad')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@section('contenido')
<div class="container">

    {{-- Form sin campos propios: solo habilita el modo AJAX de auto-filter.js
         para que los links de navegación de semana (prev/next/hoy) dentro de
         la región se reemplacen sin recargar la página. --}}
    <form method="GET" action="{{ route('disponibilidad.general') }}" data-auto-filter data-auto-filter-ajax>
        <div data-auto-filter-region="calendario">
            @include('disponibilidad.partials.calendario-general')
        </div>
    </form>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/designaciones/designaciones.js'])
@endpush
