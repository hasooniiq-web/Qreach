<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * Two plans: Basic (free default — visitors can only message the owner) and
 * Pro (messaging plus publicly showing the owner's chosen phone number,
 * Instagram and other contact info).
 *
 * The app targets Iraq, so currency is IQD. Fils aren't used in everyday
 * pricing, so `price_cents` stores whole IQD (see docs/ARCHITECTURE_DECISIONS.md).
 * The Pro price below is a PLACEHOLDER — the real price is a business
 * decision, editable per plan in the dashboard (Plans page). No payment
 * gateway exists yet; upgrades are approved manually by an admin.
 *
 * Existing plan rows are updated in place, except that an admin-edited
 * price is never overwritten on re-seed.
 */
class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionPlan::updateOrCreate(['slug' => SubscriptionPlan::BASIC], [
            'name_ar' => 'الأساسية', 'name_en' => 'Basic',
            'description_ar' => 'التواصل عبر الرسائل فقط — يراسلك الزائر دون أن يرى أي بيانات تواصل مباشرة.',
            'currency' => 'IQD', 'billing_period_days' => 365,
            'max_vehicles' => 1, 'max_tags' => 1, 'is_active' => true, 'sort_order' => 1,
            'features' => ['messaging' => true, 'public_contacts' => []],
        ] + (SubscriptionPlan::where('slug', SubscriptionPlan::BASIC)->exists() ? [] : ['price_cents' => 0]));

        SubscriptionPlan::updateOrCreate(['slug' => SubscriptionPlan::PRO], [
            'name_ar' => 'الاحترافية', 'name_en' => 'Pro',
            'description_ar' => 'الرسائل + عرض رقم هاتفك وحساب إنستغرام ومعلومات تواصل إضافية للزائر مباشرة.',
            'currency' => 'IQD', 'billing_period_days' => 365,
            'max_vehicles' => 5, 'max_tags' => 10, 'is_active' => true, 'sort_order' => 2,
            'features' => ['messaging' => true, 'public_contacts' => SubscriptionPlan::PUBLIC_CONTACT_TYPES],
        ] + (SubscriptionPlan::where('slug', SubscriptionPlan::PRO)->exists() ? [] : ['price_cents' => 25000]));
    }
}
