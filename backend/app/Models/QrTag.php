<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrTag extends Model
{
    protected $fillable = ['tag_id', 'error_correction_level', 'image_svg_path', 'image_png_path', 'print_sheet_position'];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
