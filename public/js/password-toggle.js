// Password visibility toggle functionality
(function () {
    'use strict';

    // Initialize password toggle buttons
    function initPasswordToggle() {
        const toggleButtons = document.querySelectorAll('[data-password-toggle]');
        
        toggleButtons.forEach(function (button) {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = button.getAttribute('data-password-toggle');
                const input = document.getElementById(targetId);
                const icon = button.querySelector('i');
                
                if (!input) return;
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                    button.setAttribute('aria-label', 'Hide password');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                    button.setAttribute('aria-label', 'Show password');
                }
            });
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPasswordToggle);
    } else {
        initPasswordToggle();
    }
})();
