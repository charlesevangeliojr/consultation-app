@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-rose-400">Overview</p>
    <h2 class="mt-3 text-3xl font-bold sm:text-4xl">Welcome, {{ auth()->user()->name }}</h2>
    <p class="mt-3 max-w-2xl text-slate-400">You are signed in with administrator privileges. Use the sidebar to manage your admin area.</p>

    <section class="mt-10 grid gap-5 sm:grid-cols-2">
        <article class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
            <p class="text-sm text-slate-400">Signed in as</p>
            <p class="mt-2 break-all text-lg font-semibold">{{ auth()->user()->email }}</p>
        </article>
        <article class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
            <p class="text-sm text-slate-400">Account role</p>
            <p class="mt-2 text-lg font-semibold text-rose-300">{{ auth()->user()->isAdmin() ? 'Full access administrator' : (auth()->user()->hasAdminAccess() ? 'Custom module access' : 'Standard account') }}</p>
        </article>
    </section>
@endsection
