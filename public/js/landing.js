(() => {
    if (window.consultationLandingInitialized) return;
    window.consultationLandingInitialized = true;
    function selectRole(tab) {
        const switcher = tab.closest('[data-landing-role-switcher]');
        if (!switcher) return;
        switcher.querySelectorAll('[data-landing-role]').forEach(button => {
            const active = button === tab;
            button.setAttribute('aria-selected', String(active));
            button.tabIndex = active ? 0 : -1;
        });
        switcher.querySelectorAll('[data-landing-role-panel]').forEach(panel => {
            panel.hidden = panel.dataset.landingRolePanel !== tab.dataset.landingRole;
        });
    }
    function closeMenu() {
        const button = document.querySelector('[data-landing-menu-button]');
        const menu = document.getElementById('landing-mobile-menu');
        if (!button || !menu) return;
        menu.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-label', 'Open navigation menu');
    }
    document.addEventListener('click', event => {
        const roleTab = event.target.closest('[data-landing-role]');
        if (roleTab) selectRole(roleTab);
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
        const roleTab = event.target.closest('[data-landing-role]');
        if (roleTab && ['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
            event.preventDefault();
            const tabs = [...roleTab.closest('[role="tablist"]').querySelectorAll('[data-landing-role]')];
            const next = event.key === 'Home' ? tabs[0] : event.key === 'End' ? tabs[tabs.length - 1]
                : tabs[(tabs.indexOf(roleTab) + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length];
            selectRole(next);
            next.focus();
            return;
        }
        if (event.key !== 'Escape' || document.getElementById('landing-mobile-menu')?.hidden !== false) return;
        closeMenu();
        document.querySelector('[data-landing-menu-button]')?.focus();
    });
    document.addEventListener('turbo:before-cache', closeMenu);
})();
