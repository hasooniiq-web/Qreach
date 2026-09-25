<?php

namespace App\Console\Commands;

use App\Models\ContactRequest;
use App\Models\Scan;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

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

        // cascade removes the reply messages
        $deleted = ContactRequest::where('last_activity_at', '<', $cutoff)->delete();

        // Copies of the text: keep the scan row (analytics) but drop the message.
        Scan::where('action_type', 'contact_owner')->where('created_at', '<', $cutoff)
            ->whereNotNull('payload')->update(['payload' => null]);

        DatabaseNotification::where('created_at', '<', $cutoff)
            ->where('data', 'like', '%"type":"contact_request"%')->delete();

        $this->info("Deleted {$deleted} expired conversation(s).");

        return self::SUCCESS;
    }
}
