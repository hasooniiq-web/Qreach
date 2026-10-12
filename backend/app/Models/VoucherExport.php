<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A record that a batch's serials were exported (or printed), by whom, and for whom. */
class VoucherExport extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['batch_id', 'exported_by', 'format', 'count', 'recipient', 'note'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class, 'batch_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'exported_by');
    }
}
