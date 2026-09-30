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
        'vehicle_id', 'tag_id', 'visitor_name', 'visitor_phone', 'message', 'status',
        'request_ip', 'user_agent', 'read_at', 'responded_at', 'last_activity_at', 'reason',
    ];

    /** Conversations are deleted this many days after their last activity. */
    public const RETENTION_DAYS = 15;

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
        return ($this->last_activity_at ?? $this->created_at)->copy()->addDays(self::RETENTION_DAYS);
    }

    /** Adds a message to the conversation and refreshes its 15-day clock. */
    public function addMessage(string $sender, string $body, ?string $imagePath = null): ContactRequestMessage
    {
        $message = $this->messages()->create(['sender' => $sender, 'body' => $body, 'image_path' => $imagePath]);
        $this->forceFill(['last_activity_at' => now()])->save();

        return $message;
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
