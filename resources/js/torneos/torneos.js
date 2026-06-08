/* 
   NovaReef — M03 Torneos
   - Secciones colapsables del perfil
   - SweetAlert2 (window.novaAlert) para confirmaciones de eliminación
   - Manejo de modales genérico (compatible con arbitros.js)
    */

document.addEventListener('DOMContentLoaded', function () {

    //  Secciones colapsables del perfil 
    document.querySelectorAll('.perfil-step-head').forEach(function (head) {
        head.addEventListener('click', function (e) {
            // No colapsar si el click viene de un botón dentro del head
            if (e.target.closest('button, a, input')) return;
            head.closest('.perfil-step')?.classList.toggle('is-open');
        });
    });

    //  Eliminar (botones genéricos: divisiones, sedes, tarifas) 
    //    data-delete-form="formulario-id" + data-confirm-title + data-confirm-text
    document.querySelectorAll('[data-delete-form]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            if (!window.novaAlert) return;

            var formId  = btn.dataset.deleteForm;
            var titulo  = btn.dataset.confirmTitle || '¿Eliminar?';
            var texto   = btn.dataset.confirmText  || 'Esta acción no se puede deshacer.';
            var btnLbl  = btn.dataset.confirmBtn   || 'Sí, eliminar';

            var result = await novaAlert.confirm({
                titulo:         titulo,
                texto:          texto,
                confirmarTexto: btnLbl,
                confirmColor:   '#ef4444',
            });

            if (result.isConfirmed) {
                document.getElementById(formId)?.submit();
            }
        });
    });

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

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.modal.is-open').forEach(function (m) {
            m.classList.remove('is-open');
        });
    });

    //  Confirmar cambio de estado (torneo o partido) 
    document.querySelectorAll('[data-confirm-submit]').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            if (form.dataset.confirmed === '1' || !window.novaAlert) return;
            e.preventDefault();

            var titulo = form.dataset.confirmTitle || '¿Confirmar?';
            var texto  = form.dataset.confirmText  || '¿Estás seguro?';
            var color  = form.dataset.confirmColor || '#4f8ef7';
            var btnLbl = form.dataset.confirmBtn   || 'Sí, continuar';

            var result = await novaAlert.confirm({
                titulo:         titulo,
                texto:          texto,
                icono:          'question',
                iconColor:      color,
                confirmarTexto: btnLbl,
                confirmColor:   color,
            });

            if (result.isConfirmed) {
                form.dataset.confirmed = '1';
                form.submit();
            }
        });
    });

    //  Reglamento PDF: auto-submit del input file ─
    var inputReglamento = document.getElementById('input-reglamento');
    if (inputReglamento) {
        inputReglamento.addEventListener('change', function () {
            if (inputReglamento.files && inputReglamento.files[0]) {
                inputReglamento.form.submit();
            }
        });
    }
});
