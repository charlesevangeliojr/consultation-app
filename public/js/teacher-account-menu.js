(() => {
    // Native details/summary supplies keyboard activation and expanded state.
    // Delegated listeners survive Turbo navigation without duplicate handlers.
    document.addEventListener('click', event => {
        document.querySelectorAll('[data-teacher-account-menu][open], [data-student-account-menu][open]').forEach(menu => {
            if (!menu.contains(event.target) || event.target.closest('a')) menu.open = false;
        });
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('[data-teacher-account-menu][open], [data-student-account-menu][open]').forEach(menu => {
            menu.open = false;
            menu.querySelector('summary').focus();
        });
    });
    document.addEventListener('turbo:before-cache', () => {
        document.querySelectorAll('[data-teacher-account-menu], [data-student-account-menu]').forEach(menu => { menu.open = false; });
    });
})();
