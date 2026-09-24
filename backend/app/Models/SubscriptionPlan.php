<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = ['slug', 'name_ar', 'name_en', 'price_cents', 'currency', 'billing_period_days', 'max_vehicles', 'max_tags', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
