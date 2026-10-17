@if(auth()->user()->student?->profile_photo_path)
    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->student->profile_photo_path) }}" alt="Student profile picture" class="{{ $avatarSize }} shrink-0 rounded-full border border-slate-700 object-cover">
@else
    <span aria-hidden="true" class="{{ $avatarSize }} grid shrink-0 place-items-center rounded-full border border-slate-700 bg-slate-950 font-semibold text-rose-300">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
@endif
