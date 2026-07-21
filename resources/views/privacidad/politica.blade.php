@extends('layouts.app')

@section('titulo', 'Política de tratamiento de datos')
@section('seccion', 'Privacidad')

@section('contenido')
<div class="container" style="max-width:760px;">

    <div style="background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.35);border-radius:10px;padding:0.9rem 1.1rem;margin-bottom:1.5rem;">
        <strong style="color:#f59e0b;">Borrador — pendiente de revisión legal.</strong>
        <span style="color:var(--text-muted);font-size:0.88rem;">
            Este documento describe cómo NovaReef trata los datos personales, pero todavía no ha sido
            revisado por un abogado. No debe considerarse la versión definitiva ni vinculante hasta que
            esa revisión se complete.
        </span>
    </div>

    <h1 class="page-heading" style="margin-bottom:0.25rem;">Política de tratamiento de datos personales</h1>
    <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:2rem;">
        Versión {{ \App\Services\PoliticaPrivacidadService::VERSION_ACTUAL }} · Ley 1581 de 2012 (Colombia) y su decreto reglamentario 1377 de 2013.
    </p>

    <div class="form-card" style="line-height:1.7;">
        <div class="form-section">
            <p class="form-section-title">1. Responsable del tratamiento</p>
            <p>NovaReef presta el servicio de gestión a colegios de árbitros de fútbol. Cada colegio,
            como responsable operativo de sus árbitros, decide qué datos se recolectan a través de la
            plataforma; NovaReef actúa como encargado técnico del tratamiento sobre esa información.</p>
        </div>

        <div class="form-section">
            <p class="form-section-title">2. Datos que se recolectan</p>
            <p>Datos de identificación (nombre, documento, lugar de expedición), contacto (correo,
            teléfono, dirección), profesionales (categoría, disponibilidad, historial de partidos),
            financieros (pagos, saldos, multas) y, de forma opcional, datos de salud: grupo sanguíneo
            (RH) y afiliación a EPS.</p>
        </div>

        <div class="form-section">
            <p class="form-section-title">3. Datos sensibles</p>
            <p>El grupo sanguíneo y la EPS son datos sensibles de salud (art. 5, Ley 1581/2012). Son
            <strong>opcionales</strong> — no estás obligado a suministrarlos, y solo se recolectan con tu
            autorización explícita y separada, distinta de la aceptación general de esta política.</p>
        </div>

        <div class="form-section">
            <p class="form-section-title">4. Finalidad</p>
            <p>Los datos se usan para operar el colegio de árbitros: designar partidos, verificar
            disponibilidad, gestionar pagos y sanciones, y llevar el registro académico. No se venden
            ni se comparten con terceros para fines comerciales ajenos a este propósito.</p>
        </div>

        <div class="form-section">
            <p class="form-section-title">5. Encargados externos</p>
            <p>Algunos proveedores procesan datos en nombre del colegio para que la plataforma funcione:
            envío de correos transaccionales y almacenamiento en servidores de hosting. Ninguno usa los
            datos con fines propios distintos a prestar ese servicio técnico.</p>
        </div>

        <div class="form-section">
            <p class="form-section-title">6. Tus derechos (ARCO)</p>
            <p>Como titular de los datos, tienes derecho a <strong>A</strong>cceder a ellos,
            <strong>R</strong>ectificarlos si están desactualizados o son inexactos, pedir su
            <strong>C</strong>ancelación, y <strong>O</strong>ponerte a un tratamiento específico
            (art. 8, Ley 1581/2012). Puedes ejercerlos desde
            <a href="{{ route('privacidad.solicitud.create') }}">este formulario</a>.</p>
        </div>

        <div class="form-section">
            <p class="form-section-title">7. Vigencia</p>
            <p>Los datos se conservan mientras exista la relación con el colegio y durante el tiempo
            adicional que exijan las obligaciones legales o contractuales aplicables.</p>
        </div>

        <div class="form-section">
            <p class="form-section-title">8. Autoridad de control</p>
            <p>Si consideras que tus derechos no fueron atendidos, puedes acudir a la Superintendencia
            de Industria y Comercio (SIC), autoridad de protección de datos en Colombia.</p>
        </div>
    </div>

</div>
@endsection
