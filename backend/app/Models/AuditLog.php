<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only. Never update or delete rows here except via a documented
 * retention job (section 52).
 */
class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'actor_type', 'actor_id', 'action', 'resource_type', 'resource_id',
        'request_ip', 'user_agent', 'result', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
