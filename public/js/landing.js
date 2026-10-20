(() => {
    if (window.consultationLandingInitialized) return;
    window.consultationLandingInitialized = true;
    function closeMenu() {
        const button = document.querySelector('[data-landing-menu-button]');
        const menu = document.getElementById('landing-mobile-menu');
        if (!button || !menu) return;
        menu.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-label', 'Open navigation menu');
    }
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-landing-menu-button]');
        if (button) {
            const menu = document.getElementById('landing-mobile-menu');
            if (!menu) return;
            menu.hidden = !menu.hidden;
            button.setAttribute('aria-expanded', String(!menu.hidden));
            button.setAttribute('aria-label', menu.hidden ? 'Open navigation menu' : 'Close navigation menu');
        } else if (event.target.closest('#landing-mobile-menu a')) closeMenu();
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || document.getElementById('landing-mobile-menu')?.hidden !== false) return;
        closeMenu();
        document.querySelector('[data-landing-menu-button]')?.focus();
    });
    document.addEventListener('turbo:before-cache', closeMenu);
})();
