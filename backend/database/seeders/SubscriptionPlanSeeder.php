<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * App targets Iraq — currency is IQD. The Iraqi dinar's minor unit (fils)
 * isn't used in everyday pricing, so `price_cents` here stores whole IQD
 * amounts directly rather than a fils subdivision (a deliberate choice
 * for this currency, not a bug — see docs/ARCHITECTURE_DECISIONS.md).
 * These are illustrative placeholder prices; no payment gateway is wired
 * up yet (no PSP was specified) — see docs/ARCHITECTURE_DECISIONS.md.
 */
class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionPlan::updateOrCreate(['slug' => 'free'], [
            'name_ar' => 'مجاني', 'name_en' => 'Free', 'price_cents' => 0,
            'currency' => 'IQD', 'billing_period_days' => 365,
            'max_vehicles' => 1, 'max_tags' => 1, 'is_active' => true,
        ]);

        SubscriptionPlan::updateOrCreate(['slug' => 'standard'], [
            'name_ar' => 'أساسي', 'name_en' => 'Standard', 'price_cents' => 25000,
            'currency' => 'IQD', 'billing_period_days' => 365,
            'max_vehicles' => 3, 'max_tags' => 5, 'is_active' => true,
        ]);
    }
}
