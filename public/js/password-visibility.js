(() => {
    function hide(button) {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        if (!input) return;
        input.type = 'password';
        button.setAttribute('aria-label', 'Show password');
        button.setAttribute('aria-pressed', 'false');
        button.querySelector('[data-password-slash]')?.classList.add('hidden');
    }

    function initialize() {
        document.querySelectorAll('[data-login-password-toggle]').forEach(button => {
            if (button.dataset.initialized) return;
            const input = document.getElementById(button.getAttribute('aria-controls'));
            if (!input) return;
            button.dataset.initialized = 'true';
            hide(button);
            button.addEventListener('click', () => {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                button.setAttribute('aria-pressed', String(show));
                button.querySelector('[data-password-slash]')?.classList.toggle('hidden', !show);
            });
            input.form?.addEventListener('submit', () => hide(button));
        });
    }

    document.addEventListener('DOMContentLoaded', initialize);
    document.addEventListener('turbo:load', initialize);
    document.addEventListener('turbo:before-cache', () => {
        document.querySelectorAll('[data-login-password-toggle]').forEach(button => {
            hide(button);
            const input = document.getElementById(button.getAttribute('aria-controls'));
            if (input) input.value = '';
            delete button.dataset.initialized;
        });
    });
    if (document.readyState !== 'loading') initialize();
})();
