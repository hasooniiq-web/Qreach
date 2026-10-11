<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Upgrades done by an admin grant or by a payment never created an entry in the
 * upgrade-requests list, so the "approved" tab looked empty while accounts were
 * upgraded. Adds one approved entry for every subscription that has none.
 */
return new class extends Migration
{
    public function up(): void
    {
        $subscriptions = DB::table('subscriptions')->orderBy('id')->get();

        foreach ($subscriptions as $subscription) {
            $exists = DB::table('plan_upgrade_requests')
                ->where('user_id', $subscription->user_id)
                ->where('plan_id', $subscription->plan_id)
                ->whereIn('status', ['approved', 'pending'])
                ->exists();

            if ($exists) {
                continue;
            }

            $when = $subscription->current_period_starts_at ?? $subscription->created_at ?? now();

            DB::table('plan_upgrade_requests')->insert([
                'uuid' => (string) Str::uuid(),
                'user_id' => $subscription->user_id,
                'plan_id' => $subscription->plan_id,
                'status' => 'approved',
                'note' => 'ترقية سابقة (تفعيل مباشر أو دفع)',
                'handled_at' => $when,
                'created_at' => $when,
                'updated_at' => $when,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('plan_upgrade_requests')->where('note', 'ترقية سابقة (تفعيل مباشر أو دفع)')->delete();
    }
};
