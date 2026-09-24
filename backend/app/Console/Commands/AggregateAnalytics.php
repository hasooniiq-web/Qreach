<?php

namespace App\Console\Commands;

use App\Models\ContactRequest;
use App\Models\Scan;
use App\Models\TagAssignment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Rolls up a single day's activity from the transactional tables into
 * analytics_daily_stats (section 43). Dashboard charts read only from this
 * table, never from `scans`/`audit_logs` directly, so this stays cheap
 * regardless of how large those tables grow.
 *
 * Idempotent: re-running for the same date overwrites that date's rows
 * (upsert on the date/metric_key/dimension unique key), so a missed or
 * re-run day never double-counts.
 */
class AggregateAnalytics extends Command
{
    protected $signature = 'analytics:aggregate {date? : Y-m-d date to aggregate; defaults to yesterday}';

    protected $description = 'Aggregate scans/registrations/activations/etc. into analytics_daily_stats for one day';

    public function handle(): int
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : Carbon::yesterday();
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $this->info("Aggregating analytics for {$date->toDateString()}...");

        $this->upsert($date, 'scans', 'all', Scan::whereBetween('created_at', [$start, $end])->count());

        foreach (['view', 'vehicle_block', 'lights_on', 'important_message', 'emergency', 'contact_owner'] as $actionType) {
            $count = Scan::whereBetween('created_at', [$start, $end])->where('action_type', $actionType)->count();
            $this->upsert($date, 'scans', $actionType, $count);
        }

        $this->upsert($date, 'registrations', 'all', User::whereBetween('created_at', [$start, $end])->count());

        $this->upsert(
            $date, 'qr_activations', 'all',
            TagAssignment::whereBetween('assigned_at', [$start, $end])->where('event', 'assigned')->count()
        );

        $this->upsert(
            $date, 'contact_requests', 'all',
            ContactRequest::whereBetween('created_at', [$start, $end])->count()
        );

        $this->upsert(
            $date, 'notifications', 'all',
            DatabaseNotification::whereBetween('created_at', [$start, $end])->count()
        );

        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * Deliberately a raw query-builder upsert, not
     * AnalyticsDailyStat::updateOrCreate(). The model's `date` cast
     * normalizes on read but the search array here is a plain string, so
     * updateOrCreate's own SELECT could fail to match an existing row's
     * cast-formatted value and then collide with the unique constraint on
     * the fallback INSERT — confirmed by an idempotency test that
     * genuinely failed with this exact bug before this fix.
     */
    private function upsert(Carbon $date, string $metricKey, string $dimension, int $value): void
    {
        $keys = ['date' => $date->toDateString(), 'metric_key' => $metricKey, 'dimension' => $dimension];

        $updated = DB::table('analytics_daily_stats')->where($keys)
            ->update(['value' => $value, 'updated_at' => now()]);

        if ($updated === 0) {
            DB::table('analytics_daily_stats')->insert($keys + [
                'value' => $value, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
