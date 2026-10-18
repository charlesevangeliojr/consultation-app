@extends('layouts.consultations')
@section('title', 'Student Profile')
@section('content')
<div class="teacher-profile-page student-profile-page">
    @include('student.partials.page-header', ['eyebrow' => 'ACCOUNT SETTINGS / STUDENT PROFILE', 'heading' => 'Your profile', 'description' => 'Keep your school account accurate, recognizable, and ready for consultations.'])
    <form method="POST" action="{{ route('consultations.student-profile.update') }}" enctype="multipart/form-data" class="profile-workspace mt-6" data-student-profile-form data-has-structured-address="{{ $student->region ? 'true' : 'false' }}">
        @csrf @method('PUT')
        @if($errors->any())<div class="profile-error-summary" role="alert"><h2>Please review your profile details</h2><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul><p>If uploading a picture, please select it again.</p></div>@endif
        <div class="profile-overview">
            <section class="profile-card profile-identity" aria-labelledby="student-photo-heading">
                <div class="profile-identity-cover"><span>YOUR PROFILE PHOTO</span></div>
                <div class="profile-identity-body">
                    <div class="profile-portrait">
                        <img @if($student->profile_photo_path) src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($student->profile_photo_path) }}" @else hidden @endif alt="Your profile picture" data-student-photo-preview data-original-src="{{ $student->profile_photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($student->profile_photo_path) : '' }}">
                        <span data-student-photo-fallback @if($student->profile_photo_path) hidden @endif aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span>
                    </div>
                    <h2 class="profile-identity-name">{{ $user->name }}</h2>
                    <p class="profile-identity-role">Student · {{ $siteSettings->name ?: config('app.name') }}</p>
                    <label class="profile-upload" for="profile_photo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 8h4l2-3h4l2 3h4v12H4Z"/><circle cx="12" cy="13" r="3"/></svg><span id="student-photo-heading">{{ $student->profile_photo_path ? 'Change profile picture' : 'Add profile picture' }}</span><input id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="student-photo-help student-photo-error"></label>
                    <p id="student-photo-help" class="profile-help">JPG, PNG, or WebP · Up to 2 MB.<br>Crop and adjust your picture before saving.</p>
                    <p class="profile-help" data-student-photo-filename></p>
                    <p id="student-photo-error" class="profile-field-error" role="alert" data-student-photo-error hidden></p>
                    @error('profile_photo')<p class="profile-field-error">{{ $message }}</p>@enderror
                </div>
            </section>
            <section class="profile-card student-school-info" aria-labelledby="student-school-heading">
                <div class="flex items-center justify-between gap-3"><h2 id="student-school-heading" class="text-sm font-semibold">School information</h2><span class="profile-section-tag">Admin managed</span></div>
                <dl class="mt-5"><div><dt>Full name</dt><dd>{{ $user->name }}</dd></div><div><dt>Grade level</dt><dd>{{ $student->gradeLevel?->name ?: 'Not assigned' }}</dd></div><div><dt>Section</dt><dd>{{ $student->section?->name ?: 'Not assigned' }}</dd></div></dl>
                <p class="profile-help mt-5">Contact an administrator to correct your name or class assignment.</p>
            </section>
        </div>
        <div class="profile-details">
            <section class="profile-card" aria-labelledby="student-personal-heading">
                <div class="profile-section-heading"><span class="profile-section-number" aria-hidden="true">01</span><div><h2 id="student-personal-heading">Personal information</h2><p>Your account, contact, and student details.</p></div><span class="profile-section-tag">Private details</span></div>
                <div class="profile-fields">
                    @foreach(['email' => ['Email', 'email', $user->email, 255, 'email'], 'contact_number' => ['Contact number', 'tel', $student->contact_number, 30, 'tel'], 'birthday' => ['Birthday', 'date', $student->birthday?->format('Y-m-d'), null, 'bday'], 'lrn' => ['LRN (12 digits)', 'text', $student->lrn, 12, 'off']] as $field => [$label, $type, $value, $max, $autocomplete])
                        <div class="profile-field"><label for="{{ $field }}">{{ $label }} @if($field === 'email')<span>(optional)</span>@endif</label>
                            <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field, $value) }}" autocomplete="{{ $autocomplete }}" @if($max) maxlength="{{ $max }}" @endif @if($field === 'lrn') inputmode="numeric" pattern="[0-9]{12}" @endif @if($field === 'birthday') max="{{ today()->subDay()->toDateString() }}" @endif @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                            @error($field)<p id="{{ $field }}-error" class="profile-field-error">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                    <div class="profile-field"><label for="gender">Sex</label><select id="gender" name="gender" @error('gender') aria-invalid="true" aria-describedby="gender-error" @enderror><option value="">Select sex</option>@php($savedSex = old('gender', $student->gender)) @foreach(array_unique(array_filter(['Male', 'Female', $savedSex])) as $sex)<option value="{{ $sex }}" @selected($savedSex === $sex)>{{ $sex }}</option>@endforeach</select>@error('gender')<p id="gender-error" class="profile-field-error">{{ $message }}</p>@enderror</div>
                </div>
                <p class="profile-privacy-note">Your personal contact details and address are not displayed in public teacher listings or student queues.</p>
            </section>
            <section class="profile-card" aria-labelledby="student-address-heading" data-address-suggestions>
                <div class="profile-section-heading"><span class="profile-section-number" aria-hidden="true">02</span><div><h2 id="student-address-heading">Address</h2><p>Start with your region. Suggestions help you fill in the rest.</p></div></div>
                <div class="profile-fields">
                    @foreach(['region' => ['Region', 150], 'province' => ['Province', 150], 'city' => ['City / municipality', 150], 'barangay' => ['Barangay', 150], 'house_number' => ['House / unit no.', 100]] as $field => [$label, $max])
                        <div class="profile-field"><label for="{{ $field }}">{{ $label }} @if(in_array($field, ['province', 'house_number']))<span class="profile-help">(optional)</span>@endif</label>
                            <input id="{{ $field }}" name="{{ $field }}" type="text" maxlength="{{ $max }}" value="{{ old($field, $student->{$field}) }}" @if($student->region && in_array($field, ['region', 'city', 'barangay'])) required @endif @if(in_array($field, ['region', 'province', 'city', 'barangay'])) list="{{ $field }}-options" data-location-input autocomplete="off" placeholder="Type or select {{ strtolower($label) }}" @else autocomplete="address-line1" @endif aria-describedby="location-help @error($field) {{ $field }}-error @enderror" @error($field) aria-invalid="true" @enderror>
                            @if(in_array($field, ['region', 'province', 'city', 'barangay']))<datalist id="{{ $field }}-options"></datalist>@endif
                            @error($field)<p id="{{ $field }}-error" class="profile-field-error">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
                <p id="location-help" class="profile-help profile-school-help" aria-live="polite"></p>
            </section>
            <div class="profile-save-bar"><p class="profile-save-state"><span class="profile-save-dot" aria-hidden="true"></span><span><strong data-student-save-state>Your profile is up to date</strong><small>Changes are applied when you save.</small></span></p><button type="submit">Save profile <span aria-hidden="true">→</span></button></div>
            <a href="{{ route('consultations.dashboard') }}" class="profile-back-link">← Back to dashboard</a>
        </div>
    </form>
    @include('teacher.partials.photo-cropper')
</div>
@endsection
