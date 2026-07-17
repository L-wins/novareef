/**
 * Formulario de crear partido: carga dinámica de divisiones/sedes al elegir
 * torneo, y preview de roles según el formato de designación elegido.
 */

// Destruye la instancia Choices existente, repuebla el <select> nativo y la recrea.
export async function cargarDivisionesYSedes(torneoId) {
    const selDiv  = document.getElementById('sel-division');
    const selSede = document.getElementById('sel-sede');

    if (!selDiv || !selSede) return;

    // Destruir instancias Choices previas para poder manipular el <select> nativo
    if (selDiv._choicesInstance) {
        selDiv._choicesInstance.destroy();
        selDiv._choicesInstance = null;
        delete selDiv.dataset.choicesInit;
    }
    if (selSede._choicesInstance) {
        selSede._choicesInstance.destroy();
        selSede._choicesInstance = null;
        delete selSede.dataset.choicesInit;
    }

    // Mostrar estado de carga
    selDiv.innerHTML  = '<option value="">Cargando divisiones...</option>';
    selSede.innerHTML = '<option value="">Cargando sedes...</option>';
    selDiv.disabled  = true;
    selSede.disabled = true;

    try {
        const [rDiv, rSede] = await Promise.all([
            fetch(`${window.urlDivisiones}/${torneoId}/divisiones`),
            fetch(`${window.urlSedes}/${torneoId}/sedes`),
        ]);

        if (!rDiv.ok || !rSede.ok) throw new Error('Error del servidor');

        const divisiones = await rDiv.json();
        const sedes      = await rSede.json();

        selDiv.innerHTML = divisiones.length === 0
            ? '<option value="">Este torneo no tiene divisiones</option>'
            : '<option value="">Selecciona división</option>' +
              divisiones.map(d => `<option value="${d.idDivision}">${d.nombreDivision}</option>`).join('');

        selSede.innerHTML = sedes.length === 0
            ? '<option value="">Este torneo no tiene sedes</option>'
            : '<option value="">Selecciona sede</option>' +
              sedes.map(s => `<option value="${s.idSede}">${s.nombreSede}${s.municipio ? ' — ' + s.municipio : ''}</option>`).join('');

    } catch (e) {
        console.error('cargarDivisionesYSedes error', e);
        selDiv.innerHTML  = '<option value="">Error al cargar divisiones</option>';
        selSede.innerHTML = '<option value="">Error al cargar sedes</option>';
    } finally {
        selDiv.disabled  = false;
        selSede.disabled = false;

        // Recrear instancias Choices con el nuevo contenido
        selDiv._choicesInstance = new window.Choices(selDiv, {
            searchEnabled: false,
            shouldSort: false,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: selDiv.options[0]?.text || 'Selecciona división',
            allowHTML: false,
            position: 'auto',
        });
        selDiv.dataset.choicesInit = '1';

        selSede._choicesInstance = new window.Choices(selSede, {
            searchEnabled: false,
            shouldSort: false,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: selSede.options[0]?.text || 'Selecciona sede',
            allowHTML: false,
            position: 'auto',
        });
        selSede.dataset.choicesInit = '1';
    }
}

export function mostrarPreviewFormato() {
    const sel     = document.getElementById('sel-formato');
    const preview = document.getElementById('formato-preview');
    const lista   = document.getElementById('formato-roles');

    if (!sel || !preview || !lista) return;

    const opt = sel.options[sel.selectedIndex];
    if (!opt?.value) { preview.style.display = 'none'; return; }

    const nArbitros = parseInt(opt.dataset.arbitros ?? '0');
    const roles     = ['Central', 'Asistente 1', 'Asistente 2', 'Cuarto árbitro', 'VAR'];

    lista.innerHTML = roles.slice(0, nArbitros)
        .map(r => `<span class="formato-rol-item"><i class="fa-solid fa-user-tie"></i> ${r}</span>`)
        .join('');

    preview.style.display = 'block';
}
