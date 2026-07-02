document.addEventListener('DOMContentLoaded', function () {

    // Confirmación antes de cambiar estado
    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
        btn.closest('form').addEventListener('submit', function (e) {
            if (!window.confirm(btn.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // Auto-dismiss flash messages después de 4 s
    const flash = document.getElementById('flash-msg');
    if (flash) {
        setTimeout(function () {
            flash.style.transition = 'opacity 0.5s ease, margin-bottom 0.5s ease, padding 0.5s ease, max-height 0.5s ease';
            flash.style.opacity    = '0';
            flash.style.maxHeight  = '0';
            flash.style.padding    = '0';
            flash.style.marginBottom = '0';
            setTimeout(function () { flash.remove(); }, 520);
        }, 4000);
    }

});
