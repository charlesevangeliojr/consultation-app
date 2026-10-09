<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin login · {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-950 px-6 text-white">
    <main class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-xl">
        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-rose-400">Administration</p>
        <h1 class="mt-3 text-3xl font-bold">Sign in</h1>
        <p class="mt-2 text-slate-400">Use an administrator account to continue.</p>

        @if ($errors->any())
            <div role="alert" class="mt-6 rounded-lg border border-rose-900 bg-rose-950/50 p-3 text-sm text-rose-200">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <label for="email" class="mb-2 block text-sm font-medium">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400">
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-medium">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400">
            </div>
            <button type="submit" class="w-full rounded-lg bg-rose-500 px-4 py-3 font-semibold transition hover:bg-rose-400">Sign in</button>
        </form>
        <a href="{{ url('/') }}" class="mt-6 inline-block text-sm text-slate-400 hover:text-white">← Back to home</a>
    </main>
</body>
</html>
