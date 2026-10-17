(() => {
    const minutes = value => /^\d{2}:\d{2}$/.test(value) ? Number(value.slice(0, 2)) * 60 + Number(value.slice(3, 5)) : NaN;
    const time = value => `${String(Math.floor(value / 60)).padStart(2, '0')}:${String(value % 60).padStart(2, '0')}`;
    function update(form) {
        const start = form.elements.starts_at;
        const end = form.elements.ends_at;
        const from = minutes(start.value);
        const until = minutes(end.value);
        const maximum = Number(form.dataset.bookingMaximum);
        const caption = form.querySelector('[data-booking-duration]');
        if (!end.dataset.windowEnd) end.dataset.windowEnd = end.max;
        start.setCustomValidity('');
        end.setCustomValidity('');
        end.max = Number.isFinite(from) ? time(Math.min(from + maximum, minutes(end.dataset.windowEnd))) : end.dataset.windowEnd;
        let message = '';
        if (Number.isFinite(from) && Number.isFinite(until)) {
            if (until <= from) message = 'End time must be after start time.';
            else if (until - from > maximum) message = `Choose no more than ${maximum} minutes for this teacher.`;
            else if (from < minutes(start.min) || from > minutes(start.max) || until > minutes(end.dataset.windowEnd)) message = 'Choose a time within the published availability.';
            else {
                const busy = JSON.parse(form.dataset.bookingBusy || '[]');
                if (busy.some(range => from < minutes(range.end) && until > minutes(range.start))) message = 'This range overlaps a reserved time or another consultation you have. Choose an available range.';
            }
            if (message) end.setCustomValidity(message);
        }
        caption.textContent = message || (until > from ? `${until - from} minute consultation · Maximum ${maximum} minutes` : `Choose a start and end time. Maximum ${maximum} minutes.`);
        caption.dataset.invalid = String(!!message);
    }
    const initialize = () => document.querySelectorAll('[data-booking-range]').forEach(update);
    // Delegation also covers session forms moved into the mobile schedule dialog.
    document.addEventListener('input', event => {
        const form = event.target.closest('[data-booking-range]');
        if (form) update(form);
    });
    document.addEventListener('change', event => {
        const form = event.target.closest('[data-booking-range]');
        if (form) update(form);
    });
    document.addEventListener('DOMContentLoaded', initialize);
    document.addEventListener('turbo:load', initialize);
    if (document.readyState !== 'loading') initialize();
})();
