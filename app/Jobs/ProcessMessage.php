<?php

namespace App\Jobs;

use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Standard queued job (QUEUE_CONNECTION=database). Dispatched when a message is
 * posted; runs asynchronously by a queue worker. It "scans" the optional
 * attachment (reads its size from the s3/R2 disk) and marks the row processed,
 * proving the work happens off the request path.
 */
class ProcessMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $messageId)
    {
    }

    public function handle(): void
    {
        $message = Message::find($this->messageId);

        if (! $message) {
            return;
        }

        if ($message->attachment_path) {
            // "Scan" the attachment: confirm it exists on the s3 (Velq R2) disk.
            $disk = Storage::disk('s3');
            if ($disk->exists($message->attachment_path)) {
                $message->attachment_scanned = true;
                $message->save();
            }
        } else {
            $message->attachment_scanned = true;
            $message->save();
        }

        // Bump a cached "processed" counter so the async work is observable.
        // add() seeds the key first so increment() works on every cache store
        // (the database store does not auto-create on a bare increment).
        Cache::add('messages.processed', 0);
        Cache::increment('messages.processed');
    }
}
