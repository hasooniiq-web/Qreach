<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['payment_id', 'gateway', 'event_key', 'type', 'signature_valid', 'outcome', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'signature_valid' => 'boolean'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
