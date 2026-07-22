@extends('layouts.public')

@section('contenido')

    @include('welcome.navbar')
    @include('welcome.hero')
    @include('welcome.estadisticas')
    @include('welcome.trust-bar')
    @include('welcome.que-es')
    @include('welcome.modulos')
    @include('welcome.para-quien')
    @include('welcome.planes')
    @include('welcome.cta-final')
    @include('welcome.footer')

@endsection
