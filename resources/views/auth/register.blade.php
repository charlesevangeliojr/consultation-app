<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account · {{ $siteSettings->name ?: 'ConSync' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/form-controls.css') }}">
    @include('partials.theme-head')
</head>
<body class="grid min-h-screen place-items-center bg-slate-950 px-5 py-10 text-slate-100">
    @include('partials.pwa-ui')
    @include('partials.theme-auth-navigation')
    <main class="w-full max-w-xl rounded-2xl border border-slate-800 bg-slate-900 p-7 shadow-2xl sm:p-9">
        <a href="{{ route('register') }}" class="text-sm text-slate-400 hover:text-white">← Change account type</a>
        <p class="mt-6 text-sm font-semibold uppercase tracking-[.2em] text-rose-400">{{ ucfirst($selectedRole) }} registration</p>
        <h1 class="mt-2 text-3xl font-bold">Create your account</h1>

        @php
            $credentialStep = $selectedRole === 'student' ? 3 : 2;
            $initialStep = $errors->hasAny(['first_name', 'middle_name', 'last_name', 'suffix', 'email', 'lrn', 'role']) ? 1 : ($errors->hasAny(['contact_number', 'birthday', 'gender', 'house_number', 'region', 'province', 'city', 'barangay', 'grade_level_id', 'section_id']) ? 2 : ($errors->hasAny(['password', 'password_confirmation']) ? $credentialStep : 1));
            $stepCount = $selectedRole === 'student' ? 3 : 2;
            $firstStepFields = $selectedRole === 'teacher' ? ['first_name', 'last_name', 'email'] : ['first_name', 'last_name', 'lrn'];
            $secondStepFields = $selectedRole === 'student' ? ['contact_number', 'birthday', 'gender', 'grade_level_id', 'section_id'] : ['password', 'password_confirmation'];
        @endphp

        <div id="registration-form" data-initial-step="{{ $initialStep }}" data-step-count="{{ $stepCount }}">
            <div class="mt-6 flex items-center gap-2" aria-label="Registration progress">
                @for ($step = 1; $step <= $stepCount; $step++)
                    <span data-progress-step="{{ $step }}" class="h-1.5 flex-1 rounded-full bg-slate-700"></span>
                @endfor
            </div>
            <p id="step-label" class="mt-2 text-xs text-slate-500"></p>

            <form id="register-form" method="POST" action="{{ route('register.store') }}" class="mt-6">
                @csrf
                <input type="hidden" name="role" value="{{ $selectedRole }}">

                <section data-form-step="1" class="space-y-5">
                    <h2 class="text-lg font-semibold">Account information</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="first_name" class="mb-2 block text-sm font-medium">First name</label>
                            <input id="first_name" name="first_name" type="text" maxlength="100" autocomplete="given-name" value="{{ old('first_name') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400">
                            @error('first_name') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="middle_name" class="mb-2 block text-sm font-medium">Middle name <span class="text-slate-500">(optional)</span></label>
                            <input id="middle_name" name="middle_name" type="text" maxlength="100" autocomplete="additional-name" value="{{ old('middle_name') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400">
                            @error('middle_name') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="last_name" class="mb-2 block text-sm font-medium">Last name</label>
                            <input id="last_name" name="last_name" type="text" maxlength="100" autocomplete="family-name" value="{{ old('last_name') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400">
                            @error('last_name') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="suffix" class="mb-2 block text-sm font-medium">Suffix <span class="text-slate-500">(optional)</span></label>
                            <input id="suffix" name="suffix" type="text" maxlength="30" placeholder="Jr., III" value="{{ old('suffix') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400">
                            @error('suffix') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @if ($selectedRole === 'student')
                        <div>
                            <label for="lrn" class="mb-2 block text-sm font-medium">LRN <span class="text-slate-500">(12 digits)</span></label>
                            <input id="lrn" name="lrn" type="text" inputmode="numeric" maxlength="12" pattern="[0-9]{12}" autocomplete="username" placeholder="12-digit learner reference number" value="{{ old('lrn') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400">
                            <p class="mt-1 text-xs text-slate-500">Enter exactly 12 digits. Use your LRN to sign in.</p>
                            @error('lrn') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium">Email</label>
                            <input id="email" name="email" type="email" maxlength="255" autocomplete="email" value="{{ old('email') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400">
                            @error('email') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <button type="button" data-next class="w-full rounded-lg bg-rose-500 px-5 py-3 font-semibold transition hover:bg-rose-400">Continue</button>
                </section>

                @if ($selectedRole === 'student')
                    <section data-form-step="2" class="hidden space-y-5">
                        <h2 class="text-lg font-semibold">Student details</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="grade_level_id" class="mb-2 block text-sm font-medium">Grade level</label>
                                <select id="grade_level_id" name="grade_level_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3">
                                    <option value="">Select grade level</option>
                                    @foreach ($gradeLevels as $grade)<option value="{{ $grade->id }}" @selected(old('grade_level_id') == $grade->id)>{{ $grade->name }}</option>@endforeach
                                </select>
                                @error('grade_level_id')<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="section_id" class="mb-2 block text-sm font-medium">Section</label>
                                <select id="section_id" name="section_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3">
                                    <option value="">Select section</option>
                                    @foreach ($gradeLevels as $grade) @foreach ($grade->sections as $section)<option value="{{ $section->id }}" data-grade="{{ $grade->id }}" @selected(old('section_id') == $section->id)>{{ $section->name }}</option>@endforeach @endforeach
                                </select>
                                @error('section_id')<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        @if ($gradeLevels->isEmpty() || $gradeLevels->every(fn ($grade) => $grade->sections->isEmpty()))<p class="text-sm text-amber-300">An administrator must add grade levels and sections before you can register.</p>@endif
                        <div>
                            <label for="contact_number" class="mb-2 block text-sm font-medium">Contact number <span class="text-rose-400" aria-label="required">*</span></label>
                            <div class="flex overflow-hidden rounded-lg border border-slate-700 bg-slate-950 focus-within:border-rose-400">
                                <span class="grid place-items-center border-r border-slate-700 px-4 text-slate-300">+63</span>
                                <input id="contact_number" name="contact_number" type="tel" inputmode="numeric" maxlength="10" pattern="9[0-9]{9}" title="Enter 10 digits starting with 9" placeholder="9XXXXXXXXX" autocomplete="tel-national" value="{{ old('contact_number') }}" class="min-w-0 flex-1 bg-transparent px-4 py-3 outline-none">
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Enter a Philippine mobile number starting with 9 (10 digits after +63).</p>
                            @if ($errors->has('contact_number') && old('contact_number'))
                                <p class="mt-2 text-sm text-rose-300">{{ $errors->first('contact_number') }}</p>
                            @endif
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="birthday" class="mb-2 block text-sm font-medium">Date of birth</label>
                                <input id="birthday" name="birthday" type="date" max="{{ today()->subDay()->toDateString() }}" value="{{ old('birthday') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400">
                                <p class="mt-1 text-xs text-slate-500">Choose a date in the past.</p>
                                @error('birthday') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="gender" class="mb-2 block text-sm font-medium">Sex</label>
                                <select id="gender" name="gender" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400">
                                    <option value="">Select sex</option>
                                    @foreach (['Male', 'Female'] as $sex)
                                        <option value="{{ $sex }}" @selected(old('gender') === $sex)>{{ $sex }}</option>
                                    @endforeach
                                </select>
                                @error('gender') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <fieldset class="space-y-3">
                            <legend class="text-sm font-medium">Address</legend>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="region" class="sr-only">Region</label>
                                    <input id="region" name="region" list="region-options" data-location-input autocomplete="off" placeholder="Type or select region" value="{{ old('region') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none placeholder:text-slate-500 focus:border-rose-400">
                                    <datalist id="region-options"></datalist>
                                    @error('region') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="province" class="sr-only">Province</label>
                                    <input id="province" name="province" list="province-options" data-location-input disabled autocomplete="off" placeholder="Type or select province" value="{{ old('province') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none placeholder:text-slate-500 focus:border-rose-400 disabled:opacity-50">
                                    <datalist id="province-options"></datalist>
                                    @error('province') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="city" class="sr-only">City or municipality</label>
                                    <input id="city" name="city" list="city-options" data-location-input disabled autocomplete="off" placeholder="Type or select city/municipality" value="{{ old('city') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none placeholder:text-slate-500 focus:border-rose-400 disabled:opacity-50">
                                    <datalist id="city-options"></datalist>
                                    @error('city') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="barangay" class="sr-only">Barangay</label>
                                    <input id="barangay" name="barangay" list="barangay-options" data-location-input disabled autocomplete="off" placeholder="Type or select barangay" value="{{ old('barangay') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none placeholder:text-slate-500 focus:border-rose-400 disabled:opacity-50">
                                    <datalist id="barangay-options"></datalist>
                                    @error('barangay') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="house_number" class="sr-only">House / unit no.</label>
                                    <input id="house_number" name="house_number" type="text" maxlength="100" placeholder="House / unit no. (optional)" value="{{ old('house_number') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none placeholder:text-slate-500 focus:border-rose-400">
                                    @error('house_number') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <p id="location-help" class="text-xs text-slate-500">Choose a region, then select the province, city/municipality, and barangay.</p>
                        </fieldset>
                        <div class="flex gap-3">
                            <button type="button" data-previous class="w-1/3 rounded-lg border border-slate-700 px-4 py-3 font-semibold hover:bg-slate-800">Back</button>
                            <button type="button" data-next class="flex-1 rounded-lg bg-rose-500 px-5 py-3 font-semibold transition hover:bg-rose-400">Continue</button>
                        </div>
                    </section>
                @endif

                <section data-form-step="{{ $credentialStep }}" class="hidden space-y-5">
                    <h2 class="text-lg font-semibold">Create a password</h2>
                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium">Password</label>
                        <div class="flex overflow-hidden rounded-lg border border-slate-700 bg-slate-950 focus-within:border-rose-400">
                            <input id="password" name="password" type="password" autocomplete="new-password" class="min-w-0 flex-1 bg-transparent px-4 py-3 outline-none">
                            <button type="button" data-toggle-password="password" aria-label="Show password" class="px-4 text-sm text-rose-300 hover:text-white">Show</button>
                        </div>
                        <p class="mt-2 text-xs leading-5 text-slate-400">Password must be at least 8 characters.</p>
                        @error('password') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-medium">Confirm password</label>
                        <div class="flex overflow-hidden rounded-lg border border-slate-700 bg-slate-950 focus-within:border-rose-400">
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="min-w-0 flex-1 bg-transparent px-4 py-3 outline-none">
                            <button type="button" data-toggle-password="password_confirmation" aria-label="Show confirmation password" class="px-4 text-sm text-rose-300 hover:text-white">Show</button>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" data-previous class="w-1/3 rounded-lg border border-slate-700 px-4 py-3 font-semibold hover:bg-slate-800">Back</button>
                        <button type="submit" class="flex-1 rounded-lg bg-rose-500 px-5 py-3 font-semibold transition hover:bg-rose-400">Create account</button>
                    </div>
                </section>
            </form>
        </div>
        <p class="mt-6 text-center text-sm text-slate-400">Already registered? <a href="{{ route('portal.login') }}" class="font-medium text-rose-300 hover:text-rose-200">Log in</a></p>
    </main>
    <script>
        (() => {
            const wrapper = document.getElementById('registration-form');
            const form = document.getElementById('register-form');
            const count = Number(wrapper.dataset.stepCount);
            let current = Number(wrapper.dataset.initialStep);
            const sections = [...form.querySelectorAll('[data-form-step]')];
            const serverErrors = @json($errors->messages());
            const fields = [...form.querySelectorAll('section input, section select, section textarea')];
            const serverErrorValues = new Map();
            fields.forEach(field => {
                const errorName = field.name.replace(/\[\]$/, '');
                const message = serverErrors[errorName]?.[0] || Object.entries(serverErrors).find(([key]) => key.startsWith(`${errorName}.`))?.[1]?.[0];
                if (message) {
                    serverErrors[field.name] = [message];
                    serverErrorValues.set(field.name, field.multiple ? JSON.stringify([...field.selectedOptions].map(option => option.value)) : field.value);
                }
                if (['region', 'province', 'city', 'barangay'].includes(field.name)) field.maxLength = 150;
            });
            document.getElementById('password').minLength = 8;
            const stepFields = {
                1: @json($firstStepFields),
                2: @json($secondStepFields),
                3: ['password', 'password_confirmation'],
            };
            const oldLocation = {
                region: @json(old('region')),
                province: @json(old('province')),
                city: @json(old('city')),
                barangay: @json(old('barangay')),
            };
            const regionInput = document.getElementById('region');
            const provinceInput = document.getElementById('province');
            const cityInput = document.getElementById('city');
            const barangayInput = document.getElementById('barangay');
            const locationHelp = document.getElementById('location-help');
            const psgcApi = 'https://psgc.gitlab.io/api';
            const locationLists = {
                region: { input: regionInput, list: document.getElementById('region-options'), places: [] },
                province: { input: provinceInput, list: document.getElementById('province-options'), places: [] },
                city: { input: cityInput, list: document.getElementById('city-options'), places: [] },
                barangay: { input: barangayInput, list: document.getElementById('barangay-options'), places: [] },
            };
            function resetLocation(key, disabled = true) {
                const item = locationLists[key];
                item.list.replaceChildren();
                item.places = [];
                item.input.disabled = disabled;
            }
            function placeFor(key, name) {
                return locationLists[key].places.find(place => place.name.toLocaleLowerCase() === (name || '').trim().toLocaleLowerCase());
            }
            function fillLocations(key, places, selected = '') {
                const item = locationLists[key];
                item.places = [...places].sort((a, b) => a.name.localeCompare(b.name));
                item.list.replaceChildren(...item.places.map(place => new Option(place.name)));
                item.input.disabled = false;
                if (selected) item.input.value = selected;
            }
            async function getPlaces(path) {
                const response = await fetch(`${psgcApi}/${path}`);
                if (!response.ok) throw new Error('Unable to load locations. Please try again.');
                return response.json();
            }
            async function loadRegions(selected = '') {
                try {
                    const places = await getPlaces('regions/');
                    fillLocations('region', places, selected);
                    if (regionInput.value) await loadProvinces(regionInput.value, oldLocation.province);
                } catch (error) {
                    resetLocation('region', false);
                    locationHelp.textContent = 'Philippine location options could not load. Refresh the page to try again.';
                }
            }
            async function loadProvinces(regionName, selected = '') {
                resetLocation('province', !regionName);
                resetLocation('city');
                resetLocation('barangay');
                const region = placeFor('region', regionName);
                if (!region) {
                    provinceInput.value = selected;
                    provinceInput.disabled = !regionName;
                    return;
                }
                try {
                    const places = await getPlaces(`provinces/?regionCode=${encodeURIComponent(region.code)}`);
                    fillLocations('province', places, selected);
                    if (placeFor('province', provinceInput.value)) await loadCities(provinceInput.value, oldLocation.city);
                    else if (places.length === 0) await loadCities('', oldLocation.city);
                } catch (error) {
                    resetLocation('province', false);
                    locationHelp.textContent = error.message;
                }
            }
            async function loadCities(provinceName, selected = '') {
                resetLocation('city', !provinceName && !regionInput.value);
                resetLocation('barangay');
                const province = placeFor('province', provinceName);
                const region = placeFor('region', regionInput.value);
                const filter = province ? `provinceCode=${encodeURIComponent(province.code)}` : (region ? `regionCode=${encodeURIComponent(region.code)}` : null);
                if (!filter) return;
                try {
                    const places = await getPlaces(`cities-municipalities/?${filter}`);
                    fillLocations('city', places, selected);
                    if (placeFor('city', cityInput.value)) await loadBarangays(cityInput.value, oldLocation.barangay);
                } catch (error) {
                    resetLocation('city', false);
                    locationHelp.textContent = error.message;
                }
            }
            async function loadBarangays(cityName, selected = '') {
                resetLocation('barangay', !cityName);
                const city = placeFor('city', cityName);
                if (!city) {
                    barangayInput.value = selected;
                    return;
                }
                const locationType = city.isCity ? 'cityCode' : 'municipalityCode';
                try {
                    const places = await getPlaces(`barangays/?${locationType}=${encodeURIComponent(city.code)}`);
                    fillLocations('barangay', places, selected);
                } catch (error) {
                    resetLocation('barangay', false);
                    locationHelp.textContent = error.message;
                }
            }
            function render() {
                sections.forEach(section => {
                    const active = Number(section.dataset.formStep) === current;
                    section.classList.toggle('hidden', !active);
                    section.querySelectorAll('input, select, textarea').forEach(field => {
                        field.required = (stepFields[Number(section.dataset.formStep)] || []).includes(field.name);
                    });
                });
                document.querySelectorAll('[data-progress-step]').forEach(item => {
                    const active = Number(item.dataset.progressStep) <= current;
                    item.classList.toggle('bg-rose-400', active);
                    item.classList.toggle('bg-slate-700', !active);
                });
                document.getElementById('step-label').textContent = `Step ${current} of ${count}`;
            }
            function updateValidity(field) {
                let message = '';
                const value = field.multiple ? JSON.stringify([...field.selectedOptions].map(option => option.value)) : field.value;
                if (serverErrorValues.has(field.name) && value === serverErrorValues.get(field.name)) {
                    message = serverErrors[field.name][0];
                } else if (field.required && !field.value.trim()) {
                    message = 'Please fill out this field.';
                } else if (field.name === 'password' && field.value.length < 8) {
                    message = 'Password must be at least 8 characters.';
                } else if (field.name === 'password_confirmation' && field.value !== document.getElementById('password').value) {
                    message = 'Passwords must match.';
                } else if (field.maxLength > 0 && field.value.length > field.maxLength) {
                    message = `Please use no more than ${field.maxLength} characters.`;
                }
                field.setCustomValidity(message);
            }
            function validateStep(step) {
                const section = sections.find(section => Number(section.dataset.formStep) === step);
                const stepInputs = [...section.querySelectorAll('input, select, textarea')];
                stepInputs.forEach(updateValidity);
                const invalidField = stepInputs.find(field => !field.checkValidity());
                if (!invalidField) return true;
                current = step;
                render();
                invalidField.reportValidity();
                return false;
            }
            fields.forEach(field => {
                const update = () => {
                    updateValidity(field);
                    if (field.name === 'password') updateValidity(document.getElementById('password_confirmation'));
                };
                field.addEventListener('input', update);
                field.addEventListener('change', update);
            });
            form.querySelectorAll('[data-next]').forEach(button => button.addEventListener('click', () => {
                if (validateStep(current) && current < count) { current++; render(); }
            }));
            // Validate before reporting errors so hidden steps can be shown and focused.
            form.noValidate = true;
            form.addEventListener('submit', event => {
                for (let step = 1; step <= count; step++) {
                    if (!validateStep(step)) {
                        event.preventDefault();
                        return;
                    }
                }
                if (current < count) {
                    event.preventDefault();
                    current++;
                    render();
                }
            });
            form.querySelectorAll('[data-previous]').forEach(button => button.addEventListener('click', () => {
                if (current > 1) { current--; render(); }
            }));
            regionInput?.addEventListener('change', () => loadProvinces(regionInput.value));
            provinceInput?.addEventListener('change', () => loadCities(provinceInput.value));
            cityInput?.addEventListener('change', () => loadBarangays(cityInput.value));
            form.querySelectorAll('[data-toggle-password]').forEach(button => button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.togglePassword);
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.textContent = show ? 'Hide' : 'Show';
                button.setAttribute('aria-label', `${show ? 'Hide' : 'Show'} ${input.id === 'password' ? 'password' : 'confirmation password'}`);
            }));
            render();
            const gradeInput = document.getElementById('grade_level_id');
            const sectionInput = document.getElementById('section_id');
            if (gradeInput && sectionInput) {
                const options = [...sectionInput.options].slice(1);
                const filterSections = () => {
                    const selected = sectionInput.value;
                    sectionInput.replaceChildren(new Option('Select section', ''), ...options.filter(option => option.dataset.grade === gradeInput.value));
                    sectionInput.value = [...sectionInput.options].some(option => option.value === selected) ? selected : '';
                };
                gradeInput.addEventListener('change', filterSections);
                filterSections();
            }
            if (regionInput) loadRegions(oldLocation.region);
        })();
    </script>
</body>
</html>
