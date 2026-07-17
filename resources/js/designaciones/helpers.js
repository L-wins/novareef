/**
 * Utilidades pequeñas y sin dependencias del resto del módulo.
 */

// ── Historial de acciones: "Ver más" / "Ver menos" ────
export function toggleHistorialCompleto() {
    const timeline = document.getElementById('historial-timeline');
    const btn       = document.getElementById('btn-historial-toggle');
    if (!timeline || !btn) return;

    const expandido = timeline.classList.toggle('mostrar-todo');
    const restantes  = btn.dataset.restantes ?? '0';

    btn.innerHTML = expandido
        ? '<i class="fa-solid fa-chevron-up"></i> Ver menos'
        : `<i class="fa-solid fa-chevron-down"></i> Ver ${restantes} más...`;
}
window.toggleHistorialCompleto = toggleHistorialCompleto;

export function configurarContador(textareaId, counterId) {
    const ta    = document.getElementById(textareaId);
    const count = document.getElementById(counterId);
    if (!ta || !count) return;

    ta.addEventListener('input', function () {
        count.textContent = ta.value.length;
    });
}
