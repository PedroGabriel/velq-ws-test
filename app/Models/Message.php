<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'author', 'body', 'attachment_path', 'attachment_scanned'])]
class Message extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'attachment_scanned' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Public URL of the attachment on the s3 (Velq R2) disk, or null.
     */
    public function attachmentUrl(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('s3')->url($this->attachment_path);
    }
}
