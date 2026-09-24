<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TagBatch extends Model
{
    protected $fillable = ['batch_code', 'tag_type', 'quantity', 'status', 'created_by', 'notes', 'generated_at', 'exported_at'];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'exported_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $batch) {
            $batch->uuid ??= (string) Str::uuid();
        });
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class, 'batch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
