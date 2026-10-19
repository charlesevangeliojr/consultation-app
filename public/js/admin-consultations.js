(() => {
    if (window.consultationRecordsInitialized) return;
    window.consultationRecordsInitialized = true;
    const unlock = () => document.documentElement.classList.remove('booking-detail-open');
    document.addEventListener('click', event => {
        const row = event.target.closest('[data-booking-open], [data-record-open]');
        if (row) {
            const dialog = document.getElementById(row.dataset.bookingOpen || row.dataset.recordOpen);
            if (!dialog || dialog.open) return;
            row.querySelector('button').focus();
            dialog.showModal();
            document.documentElement.classList.add('booking-detail-open');
        }
        if (event.target.closest('[data-booking-close], [data-record-close]')) event.target.closest('[data-booking-dialog], [data-record-dialog]').close();
        if (event.target.matches('[data-booking-dialog], [data-record-dialog]')) {
            const bounds = event.target.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) event.target.close();
        }
    });
    document.addEventListener('close', event => { if (event.target.matches('[data-booking-dialog], [data-record-dialog]')) unlock(); }, true);
    document.addEventListener('turbo:before-cache', () => {
        document.querySelectorAll('[data-booking-dialog][open], [data-record-dialog][open], [data-account-delete-dialog][open]').forEach(dialog => dialog.close());
        unlock();
    });
})();
