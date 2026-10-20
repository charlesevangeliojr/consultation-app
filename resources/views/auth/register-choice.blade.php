<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register · {{ $siteSettings->name ?: 'ConSync' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.theme-head')
</head>
<body class="grid min-h-screen place-items-center bg-slate-950 px-5 py-10 text-slate-100">
    @include('partials.pwa-ui')
    @include('partials.theme-auth-navigation')
    <main class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-7 text-center shadow-2xl sm:p-9">
        <a href="{{ url('/') }}" class="mx-auto inline-flex items-center gap-3 rounded-lg text-xl font-bold tracking-tight text-white">
            @if ($siteSettings->logo_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->logo_path) }}" alt="" class="h-10 w-10 rounded-lg object-contain">
            @else
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-rose-500 text-white" aria-hidden="true">{{ mb_substr($siteSettings->name ?: 'C', 0, 1) }}</span>
            @endif
            <span>{{ $siteSettings->name ?: 'ConSync' }}</span>
        </a>
        <h1 class="mt-8 text-2xl font-bold">Choose your account type</h1>
        <div class="mt-6 grid gap-3">
            <a href="{{ route('register', ['role' => 'student']) }}" class="rounded-lg bg-rose-500 px-5 py-3 font-semibold text-white transition hover:bg-rose-400">Student</a>
            <a href="{{ route('register', ['role' => 'teacher']) }}" class="rounded-lg border border-slate-700 bg-slate-950 px-5 py-3 font-semibold text-white transition hover:border-rose-400 hover:bg-slate-800">Teacher</a>
        </div>
        <a href="{{ url('/') }}" class="mt-6 inline-flex min-h-11 items-center gap-2 rounded-lg px-3 text-sm text-slate-400 transition hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-rose-400"><span aria-hidden="true">←</span> Back to home</a>
    </main>
</body>
</html>
