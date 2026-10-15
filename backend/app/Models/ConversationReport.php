<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A complaint about the other side of a conversation, filed from the app and read in the dashboard. */
class ConversationReport extends Model
{
    public const REASONS = ['harassment', 'spam', 'abuse', 'scam', 'other'];

    protected $fillable = [
        'contact_request_id', 'reported_by_user_id', 'reported_by_role', 'reported_user_id',
        'reason', 'details', 'status', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ContactRequest::class, 'contact_request_id');
    }

    public function filedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }
}
