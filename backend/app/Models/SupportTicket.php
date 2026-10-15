<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** A technical-support conversation a customer opened from the app. */
class SupportTicket extends Model
{
    public const CATEGORIES = ['general', 'account', 'vehicle', 'payment', 'bug', 'other'];

    /** Tickets one customer may have waiting for support at the same time. */
    public const MAX_OPEN = 3;

    protected $fillable = ['user_id', 'category', 'subject', 'status', 'last_activity_at', 'closed_at'];

    protected function casts(): array
    {
        return ['last_activity_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $ticket) {
            $ticket->uuid ??= (string) Str::uuid();
            $ticket->last_activity_at ??= now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('id');
    }

    public function addMessage(string $sender, string $body, ?string $imagePath = null, ?int $adminId = null): SupportMessage
    {
        $message = $this->messages()->create([
            'sender' => $sender, 'body' => $body, 'image_path' => $imagePath, 'admin_id' => $adminId,
            'created_at' => now(),
        ]);
        $this->forceFill(['last_activity_at' => now()])->save();

        return $message;
    }
}
