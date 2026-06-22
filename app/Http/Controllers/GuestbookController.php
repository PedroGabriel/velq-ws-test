<?php

namespace App\Http\Controllers;

use App\Events\MessagePosted;
use App\Jobs\ProcessMessage;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GuestbookController extends Controller
{
    /**
     * Show the guestbook: the message list + a cached total counter.
     */
    public function index(): View
    {
        $messages = Message::query()->latest()->limit(50)->get();

        // CACHE (standard Cache::): the total is cached for 10s. On Velq this is
        // the default database cache store; see the README for the KV note.
        $total = Cache::remember('messages.total', 10, fn () => Message::count());

        return view('guestbook', [
            'messages' => $messages,
            'total' => $total,
        ]);
    }

    /**
     * Post a message: store it (DB), optionally upload an attachment (Files /
     * s3 -> Velq R2), broadcast it live (WebSockets), and dispatch a queued job
     * to process it asynchronously (Queue).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'author' => ['required_without:_auth', 'nullable', 'string', 'max:80'],
            'attachment' => ['nullable', 'image', 'max:5120'],
        ]);

        $author = Auth::check() ? Auth::user()->name : ($data['author'] ?? 'Guest');

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            // FILES: standard Storage on the 's3' disk, which velq-init points at
            // Velq R2 via the injected AWS_* + AWS_ENDPOINT env. Stored under public/ so the Velq
            // public-file proxy (AWS_URL=.../_files) serves it: only the public/ prefix is world-readable;
            // any other prefix 404s (a coarse public/private split by key, see files_proxy.go).
            $attachmentPath = $request->file('attachment')->store('public/attachments', 's3');
        }

        $message = Message::create([
            'user_id' => Auth::id(),
            'author' => $author,
            'body' => $data['body'],
            'attachment_path' => $attachmentPath,
        ]);

        // WEBSOCKETS: broadcast over the standard pusher broadcaster (Velq edge).
        // ShouldBroadcastNow runs the publish synchronously, so the live update
        // does not depend on a queue worker. The publish is wrapped so a
        // transport error (e.g. before Velq injects PUSHER_HOST) cannot break
        // posting; the message is already saved and the queue job still runs.
        try {
            broadcast(new MessagePosted($message));
        } catch (\Throwable $e) {
            Log::warning('Live broadcast failed; message still posted.', ['error' => $e->getMessage()]);
        }

        // QUEUE: process the message asynchronously (database queue).
        ProcessMessage::dispatch($message->id);

        // Invalidate the cached counter so the next page load is fresh.
        Cache::forget('messages.total');

        return redirect()->route('guestbook')->with('status', 'Message posted.');
    }
}
