<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'nickname', 'plate_number', 'plate_country', 'make', 'model',
        'year', 'color', 'vehicle_type', 'description', 'status', 'privacy_settings',
    ];

    protected function casts(): array
    {
        return ['privacy_settings' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $vehicle) {
            $vehicle->uuid ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function activeTags(): HasMany
    {
        return $this->tags()->whereIn('status', ['assigned']);
    }

    public function contactRequests(): HasMany
    {
        return $this->hasMany(ContactRequest::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Fields exposed on the public tag page, subject to owner privacy_settings.
     * Server-side enforced — never trust a client-side flag (section 78).
     */
    public function publicAttributes(): array
    {
        $settings = $this->privacy_settings ?? [];
        $allowed = ['nickname', 'make', 'model', 'color', 'vehicle_type'];

        $result = [];
        foreach ($allowed as $field) {
            if ($settings[$field] ?? true) {
                $result[$field] = $this->{$field};
            }
        }

        return $result;
    }
}
