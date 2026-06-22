@extends('layouts.app')

@section('title', 'Sign in - Velq Guestbook')

@section('content')
    <header class="mb-8">
        <a href="{{ route('guestbook') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
            &larr; Back to the guestbook
        </a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight">Sign in or sign up</h1>
        <p class="mt-1 text-sm text-slate-500">Sessions persist on the per-instance SQLite. Signup sends a welcome email.</p>
    </header>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 sm:grid-cols-2">
        <form method="POST" action="{{ route('auth.register') }}"
              class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            <h2 class="mb-4 font-semibold text-slate-800">Sign up</h2>
            <input name="name" placeholder="Name" value="{{ old('name') }}"
                   class="mb-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            <input name="email" type="email" placeholder="Email" value="{{ old('email') }}"
                   class="mb-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            <input name="password" type="password" placeholder="Password (min 8)"
                   class="mb-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Create account
            </button>
        </form>

        <form method="POST" action="{{ route('auth.login') }}"
              class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            <h2 class="mb-4 font-semibold text-slate-800">Log in</h2>
            <input name="email" type="email" placeholder="Email"
                   class="mb-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            <input name="password" type="password" placeholder="Password"
                   class="mb-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            <button class="w-full rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
                Sign in
            </button>
        </form>
    </div>
@endsection
