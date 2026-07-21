/**
 * NovaReef — Módulo Designaciones — M04 Bloque 3
 * Punto de entrada: importa cada bloque de responsabilidad (cada import
 * deja sus propios window.X = ... para los onclick= inline de Blade) y
 * conecta los listeners de DOMContentLoaded. Dividido por responsabilidad
 * para no volver a tener un solo archivo de ~980 líneas — ver auditoría de
 * plataforma, punto 3.1.
 */
import { initDateDividers } from '../shared/date-divider.js';
import { suscribirCanalesTiempoReal } from './realtime.js';
import { buscarArbitros } from './busqueda-arbitros.js';
import './asignacion.js';
import { rechazarDesignacion } from './mi-designacion.js';
import { cargarDivisionesYSedes, mostrarPreviewFormato } from './torneo-selects.js';
import { inicializarModalEditarPartido } from './estado-partido.js';
import { configurarContador } from './helpers.js';

document.addEventListener('DOMContentLoaded', function () {

    window.initNovaSelects?.();
    initDateDividers();

    suscribirCanalesTiempoReal();

    // ── Exportar PDF con rango de fechas (partidos-torneo.blade)
    const btnAbrirExportarPdf = document.getElementById('btn-abrir-exportar-pdf');
    const formExportarPdf     = document.getElementById('form-exportar-pdf');
    btnAbrirExportarPdf?.addEventListener('click', function () {
        formExportarPdf.style.display = formExportarPdf.style.display === 'none' ? 'flex' : 'none';
    });

    // ── Búsqueda de árbitros (show.blade)
    document.querySelectorAll('.arbitro-search').forEach(function (wrap) {
        const input        = wrap.querySelector('.arbitro-search__input');
        const results      = wrap.querySelector('.arbitro-search__results');
        const rolId        = wrap.dataset.rol;
        const partidoId    = wrap.dataset.partido;
        const desigReasignar = wrap.dataset.reasignar ?? null;

        let debounceTimer;

        input?.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = input.value.trim();

            if (q.length < 1) {
                results.style.display = 'none';
                results.innerHTML = '';
                return;
            }

            debounceTimer = setTimeout(() => buscarArbitros(partidoId, q, rolId, results, desigReasignar), 300);
        });

        // Cerrar al hacer clic fuera
        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) {
                results.style.display = 'none';
            }
        });
    });

    // ── Select torneo → cargar divisiones y sedes (partido-crear.blade) ──────
    // Escuchar tanto el evento nativo como el de Choices (dispara ambos en v11)
    const selTorneo = document.getElementById('sel-torneo');
    if (selTorneo) {
        selTorneo.addEventListener('change', function () {
            const torneoId = this.value;
            if (!torneoId) return;
            cargarDivisionesYSedes(torneoId);
        });
    }

    // ── Preview de roles según formato ───
    const selFormato = document.getElementById('sel-formato');
    if (selFormato) {
        selFormato.addEventListener('change', mostrarPreviewFormato);
    }

    // Si hay un torneo preseleccionado (old() en validación fallida), cargarlo
    if (selTorneo && selTorneo.value) {
        cargarDivisionesYSedes(selTorneo.value);
    }

    // ── Contadores de textarea ────────────
    configurarContador('obs-textarea', 'obs-counter');
    configurarContador('rechazo-motivo', 'rechazo-counter');

    // ── Botón confirmar rechazo ───────────
    const btnConfRechazo = document.getElementById('btn-confirmar-rechazo');
    if (btnConfRechazo) {
        btnConfRechazo.addEventListener('click', function () {
            const desigId = parseInt(this.dataset.desigId ?? '0');
            const motivo  = document.getElementById('rechazo-motivo')?.value?.trim() ?? '';

            if (!desigId) return;
            if (motivo.length < 10) {
                novaAlert.error('El motivo debe tener al menos 10 caracteres.');
                return;
            }
            rechazarDesignacion(desigId, motivo);
        });
    }

    inicializarModalEditarPartido();
});
