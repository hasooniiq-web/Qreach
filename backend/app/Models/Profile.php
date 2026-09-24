<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    protected $fillable = ['user_id', 'avatar_path', 'bio', 'notification_preferences', 'privacy_settings'];

    protected function casts(): array
    {
        return [
            'notification_preferences' => 'array',
            'privacy_settings' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
