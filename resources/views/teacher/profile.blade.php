@extends('layouts.consultations')

@section('title', 'Teacher Profile')

@section('content')
@php
    $fieldLabels = \App\Models\Teacher::profileFieldLabels();
    $completion = (int) round((count($fieldLabels) - count($missingFields)) / count($fieldLabels) * 100);
    $selectedCountry = old('phone_country', $phone['country']);
    $selectedCountry = is_string($selectedCountry) && isset($phoneCountries[$selectedCountry]) ? $selectedCountry : 'PH';
@endphp
<div class="teacher-profile-page">
    <header class="profile-page-heading">
        <div>
            <p class="profile-eyebrow">ACCOUNT SETTINGS <span aria-hidden="true">/</span> TEACHER PROFILE</p>
            <h1>Your profile</h1>
            <p>Make it easy for students to recognize you and discover the support you offer.</p>
        </div>
        <span class="profile-status {{ count($missingFields) ? 'profile-status-pending' : 'profile-status-complete' }}">{{ count($missingFields) ? 'Setup required' : 'Profile complete' }}</span>
    </header>

    @if(count($missingFields))
        <div class="profile-setup-notice" role="status">
            <span class="profile-notice-icon" aria-hidden="true">!</span>
            <div><h2>Let’s finish setting up your account</h2><p>{{ session('profile_required', 'Complete all required details and add a profile picture before using the teacher workspace.') }} Your consultation approval is managed separately by an administrator.</p></div>
        </div>
    @endif

    <nav class="profile-section-navigation" aria-label="Profile sections">
        <a href="#personal-information" data-profile-section-link="personal-information" aria-current="location"><span aria-hidden="true">01</span>Personal details</a>
        <a href="#address" data-profile-section-link="address"><span aria-hidden="true">02</span>Address</a>
        <a href="#consultation-offerings" data-profile-section-link="consultation-offerings"><span aria-hidden="true">03</span>Offerings</a>
    </nav>

    <form method="POST" action="{{ route('consultations.teacher-profile.update') }}" enctype="multipart/form-data" class="profile-workspace" data-teacher-profile-form data-existing-photo="{{ $teacher->profile_photo_path ? 'true' : 'false' }}">
        @csrf @method('PUT')
        @if($errors->any())
            <div role="alert" class="profile-error-summary" tabindex="-1">
                <h2>Please check your profile details</h2>
                <ul>@foreach($errors->messages() as $field => $messages)<li><a href="#{{ $field }}">{{ $messages[0] }}</a></li>@endforeach</ul>
                <p>If uploading a picture, please select the file again.</p>
            </div>
        @endif

        <div class="profile-overview">
            <section class="profile-card profile-identity" aria-labelledby="photo-heading">
                <div class="profile-identity-cover"><span>YOUR PUBLIC PHOTO</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="m3 16 5-5 4 4 3-3 6 6"/><circle cx="16" cy="9" r="1"/></svg></div>
                <div class="profile-identity-body">
                    <div class="profile-portrait">
                        @if($teacher->profile_photo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($teacher->profile_photo_path) }}" alt="Your profile picture" data-profile-photo-preview data-original-src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($teacher->profile_photo_path) }}">
                        @else
                            <img alt="Preview of your selected profile picture" data-profile-photo-preview data-original-src="" hidden>
                        @endif
                        <span data-profile-photo-fallback @if($teacher->profile_photo_path) hidden @endif aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span>
                    </div>
                    <h2 class="profile-identity-name">{{ $user->name }}</h2>
                    <p class="profile-identity-role">Teacher · {{ $siteSettings->name ?: 'ConSync' }}</p>
                    <label class="profile-upload" for="profile_photo">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8h4l2-3h4l2 3h4v12H4Z"/><circle cx="12" cy="13" r="3"/></svg>
                        <span id="photo-heading">{{ $teacher->profile_photo_path ? 'Change profile picture' : 'Add profile picture' }}</span>
                        <input id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" @unless($teacher->profile_photo_path) required @endunless aria-describedby="profile-photo-help profile-photo-client-error @error('profile_photo') profile_photo-error @enderror" @error('profile_photo') aria-invalid="true" @enderror data-completion-input>
                    </label>
                    <p id="profile-photo-help" class="profile-help">JPG, PNG, or WebP · Up to 2 MB.<br>Crop and adjust your picture before saving.</p>
                    <p id="profile-photo-client-error" class="profile-field-error" role="alert" data-photo-error hidden></p>
                    @error('profile_photo')<p id="profile_photo-error" class="profile-field-error">{{ $message }}</p>@enderror
                    <p class="profile-photo-filename" data-photo-filename aria-live="polite"></p>
                </div>
            </section>

            <section class="profile-card profile-progress-card" aria-labelledby="completion-heading" @if($completion === 100) hidden @endif>
                <div class="profile-progress-heading"><h2 id="completion-heading">Profile checklist</h2><strong data-profile-percentage>{{ $completion }}%</strong></div>
                <div class="profile-progress-track" role="progressbar" aria-label="Profile completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $completion }}" data-profile-progress><span style="width: {{ $completion }}%"></span></div>
                <p class="profile-help" data-profile-progress-caption>{{ count($missingFields) ? 'Complete the missing details, then save your profile.' : 'All required information is saved. You’re ready to use your workspace.' }}</p>
                <details class="profile-checklist-disclosure">
                <summary><span>View profile checklist</span><span data-profile-completed-count>{{ count($fieldLabels) - count($missingFields) }}/{{ count($fieldLabels) }} ready</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary>
                <ul class="profile-checklist">
                    @foreach($fieldLabels as $field => $label)<li data-completion-field="{{ $field }}" data-complete="{{ isset($missingFields[$field]) ? 'false' : 'true' }}"><span class="profile-check-mark" aria-hidden="true">{{ isset($missingFields[$field]) ? '○' : '✓' }}</span><a href="#{{ $field }}">{{ $label }}</a><span class="profile-check-state">{{ isset($missingFields[$field]) ? 'Required' : 'Done' }}</span></li>@endforeach
                </ul>
                </details>
            </section>
        </div>

        <div class="profile-details">
            <section class="profile-card" id="personal-information" aria-labelledby="personal-heading">
                <div class="profile-section-heading"><span class="profile-section-number" aria-hidden="true">01</span><div><h2 id="personal-heading">Personal information</h2><p>Your identity and contact details, all in one place.</p></div><span class="profile-section-tag">Private details</span></div>
                <div class="profile-fields">
                    @include('teacher.partials.profile-field', ['field' => 'name', 'label' => 'Full name', 'type' => 'text', 'value' => $user->name, 'max' => 255, 'autocomplete' => 'name'])
                    @include('teacher.partials.profile-field', ['field' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => $user->email, 'max' => 255, 'autocomplete' => 'email'])
                    <div class="profile-field">
                        <label for="phone_number">Contact number <span class="profile-required" aria-hidden="true">*</span></label>
                        <div class="profile-phone-input" id="contact_number">
                            <select id="phone_country" name="phone_country" aria-label="Phone country and calling code" required data-phone-country>
                                @foreach($phoneCountries as $iso => $option)<option value="{{ $iso }}" aria-label="{{ $option['name'] }} +{{ $option['code'] }}" data-code="{{ $option['code'] }}" data-pattern="{{ $option['pattern'] }}" data-example="{{ $option['example'] }}" @selected($selectedCountry === $iso)>{{ $iso }} +{{ $option['code'] }}</option>@endforeach
                            </select>
                            <input id="phone_number" name="phone_number" type="tel" inputmode="numeric" autocomplete="tel-national" required maxlength="15" pattern="{{ $phoneCountries[$selectedCountry]['pattern'] }}" placeholder="{{ $phoneCountries[$selectedCountry]['example'] }}" value="{{ old('phone_number', $phone['number']) }}" aria-describedby="contact-number-help @if($errors->hasAny(['phone_number', 'phone_country'])) phone-error @endif" @if($errors->hasAny(['phone_number', 'phone_country'])) aria-invalid="true" @endif data-completion-input>
                        </div>
                        <p id="contact-number-help" class="profile-help" data-phone-help>National number only, without +{{ $phoneCountries[$selectedCountry]['code'] }}. Example: {{ $phoneCountries[$selectedCountry]['example'] }}.</p>
                        @if($errors->hasAny(['phone_number', 'phone_country']))<p id="phone-error" class="profile-field-error">{{ $errors->first('phone_number') ?: $errors->first('phone_country') }}</p>@endif
                    </div>
                    @include('teacher.partials.profile-field', ['field' => 'birthday', 'label' => 'Birthday', 'type' => 'date', 'value' => $teacher->birthday?->format('Y-m-d'), 'max' => null, 'autocomplete' => 'bday'])
                    <div class="profile-field">
                        <label for="gender">Sex <span class="profile-required" aria-hidden="true">*</span></label>
                        <select id="gender" name="gender" required autocomplete="sex" data-completion-input @error('gender') aria-invalid="true" aria-describedby="gender-error" @enderror><option value="">Select sex</option>@foreach(['Male', 'Female'] as $sex)<option value="{{ $sex }}" @selected(old('gender', $teacher->gender) === $sex)>{{ $sex }}</option>@endforeach</select>
                        @error('gender')<p id="gender-error" class="profile-field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <p class="profile-privacy-note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6Z"/><path d="m8 12 3 3 5-6"/></svg><span>Your contact number, birthday, sex, and address are never shown in student calendars or public teacher listings.</span></p>
            </section>

            <section class="profile-card" aria-labelledby="address-heading" id="address" data-address-suggestions>
                <div class="profile-section-heading"><span class="profile-section-number" aria-hidden="true">02</span><div><h2 id="address-heading">Address</h2><p>Start with your region. Suggestions help you fill in the rest.</p></div></div>
                @if($teacher->address && !$teacher->region)<p class="profile-privacy-note profile-legacy-address">Previously saved: {{ $teacher->address }}. Please enter the address in the fields below.</p>@endif
                <div class="profile-fields">
                    @foreach(['region' => ['Region', 150, true], 'province' => ['Province', 150, false], 'city' => ['City / municipality', 150, true], 'barangay' => ['Barangay', 150, true], 'house_number' => ['House / unit no.', 100, false], 'postal_code' => ['Postal code', 20, false]] as $field => [$label, $max, $required])
                        <div class="profile-field">
                            <label for="{{ $field }}">{{ $label }} @if($required)<span class="profile-required" aria-hidden="true">*</span>@else<span class="profile-help">(optional)</span>@endif</label>
                            <input id="{{ $field }}" name="{{ $field }}" type="text" maxlength="{{ $max }}" @if($required) required @endif value="{{ old($field, $teacher->{$field}) }}" @if(in_array($field, ['region', 'province', 'city', 'barangay'])) list="{{ $field }}-options" data-location-input autocomplete="off" placeholder="Type or select {{ strtolower($label) }}" @else autocomplete="{{ $field === 'postal_code' ? 'postal-code' : 'address-line1' }}" @endif @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror data-completion-input>
                            @if(in_array($field, ['region', 'province', 'city', 'barangay']))<datalist id="{{ $field }}-options"></datalist>@endif
                            @error($field)<p id="{{ $field }}-error" class="profile-field-error">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
                <p id="location-help" class="profile-help profile-school-help" aria-live="polite">Suggestions load as you choose a region, province, city / municipality, and barangay. You can also type your address manually. Province is optional for NCR.</p>
            </section>

            <section class="profile-card profile-offerings-card" id="consultation-offerings" aria-labelledby="offerings-heading">
                <div class="profile-section-heading"><span class="profile-section-number" aria-hidden="true">03</span><div><h2 id="offerings-heading">Consultation offerings</h2><p>Help students know when to reach out to you.</p></div><span class="profile-section-tag profile-section-tag-public">Visible to students</span></div>
                <div class="profile-fields">
                    <div class="profile-field profile-field-wide">
                        <label for="consultation_offerings">What can you offer students? <span class="profile-required" aria-hidden="true">*</span></label>
                        <textarea id="consultation_offerings" name="consultation_offerings" rows="4" required maxlength="2000" placeholder="For example: Algebra problem-solving, science project feedback, exam preparation, and study planning." data-completion-input aria-describedby="offerings-help @error('consultation_offerings') consultation_offerings-error @enderror" @error('consultation_offerings') aria-invalid="true" @enderror>{{ old('consultation_offerings', $teacher->consultation_offerings) }}</textarea>
                        <div class="profile-offerings-caption"><span>Be specific about the topics and support you provide.</span><span data-offerings-count aria-label="Description character count">{{ mb_strlen(old('consultation_offerings', $teacher->consultation_offerings) ?? '') }} / 2,000</span></div>
                        <p id="offerings-help" class="profile-help">This description is shown to students when they browse your consultations. No admin confirmation is needed for these edits.</p>
                        @error('consultation_offerings')<p id="consultation_offerings-error" class="profile-field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <div class="profile-save-bar">
                <p class="profile-save-state"><span class="profile-save-dot" aria-hidden="true"></span><span><strong data-profile-save-state>{{ count($missingFields) ? 'Finish your profile setup' : 'Your profile is up to date' }}</strong><small>Changes are applied when you save.</small></span></p>
                <button type="submit">{{ count($missingFields) ? 'Complete profile' : 'Save changes' }} <span aria-hidden="true">→</span></button>
            </div>
            @if(!count($missingFields))<a href="{{ route('consultations.teacher') }}" class="profile-back-link">← Back to calendar</a>@endif
        </div>
    </form>
    @include('teacher.partials.photo-cropper')
</div>
@endsection
