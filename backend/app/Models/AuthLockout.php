<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Escalating sign-in lockout state for one account (see LoginLockout). */
class AuthLockout extends Model
{
    protected $fillable = ['scope', 'identifier', 'failures', 'level', 'locked_until', 'last_failure_at'];

    protected function casts(): array
    {
        return [
            'failures' => 'integer',
            'level' => 'integer',
            'locked_until' => 'datetime',
            'last_failure_at' => 'datetime',
        ];
    }
}
