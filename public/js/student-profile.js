(() => {
    function initialize() {
        const form = document.querySelector('[data-student-profile-form]');
        if (!form || form.dataset.initialized) return;
        form.dataset.initialized = 'true';
        const controller = new AbortController();
        const options = { signal: controller.signal };
        const file = form.elements.profile_photo;
        const preview = form.querySelector('[data-student-photo-preview]');
        const fallback = form.querySelector('[data-student-photo-fallback]');
        const error = form.querySelector('[data-student-photo-error]');
        const filename = form.querySelector('[data-student-photo-filename]');
        const saveBar = form.querySelector('.profile-save-bar');
        const snapshot = () => JSON.stringify([...new FormData(form)].map(([key, value]) => [key, value instanceof File ? (value.name ? `${value.name}:${value.size}:${value.lastModified}` : '') : value]));
        const initial = snapshot();
        let acceptedPhoto = null;
        let objectURL = null;
        const update = () => {
            const addressStarted = form.dataset.hasStructuredAddress === 'true' || ['house_number', 'region', 'province', 'city', 'barangay', 'postal_code'].some(key => form.elements[key].value.trim());
            ['region', 'city', 'barangay'].forEach(key => { form.elements[key].required = addressStarted; });
            const dirty = snapshot() !== initial;
            saveBar.dataset.dirty = String(dirty);
            form.querySelector('[data-student-save-state]').textContent = dirty ? 'You have unsaved changes' : 'Your profile is up to date';
        };
        const resetPreview = () => {
            if (objectURL) URL.revokeObjectURL(objectURL);
            objectURL = null;
            const original = preview.dataset.originalSrc;
            if (original) preview.src = original;
            else preview.removeAttribute('src');
            preview.hidden = !original;
            fallback.hidden = !!original;
        };
        const setPhoto = photo => {
            const transfer = new DataTransfer();
            if (photo) transfer.items.add(photo);
            file.files = transfer.files;
            file.setCustomValidity('');
            resetPreview();
            if (photo) {
                objectURL = URL.createObjectURL(photo);
                preview.src = objectURL;
                preview.hidden = false;
                fallback.hidden = true;
            }
            filename.textContent = photo ? 'Cropped picture ready to save' : '';
            update();
        };
        const cropper = window.createProfilePhotoCropper(document.querySelector('[data-photo-crop-dialog]'), {
            onApply(photo) { acceptedPhoto = photo; setPhoto(photo); },
            onCancel() { setPhoto(acceptedPhoto); },
            onError(message) { setPhoto(acceptedPhoto); error.textContent = message; error.hidden = false; },
        });
        file.addEventListener('change', () => {
            error.hidden = true;
            const photo = file.files[0];
            if (!photo) return setPhoto(acceptedPhoto);
            let message = '';
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(photo.type)) message = 'Choose a JPG, PNG, or WebP image.';
            else if (photo.size > 2 * 1024 * 1024) message = 'Your picture must be 2 MB or smaller.';
            if (message) {
                setPhoto(acceptedPhoto);
                error.textContent = message;
                error.hidden = false;
            } else {
                file.setCustomValidity('Crop your selected picture or cancel to keep the previous one.');
                cropper.open(photo);
            }
        }, options);
        form.addEventListener('input', update, options);
        form.addEventListener('change', update, options);
        document.addEventListener('turbo:before-cache', () => {
            controller.abort();
            cropper.destroy();
            resetPreview();
            file.value = '';
            file.setCustomValidity('');
            delete form.dataset.initialized;
        }, { once: true, signal: controller.signal });
        update();
    }
    document.addEventListener('DOMContentLoaded', initialize);
    document.addEventListener('turbo:load', initialize);
    if (document.readyState !== 'loading') initialize();
})();
