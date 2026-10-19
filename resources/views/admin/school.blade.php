@extends('admin.layout')
@section('title', 'Grades & sections')
@section('content')
<p class="text-sm font-semibold uppercase tracking-widest text-rose-400">Academic setup</p>
<h2 class="mt-3 text-3xl font-bold">Grades &amp; sections</h2>
<p class="mt-3 text-slate-400">Grades 7–12 are provided automatically. Choose which grades students can select during registration, then manage the sections under each grade.</p>
@if(session('status'))<p role="status" class="mt-5 rounded-lg bg-emerald-950 p-4 text-sm text-emerald-200">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="mt-5 rounded-lg bg-rose-950 p-4 text-sm text-rose-200">@foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>@endif
@php $canEdit = auth()->user()->canAccess('settings', 'edit'); @endphp
<section class="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-5" aria-labelledby="registration-grades-heading">
    <h3 id="registration-grades-heading" class="text-xl font-semibold">Grades shown in registration</h3>
    <p id="registration-grades-help" class="mt-3 text-sm leading-6 text-slate-400">Check a grade to show it. Uncheck it to hide the grade and its sections from new registrations. Existing student records are not changed. Add at least one section for every grade you offer.</p>
    <form method="POST" action="{{ route('admin.school.registration-grades') }}" class="mt-5" aria-describedby="registration-grades-help">
        @csrf @method('PUT')
        <fieldset @disabled(!$canEdit)><legend class="sr-only">Show in student registration</legend><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($gradeLevels as $grade)
                <label class="flex min-h-16 cursor-pointer items-center gap-3 rounded-xl border border-slate-700 bg-slate-950 p-4">
                    <input type="checkbox" name="grades[]" value="{{ $grade->id }}" @checked(session()->hasOldInput() ? in_array($grade->id, old('grades', [])) : $grade->registration_enabled) class="h-5 w-5 shrink-0 accent-rose-500" aria-label="Show {{ $grade->name }} in registration">
                    <span><strong class="block text-sm">{{ $grade->name }}</strong><span class="mt-1 block text-xs text-slate-400">{{ $grade->sections->count() }} {{ \Illuminate\Support\Str::plural('section', $grade->sections->count()) }}</span></span>
                </label>
            @endforeach
        </div></fieldset>
        @if($canEdit)<button type="submit" class="mt-5 min-h-11 rounded-lg bg-rose-500 px-4 py-3 text-sm font-semibold text-white">Save registration grades</button>@endif
        <p class="mt-3 text-xs text-slate-400">Leave all grades unchecked to pause new student registration. Grade names are fixed and cannot be added, renamed, or deleted here.</p>
    </form>
</section>
<section class="mt-6 rounded-2xl border border-slate-800 bg-slate-900 p-5">
    <h3 class="text-xl font-semibold">Sections</h3>
    @if($canEdit)<form method="POST" action="{{ route('admin.school.store', 'sections') }}" class="mt-4 flex flex-wrap gap-3">@csrf
        <select name="grade_level_id" required aria-label="Section grade level" class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2"><option value="">Select grade level</option>@foreach($gradeLevels as $grade)<option value="{{ $grade->id }}">{{ $grade->name }}</option>@endforeach</select>
        <input name="name" required maxlength="100" placeholder="New section name" aria-label="New section name" class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
        <button class="rounded-lg bg-rose-500 px-4 py-2 text-sm font-semibold">Add section</button>
    </form>@endif
    <div class="mt-6 grid gap-6 sm:grid-cols-2">
        @foreach($gradeLevels as $grade)<div><h4 class="font-semibold text-slate-300">{{ $grade->name }}</h4><div class="mt-3 space-y-2">
            @forelse($grade->sections->sortBy('name') as $section)<div class="flex items-center gap-2">
                @if($canEdit)<form method="POST" action="{{ route('admin.school.update', ['sections', $section->id]) }}" class="flex min-w-0 flex-1 gap-2">@csrf @method('PUT')<input name="name" value="{{ $section->name }}" required maxlength="100" aria-label="Rename section {{ $section->name }}" class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"><button class="rounded-lg border border-slate-700 px-3 py-2 text-xs">Save</button></form>
                <form method="POST" action="{{ route('admin.school.destroy', ['sections', $section->id]) }}">@csrf @method('DELETE')<button class="text-xs text-rose-300">Delete</button></form>
                @else<p class="text-sm">{{ $section->name }}</p>@endif
            </div>@empty<p class="text-sm text-slate-500">No sections.</p>@endforelse
        </div></div>@endforeach
    </div>
</section>
@endsection
