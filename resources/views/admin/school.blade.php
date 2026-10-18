@extends('admin.layout')
@section('title', 'Academic lists')
@section('content')
<p class="text-sm font-semibold uppercase tracking-widest text-rose-400">School setup</p>
<h2 class="mt-3 text-3xl font-bold">Grade levels &amp; sections</h2>
<p class="mt-3 text-slate-400">These lists appear during registration. A section belongs to one grade level. Used options cannot be deleted.</p>
@if(session('status'))<p role="status" class="mt-5 rounded-lg bg-emerald-950 p-4 text-sm text-emerald-200">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="mt-5 rounded-lg bg-rose-950 p-4 text-sm text-rose-200">@foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>@endif
@php $canEdit = auth()->user()->canAccess('settings', 'edit'); @endphp
<div class="mt-8">
    @foreach(['grade-levels' => ['Grade levels', $gradeLevels]] as $type => [$label, $items])
    <section class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
        <h3 class="text-xl font-semibold">{{ $label }}</h3>
        @if($canEdit)<form method="POST" action="{{ route('admin.school.store', $type) }}" class="mt-4 flex gap-2">@csrf
            <input name="name" required maxlength="100" placeholder="New grade level" aria-label="New {{ $label }} name" class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
            <button class="rounded-lg bg-rose-500 px-4 py-2 text-sm font-semibold">Add</button>
        </form>@endif
        <div class="mt-5 space-y-3">
        @forelse($items as $item)
            <div class="flex flex-wrap items-center gap-2">
                @if($canEdit)
                <form method="POST" action="{{ route('admin.school.update', [$type, $item->id]) }}" class="flex min-w-0 flex-1 gap-2">@csrf @method('PUT')
                    <input name="name" value="{{ $item->name }}" required maxlength="100" aria-label="Rename {{ $item->name }}" class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm">
                    <button class="rounded-lg border border-slate-700 px-3 py-2 text-xs">Save</button>
                </form>
                <form method="POST" action="{{ route('admin.school.destroy', [$type, $item->id]) }}">@csrf @method('DELETE')<button class="px-2 py-2 text-xs text-rose-300">Delete</button></form>
                @else<p>{{ $item->name }}</p>@endif
            </div>
        @empty<p class="text-sm text-slate-500">No options yet.</p>@endforelse
        </div>
    </section>
    @endforeach
</div>
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
