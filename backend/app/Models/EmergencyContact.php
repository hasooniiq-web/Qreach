<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sensitive by definition — never serialize this model in any public-facing
 * response. See docs/SECURITY.md.
 */
class EmergencyContact extends Model
{
    protected $fillable = ['user_id', 'name', 'relationship', 'phone', 'priority', 'status', 'is_public'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
