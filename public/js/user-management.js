(() => {
    function initialize() {
        document.querySelectorAll('[data-open-delete]:not([data-delete-initialized])').forEach(button => {
            button.dataset.deleteInitialized = 'true';
            button.addEventListener('click', () => {
                document.getElementById(button.dataset.openDelete)?.showModal();
            });
        });
        document.querySelectorAll('[data-close-delete]:not([data-delete-initialized])').forEach(button => {
            button.dataset.deleteInitialized = 'true';
            button.addEventListener('click', () => button.closest('dialog')?.close());
        });

        document.querySelectorAll('[data-account-form]').forEach(form => {
            if (form.dataset.initialized) return;
            form.dataset.initialized = 'true';
            const permissions = [...form.querySelectorAll('[data-permission]:not(:disabled)')];
            const all = form.querySelector('[data-permission-all]');
            const columns = [...form.querySelectorAll('[data-permission-column]')];
            const rows = [...form.querySelectorAll('[data-permission-row]')];
            const roleSelect = form.querySelector('[data-account-role]');
            function syncBox(box, group) {
                if (box.disabled) return;
                box.disabled = group.length === 0;
                box.checked = group.length > 0 && group.every(input => input.checked);
                box.indeterminate = group.some(input => input.checked) && !box.checked;
            }
            function sync() {
                syncBox(all, permissions);
                columns.forEach(box => syncBox(box, permissions.filter(input => input.dataset.action === box.dataset.permissionColumn)));
                rows.forEach(box => syncBox(box, permissions.filter(input => input.dataset.module === box.dataset.permissionRow)));
            }
            form.addEventListener('change', event => {
                const box = event.target;
                if (box.matches('[data-account-role]')) {
                    const selected = box.selectedOptions[0];
                    if (selected?.dataset.permissions) {
                        const defaults = JSON.parse(selected.dataset.permissions);
                        permissions.forEach(input => {
                            input.checked = Boolean(defaults[input.dataset.module]?.[input.dataset.action]);
                        });
                        sync();
                    }
                    return;
                }
                let group = [];
                if (box.matches('[data-permission-all]')) group = permissions;
                if (box.matches('[data-permission-column]')) group = permissions.filter(input => input.dataset.action === box.dataset.permissionColumn);
                if (box.matches('[data-permission-row]')) group = permissions.filter(input => input.dataset.module === box.dataset.permissionRow);
                group.forEach(input => { input.checked = box.checked; });
                sync();
            });
            form.querySelector('[data-permission-clear]')?.addEventListener('click', () => {
                permissions.forEach(input => { input.checked = false; });
                sync();
            });
            form.querySelectorAll('[data-password-toggle]').forEach(button => {
                button.addEventListener('click', () => {
                    const input = form.querySelector(`#${button.dataset.passwordToggle}`);
                    const visible = input.type === 'password';
                    input.type = visible ? 'text' : 'password';
                    button.setAttribute('aria-pressed', String(visible));
                    button.setAttribute('aria-label', `${visible ? 'Hide' : 'Show'} ${input.id === 'password' ? 'password' : 'confirm password'}`);
                });
            });
            sync();
        });
    }
    document.addEventListener('DOMContentLoaded', initialize);
    document.addEventListener('turbo:load', initialize);
    document.addEventListener('turbo:before-cache', () => {
        document.querySelectorAll('[data-account-form]').forEach(form => {
            delete form.dataset.initialized;
            form.querySelectorAll('[data-password-toggle]').forEach(button => {
                const input = form.querySelector(`#${button.dataset.passwordToggle}`);
                input.type = 'password';
                input.value = '';
                button.setAttribute('aria-pressed', 'false');
                button.setAttribute('aria-label', `Show ${input.id === 'password' ? 'password' : 'confirm password'}`);
            });
        });
    });
    if (document.readyState !== 'loading') initialize();
})();
