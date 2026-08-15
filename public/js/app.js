/* Small bits of UI behaviour. No framework, no jQuery. */
(function () {
    'use strict';

    /* Mobile navigation ------------------------------------------------- */
    var sidebar = document.getElementById('sidebar');
    var toggle = document.querySelector('[data-nav-toggle]');
    var scrim = null;

    function closeNav() {
        if (!sidebar) return;
        sidebar.classList.remove('is-open');
        if (scrim) {
            scrim.remove();
            scrim = null;
        }
    }

    function openNav() {
        if (!sidebar) return;
        sidebar.classList.add('is-open');
        scrim = document.createElement('div');
        scrim.className = 'scrim';
        scrim.addEventListener('click', closeNav);
        document.body.appendChild(scrim);
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            sidebar.classList.contains('is-open') ? closeNav() : openNav();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeNav();
    });

    /* Dismissible flash messages ---------------------------------------- */
    document.querySelectorAll('[data-flash-close]').forEach(function (button) {
        button.addEventListener('click', function () {
            var flash = button.closest('.flash');
            if (flash) flash.remove();
        });
    });

    /* Confirm before destructive submits --------------------------------
       Opt in with data-confirm="Question?" on the form.                   */
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
})();
