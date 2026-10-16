(() => {
    function initialize() {
        const calendar = document.querySelector('#teacher-month-calendar, #student-teacher-calendar');
        if (!calendar || calendar.dataset.modalInitialized) return;
        calendar.dataset.modalInitialized = 'true';
        const controller = new AbortController();
        const mobile = window.matchMedia('(max-width: 1023px)');
        const dialog = document.createElement('dialog');
        dialog.className = 'calendar-schedule-dialog';
        dialog.setAttribute('aria-labelledby', 'calendar-schedule-title');
        dialog.innerHTML = '<header class="calendar-schedule-header"><div><p>DAILY SCHEDULE</p><h2 id="calendar-schedule-title"></h2></div><button type="button" aria-label="Close schedule" autofocus>×</button></header><div class="calendar-schedule-content"></div>';
        document.body.append(dialog);
        const title = dialog.querySelector('h2');
        const content = dialog.querySelector('.calendar-schedule-content');
        let request = null;
        let moved = [];
        let trigger = null;
        const restore = () => {
            request?.abort();
            request = null;
            moved.forEach(({ node, marker }) => marker.replaceWith(node));
            moved = [];
            content.replaceChildren();
            content.removeAttribute('aria-busy');
            document.documentElement.classList.remove('calendar-schedule-open');
        };
        const close = () => dialog.close();
        dialog.querySelector('button').addEventListener('click', close);
        dialog.addEventListener('close', () => { restore(); trigger?.focus({ preventScroll: true }); });
        dialog.addEventListener('click', event => {
            if (event.target !== dialog) return;
            const rect = dialog.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) close();
        });
        mobile.addEventListener('change', () => { if (dialog.open) close(); }, { signal: controller.signal });
        calendar.addEventListener('click', async event => {
            const link = event.target.closest('a[data-calendar-date]');
            if (!link || !mobile.matches || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            restore();
            trigger = link;
            const date = link.dataset.calendarDate;
            title.textContent = new Intl.DateTimeFormat(document.documentElement.lang || 'en', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }).format(new Date(`${date}T12:00:00`));
            document.documentElement.classList.add('calendar-schedule-open');
            dialog.showModal();
            if (calendar.id === 'student-teacher-calendar') {
                // Move rather than clone: keep booking forms, entered reasons, and IDs intact.
                document.querySelectorAll(`[data-schedule-date="${date}"]`).forEach(node => {
                    const marker = document.createComment('schedule position');
                    node.replaceWith(marker);
                    moved.push({ node, marker });
                    content.append(node);
                });
                if (!moved.length) content.textContent = 'No sessions published for this date.';
                return;
            }
            const loading = document.createElement('p');
            loading.setAttribute('role', 'status');
            loading.textContent = 'Loading schedule…';
            content.append(loading);
            content.setAttribute('aria-busy', 'true');
            const current = new AbortController();
            request = current;
            try {
                const response = await fetch(link.href, { signal: current.signal, headers: { Accept: 'text/html' } });
                if (!response.ok) throw new Error('Schedule unavailable');
                const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                const agenda = page.querySelector('#teacher-month-calendar .calendar-agenda');
                if (!agenda) throw new Error('Schedule unavailable');
                if (current.signal.aborted) return;
                const heading = agenda.querySelector('#selected-day-heading');
                heading?.remove();
                content.replaceChildren(...agenda.childNodes);
            } catch (error) {
                if (current.signal.aborted) return;
                const message = document.createElement('p');
                message.setAttribute('role', 'alert');
                message.textContent = 'Unable to load this schedule. Please try again or open the full calendar.';
                const fallback = document.createElement('a');
                fallback.href = link.href;
                fallback.textContent = 'Open full calendar →';
                content.replaceChildren(message, fallback);
            } finally {
                if (request === current) content.removeAttribute('aria-busy');
            }
        }, { signal: controller.signal });
        document.addEventListener('turbo:before-cache', () => {
            controller.abort();
            restore();
            dialog.remove();
            delete calendar.dataset.modalInitialized;
        }, { once: true, signal: controller.signal });
    }
    document.addEventListener('DOMContentLoaded', initialize);
    document.addEventListener('turbo:load', initialize);
    if (document.readyState !== 'loading') initialize();
})();
