<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One invited friend: who invited them and whether the inviter's reward was paid. */
class Referral extends Model
{
    protected $fillable = ['referrer_id', 'referred_id', 'status', 'reward_days', 'rewarded_at'];

    protected function casts(): array
    {
        return ['rewarded_at' => 'datetime', 'reward_days' => 'integer'];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }
}
