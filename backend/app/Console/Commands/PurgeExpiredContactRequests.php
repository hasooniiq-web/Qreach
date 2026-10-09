<?php

namespace App\Console\Commands;

use App\Models\ContactRequest;
use App\Models\ContactRequestMessage;
use App\Models\Scan;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Visitor conversations are deleted ContactRequest::RETENTION_DAYS (7) days
 * after their last message — measured on the server clock (`last_activity_at`),
 * never on a device. Deletion is permanent (hard delete), and covers the copies
 * of the message text kept elsewhere (the scan log and the owner's notification
 * feed) and every attached photo, so nothing outlives the conversation.
 *
 * Safe to re-run at any time: a run that is interrupted leaves rows that the
 * next run simply picks up. Rows are deleted before their files, so a failure
 * can only leave a private orphan file (swept below) — never a row pointing at
 * a missing file. Photos live on the private "local" disk, never a public URL.
 */
class PurgeExpiredContactRequests extends Command
{
    protected $signature = 'contacts:purge';

    protected $description = 'Delete visitor conversations (and their photos) 7 days after their last activity';

    private const IMAGE_DIR = 'contact-message-images';

    public function handle(): int
    {
        $cutoff = now()->subDays(ContactRequest::RETENTION_DAYS);
        $disk = Storage::disk('local');
        $deleted = 0;

        ContactRequest::where('last_activity_at', '<', $cutoff)->select('id')->chunkById(200, function ($chunk) use ($disk, &$deleted) {
            $ids = $chunk->pluck('id');

            $paths = ContactRequestMessage::whereIn('contact_request_id', $ids)
                ->whereNotNull('image_path')->pluck('image_path')->all();

            // cascade removes the messages
            $deleted += DB::transaction(fn () => ContactRequest::whereIn('id', $ids)->delete());

            foreach ($paths as $path) {
                try {
                    $disk->delete($path);
                } catch (\Throwable $e) {
                    // The row is gone; the orphan sweep below retries this file next run.
                    Log::warning('contacts:purge could not delete a photo', ['path' => $path, 'error' => $e->getMessage()]);
                }
            }
        });

        // Copies of the text: keep the scan row (analytics) but drop the message.
        Scan::where('action_type', 'contact_owner')->where('created_at', '<', $cutoff)
            ->whereNotNull('payload')->update(['payload' => null]);

        DatabaseNotification::where('created_at', '<', $cutoff)
            ->where('data', 'like', '%"type":"contact_request"%')->delete();

        $orphans = $this->sweepOrphanPhotos();

        $this->info("Deleted {$deleted} expired conversation(s) and {$orphans} orphan photo(s).");

        return self::SUCCESS;
    }

    /** Photos with no message row (an interrupted upload or delete), older than a day so an in-flight upload is never touched. */
    private function sweepOrphanPhotos(): int
    {
        $disk = Storage::disk('local');
        $referenced = ContactRequestMessage::whereNotNull('image_path')->pluck('image_path')->flip();
        $removed = 0;

        foreach ($disk->files(self::IMAGE_DIR) as $path) {
            if (isset($referenced[$path]) || $disk->lastModified($path) > now()->subDay()->getTimestamp()) {
                continue;
            }
            $disk->delete($path);
            $removed++;
        }

        return $removed;
    }
}
