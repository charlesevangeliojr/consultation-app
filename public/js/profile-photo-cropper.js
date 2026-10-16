(() => {
    window.createProfilePhotoCropper = (dialog, callbacks) => {
        const controller = new AbortController();
        const options = { signal: controller.signal };
        const canvas = dialog.querySelector('[data-crop-canvas]');
        const context = canvas.getContext('2d');
        const zoom = dialog.querySelector('[data-crop-zoom]');
        const output = dialog.querySelector('[data-crop-zoom-value]');
        const apply = dialog.querySelector('[data-crop-apply]');
        const status = dialog.querySelector('[data-crop-status]');
        const size = canvas.width;
        let image = null;
        let sourceURL = null;
        let version = 0;
        let destroyed = false;
        let drag = null;
        let x = 0;
        let y = 0;
        const release = () => {
            if (sourceURL) URL.revokeObjectURL(sourceURL);
            sourceURL = null;
            image = null;
            drag = null;
        };
        const draw = () => {
            if (!image?.naturalWidth) return;
            const scale = Math.max(size / image.naturalWidth, size / image.naturalHeight) * Number(zoom.value);
            const width = image.naturalWidth * scale;
            const height = image.naturalHeight * scale;
            x = Math.max(-(width - size) / 2, Math.min((width - size) / 2, x));
            y = Math.max(-(height - size) / 2, Math.min((height - size) / 2, y));
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, size, size);
            context.drawImage(image, (size - width) / 2 + x, (size - height) / 2 + y, width, height);
            output.value = `${Math.round(Number(zoom.value) * 100)}%`;
        };
        const cancel = () => { if (dialog.open) dialog.close('cancel'); };
        dialog.querySelectorAll('[data-crop-cancel]').forEach(button => button.addEventListener('click', cancel, options));
        dialog.addEventListener('cancel', event => { event.preventDefault(); cancel(); }, options);
        dialog.addEventListener('close', () => {
            version++;
            release();
            dialog.removeAttribute('aria-busy');
            if (!destroyed && dialog.returnValue === 'cancel') callbacks.onCancel();
        }, options);
        dialog.addEventListener('click', event => {
            if (event.target !== dialog) return;
            const rect = dialog.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) cancel();
        }, options);
        zoom.addEventListener('input', draw, options);
        dialog.querySelector('[data-crop-reset]').addEventListener('click', () => {
            x = y = 0; zoom.value = '1'; draw();
        }, options);
        canvas.addEventListener('pointerdown', event => {
            if (!image?.naturalWidth || (event.pointerType === 'mouse' && event.button !== 0)) return;
            canvas.setPointerCapture(event.pointerId);
            drag = { id: event.pointerId, x: event.clientX, y: event.clientY };
        }, options);
        canvas.addEventListener('pointermove', event => {
            if (!drag || drag.id !== event.pointerId) return;
            const scale = size / canvas.getBoundingClientRect().width;
            x += (event.clientX - drag.x) * scale;
            y += (event.clientY - drag.y) * scale;
            drag.x = event.clientX; drag.y = event.clientY;
            draw();
        }, options);
        const stopDrag = () => { drag = null; };
        canvas.addEventListener('pointerup', stopDrag, options);
        canvas.addEventListener('pointercancel', stopDrag, options);
        canvas.addEventListener('lostpointercapture', stopDrag, options);
        canvas.addEventListener('keydown', event => {
            const arrows = { ArrowLeft: [-10, 0], ArrowRight: [10, 0], ArrowUp: [0, -10], ArrowDown: [0, 10] };
            if (!arrows[event.key]) return;
            event.preventDefault();
            x += arrows[event.key][0]; y += arrows[event.key][1]; draw();
        }, options);
        apply.addEventListener('click', () => {
            if (!image?.naturalWidth || apply.disabled) return;
            const current = version;
            apply.disabled = true;
            dialog.setAttribute('aria-busy', 'true');
            status.textContent = 'Preparing your cropped picture…';
            canvas.toBlob(blob => {
                if (destroyed || current !== version || !dialog.open) return;
                if (!blob || blob.size > 2 * 1024 * 1024) {
                    dialog.close('error');
                    callbacks.onError('The picture could not be cropped. Please choose another photo.');
                    return;
                }
                callbacks.onApply(new File([blob], 'profile-photo-cropped.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
                dialog.close('apply');
            }, 'image/jpeg', 0.9);
        }, options);
        return {
            open(photo) {
                release();
                const current = ++version;
                x = y = 0; zoom.value = '1'; output.value = '100%';
                apply.disabled = true;
                context.clearRect(0, 0, size, size);
                status.textContent = 'Loading picture…';
                image = new Image();
                image.onload = () => {
                    if (destroyed || current !== version) return;
                    draw(); apply.disabled = false;
                    status.textContent = 'Drag the picture or use arrow keys to reposition.';
                    canvas.focus();
                };
                image.onerror = () => {
                    if (destroyed || current !== version) return;
                    dialog.close('error');
                    callbacks.onError('This image could not be opened. Please choose a valid JPG, PNG, or WebP photo.');
                };
                sourceURL = URL.createObjectURL(photo);
                image.src = sourceURL;
                dialog.showModal();
            },
            destroy() {
                destroyed = true; version++;
                controller.abort();
                if (dialog.open) dialog.close('cleanup');
                release();
            },
        };
    };
})();
