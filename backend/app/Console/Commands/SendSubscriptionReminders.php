<?php

namespace App\Console\Commands;

use App\Services\SubscriptionReminders;
use Illuminate\Console\Command;

class SendSubscriptionReminders extends Command
{
    protected $signature = 'subscriptions:remind';

    protected $description = 'Send the subscription-ending reminders (30/10/1 days before, at expiry, after the grace week).';

    public function handle(SubscriptionReminders $reminders): int
    {
        $sent = $reminders->run();

        $this->info($sent === [] ? 'No reminders due.' : 'Sent: '.json_encode($sent));

        return self::SUCCESS;
    }
}
