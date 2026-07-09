{{-- Paginación estándar de listados admin.
     Uso: @include('admin.partials.pagination', ['paginator' => $usuarios, 'etiqueta' => 'usuarios']) --}}
@if($paginator->hasPages())
<div class="admin-pagination">
    <span class="admin-pagination__info">
        Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
        de {{ $paginator->total() }} {{ $etiqueta }}
    </span>
    <div class="admin-pagination__nav">
        @if($paginator->onFirstPage())
            <span class="admin-pagination__btn admin-pagination__btn--disabled">
                <i class="fa-solid fa-chevron-left"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="admin-pagination__btn">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
        @endif
        <span class="admin-pagination__pages">
            Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}
        </span>
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="admin-pagination__btn">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        @else
            <span class="admin-pagination__btn admin-pagination__btn--disabled">
                <i class="fa-solid fa-chevron-right"></i>
            </span>
        @endif
    </div>
</div>
@endif
