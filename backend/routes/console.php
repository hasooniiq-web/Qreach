<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Section 43: dashboard charts read only from analytics_daily_stats, never
// from raw transactional tables — this is what keeps that table current.
// Requires a running scheduler (`php artisan schedule:work` in dev, cron ->
// `php artisan schedule:run` every minute in production — see docs/DEPLOYMENT.md).
Schedule::command('analytics:aggregate')->dailyAt('00:15');

// Section 41: automated encrypted backups. Requires BACKUP_ENCRYPTION_KEY
// to be set — the command refuses to run without it rather than silently
// writing an unencrypted dump.
Schedule::command('backup:database')->dailyAt('02:00');

// Visitor conversations live 15 days after their last message.
Schedule::command('contacts:purge')->dailyAt('03:00');

// Retention: anonymize/delete stale raw data.
Schedule::command("data:prune")->dailyAt("03:30");
