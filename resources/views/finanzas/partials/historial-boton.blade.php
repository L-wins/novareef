{{--
    Botón + contenido para el modal compartido #modal-historial.
    Uso: @include('finanzas.partials.historial-boton', ['mov' => $mov])
    La página que lo use debe incluir una sola vez el modal
    (finanzas.partials.historial-modal) y cargar finanzas.js.
--}}
<button type="button" class="btn btn-ghost btn-sm" data-open-modal="historial"
        data-historial-target="hist-{{ $mov->idMovimiento }}" title="Ver historial">
    <i class="fa-solid fa-clock-rotate-left"></i>
</button>
<template id="hist-{{ $mov->idMovimiento }}">
    @forelse ($mov->historial as $h)
        <div class="historial-item">
            <div class="historial-item__cabecera">
                <span class="historial-item__accion">{{ ucfirst($h->tipoAccion) }}</span>
                <span class="historial-item__fecha">{{ $h->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <p class="historial-item__usuario">{{ $h->usuarioAccion->nombreUsuario ?? 'Sistema' }}</p>
            @if ($h->detalle)
                <p class="historial-item__detalle">{{ $h->detalle }}</p>
            @endif
        </div>
    @empty
        <p class="card-empty-note">Sin historial registrado.</p>
    @endforelse
</template>
