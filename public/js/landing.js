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
    let manualDevice = window.innerWidth < 1024 ? 'mobile' : 'desktop';
    let lightboxData = null;

    function selectManualRole(tab) {
        const manual = tab.closest('[data-landing-manual]');
        if (!manual) return;
        manual.querySelectorAll('[data-manual-role]').forEach(button => {
            const active = button === tab;
            button.setAttribute('aria-selected', String(active));
            button.tabIndex = active ? 0 : -1;
        });
        manual.querySelectorAll('[data-manual-panel]').forEach(panel => {
            panel.hidden = panel.dataset.manualPanel !== tab.dataset.manualRole;
        });
    }

    function applyManualDevice(mode) {
        manualDevice = mode === 'mobile' ? 'mobile' : 'desktop';
        const manual = document.querySelector('[data-landing-manual]');
        if (!manual) return;
        manual.querySelectorAll('[data-manual-device]').forEach(button => {
            button.setAttribute('aria-pressed', String(button.dataset.manualDevice === manualDevice));
        });
        manual.querySelectorAll('[data-manual-img]').forEach(img => {
            const src = manualDevice === 'mobile' ? img.dataset.mobileSrc : img.dataset.desktopSrc;
            if (src) img.src = src;
            img.dataset.mode = manualDevice;
        });
        manual.querySelectorAll('[data-manual-mode-label]').forEach(label => {
            label.textContent = manualDevice === 'mobile' ? 'Mobile screen' : 'Desktop screen';
        });
    }

    function renderLightbox(mode) {
        const dialog = document.querySelector('[data-manual-lightbox]');
        if (!dialog || !lightboxData) return;
        const device = mode === 'mobile' ? 'mobile' : 'desktop';
        const img = dialog.querySelector('[data-manual-lightbox-img]');
        dialog.querySelectorAll('[data-manual-lightbox-device]').forEach(button => {
            button.setAttribute('aria-pressed', String(button.dataset.manualLightboxDevice === device));
        });
        img.src = device === 'mobile' ? lightboxData.mobile : lightboxData.desktop;
        img.dataset.mode = device;
        img.alt = `${lightboxData.title} (${device} screenshot)`;
    }

    function openLightbox(trigger) {
        const dialog = document.querySelector('[data-manual-lightbox]');
        if (!dialog) return;
        lightboxData = {
            title: trigger.dataset.stepTitle || '',
            text: trigger.dataset.stepText || '',
            desktop: trigger.dataset.desktopSrc || '',
            mobile: trigger.dataset.mobileSrc || '',
        };
        dialog.querySelector('[data-manual-lightbox-title]').textContent = lightboxData.title;
        dialog.querySelector('[data-manual-lightbox-text]').textContent = lightboxData.text;
        renderLightbox(manualDevice);
        dialog.showModal();
    }

    document.addEventListener('click', event => {
        const roleTab = event.target.closest('[data-landing-role]');
        if (roleTab) selectRole(roleTab);
        const manualTab = event.target.closest('[data-manual-role]');
        if (manualTab) selectManualRole(manualTab);
        const manualDeviceBtn = event.target.closest('[data-manual-device]');
        if (manualDeviceBtn) applyManualDevice(manualDeviceBtn.dataset.manualDevice);
        const zoomBtn = event.target.closest('[data-manual-zoom]');
        if (zoomBtn) openLightbox(zoomBtn);
        const lightboxDeviceBtn = event.target.closest('[data-manual-lightbox-device]');
        if (lightboxDeviceBtn) renderLightbox(lightboxDeviceBtn.dataset.manualLightboxDevice);
        const lightboxClose = event.target.closest('[data-manual-lightbox-close]');
        if (lightboxClose) document.querySelector('[data-manual-lightbox]')?.close();
        const lightboxDialog = event.target.matches('[data-manual-lightbox]') ? event.target : null;
        if (lightboxDialog) {
            const bounds = lightboxDialog.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) {
                lightboxDialog.close();
            }
        }
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
        const roleTab = event.target.closest('[data-landing-role], [data-manual-role]');
        if (roleTab && ['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
            event.preventDefault();
            const selector = roleTab.hasAttribute('data-manual-role') ? '[data-manual-role]' : '[data-landing-role]';
            const tabs = [...roleTab.closest('[role="tablist"]').querySelectorAll(selector)];
            const next = event.key === 'Home' ? tabs[0] : event.key === 'End' ? tabs[tabs.length - 1]
                : tabs[(tabs.indexOf(roleTab) + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length];
            if (roleTab.hasAttribute('data-manual-role')) selectManualRole(next);
            else selectRole(next);
            next.focus();
            return;
        }
        if (event.key !== 'Escape' || document.getElementById('landing-mobile-menu')?.hidden !== false) return;
        closeMenu();
        document.querySelector('[data-landing-menu-button]')?.focus();
    });
    document.addEventListener('DOMContentLoaded', () => applyManualDevice(window.innerWidth < 1024 ? 'mobile' : 'desktop'));
    document.addEventListener('turbo:load', () => applyManualDevice(window.innerWidth < 1024 ? 'mobile' : 'desktop'));
    document.addEventListener('turbo:before-cache', () => {
        closeMenu();
        document.querySelector('[data-manual-lightbox]')?.close();
    });
    if (document.readyState !== 'loading') applyManualDevice(manualDevice);
})();
