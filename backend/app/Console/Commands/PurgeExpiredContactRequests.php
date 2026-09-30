<?php

namespace App\Console\Commands;

use App\Models\ContactRequest;
use App\Models\ContactRequestMessage;
use App\Models\Scan;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;

/**
 * Visitor conversations are deleted 15 days after their last message. That
 * includes the copies of the message text kept elsewhere (the scan log and
 * the owner's notification feed), so nothing outlives the conversation.
 */
class PurgeExpiredContactRequests extends Command
{
    protected $signature = 'contacts:purge';

    protected $description = 'Delete visitor conversations 15 days after their last activity';

    public function handle(): int
    {
        $cutoff = now()->subDays(ContactRequest::RETENTION_DAYS);
        $expiringIds = ContactRequest::where('last_activity_at', '<', $cutoff)->pluck('id');

        // Attached photos live on disk, not in the DB row the cascade removes.
        $imagePaths = ContactRequestMessage::whereIn('contact_request_id', $expiringIds)
            ->whereNotNull('image_path')->pluck('image_path');
        Storage::disk('local')->delete($imagePaths->all());

        // cascade removes the reply messages
        $deleted = ContactRequest::whereIn('id', $expiringIds)->delete();

        // Copies of the text: keep the scan row (analytics) but drop the message.
        Scan::where('action_type', 'contact_owner')->where('created_at', '<', $cutoff)
            ->whereNotNull('payload')->update(['payload' => null]);

        DatabaseNotification::where('created_at', '<', $cutoff)
            ->where('data', 'like', '%"type":"contact_request"%')->delete();

        $this->info("Deleted {$deleted} expired conversation(s).");

        return self::SUCCESS;
    }
}
