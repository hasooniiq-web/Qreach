<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A reminder that was sent for one subscription period (see SubscriptionReminders). */
class SubscriptionNotice extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['subscription_id', 'kind', 'period_ends_at'];

    protected function casts(): array
    {
        return ['period_ends_at' => 'datetime'];
    }
}
