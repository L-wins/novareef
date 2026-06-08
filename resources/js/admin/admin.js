// NovaReef — Panel Admin JS

document.addEventListener('DOMContentLoaded', function () {

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
