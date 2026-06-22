@extends('layouts.app')

@section('title', 'Velq Guestbook')

@section('content')
    <header class="mb-8 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Velq Guestbook</h1>
            <p class="mt-1 text-sm text-slate-500">
                A default Laravel app on Velq: Database, Cache, Files, Queue, Mail and live WebSockets.
            </p>
        </div>
        <div class="text-right">
            @auth
                <p class="text-sm text-slate-600">{{ auth()->user()->name }}</p>
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button class="text-sm font-medium text-slate-500 hover:text-slate-800">Sign out</button>
                </form>
            @else
                <a href="{{ route('auth') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Sign in / Sign up
                </a>
            @endauth
        </div>
    </header>

    @if (session('status'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex items-center gap-4 text-sm">
        <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700">
            Messages: <span id="message-count">{{ $total }}</span>
        </span>
        <span class="flex items-center gap-1.5 text-slate-400">
            <span class="inline-block h-2 w-2 rounded-full bg-current"></span>
            <span id="live-status">connecting</span>
        </span>
    </div>

    <form method="POST" action="{{ route('messages.store') }}" enctype="multipart/form-data"
          class="mb-8 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        @csrf
        @guest
            <input type="hidden" name="_auth" value="0">
            <input name="author" placeholder="Your name"
                   class="mb-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                   value="{{ old('author') }}" maxlength="80">
        @endguest

        <textarea name="body" rows="3" placeholder="Write a message..."
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                  maxlength="2000">{{ old('body') }}</textarea>

        @error('body')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror

        <div class="mt-3 flex items-center justify-between gap-3">
            <input type="file" name="attachment" accept="image/*"
                   class="text-xs text-slate-500 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-slate-700">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Post
            </button>
        </div>
        @error('attachment')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </form>

    <ul id="messages" class="space-y-3">
        @forelse ($messages as $message)
            <li class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold text-slate-800">{{ $message->author }}</span>
                    <span class="text-xs text-slate-400">{{ $message->created_at?->diffForHumans() }}</span>
                </div>
                <p class="mt-1 whitespace-pre-wrap text-slate-700">{{ $message->body }}</p>
                @if ($message->attachmentUrl())
                    <a href="{{ $message->attachmentUrl() }}" target="_blank" rel="noopener" class="mt-2 block">
                        <img src="{{ $message->attachmentUrl() }}" alt="attachment"
                             class="max-h-48 rounded-lg border border-slate-200">
                    </a>
                @endif
            </li>
        @empty
            <li class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-400">
                No messages yet. Be the first to post.
            </li>
        @endforelse
    </ul>
@endsection
