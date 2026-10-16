<div class="profile-field">
    <label for="{{ $field }}">{{ $label }} <span class="profile-required" aria-hidden="true">*</span></label>
    <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field, $value) }}" required @if($max) maxlength="{{ $max }}" @endif autocomplete="{{ $autocomplete }}" @if($field === 'birthday') max="{{ today()->subDay()->toDateString() }}" @endif @if($field === 'contact_number') pattern="\+?([0-9][ .\(\)\-]*){7,15}" placeholder="e.g. +63 912 345 6789" aria-describedby="contact-number-help @error($field) {{ $field }}-error @enderror" @elseif($errors->has($field)) aria-describedby="{{ $field }}-error" @endif @error($field) aria-invalid="true" @enderror data-completion-input>
    @if($field === 'contact_number')<p id="contact-number-help" class="profile-help">Use 7–15 digits, including the country code if applicable.</p>@endif
    @error($field)<p id="{{ $field }}-error" class="profile-field-error">{{ $message }}</p>@enderror
</div>
