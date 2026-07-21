/*
   Preview del importador de partidos desde Word — los selects/fechas de
   cada fila ya se inicializan globalmente via window.initNovaSelects()
   (app.js, en DOMContentLoaded). Este módulo solo agrega la confirmación
   antes de "Confirmar importación" (crea partidos reales) y el toggle
   visual de "Incluir" por fila.
*/

document.addEventListener('DOMContentLoaded', function () {
    // ── Guía de formato: recuerda si el usuario ya la colapsó ──
    // Abierta por defecto la primera vez (ayuda a quien no conoce el
    // formato); en cuanto alguien la cierra, se respeta esa preferencia
    // en sus próximas visitas — no tiene sentido obligar a un usuario con
    // experiencia a recogerla cada vez que entra a importar.
    const guia = document.getElementById('importar-guia');
    if (guia) {
        const CLAVE = 'novareef.importarGuiaAbierta';
        const preferencia = localStorage.getItem(CLAVE);
        if (preferencia !== null) {
            guia.open = preferencia === '1';
        }
        guia.addEventListener('toggle', () => {
            localStorage.setItem(CLAVE, guia.open ? '1' : '0');
        });
    }

    const btnConfirmar = document.getElementById('btn-confirmar-importacion');
    const form          = document.getElementById('form-importar-preview');

    if (btnConfirmar && form) {
        btnConfirmar.addEventListener('click', async function (e) {
            if (btnConfirmar.dataset.confirmado === '1') return;
            e.preventDefault();

            const incluidos = form.querySelectorAll('.importar-card input[type="checkbox"]:checked').length;

            if (incluidos === 0) {
                window.novaAlert?.error('No hay ningún partido marcado para incluir.');
                return;
            }

            const resultado = await window.novaAlert.confirm({
                titulo: '¿Confirmar importación?',
                texto: `Se crearán ${incluidos} partido(s) en estado Borrador. Podrás designar árbitros normalmente después.`,
                icono: 'question',
                confirmColor: '#4f8ef7',
                iconColor: '#4f8ef7',
                confirmarTexto: 'Sí, importar',
            });

            if (resultado.isConfirmed) {
                btnConfirmar.dataset.confirmado = '1';
                form.requestSubmit(btnConfirmar);
            }
        });
    }

    document.querySelectorAll('.importar-card').forEach(function (fila) {
        const checkbox = fila.querySelector('input[type="checkbox"]');
        if (!checkbox) return;

        const camposEditables = fila.querySelectorAll('input[type="text"], select');

        const aplicarEstado = () => {
            camposEditables.forEach((campo) => {
                campo.classList.toggle('importar-campo-deshabilitado', !checkbox.checked);
            });
        };

        checkbox.addEventListener('change', aplicarEstado);
        aplicarEstado();
    });

    // ── Dropzone del archivo .docx (pantalla de subida) ──
    const dropzone = document.getElementById('importar-dropzone');
    const inputArchivo = document.getElementById('input-archivo-word');
    const textoArchivo = document.getElementById('importar-dropzone-texto');

    if (dropzone && inputArchivo && textoArchivo) {
        const mostrarArchivo = (archivo) => {
            if (!archivo) {
                textoArchivo.innerHTML = '<strong>Haz clic para elegir el archivo</strong><span>o arrástralo aquí — solo .docx</span>';
                dropzone.classList.remove('importar-dropzone--valido', 'importar-dropzone--invalido');
                return;
            }

            const esDocx = /\.docx$/i.test(archivo.name);
            const pesoMb = (archivo.size / (1024 * 1024)).toFixed(1);

            textoArchivo.innerHTML = `<strong>${archivo.name}</strong><span>${pesoMb} MB${esDocx ? '' : ' — este archivo no es .docx'}</span>`;
            dropzone.classList.toggle('importar-dropzone--valido', esDocx);
            dropzone.classList.toggle('importar-dropzone--invalido', !esDocx);
        };

        inputArchivo.addEventListener('change', () => mostrarArchivo(inputArchivo.files[0]));

        ['dragenter', 'dragover'].forEach((evento) => {
            dropzone.addEventListener(evento, (e) => {
                e.preventDefault();
                dropzone.classList.add('importar-dropzone--sobre');
            });
        });
        ['dragleave', 'drop'].forEach((evento) => {
            dropzone.addEventListener(evento, (e) => {
                e.preventDefault();
                dropzone.classList.remove('importar-dropzone--sobre');
            });
        });
        dropzone.addEventListener('drop', (e) => {
            const archivo = e.dataTransfer?.files?.[0];
            if (archivo) {
                inputArchivo.files = e.dataTransfer.files;
                mostrarArchivo(archivo);
            }
        });
    }

    // ── Campo hora del preview: texto plano con auto-formato "HH:MM" ──
    // Se evitó a propósito el widget de hora de Flatpickr aquí — sus dos
    // <input type="number"> con flechas de incremento no caben en una celda
    // de tabla angosta (se ven truncados). Un input de texto simple con
    // auto-inserción de ":" es más robusto en ese espacio.
    document.querySelectorAll('.importar-input-hora').forEach(function (input) {
        input.addEventListener('input', function () {
            let digitos = input.value.replace(/\D/g, '').slice(0, 4);
            if (digitos.length >= 3) {
                input.value = digitos.slice(0, 2) + ':' + digitos.slice(2);
            } else {
                input.value = digitos;
            }
        });
    });
});
