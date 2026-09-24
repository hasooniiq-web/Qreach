<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ContactRequest extends Model
{
    protected $fillable = [
        'vehicle_id', 'tag_id', 'visitor_name', 'visitor_phone', 'message', 'status',
        'request_ip', 'user_agent', 'read_at', 'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $request) {
            $request->uuid ??= (string) Str::uuid();
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
