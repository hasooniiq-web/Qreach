<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A message from the public "Contact us" form. */
class SiteMessage extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'message', 'locale', 'status', 'ip', 'emailed_at', 'read_at'];

    protected function casts(): array
    {
        return ['emailed_at' => 'datetime', 'read_at' => 'datetime'];
    }
}
