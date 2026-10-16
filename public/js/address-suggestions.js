(() => {
    const cache = new Map();
    function initialize() {
        document.querySelectorAll('[data-address-suggestions]').forEach(container => {
            if (container.dataset.initialized) return;
            container.dataset.initialized = 'true';
            const keys = ['region', 'province', 'city', 'barangay'];
            const inputs = Object.fromEntries(keys.map(key => [key, container.querySelector(`[name="${key}"]`)]));
            const lists = Object.fromEntries(keys.map(key => [key, container.querySelector(`#${key}-options`)]));
            const places = Object.fromEntries(keys.map(key => [key, []]));
            const help = container.querySelector('#location-help');
            const normalHelp = help.textContent;
            const controller = new AbortController();
            let generation = 0;
            let active = true;
            const match = key => places[key].find(place => place.name.toLocaleLowerCase() === inputs[key].value.trim().toLocaleLowerCase());
            const fill = (key, values) => {
                places[key] = [...values].sort((a, b) => a.name.localeCompare(b.name));
                lists[key].replaceChildren(...places[key].map(place => new Option(place.name)));
            };
            async function get(path) {
                if (!cache.has(path)) {
                    cache.set(path, fetch(`https://psgc.gitlab.io/api/${path}`).then(response => {
                        if (!response.ok) throw new Error('Location service unavailable');
                        return response.json();
                    }).catch(error => { cache.delete(path); throw error; }));
                }
                const result = await cache.get(path);
                if (!Array.isArray(result)) throw new Error('Invalid location response');
                return result;
            }
            async function cascade(start, revision) {
                try {
                    for (let index = start; index < keys.length; index++) {
                        const key = keys[index];
                        let path = null;
                        const region = match('region');
                        const province = match('province');
                        const city = match('city');
                        if (key === 'region') path = 'regions/';
                        if (key === 'province' && region) path = `regions/${encodeURIComponent(region.code)}/provinces/`;
                        if (key === 'city') {
                            if (province) path = `provinces/${encodeURIComponent(province.code)}/cities-municipalities/`;
                            else if (region && (!inputs.province.value.trim() || places.province.length === 0)) path = `regions/${encodeURIComponent(region.code)}/cities-municipalities/`;
                        }
                        if (key === 'barangay' && city) path = `cities-municipalities/${encodeURIComponent(city.code)}/barangays/`;
                        const values = path ? await get(path) : [];
                        if (!active || generation !== revision) return;
                        fill(key, values);
                    }
                    if (active && generation === revision) help.textContent = normalHelp;
                } catch {
                    if (active && generation === revision) help.textContent = 'Address suggestions are temporarily unavailable. You can still type your complete address manually and save.';
                }
            }
            keys.slice(0, -1).forEach((key, index) => {
                const changed = () => {
                    const revision = ++generation;
                    keys.slice(index + 1).forEach(child => {
                        inputs[child].value = '';
                        fill(child, []);
                    });
                    container.dispatchEvent(new Event('input', { bubbles: true }));
                    cascade(index + 1, revision);
                };
                inputs[key].addEventListener('input', changed, { signal: controller.signal });
            });
            // The top-level list remains useful even if the user starts typing
            // before the first request finishes. Never replace their input.
            get('regions/').then(regions => {
                if (!active) return;
                fill('region', regions);
                cascade(1, ++generation);
            }).catch(() => {
                if (active) help.textContent = 'Address suggestions are temporarily unavailable. You can still type your complete address manually and save.';
            });
            document.addEventListener('turbo:before-cache', () => {
                active = false;
                controller.abort();
                delete container.dataset.initialized;
            }, { once: true, signal: controller.signal });
        });
    }
    document.addEventListener('DOMContentLoaded', initialize);
    document.addEventListener('turbo:load', initialize);
    if (document.readyState !== 'loading') initialize();
})();
