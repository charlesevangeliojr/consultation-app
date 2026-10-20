@extends('admin.layout')

@section('title', 'School settings')

@section('content')
    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-rose-400">Administration</p>
    <h2 class="mt-3 text-3xl font-bold sm:text-4xl">School settings</h2>
    <p class="mt-3 max-w-2xl text-slate-400">Manage school branding and student-name visibility in consultation queues.</p>

    @if (session('status'))
        <p role="status" class="mt-6 max-w-2xl rounded-lg border border-emerald-900 bg-emerald-950/50 p-4 text-sm text-emerald-200">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="mt-8 max-w-2xl space-y-6 rounded-2xl border border-slate-800 bg-slate-900 p-6">
        @csrf
        @method('PUT')
        <div>
            <label for="name" class="mb-2 block text-sm font-medium">Project name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $siteSettings->name) }}" required maxlength="80"
                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400">
            @error('name') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="logo" class="mb-2 block text-sm font-medium">Project logo</label>
            @if ($siteSettings->logo_path)
                <div class="mb-4 flex items-center gap-4">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->logo_path) }}" alt="Current project logo" class="h-16 w-16 rounded-lg border border-slate-700 bg-white object-contain p-2">
                    <label class="flex items-center gap-2 text-sm text-slate-300">
                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-600 bg-slate-950 text-rose-500">
                        Remove current logo
                    </label>
                </div>
            @endif
            <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp,image/gif"
                class="block w-full text-sm text-slate-300 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-700 file:px-4 file:py-2 file:font-medium file:text-white hover:file:bg-slate-600">
            <p class="mt-2 text-xs text-slate-500">PNG, JPG, WebP, or GIF. Maximum size: 2 MB.</p>
            @error('logo') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
        </div>

        <div class="border-t border-slate-800 pt-6">
            <label for="favicon" class="mb-2 block text-sm font-medium">Browser tab icon (favicon)</label>
            @if ($siteSettings->favicon_path)
                <div class="mb-4 flex items-center gap-4">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->favicon_path) }}" alt="Current browser tab icon" class="h-10 w-10 rounded border border-slate-700 bg-white object-contain p-1">
                    <label class="flex items-center gap-2 text-sm text-slate-300">
                        <input type="checkbox" name="remove_favicon" value="1" class="rounded border-slate-600 bg-slate-950 text-rose-500">
                        Remove current favicon
                    </label>
                </div>
            @endif
            <input id="favicon" name="favicon" type="file" accept=".ico,image/png,image/jpeg,image/webp"
                class="block w-full text-sm text-slate-300 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-700 file:px-4 file:py-2 file:font-medium file:text-white hover:file:bg-slate-600">
            <p class="mt-2 text-xs text-slate-500">ICO or PNG recommended. JPG and WebP are also supported. Maximum size: 1 MB.</p>
            @error('favicon') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
        </div>

        <div class="border-t border-slate-800 pt-6">
            <label for="queue_name_visibility" class="mb-2 block text-sm font-medium">Student names in consultation queues</label>
            <select id="queue_name_visibility" name="queue_name_visibility" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3">
                @foreach(['teacher_choice' => 'Let each teacher choose', 'hide_all' => 'Hide student names school-wide', 'show_all' => 'Show student names school-wide'] as $value => $label)<option value="{{ $value }}" @selected(old('queue_name_visibility', $siteSettings->queue_name_visibility) === $value)>{{ $label }}</option>@endforeach
            </select>
            <p class="mt-2 text-xs text-slate-400">School-wide rules override teacher preferences immediately. Teachers and authorized administrators can always see names; students always recognize their own bookings. Consultation reasons stay private.</p>
            @error('queue_name_visibility')<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="rounded-lg bg-rose-500 px-5 py-3 font-semibold transition hover:bg-rose-400">Save settings</button>
    </form>
@endsection
