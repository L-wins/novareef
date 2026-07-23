{{-- Región reemplazable vía AJAX (cuenta activos/por categoría/plantillas) —
     ver RequisitoDocumentoArbitroController. Recibe: $requisitos. --}}
<section class="document-admin-hero">
    <div>
        <span class="page-kicker">Expediente arbitral</span>
        <h1 class="page-heading">Documentos solicitados</h1>
        <p class="page-subtitle">
            Configura documentos globales o exclusivos por categoría. La revisión documental no bloquea la activación del árbitro.
        </p>
    </div>
    <div class="document-admin-hero__stats">
        <div>
            <span>Activos</span>
            <strong>{{ $requisitos->where('activo', true)->count() }}</strong>
        </div>
        <div>
            <span>Por categoría</span>
            <strong>{{ $requisitos->whereNotNull('idCategoria')->count() }}</strong>
        </div>
        <div>
            <span>Plantillas</span>
            <strong>{{ $requisitos->whereNotNull('plantillaRuta')->count() }}</strong>
        </div>
    </div>
</section>
