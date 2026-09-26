<?php

namespace App\Console\Commands;

use App\Models\OtpCode;
use App\Models\Scan;
use App\Models\SecurityEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Data-retention job (spec: retention/anonymization). Raw visitor identifiers
 * are only useful for a short window (abuse investigation), so:
 *  - scans lose their IP and user agent after 90 days and are deleted after 400
 *    (charts read analytics_daily_stats, never raw scans);
 *  - security events lose their IP and user agent after 180 days (the event
 *    itself is kept — it is the audit trail);
 *  - spent OTP codes are deleted a day after they expire.
 * Audit logs are intentionally never pruned here.
 */
class PruneStaleData extends Command
{
    protected $signature = 'data:prune';

    protected $description = 'Anonymize and delete stale raw data per the retention policy';

    public function handle(): int
    {
        $scansAnonymized = Scan::where('created_at', '<', now()->subDays(90))
            ->where(fn ($q) => $q->whereNotNull('request_ip')->orWhereNotNull('user_agent'))
            ->update(['request_ip' => null, 'user_agent' => null]);

        $scansDeleted = Scan::where('created_at', '<', now()->subDays(400))->delete();

        $eventsAnonymized = SecurityEvent::where('created_at', '<', now()->subDays(180))
            ->where(fn ($q) => $q->whereNotNull('request_ip')->orWhereNotNull('user_agent'))
            ->update(['request_ip' => null, 'user_agent' => null]);

        $otpDeleted = OtpCode::where('expires_at', '<', now()->subDay())->delete();

        // Personal-access tokens that expired long ago are dead weight.
        $tokensDeleted = DB::table('personal_access_tokens')
            ->whereNotNull('expires_at')->where('expires_at', '<', now()->subDays(30))->delete();

        $this->info("scans anonymized {$scansAnonymized}, deleted {$scansDeleted}; security events anonymized {$eventsAnonymized}; otp deleted {$otpDeleted}; tokens deleted {$tokensDeleted}");

        return self::SUCCESS;
    }
}
