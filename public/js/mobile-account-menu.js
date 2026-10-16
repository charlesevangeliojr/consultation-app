(() => {
    const selector = '[data-mobile-account-menu]';
    const closeMenus = (restoreFocus = false) => {
        document.querySelectorAll(`${selector}[open]`).forEach(menu => {
            menu.open = false;
            if (restoreFocus) menu.querySelector('summary').focus();
        });
    };
    document.addEventListener('click', event => {
        document.querySelectorAll(`${selector}[open]`).forEach(menu => {
            if (!menu.contains(event.target)) menu.open = false;
        });
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && document.querySelector(`${selector}[open]`)) {
            event.preventDefault();
            closeMenus(true);
        }
    });
    document.addEventListener('focusin', event => {
        document.querySelectorAll(`${selector}[open]`).forEach(menu => {
            if (!menu.contains(event.target)) menu.open = false;
        });
    });
    window.matchMedia('(min-width: 1024px)').addEventListener('change', () => closeMenus());
    document.addEventListener('turbo:before-cache', () => closeMenus());
})();
