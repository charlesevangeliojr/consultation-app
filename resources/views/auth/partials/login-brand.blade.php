<div class="mb-6 flex min-w-0 items-center gap-3" data-login-brand>
    @if($siteSettings->logo_path)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->logo_path) }}" alt="" class="h-12 w-12 shrink-0 rounded-xl border border-slate-700 bg-slate-950 object-contain p-1.5">
    @else
        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl border border-rose-400/20 bg-rose-500/10 text-xl font-bold text-rose-300" aria-hidden="true">{{ mb_substr($siteSettings->name ?: config('app.name', 'Consultation'), 0, 1) }}</span>
    @endif
    <p class="min-w-0 break-words text-base font-semibold leading-snug text-slate-100 [overflow-wrap:anywhere]">{{ $siteSettings->name ?: config('app.name', 'Consultation') }}</p>
</div>
