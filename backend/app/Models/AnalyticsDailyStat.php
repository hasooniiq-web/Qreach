<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsDailyStat extends Model
{
    protected $fillable = ['date', 'metric_key', 'dimension', 'value'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
