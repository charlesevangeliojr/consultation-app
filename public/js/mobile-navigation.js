(() => {
    function initializeNavigation(navigation) {
        if (navigation.dataset.initialized) return;
        const links = [...navigation.querySelectorAll('[data-navigation-section]')];
        if (!links.length) return;
        navigation.dataset.initialized = 'true';
        const controller = new AbortController();
        let frame = null;
        const update = () => {
            frame = null;
            let active = links[0];
            let nearestTop = -Infinity;
            // Use a small top offset: a tall desktop viewport may show several
            // short sections at once, but the section navigated to stays active.
            const activationTop = Math.min(120, window.innerHeight * 0.2);
            links.forEach(link => {
                const section = document.getElementById(link.dataset.navigationSection);
                const top = section?.getBoundingClientRect().top;
                if (top !== undefined && top <= activationTop && top > nearestTop) {
                    active = link;
                    nearestTop = top;
                }
            });
            links.forEach(link => {
                if (link === active) link.setAttribute('aria-current', 'location');
                else link.removeAttribute('aria-current');
            });
        };
        const schedule = () => {
            if (frame === null) frame = requestAnimationFrame(update);
        };
        window.addEventListener('scroll', schedule, { passive: true, signal: controller.signal });
        window.addEventListener('resize', schedule, { signal: controller.signal });
        document.addEventListener('turbo:before-cache', () => {
            controller.abort();
            if (frame !== null) cancelAnimationFrame(frame);
            delete navigation.dataset.initialized;
        }, { once: true, signal: controller.signal });
        update();
    }
    function initialize() {
        document.querySelectorAll('[data-mobile-navigation], [data-section-navigation]').forEach(initializeNavigation);
    }
    document.addEventListener('DOMContentLoaded', initialize);
    document.addEventListener('turbo:load', initialize);
    if (document.readyState !== 'loading') initialize();
})();
