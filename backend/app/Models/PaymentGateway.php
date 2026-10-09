<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One configured payment provider. The credentials are stored encrypted
 * (APP_KEY) and are never sent to a view or the audit log — only whether each
 * one is set.
 */
class PaymentGateway extends Model
{
    protected $fillable = ['driver', 'name', 'is_enabled', 'is_test_mode', 'credentials', 'updated_by'];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_test_mode' => 'boolean',
            'credentials' => 'encrypted:array',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function credential(string $key): ?string
    {
        $value = ($this->credentials ?? [])[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
