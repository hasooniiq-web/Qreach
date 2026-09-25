<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactRequestMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['contact_request_id', 'sender', 'body'];

    public function contactRequest(): BelongsTo
    {
        return $this->belongsTo(ContactRequest::class);
    }
}
