// NovaReef — Panel Admin JS

import { initAutoFilter } from '../shared/auto-filter.js';
import { initTheme } from '../shared/theme.js';
import { initReloj } from '../shared/reloj.js';
import { initNovaSelects } from '../shared/nova-selects.js';
import { Swal, novaAlert, initConfirmSubmit } from '../shared/nova-alert.js';

// Globales (mismos puentes que el panel usuario)
window.Swal            = Swal;
window.novaAlert       = novaAlert;
window.initNovaSelects = initNovaSelects;

document.addEventListener('DOMContentLoaded', function () {

    initAutoFilter();
    initTheme();
    initReloj();
    initNovaSelects();
    initConfirmSubmit();

    // Sombra en el navbar al hacer scroll — mismo comportamiento que app.js
    var nav = document.getElementById('navbar');
    if (nav) {
        window.addEventListener('scroll', function () {
            nav.classList.toggle('scrolled', window.scrollY > 10);
        }, { passive: true });
    }

    //  Dropdowns simples (ej. cambiar estado en colegios/show)
    document.querySelectorAll('[data-dropdown]').forEach(function (dd) {
        var toggle = dd.querySelector('[data-dropdown-toggle]');
        var menu   = dd.querySelector('.admin-dropdown__menu');
        if (!toggle || !menu) return;

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            menu.classList.toggle('hidden');
        });
    });
    document.addEventListener('click', function () {
        document.querySelectorAll('.admin-dropdown__menu:not(.hidden)').forEach(function (m) {
            m.classList.add('hidden');
        });
    });

    //  Selección de plan (colegios/create)
    var planCards = document.querySelectorAll('.plan-card');
    planCards.forEach(function (card) {
        card.addEventListener('click', function () {
            planCards.forEach(function (c) { c.classList.remove('plan-card--selected'); });
            card.classList.add('plan-card--selected');
            var radio = card.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });

    //  Prueba gratuita (colegios/create): oculta la grilla de planes — no
    //  hay plan comercial que elegir mientras el colegio esté en trial.
    var trialToggle = document.querySelector('[data-trial-toggle]');
    var planSelector = document.querySelector('[data-plan-selector]');
    var trialSummary = document.querySelector('[data-trial-summary]');
    if (trialToggle && planSelector) {
        var syncTrialUI = function () {
            var enTrial = trialToggle.checked;
            planSelector.hidden = enTrial;
            if (trialSummary) trialSummary.hidden = !enTrial;
            planSelector.querySelectorAll('input[type="radio"]').forEach(function (radio) {
                radio.disabled = enTrial;
            });
        };
        trialToggle.addEventListener('change', syncTrialUI);
        syncTrialUI();
    }

    //  OTP Inputs
    const digits  = document.querySelectorAll('.otp-digit');
    const hidden  = document.getElementById('otp-code');

    if (digits.length === 6 && hidden) {

        function sync() {
            hidden.value = Array.from(digits).map(d => d.value).join('');
        }

        function tryAutoSubmit() {
            if (hidden.value.length === 6) {
                hidden.closest('form').submit();
            }
        }

        digits.forEach(function (digit, i) {

            digit.addEventListener('input', function () {
                // Strip non-digits, keep last char only
                this.value = this.value.replace(/\D/g, '').slice(-1);
                this.classList.toggle('filled', this.value !== '');
                sync();

                if (this.value && i < 5) {
                    digits[i + 1].focus();
                }
                if (i === 5 && this.value) {
                    tryAutoSubmit();
                }
            });

            digit.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !this.value && i > 0) {
                    digits[i - 1].value = '';
                    digits[i - 1].classList.remove('filled');
                    digits[i - 1].focus();
                    sync();
                    e.preventDefault();
                }
                // Allow only digits + control keys
                const allowed = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Enter'];
                if (!allowed.includes(e.key) && !/^\d$/.test(e.key)) {
                    e.preventDefault();
                }
            });

            digit.addEventListener('paste', function (e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData)
                    .getData('text')
                    .replace(/\D/g, '')
                    .slice(0, 6);

                digits.forEach(function (d, j) {
                    d.value = text[j] || '';
                    d.classList.toggle('filled', d.value !== '');
                });
                sync();

                const nextFocus = Math.min(text.length, 5);
                digits[nextFocus].focus();

                if (text.length === 6) {
                    setTimeout(tryAutoSubmit, 80);
                }
            });

            digit.addEventListener('focus', function () {
                this.select();
            });
        });

        // Pre-focus first digit
        digits[0].focus();

        // Mark error state if validation failed
        if (document.querySelector('.otp-row.otp-has-error')) {
            digits.forEach(function (d) { d.classList.add('otp-error'); });
        }
    }

});
