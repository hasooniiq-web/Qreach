<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Profile cover photos were removed from the product (they only added storage
 * for little value). Deletes any that were already uploaded and clears the
 * columns. Safe to run again.
 */
class PurgeProfileCovers extends Command
{
    protected $signature = 'profile:purge-covers';

    protected $description = 'Delete previously uploaded profile cover photos';

    public function handle(): int
    {
        $removed = 0;

        User::whereNotNull('cover_path')->chunkById(200, function ($users) use (&$removed) {
            foreach ($users as $user) {
                Storage::disk('local')->delete($user->cover_path);
                $user->forceFill(['cover_path' => null, 'show_cover_publicly' => false])->save();
                $removed++;
            }
        });

        // Anything left in the folder with no owner pointing at it.
        foreach (Storage::disk('local')->files('user-covers') as $file) {
            Storage::disk('local')->delete($file);
        }

        $this->info("Removed {$removed} cover photo(s).");

        return self::SUCCESS;
    }
}
