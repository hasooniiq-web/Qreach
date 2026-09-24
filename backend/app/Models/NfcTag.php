<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfcTag extends Model
{
    protected $fillable = ['tag_id', 'chip_uid', 'rewritable', 'provisioning_status'];

    protected function casts(): array
    {
        return [
            'rewritable' => 'boolean',
            'provisioned_at' => 'datetime',
        ];
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
