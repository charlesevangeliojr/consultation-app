@extends('admin.layout')

@section('title', 'Edit Profile')

@section('content')
    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-rose-400">Your account</p>
    <h2 class="mt-3 text-3xl font-bold sm:text-4xl">Edit Profile</h2>
    <p class="mt-3 text-slate-400">Update your personal details and sign-in password.</p>

    @if (session('status'))
        <p role="status" class="mt-6 max-w-2xl rounded-lg border border-emerald-900 bg-emerald-950/50 p-4 text-sm text-emerald-200">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('admin.profile.update') }}" class="mt-8 max-w-2xl space-y-6 rounded-2xl border border-slate-800 bg-slate-900 p-6">
        @csrf
        @method('PUT')
        <div>
            <label for="name" class="mb-2 block text-sm font-medium">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name"
                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400">
            @error('name') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="email" class="mb-2 block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email"
                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400">
            @error('email') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
        </div>
        <div class="border-t border-slate-800 pt-5">
            <h3 class="font-semibold">Change password <span class="font-normal text-slate-400">(optional)</span></h3>
            <label for="password" class="mb-2 mt-4 block text-sm font-medium">New password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400">
            @error('password') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
            <label for="password_confirmation" class="mb-2 mt-4 block text-sm font-medium">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-rose-400">
        </div>
        <button type="submit" class="rounded-lg bg-rose-500 px-5 py-3 font-semibold transition hover:bg-rose-400">Save profile</button>
    </form>
@endsection
