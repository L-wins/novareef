{{-- Región reemplazable vía AJAX (cuenta activas/personalizadas/asignados) —
     ver CategoriaArbitroController. Recibe: $resumen. --}}
<section class="category-admin-hero">
    <div>
        <span class="page-kicker">Catálogo operativo</span>
        <h1 class="page-heading">Categorías de árbitro</h1>
        <p class="page-subtitle">
            Organiza las categorías disponibles para registro, edición, formación y requisitos documentales del colegio.
        </p>
    </div>

    <div class="category-admin-hero__stats">
        <div>
            <span>Total</span>
            <strong>{{ $resumen['total'] }}</strong>
        </div>
        <div>
            <span>Activas</span>
            <strong>{{ $resumen['activas'] }}</strong>
        </div>
        <div>
            <span>Personalizadas</span>
            <strong>{{ $resumen['personalizadas'] }}</strong>
        </div>
        <div>
            <span>Árbitros asignados</span>
            <strong>{{ $resumen['arbitrosAsignados'] }}</strong>
        </div>
    </div>
</section>
