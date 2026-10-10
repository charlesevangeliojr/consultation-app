@extends('layouts.consultations')

@section('title', 'Student Profile')

@section('content')
    <div class="max-w-3xl">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-rose-400">Student account</p>
        <h1 class="mt-2 text-3xl font-bold">Your profile</h1>
        <p class="mt-2 text-slate-400">Update your contact and student information below.</p>

        <form method="POST" action="{{ route('consultations.student-profile.update') }}" enctype="multipart/form-data" class="mt-8 grid gap-5 rounded-2xl border border-slate-800 bg-slate-900 p-6 sm:grid-cols-2">
            @csrf
            @method('PUT')
            <div class="flex items-center gap-5 sm:col-span-2">
                @if ($student->profile_photo_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($student->profile_photo_path) }}" alt="Student profile photo" class="h-20 w-20 rounded-full border border-slate-700 object-cover">
                @else
                    <span class="grid h-20 w-20 place-items-center rounded-full border border-slate-700 bg-slate-950 text-2xl font-semibold text-rose-300">{{ mb_substr($user->name, 0, 1) }}</span>
                @endif
                <div class="flex-1">
                    <label for="profile_photo" class="mb-2 block text-sm font-medium">Profile picture</label>
                    <input id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-300 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-800 file:px-4 file:py-2 file:font-medium file:text-white hover:file:bg-slate-700">
                    <p class="mt-1 text-xs text-slate-500">Upload a JPG, PNG, or WebP image up to 2 MB.</p>
                    @error('profile_photo') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="sm:col-span-2">
                <label for="email" class="mb-2 block text-sm font-medium">Email</label>
                <input id="email" name="email" type="email" required maxlength="255" value="{{ old('email', $user->email) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3">
                @error('email') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="contact_number" class="mb-2 block text-sm font-medium">Contact number</label>
                <input id="contact_number" name="contact_number" type="tel" maxlength="30" value="{{ old('contact_number', $student->contact_number) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3">
                @error('contact_number') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="birthday" class="mb-2 block text-sm font-medium">Birthday</label>
                <input id="birthday" name="birthday" type="date" value="{{ old('birthday', $student->birthday?->format('Y-m-d')) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3">
                @error('birthday') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="gender" class="mb-2 block text-sm font-medium">Gender</label>
                <input id="gender" name="gender" type="text" maxlength="30" value="{{ old('gender', $student->gender) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3">
                @error('gender') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="lrn" class="mb-2 block text-sm font-medium">LRN <span class="text-slate-500">(12 digits)</span></label>
                <input id="lrn" name="lrn" type="text" inputmode="numeric" maxlength="12" value="{{ old('lrn', $student->lrn) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3">
                @error('lrn') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="address" class="mb-2 block text-sm font-medium">Address</label>
                <textarea id="address" name="address" rows="3" maxlength="2000" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3">{{ old('address', $student->address) }}</textarea>
                @error('address') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="status" class="mb-2 block text-sm font-medium">Status</label>
                <select id="status" name="status" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3">
                    @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $student->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-end sm:col-span-2">
                <button type="submit" class="rounded-lg bg-rose-500 px-5 py-3 font-semibold hover:bg-rose-400">Save profile</button>
            </div>
        </form>
    </div>
@endsection
