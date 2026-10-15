<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ContactRequest extends Model
{
    protected $fillable = [
        'vehicle_id', 'tag_id', 'reporter_user_id', 'visitor_name', 'visitor_phone', 'message', 'status',
        'request_ip', 'user_agent', 'read_at', 'responded_at', 'last_activity_at', 'reason',
    ];

    /** Conversations are deleted this many days after they were started (their first message). */
    public const RETENTION_DAYS = 7;

    /** Most messages a single conversation may hold (both sides together). */
    public const MAX_MESSAGES = 60;

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'responded_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $request) {
            $request->uuid ??= (string) Str::uuid();
            $request->last_activity_at ??= now();
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ContactRequestMessage::class)->orderBy('id');
    }

    public function expiresAt(): Carbon
    {
        // From the conversation's FIRST message (its creation), not the last: a chat that keeps
        // going must still end, so nothing lives longer than RETENTION_DAYS.
        return $this->created_at->copy()->addDays(self::RETENTION_DAYS);
    }

    /** Adds a message to the conversation and refreshes its retention clock. */
    public function addMessage(string $sender, string $body, ?string $imagePath = null): ContactRequestMessage
    {
        $message = $this->messages()->create(['sender' => $sender, 'body' => $body, 'image_path' => $imagePath]);
        $this->forceFill(['last_activity_at' => now()])->save();

        return $message;
    }

    /** The customer who started this conversation from the app (null for a web visitor). */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
