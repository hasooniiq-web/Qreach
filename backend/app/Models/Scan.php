<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scan extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['tag_id', 'vehicle_id', 'action_type', 'payload', 'status', 'request_ip', 'user_agent'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
