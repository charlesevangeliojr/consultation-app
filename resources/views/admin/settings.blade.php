@extends('admin.layout')
@section('title', 'School settings')
@section('content')
@php($canEditSettings = auth()->user()->canAccess('settings', 'edit'))
<div class="mx-auto max-w-5xl">
    <header><p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-300">Configuration</p><h2 class="mt-3 text-3xl font-bold sm:text-4xl">School settings</h2><p class="mt-4 max-w-2xl text-sm leading-7 text-slate-400">Keep school branding, queue privacy, and your device notifications organized in one place. School changes are saved together; device controls apply separately.</p></header>
    <nav aria-label="Settings sections" class="mt-6 flex flex-wrap gap-2">
        <a href="#branding-settings" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-xs font-semibold"><span class="text-rose-300">01</span> School identity</a>
        <a href="#queue-privacy-settings" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-xs font-semibold"><span class="text-rose-300">02</span> Queue privacy</a>
        <a href="#notification-settings" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-xs font-semibold"><span class="text-rose-300">03</span> Device notifications</a>
    </nav>
    @if(session('status'))<p role="status" class="mt-6 rounded-xl border border-emerald-900 bg-emerald-950/50 p-4 text-sm text-emerald-200">{{ session('status') }}</p>@endif
    @if(!$canEditSettings)<p class="mt-5 rounded-xl border border-slate-700 p-4 text-sm text-slate-400">School settings are read-only for your account. You can still manage notifications on your own device.</p>@endif

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="mt-7 space-y-6" aria-label="School settings form">
        @csrf @method('PUT')
        <section id="branding-settings" class="scroll-mt-24 rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-7" aria-labelledby="branding-heading">
            <header class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-rose-500/10 text-xs font-semibold text-rose-300">01</span><div><h3 id="branding-heading" class="text-xl font-semibold">School identity</h3><p class="mt-2 text-xs leading-6 text-slate-400">The name and images used on public pages, sign-in screens, and the installed app.</p></div></header>
            <fieldset @disabled(!$canEditSettings) class="mt-6 min-w-0"><legend class="sr-only">School identity fields</legend>
                <div><label for="name" class="mb-2 block text-sm font-medium">School / app name</label><input id="name" name="name" type="text" value="{{ old('name', $siteSettings->name) }}" required maxlength="80" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm outline-none focus:border-rose-400">@error('name')<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror</div>
                <div class="mt-6 grid min-w-0 gap-5 md:grid-cols-2">
                    <div class="min-w-0 rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                        <label for="logo" class="block text-sm font-semibold">School / app logo</label>
                        <div class="my-4 flex min-h-20 items-center gap-4 rounded-xl border border-slate-700 p-3">
                            @if($siteSettings->logo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->logo_path) }}" alt="Current school logo" class="h-16 w-16 shrink-0 rounded-lg bg-white object-contain p-2">@else<span class="grid h-16 w-16 shrink-0 place-items-center rounded-lg bg-rose-500/10 text-xl font-bold text-rose-300" aria-hidden="true">{{ mb_substr($siteSettings->name ?: 'S', 0, 1) }}</span>@endif
                            <div class="min-w-0"><p class="text-xs text-slate-400">Saved logo</p><p class="mt-1 text-xs leading-6 text-slate-300">{{ $siteSettings->logo_path ? 'Also used for the installed PWA icon.' : 'Default icon is currently used.' }}</p></div>
                        </div>
                        <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full min-w-0 max-w-full text-xs text-slate-300 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-700 file:px-3 file:py-2.5 file:text-xs file:font-medium file:text-white">
                        <p class="mt-3 text-xs leading-6 text-slate-400">PNG, JPG, WebP, or GIF · up to 2 MB. A square 512 × 512 logo is recommended; keep it within 2000 × 2000 pixels for shared hosting.</p>
                        @if($siteSettings->logo_path)<label class="mt-3 flex min-h-11 items-center gap-2 text-xs text-slate-300"><input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))> Remove current logo</label>@endif
                        @error('logo')<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror
                    </div>
                    <div class="min-w-0 rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                        <label for="favicon" class="block text-sm font-semibold">Browser tab icon</label>
                        <div class="my-4 flex min-h-20 items-center gap-4 rounded-xl border border-slate-700 p-3">
                            @if($siteSettings->favicon_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->favicon_path) }}" alt="Current browser tab icon" class="h-10 w-10 shrink-0 rounded bg-white object-contain p-1">@else<span class="grid h-10 w-10 shrink-0 place-items-center rounded bg-slate-800 text-xs text-slate-400" aria-hidden="true">Tab</span>@endif
                            <div><p class="text-xs text-slate-400">Saved favicon</p><p class="mt-1 text-xs leading-6 text-slate-300">Shown in browser tabs and bookmarks.</p></div>
                        </div>
                        <input id="favicon" name="favicon" type="file" accept=".ico,image/png,image/jpeg,image/webp" class="block w-full min-w-0 max-w-full text-xs text-slate-300 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-700 file:px-3 file:py-2.5 file:text-xs file:font-medium file:text-white">
                        <p class="mt-3 text-xs leading-6 text-slate-400">ICO or PNG recommended · up to 1 MB. JPG and WebP are also supported. This is separate from the installed app icon.</p>
                        @if($siteSettings->favicon_path)<label class="mt-3 flex min-h-11 items-center gap-2 text-xs text-slate-300"><input type="checkbox" name="remove_favicon" value="1" @checked(old('remove_favicon'))> Remove current favicon</label>@endif
                        @error('favicon')<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror
                    </div>
                </div>
            </fieldset>
            <p class="mt-4 text-xs leading-6 text-slate-400">Choose a replacement or Remove—not both. Changes appear after saving. Existing home-screen icons may require removing and reinstalling the PWA to update, especially on iPhone.</p>
        </section>
        <section id="queue-privacy-settings" class="scroll-mt-24 rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-7" aria-labelledby="queue-privacy-heading">
            <header class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-rose-500/10 text-xs font-semibold text-rose-300">02</span><div><h3 id="queue-privacy-heading" class="text-xl font-semibold">Queue privacy</h3><p class="mt-2 text-xs leading-6 text-slate-400">Choose whether students can see other students’ names in consultation queues.</p></div></header>
            <fieldset @disabled(!$canEditSettings) class="mt-6 min-w-0"><legend class="sr-only">Queue visibility fields</legend><label for="queue_name_visibility" class="mb-2 block text-sm font-medium">Student names in consultation queues</label><select id="queue_name_visibility" name="queue_name_visibility" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm">@foreach(['teacher_choice' => 'Let each teacher choose', 'hide_all' => 'Hide student names school-wide', 'show_all' => 'Show student names school-wide'] as $value => $label)<option value="{{ $value }}" @selected(old('queue_name_visibility', $siteSettings->queue_name_visibility) === $value)>{{ $label }}</option>@endforeach</select>@error('queue_name_visibility')<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror</fieldset>
            <p class="mt-4 rounded-xl border border-slate-700 p-4 text-xs leading-7 text-slate-400">School-wide hide/show rules override teacher preferences when saved. Students can always recognize their own bookings; teachers and authorized administrators can see student names. Consultation reasons are never shown to other students.</p>
        </section>
        @if($canEditSettings)<div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-700 p-4"><p class="text-xs leading-6 text-slate-400">Save school identity and queue privacy together.<br>Device notifications below are managed separately.</p><button type="submit" class="min-h-11 rounded-lg bg-rose-600 px-5 py-3 text-sm font-semibold text-white">Save school settings</button></div>@endif
    </form>

    <section id="notification-settings" class="mt-8 scroll-mt-24" aria-labelledby="device-notifications-heading">
        <header class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-rose-500/10 text-xs font-semibold text-rose-300">03</span><div><h3 id="device-notifications-heading" class="text-xl font-semibold">Device notifications</h3><p class="mt-2 text-xs leading-6 text-slate-400">Personal controls for your current phone or computer—not a school-wide broadcast.</p></div></header>
        @include('partials.notification-device-settings')
        <a href="{{ route('notifications.index') }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-rose-300">View your notification inbox →</a>
    </section>
</div>
@endsection
