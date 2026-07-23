@php
    $modoRevision = $modoRevision ?? false;
    $documentosRequisitos = $documentosRequisitos ?? collect();
    $documentosResumen = $documentosResumen ?? [
        'total' => 0,
        'obligatorios' => 0,
        'entregados' => 0,
        'aprobadosObligatorios' => 0,
        'pendientesRevision' => 0,
        'devueltos' => 0,
        'completo' => true,
        'porcentaje' => 100,
    ];
@endphp

<section class="detail-card document-workflow" id="documentos">
    <div class="detail-card-head document-workflow__head">
        <span class="detail-card-icon"><i class="fa-solid fa-folder-open"></i></span>
        <div>
            <p class="detail-section-title">Expediente documental</p>
            <p class="document-workflow__subtitle">Plantillas, entregas y revisión del colegio.</p>
        </div>
        @can('editar-arbitros')
            <a href="{{ route('requisitos-documentos-arbitro.index') }}" class="btn btn-secondary btn-sm document-workflow__config">
                <i class="fa-solid fa-sliders"></i>
                Configurar
            </a>
        @endcan
    </div>

    <div data-ajax-region="documentos">
        @include('arbitros.partials.documentos-panel-contenido', [
            'arbitro' => $arbitro,
            'modoRevision' => $modoRevision,
            'documentosRequisitos' => $documentosRequisitos,
            'documentosResumen' => $documentosResumen,
        ])
    </div>
</section>
