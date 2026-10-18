@if($account->isStudent() || $account->isTeacher())
<form method="POST" action="{{ route('admin.accounts.school-profile', $account) }}" class="account-form mt-8">
    @csrf @method('PUT')
    <section class="account-panel">
        <h3 class="account-panel-heading">{{ $account->isStudent() ? 'Student school profile' : 'Teacher consultation permission' }}</h3>
        <div class="account-panel-body account-fields">
            @if(session('status'))<p role="status" class="text-emerald-300">{{ session('status') }}</p>@endif
            @if($errors->any())<div role="alert" class="account-error">@foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>@endif
            @if($account->isTeacher())
                <div>
                    <label for="consultation_approved">Consultation permission</label>
                    <select id="consultation_approved" name="consultation_approved" class="account-input">
                        <option value="0" @selected(!old('consultation_approved', $account->teacher?->consultation_approved))>Not approved / revoked</option>
                        <option value="1" @selected(old('consultation_approved', $account->teacher?->consultation_approved))>Approved</option>
                    </select>
                    <p class="account-help">Approved teachers can publish availability and accept bookings. Revoking blocks new bookings and confirmations, but keeps existing records. Admin does not approve individual bookings.</p>
                </div>
            @else
                @php $student = $account->student; @endphp
                <div><label for="school-grade">Grade level</label><select id="school-grade" name="grade_level_id" required class="account-input"><option value="">Select grade level</option>@foreach($gradeLevels as $grade)<option value="{{ $grade->id }}" @selected(old('grade_level_id', $student?->grade_level_id) == $grade->id)>{{ $grade->name }}</option>@endforeach</select></div>
                <div><label for="school-section">Section</label><select id="school-section" name="section_id" required class="account-input"><option value="">Select section</option>@foreach($gradeLevels as $grade) @foreach($grade->sections as $section)<option value="{{ $section->id }}" data-grade="{{ $grade->id }}" @selected(old('section_id', $student?->section_id) == $section->id)>{{ $grade->name }} · {{ $section->name }}</option>@endforeach @endforeach</select></div>
                <div><label for="school-lrn">LRN</label><input id="school-lrn" name="lrn" value="{{ old('lrn', $student?->lrn) }}" pattern="[0-9]{12}" maxlength="12" class="account-input"></div>
                <div><label for="school-contact">Contact number (+639XXXXXXXXX)</label><input id="school-contact" name="contact_number" value="{{ old('contact_number', $student?->contact_number) }}" pattern="\+639[0-9]{9}" class="account-input"></div>
                <div><label for="school-birthday">Date of birth</label><input id="school-birthday" name="birthday" type="date" max="{{ today()->subDay()->toDateString() }}" value="{{ old('birthday', $student?->birthday?->toDateString()) }}" class="account-input"></div>
                <div><label for="school-gender">Sex</label><select id="school-gender" name="gender" class="account-input"><option value="">Select sex</option>@foreach(['Male', 'Female'] as $sex)<option @selected(old('gender', $student?->gender) === $sex)>{{ $sex }}</option>@endforeach</select></div>
                <div><label for="school-address">Address</label><textarea id="school-address" name="address" maxlength="2000" class="account-input">{{ old('address', $student?->address) }}</textarea></div>
            @endif
        </div>
    </section>
    <div class="account-save-bar"><p>Save school information separately from account permissions.</p><button class="account-button account-button-primary">Save school profile</button></div>
</form>
@if($account->isStudent())
<script>
(() => {
    const grade = document.getElementById('school-grade');
    const section = document.getElementById('school-section');
    const options = [...section.options].slice(1);
    const filter = () => {
        const selected = section.value;
        section.replaceChildren(new Option('Select section', ''), ...options.filter(option => option.dataset.grade === grade.value));
        section.value = [...section.options].some(option => option.value === selected) ? selected : '';
    };
    grade.addEventListener('change', filter);
    filter();
})();
</script>
@endif
@endif
