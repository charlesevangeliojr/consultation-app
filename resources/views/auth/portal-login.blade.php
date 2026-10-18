<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · {{ $siteSettings->name ?: config('app.name', 'Consultation') }}</title>
    @if($siteSettings->favicon_path)<link rel="icon" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->favicon_path) }}">@endif
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('js/password-visibility.js') }}" defer></script>
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-950 px-5 py-8 text-slate-100">
    <main class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-2xl">
        @include('auth.partials.login-brand')
        <p class="text-xs font-semibold uppercase tracking-[.2em] text-rose-300">Student &amp; teacher sign in</p>
        <h1 class="mt-3 text-3xl font-bold">Welcome back</h1><p class="mt-2 text-sm leading-6 text-slate-400">Sign in with your school account to book or manage consultations.</p>
        @if ($errors->any())<div role="alert" class="mt-5 rounded-lg border border-rose-900 bg-rose-950/50 p-3 text-sm text-rose-200">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('portal.login.store') }}" class="mt-7 space-y-5">@csrf
            <div><label for="login" class="mb-2 block text-sm font-medium">LRN or school email</label><input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus autocomplete="username" maxlength="255" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400"><p class="mt-2 text-xs text-slate-400">Students: use your 12-digit LRN. Teachers: use your school email.</p></div>
            @include('auth.partials.login-password')
            <button class="w-full rounded-lg bg-rose-500 px-4 py-3 font-semibold text-white transition hover:bg-rose-400">Sign in</button>
        </form><a href="{{ url('/') }}" class="mt-6 inline-block text-sm text-slate-400 hover:text-white">← Back to home</a>
    </main>
</body></html>
