<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionPlan::updateOrCreate(['slug' => 'free'], [
            'name_ar' => 'مجاني', 'name_en' => 'Free', 'price_cents' => 0,
            'currency' => 'SAR', 'billing_period_days' => 365,
            'max_vehicles' => 1, 'max_tags' => 1, 'is_active' => true,
        ]);

        SubscriptionPlan::updateOrCreate(['slug' => 'standard'], [
            'name_ar' => 'أساسي', 'name_en' => 'Standard', 'price_cents' => 4900,
            'currency' => 'SAR', 'billing_period_days' => 365,
            'max_vehicles' => 3, 'max_tags' => 5, 'is_active' => true,
        ]);
    }
}
