(() => {
    function initialize() {
        const form = document.querySelector('[data-teacher-profile-form]');
        if (!form || form.dataset.initialized) return;
        form.dataset.initialized = 'true';
        const controller = new AbortController();
        const options = { signal: controller.signal };
        const file = form.elements.profile_photo;
        const preview = form.querySelector('[data-profile-photo-preview]');
        const fallback = form.querySelector('[data-profile-photo-fallback]');
        const error = form.querySelector('[data-photo-error]');
        const filename = form.querySelector('[data-photo-filename]');
        const country = form.elements.phone_country;
        const nationalNumber = form.elements.phone_number;
        const checklist = [...form.querySelectorAll('[data-completion-field]')];
        const saveBar = form.querySelector('.profile-save-bar');
        const saveState = form.querySelector('[data-profile-save-state]');
        const initialSaveState = saveState.textContent;
        const snapshot = () => JSON.stringify([...new FormData(form)].map(([key, value]) => [key, value instanceof File ? (value.name ? `${value.name}:${value.size}:${value.lastModified}` : '') : value]));
        const initialValues = snapshot();
        const sectionLinks = [...document.querySelectorAll('[data-profile-section-link]')];
        const setActiveSection = id => sectionLinks.forEach(link => {
            if (link.dataset.profileSectionLink === id) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
        });
        const sections = sectionLinks.map(link => document.getElementById(link.dataset.profileSectionLink));
        const updateActiveSection = () => {
            let active = sections[0];
            sections.forEach(section => {
                if (section.getBoundingClientRect().top <= Math.max(120, window.innerHeight * .35)) active = section;
            });
            if (window.scrollY > 0 && Math.ceil(window.scrollY + window.innerHeight) >= document.documentElement.scrollHeight - 2) active = sections[sections.length - 1];
            setActiveSection(active.id);
        };
        window.addEventListener('scroll', updateActiveSection, { ...options, passive: true });
        window.addEventListener('resize', updateActiveSection, options);
        sectionLinks.forEach(link => {
            link.addEventListener('click', () => setActiveSection(link.dataset.profileSectionLink), options);
        });
        const validInput = input => !!input?.value.trim() && input.validity.valid;
        const updatePhone = () => {
            const option = country.selectedOptions[0];
            nationalNumber.pattern = option.dataset.pattern;
            nationalNumber.placeholder = option.dataset.example;
            nationalNumber.title = `Enter the national number only, for example ${option.dataset.example}.`;
            form.querySelector('[data-phone-help]').textContent = `National number only, without +${option.dataset.code}. Example: ${option.dataset.example}.`;
        };
        let objectURL = null;
        let acceptedPhoto = null;
        const resetPreview = () => {
            if (objectURL) URL.revokeObjectURL(objectURL);
            objectURL = null;
            const original = preview.dataset.originalSrc;
            if (original) preview.src = original;
            else preview.removeAttribute('src');
            preview.hidden = !original;
            if (fallback) fallback.hidden = !!original;
        };
        const update = () => {
            let done = 0;
            checklist.forEach(item => {
                const field = item.dataset.completionField;
                const input = form.elements[field];
                let complete;
                if (field === 'profile_photo') complete = (form.dataset.existingPhoto === 'true' || file.files.length > 0) && file.checkValidity();
                else if (field === 'contact_number') complete = validInput(country) && validInput(nationalNumber);
                else if (field === 'address') complete = ['region', 'city', 'barangay'].every(key => validInput(form.elements[key]));
                else complete = validInput(input);
                item.dataset.complete = String(complete);
                item.querySelector('.profile-check-mark').textContent = complete ? '✓' : '○';
                item.querySelector('.profile-check-state').textContent = complete ? 'Done' : 'Required';
                if (complete) done++;
            });
            const total = checklist.length;
            const percent = Math.round(done / total * 100);
            form.querySelector('.profile-progress-card').hidden = done === total;
            form.querySelector('[data-profile-percentage]').textContent = `${percent}%`;
            const progress = form.querySelector('[data-profile-progress]');
            progress.setAttribute('aria-valuenow', String(percent));
            progress.dataset.complete = String(done === total);
            progress.firstElementChild.style.width = `${percent}%`;
            form.querySelector('[data-profile-completed-count]').textContent = `${done}/${total} ready`;
            form.querySelector('[data-offerings-count]').textContent = `${Array.from(form.elements.consultation_offerings.value).length.toLocaleString('en-US')} / 2,000`;
            const dirty = snapshot() !== initialValues;
            saveBar.dataset.dirty = String(dirty);
            saveState.textContent = dirty ? 'You have unsaved changes' : initialSaveState;
            form.querySelector('[data-profile-progress-caption]').textContent = done === total
                ? 'All required fields are ready. Save your profile to apply any changes.'
                : `${total - done} required ${total - done === 1 ? 'detail' : 'details'} left. Complete the checklist, then save.`;
        };
        const showSelectedPhoto = photo => {
            resetPreview();
            if (photo) {
                objectURL = URL.createObjectURL(photo);
                preview.src = objectURL;
                preview.hidden = false;
                if (fallback) fallback.hidden = true;
                filename.textContent = `${photo.name} · Cropped and ready to save`;
            } else filename.textContent = '';
        };
        const restoreAcceptedPhoto = () => {
            const selection = new DataTransfer();
            if (acceptedPhoto) selection.items.add(acceptedPhoto);
            file.files = selection.files;
            file.setCustomValidity('');
            showSelectedPhoto(acceptedPhoto);
            update();
        };
        const cropper = window.createProfilePhotoCropper(document.querySelector('[data-photo-crop-dialog]'), {
            onApply(photo) {
                acceptedPhoto = photo;
                const selection = new DataTransfer();
                selection.items.add(photo);
                file.files = selection.files;
                file.setCustomValidity('');
                error.hidden = true;
                showSelectedPhoto(photo);
                update();
            },
            onCancel() {
                error.hidden = true;
                restoreAcceptedPhoto();
            },
            onError(message) {
                restoreAcceptedPhoto();
                error.textContent = message;
                error.hidden = false;
            },
        });
        file.addEventListener('change', () => {
            error.hidden = true;
            file.setCustomValidity('');
            const photo = file.files[0];
            if (photo) {
                let message = '';
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(photo.type)) message = 'Choose a JPG, PNG, or WebP image.';
                else if (photo.size > 2 * 1024 * 1024) message = 'Your picture must be 2 MB or smaller.';
                if (message) {
                    error.textContent = message;
                    error.hidden = false;
                    file.setCustomValidity(message);
                } else {
                    file.setCustomValidity('Crop your selected picture or cancel to keep the previous one.');
                    cropper.open(photo);
                }
            } else restoreAcceptedPhoto();
            update();
        }, options);
        form.addEventListener('input', update, options);
        form.addEventListener('change', update, options);
        country.addEventListener('change', () => { updatePhone(); update(); }, options);
        document.addEventListener('turbo:before-cache', () => {
            controller.abort();
            cropper.destroy();
            resetPreview();
            file.value = '';
            file.setCustomValidity('');
            delete form.dataset.initialized;
        }, { once: true, signal: controller.signal });
        updatePhone();
        update();
    }
    document.addEventListener('DOMContentLoaded', initialize);
    document.addEventListener('turbo:load', initialize);
    if (document.readyState !== 'loading') initialize();
})();
