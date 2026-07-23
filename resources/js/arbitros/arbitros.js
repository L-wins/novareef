document.addEventListener('DOMContentLoaded', function () {

    //  Toggle visibilidad de contraseña ─
    document.querySelectorAll('.toggle-pwd').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.target);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            var svg = btn.querySelector('svg');
            if (svg) svg.style.opacity = input.type === 'text' ? '0.5' : '1';
        });
    });

    //  Mostrar/ocultar campos de vehículo según el checkbox ─
    var vehiculoCheck  = document.getElementById('tieneVehiculo');
    var vehiculoFields = document.getElementById('vehiculo-fields');
    if (vehiculoCheck && vehiculoFields) {
        function syncVehiculo() {
            vehiculoFields.style.display = vehiculoCheck.checked ? '' : 'none';
        }
        vehiculoCheck.addEventListener('change', syncVehiculo);
        syncVehiculo();
    }

    //  Cambio de estado: motivo/fechas según selección 
    var estadoSelect = document.getElementById('estadoNuevo');
    var motivoWrap   = document.getElementById('motivo-wrap');
    var fechasWrap   = document.getElementById('fechas-wrap');
    var motivoEstado = document.getElementById('motivo');
    var fechaInicio  = document.getElementById('fechaInicio');
    var fechaFin     = document.getElementById('fechaFin');
    if (estadoSelect) {
        function syncCambioEstado() {
            var v = estadoSelect.value;
            var requiereMotivo = v === 'suspendido' || v === 'retirado';
            var requiereFechas = v === 'suspendido';

            if (motivoWrap) motivoWrap.hidden = !requiereMotivo;
            if (motivoEstado) {
                motivoEstado.required = requiereMotivo;
                motivoEstado.disabled = !requiereMotivo;
            }

            if (fechasWrap) fechasWrap.hidden = !requiereFechas;
            if (fechaInicio) {
                fechaInicio.required = requiereFechas;
                fechaInicio.disabled = !requiereFechas;
            }
            if (fechaFin) fechaFin.disabled = !requiereFechas;
        }
        estadoSelect.addEventListener('change', syncCambioEstado);
        syncCambioEstado();
    }

    //  Modales (genérico): data-open-modal="X" → #modal-X 
    document.querySelectorAll('[data-open-modal]').forEach(function (btn) {
        var key = btn.dataset.openModal;
        var modal = document.getElementById('modal-' + key);
        if (!modal) return;

        btn.addEventListener('click', function () {
            modal.classList.add('is-open');
            if (window.initNovaSelects) initNovaSelects(modal);
            var first = modal.querySelector('textarea, input:not([type="hidden"]), select');
            if (first) setTimeout(function () { first.focus(); }, 50);
        });
    });

    document.querySelectorAll('.modal').forEach(function (modal) {
        modal.querySelectorAll('[data-close-modal]').forEach(function (btn) {
            btn.addEventListener('click', function () { modal.classList.remove('is-open'); });
        });
        modal.addEventListener('click', function (e) {
            if (e.target === modal) modal.classList.remove('is-open');
        });
    });

    // Cerrar con tecla ESC
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.modal.is-open').forEach(function (m) {
            m.classList.remove('is-open');
        });
    });

    //  Foto perfil: preview antes de subir ─
    var inputFoto = document.getElementById('input-foto');
    if (inputFoto) {
        inputFoto.addEventListener('change', function () {
            if (inputFoto.files && inputFoto.files[0]) {
                inputFoto.form.submit();
            }
        });
    }

    //  Forms de acción (documentos, requisitos, categorías...) — módulo AJAX ──
    //  [data-ajax-form]: fetch con FormData (soporta adjuntos) en vez de
    //  submit nativo; la respuesta trae {success, message, regions} y
    //  reemplaza el [data-ajax-region] correspondiente sin recargar la
    //  página — mismo patrón "regions" que EstadisticasController. Se
    //  combina con [data-confirm-submit] cuando el form también lo trae
    //  (ej. aprobar un documento o eliminar una categoría): primero confirma,
    //  luego envía por AJAX. Genérico a propósito — lo usan documentos-panel,
    //  requisitos y categorías, cada uno con su propia región.
    //  initAjaxForms() es re-invocable porque el contenido de la
    //  región se reemplaza por HTML nuevo del servidor tras cada acción —
    //  sin esto, los forms/inputs recién insertados quedarían sin listeners.
    function initAjaxForms(container) {
        container.querySelectorAll('[data-document-file]').forEach(function (input) {
            if (input.dataset.docFileInit === '1') return;
            input.dataset.docFileInit = '1';

            input.addEventListener('change', function () {
                var label = input.closest('form')?.querySelector('[data-document-file-name]');
                if (!label) return;

                label.textContent = input.files && input.files[0]
                    ? input.files[0].name
                    : 'Ningún archivo';
            });
        });

        container.querySelectorAll('[data-ajax-form]').forEach(function (form) {
            if (form.dataset.ajaxInit === '1') return;
            form.dataset.ajaxInit = '1';

            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                if (form.hasAttribute('data-confirm-submit')) {
                    if (!window.novaAlert) return;

                    var result = await novaAlert.confirm({
                        titulo: form.dataset.confirmTitle || '¿Confirmar?',
                        texto: form.dataset.confirmText || '¿Estás seguro?',
                        icono: 'question',
                        iconColor: form.dataset.confirmColor || '#4f8ef7',
                        confirmarTexto: form.dataset.confirmBtn || 'Sí, continuar',
                        confirmColor: form.dataset.confirmColor || '#4f8ef7',
                    });

                    if (!result.isConfirmed) return;
                }

                enviarFormularioAjax(form);
            });
        });
    }

    async function enviarFormularioAjax(form) {
        // data-ajax-region acepta una lista separada por espacios (ej.
        // "resumen categorias") — solo controla qué región(es) muestran el
        // fade de "cargando" mientras se espera la respuesta. Cuáles
        // regiones se reemplazan de verdad lo decide el propio backend: se
        // actualiza cualquier [data-ajax-region] cuya clave venga en
        // data.regions, sin importar qué form la disparó.
        var nombresRegion = (form.dataset.ajaxRegion || '').split(/\s+/).filter(Boolean);
        var elsCargando = nombresRegion
            .map(function (nombre) { return document.querySelector('[data-ajax-region="' + nombre + '"]'); })
            .filter(Boolean);
        var btn     = form.querySelector('button[type="submit"]');
        var htmlBtn = btn ? btn.innerHTML : '';

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        }
        elsCargando.forEach(function (el) { el.classList.add('is-loading'); });

        try {
            var r = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new FormData(form),
            });

            var data = await r.json().catch(function () { return null; });

            if (data && data.regions) {
                Object.keys(data.regions).forEach(function (nombre) {
                    var el = document.querySelector('[data-ajax-region="' + nombre + '"]');
                    if (!el) return;
                    el.innerHTML = data.regions[nombre];
                    initAjaxForms(el);
                    if (window.initNovaSelects) window.initNovaSelects(el);
                });
            }

            var huboExito = !!(data && data.success && !data.errors);

            if (huboExito) {
                if (form.hasAttribute('data-ajax-reset-on-success')) form.reset();

                var idCerrar = form.dataset.ajaxCloseOnSuccess;
                var elCerrar = idCerrar ? document.getElementById(idCerrar) : null;
                if (elCerrar && 'open' in elCerrar) elCerrar.open = false;
            }

            if (!window.novaAlert) return;

            if (data && data.errors) {
                var primerCampo = Object.values(data.errors)[0];
                novaAlert.error((primerCampo && primerCampo[0]) || 'Revisa los datos del formulario.');
            } else if (huboExito) {
                novaAlert.success(data.message || 'Listo.');
            } else {
                novaAlert.error((data && data.message) || 'No se pudo completar la acción.');
            }
        } catch (err) {
            console.error('documentos AJAX error', err);
            if (window.novaAlert) novaAlert.error('Error de red. Intenta de nuevo.');
        } finally {
            if (btn && btn.isConnected) {
                btn.disabled = false;
                btn.innerHTML = htmlBtn;
            }
            elsCargando.forEach(function (el) { el.classList.remove('is-loading'); });
        }
    }

    initAjaxForms(document);

    // [data-ajax-form] ya maneja su propia confirmación+envío en initAjaxForms()
    // arriba — este loop genérico es solo para forms de submit nativo.
    document.querySelectorAll('[data-confirm-submit]:not([data-ajax-form])').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            if (form.dataset.confirmed === '1' || !window.novaAlert) return;
            e.preventDefault();

            var result = await novaAlert.confirm({
                titulo: form.dataset.confirmTitle || '¿Confirmar?',
                texto: form.dataset.confirmText || '¿Estás seguro?',
                icono: 'question',
                iconColor: form.dataset.confirmColor || '#4f8ef7',
                confirmarTexto: form.dataset.confirmBtn || 'Sí, continuar',
                confirmColor: form.dataset.confirmColor || '#4f8ef7',
            });

            if (result.isConfirmed) {
                form.dataset.confirmed = '1';
                form.submit();
            }
        });
    });

    var motivoInput = document.getElementById('motivo-archivar');
    var contador    = document.getElementById('contador-motivo');
    if (motivoInput && contador) {
        function syncContador() {
            var len = motivoInput.value.length;
            contador.textContent = len + '/150';
            contador.style.color = len >= 130 ? '#ef4444' : '#8892a4';
        }
        motivoInput.addEventListener('input', syncContador);
        syncContador();
    }

    //  Restaurar árbitro (vista archivados) 
    document.querySelectorAll('[data-character-counter]').forEach(function (input) {
        var wrapper = input.closest('.form-group') || input.parentElement;
        var output = wrapper ? wrapper.querySelector('[data-character-counter-output]') : null;
        if (!output) return;

        var limit = Number(input.dataset.characterLimit || input.getAttribute('maxlength') || 0);
        if (!limit) return;

        function syncCharacterCounter() {
            var len = input.value.length;
            output.textContent = len + '/' + limit;
            output.classList.toggle('is-near-limit', len >= Math.floor(limit * 0.8) && len < limit);
            output.classList.toggle('is-at-limit', len >= limit);
        }

        input.addEventListener('input', syncCharacterCounter);
        syncCharacterCounter();
    });

    document.querySelectorAll('.btn-restaurar').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var nombre = btn.dataset.nombre;
            var id     = btn.dataset.id;

            if (!window.novaAlert) {
                console.warn('novaAlert no disponible');
                return;
            }

            var result = await novaAlert.confirm({
                titulo:         '¿Restaurar árbitro?',
                texto:          nombre + ' volverá a la lista de árbitros activos con estado "Inactivo". Esta acción quedará registrada en el historial.',
                icono:          'question',
                iconColor:      '#4f8ef7',
                confirmarTexto: 'Sí, restaurar',
                confirmColor:   '#4f8ef7',
            });

            if (result.isConfirmed) {
                var form = document.getElementById('form-restaurar-' + id);
                if (form) form.submit();
            }
        });
    });

    //  Interceptar archivar: confirm final vía novaAlert ─
    var formArchivar = document.getElementById('form-archivar');
    if (formArchivar) {
        formArchivar.addEventListener('submit', async function (e) {
            if (formArchivar.dataset.confirmed === '1') return; // ya confirmado, dejar pasar
            e.preventDefault();

            var motivo = (motivoInput && motivoInput.value || '').trim();
            if (motivo === '') {
                if (window.novaAlert) novaAlert.error('Debes indicar el motivo del archivado.');
                if (motivoInput) motivoInput.focus();
                return;
            }

            var nombre = formArchivar.dataset.confirmNombre || 'este árbitro';
            var modal  = document.getElementById('modal-archivar');
            if (modal) modal.classList.remove('is-open');

            var result = await novaAlert.confirm({
                titulo:         '¿Archivar a ' + nombre + '?',
                texto:          'El árbitro pasará a estado "retirado" y no podrá iniciar sesión. Podrás restaurarlo desde la sección de archivados.',
                confirmarTexto: 'Sí, archivar',
                confirmColor:   '#ef4444',
            });

            if (result.isConfirmed) {
                formArchivar.dataset.confirmed = '1';
                formArchivar.submit();
            } else if (modal) {
                modal.classList.add('is-open');
            }
        });
    }

    //  Interceptar cambio de estado: confirm final vía novaAlert 
    var formCambioEstado = document.getElementById('form-cambio-estado');
    if (formCambioEstado) {
        formCambioEstado.addEventListener('submit', async function (e) {
            if (formCambioEstado.dataset.confirmed === '1') return;
            e.preventDefault();

            if (!estadoSelect || !estadoSelect.value) {
                if (window.novaAlert) novaAlert.error('Debes seleccionar un estado.');
                return;
            }

            var requiereMotivo = estadoSelect.value === 'suspendido' || estadoSelect.value === 'retirado';
            var requiereFechas = estadoSelect.value === 'suspendido';

            if (requiereMotivo && motivoEstado && motivoEstado.value.trim() === '') {
                if (window.novaAlert) novaAlert.error('Debes indicar el motivo del cambio de estado.');
                motivoEstado.focus();
                return;
            }

            if (requiereFechas && fechaInicio && fechaInicio.value === '') {
                if (window.novaAlert) novaAlert.error('Debes indicar la fecha de inicio de la suspensión.');
                fechaInicio.focus();
                return;
            }

            if (requiereFechas && fechaInicio && fechaFin && fechaInicio.value && fechaFin.value && fechaFin.value <= fechaInicio.value) {
                if (window.novaAlert) novaAlert.error('La fecha de fin debe ser posterior a la fecha de inicio.');
                fechaFin.focus();
                return;
            }

            var estadoLabel = estadoSelect.options[estadoSelect.selectedIndex].text;
            var nombre      = formCambioEstado.dataset.confirmNombre || 'este árbitro';
            var modal       = document.getElementById('modal-cambio-estado');
            if (modal) modal.classList.remove('is-open');

            var result = await novaAlert.confirm({
                titulo:         '¿Cambiar estado?',
                texto:          'El estado de ' + nombre + ' cambiará a "' + estadoLabel + '". El cambio quedará registrado en el historial.',
                icono:          'question',
                iconColor:      '#f59e0b',
                confirmarTexto: 'Sí, cambiar',
                confirmColor:   '#4f8ef7',
            });

            if (result.isConfirmed) {
                formCambioEstado.dataset.confirmed = '1';
                formCambioEstado.submit();
            } else if (modal) {
                modal.classList.add('is-open');
            }
        });
    }

    //  Validación cliente: contraseñas coinciden ─
    var pwdInput   = document.getElementById('passwordUsuario');
    var pwdConfirm = document.getElementById('passwordUsuario_confirmation');
    var pwdMsg     = document.getElementById('pwd-match-msg');

    if (pwdInput && pwdConfirm && pwdMsg) {
        function checkMatch() {
            if (pwdConfirm.value === '') {
                pwdMsg.style.display = 'none';
                pwdConfirm.classList.remove('is-invalid');
                return;
            }
            var match = pwdInput.value === pwdConfirm.value;
            pwdMsg.style.display = match ? 'none' : 'block';
            pwdConfirm.classList.toggle('is-invalid', !match);
        }
        pwdInput.addEventListener('input', checkMatch);
        pwdConfirm.addEventListener('input', checkMatch);

        var form = document.getElementById('arbitro-form');
        if (form) {
            form.addEventListener('submit', function (e) {
                if (pwdInput.value && pwdInput.value !== pwdConfirm.value) {
                    e.preventDefault();
                    pwdMsg.style.display = 'block';
                    pwdConfirm.classList.add('is-invalid');
                    pwdConfirm.focus();
                }
            });
        }
    }
});
