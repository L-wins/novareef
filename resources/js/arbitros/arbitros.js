document.addEventListener('DOMContentLoaded', function () {

    // ── Confirmación de cambio de estado ──────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!window.confirm(btn.dataset.confirm)) return;
            btn.closest('form').submit();
        });
    });

    // ── Auto-dismiss flash ────────────────────────────────────────────────
    var flash = document.getElementById('flash-msg');
    if (flash) {
        setTimeout(function () {
            flash.style.transition = 'opacity .4s, max-height .4s';
            flash.style.opacity    = '0';
            flash.style.maxHeight  = '0';
            flash.style.overflow   = 'hidden';
            flash.style.padding    = '0';
            flash.style.margin     = '0';
        }, 4000);
    }

    // ── Toggle visibilidad de contraseña ──────────────────────────────────
    document.querySelectorAll('.toggle-pwd').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.target);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            var svg = btn.querySelector('svg');
            if (svg) svg.style.opacity = input.type === 'text' ? '0.5' : '1';
        });
    });

    // ── Mostrar/ocultar campos de vehículo según el checkbox ──────────────
    var vehiculoCheck  = document.getElementById('tieneVehiculo');
    var vehiculoFields = document.getElementById('vehiculo-fields');
    if (vehiculoCheck && vehiculoFields) {
        function syncVehiculo() {
            vehiculoFields.style.display = vehiculoCheck.checked ? '' : 'none';
        }
        vehiculoCheck.addEventListener('change', syncVehiculo);
        syncVehiculo();
    }

    // ── Cambio de estado: motivo/fechas según selección ──────────────────
    var estadoSelect    = document.getElementById('estadoNuevo');
    var motivoWrap      = document.getElementById('motivo-wrap');
    var fechasWrap      = document.getElementById('fechas-wrap');
    if (estadoSelect) {
        function syncCambioEstado() {
            var v = estadoSelect.value;
            if (motivoWrap) motivoWrap.style.display = (v === 'suspendido' || v === 'retirado') ? '' : 'none';
            if (fechasWrap) fechasWrap.style.display = (v === 'suspendido') ? '' : 'none';
        }
        estadoSelect.addEventListener('change', syncCambioEstado);
        syncCambioEstado();
    }

    // ── Modal de cambio de estado ────────────────────────────────────────
    var modalEstado = document.getElementById('modal-cambio-estado');
    if (modalEstado) {
        document.querySelectorAll('[data-open-modal="cambio-estado"]').forEach(function (btn) {
            btn.addEventListener('click', function () { modalEstado.classList.add('is-open'); });
        });
        modalEstado.querySelectorAll('[data-close-modal]').forEach(function (btn) {
            btn.addEventListener('click', function () { modalEstado.classList.remove('is-open'); });
        });
        modalEstado.addEventListener('click', function (e) {
            if (e.target === modalEstado) modalEstado.classList.remove('is-open');
        });
    }

    // ── Foto perfil: preview antes de subir ──────────────────────────────
    var inputFoto = document.getElementById('input-foto');
    if (inputFoto) {
        inputFoto.addEventListener('change', function () {
            if (inputFoto.files && inputFoto.files[0]) {
                inputFoto.form.submit();
            }
        });
    }

    // ── Validación cliente: contraseñas coinciden ─────────────────────────
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
