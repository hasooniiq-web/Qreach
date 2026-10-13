<?php

namespace Database\Seeders;

use App\Models\ContactRequest;
use App\Models\SiteMessage;
use App\Models\SubscriptionPlan;
use App\Models\Tag;
use App\Models\TradeCustomer;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\QrBatchService;
use App\Services\SubscriptionManager;
use App\Services\TagLifecycleService;
use App\Services\VoucherService;
use Illuminate\Database\Seeder;

/**
 * Made-up customers, vehicles, tags, vouchers and messages for the TEST environment
 * (test.qreach.online) so every screen has something to show. Everything here is
 * invented: names, phone numbers (+9647 7000 xxxx) and plates are not real people.
 * Never run this on the real database.
 *
 * Needs no Faker, so it works from a `composer install --no-dev` tree.
 */
class DemoDataSeeder extends Seeder
{
    private const FIRST = ['علي', 'حسين', 'محمد', 'مصطفى', 'عمر', 'زينب', 'فاطمة', 'مريم', 'سارة', 'نور', 'أحمد', 'يوسف', 'ليلى', 'هدى', 'كرار'];

    private const LAST = ['الكاظمي', 'الموسوي', 'العبيدي', 'الجبوري', 'الخزرجي', 'التميمي', 'الساعدي', 'الربيعي', 'الحيدري', 'البياتي'];

    private const MAKES = [['Toyota', 'Camry'], ['Hyundai', 'Sonata'], ['Kia', 'Optima'], ['Nissan', 'Sunny'], ['Honda', 'Accord'], ['Chevrolet', 'Malibu']];

    private const COLORS = ['أبيض', 'أسود', 'فضي', 'رمادي', 'أزرق', 'أحمر'];

    public function run(): void
    {
        if (app()->environment('production') || ! config('app.test_banner')) {
            $this->command?->error('The demo data is only for the test environment (APP_TEST_BANNER=true).');

            return;
        }

        $plus = SubscriptionPlan::where('slug', 'plus')->first();
        $subscriptions = app(SubscriptionManager::class);

        // --- Tags: one printed batch of 60 (40 stay unassigned) -------------------------
        $batch = app(QrBatchService::class)->createBatch(60, null);
        app(TagLifecycleService::class)->sendBatchToPrinting($batch, 'system', null);
        app(TagLifecycleService::class)->markBatchUnassigned($batch, 'system', null);
        $tags = Tag::where('batch_id', $batch->id)->orderBy('id')->get();

        // --- 24 customers, most with a vehicle and a linked tag ------------------------------
        $users = collect();
        foreach (range(1, 24) as $i) {
            $user = User::create([
                'phone' => '+96477000'.sprintf('%04d', $i),
                'phone_verified_at' => now()->subDays(40 - $i),
                'name' => self::FIRST[$i % count(self::FIRST)].' '.self::LAST[$i % count(self::LAST)],
                'status' => $i === 24 ? 'suspended' : 'active',
                'locale' => ['ar', 'ar', 'ar', 'en', 'ckb'][$i % 5],
                'timezone' => 'Asia/Baghdad',
                'password' => 'Demo-Pass-12345',
                'show_name_publicly' => $i % 3 !== 0,
            ]);
            $users->push($user);

            if ($i > 20) {
                continue; // a few customers who never added a vehicle
            }

            [$make, $model] = self::MAKES[$i % count(self::MAKES)];
            $vehicle = Vehicle::create([
                'user_id' => $user->id,
                'nickname' => 'سيارتي',
                'plate_number' => sprintf('%05d', 10000 + $i * 37),
                'plate_letter' => ['أ', 'ب', 'ج', 'د'][$i % 4],
                'plate_governorate' => ['بغداد', 'البصرة', 'أربيل', 'النجف', 'كركوك'][$i % 5],
                'plate_country' => 'IQ',
                'make' => $make,
                'model' => $model,
                'year' => 2010 + ($i % 15),
                'color' => self::COLORS[$i % count(self::COLORS)],
                'vehicle_type' => 'sedan',
                'status' => 'active',
            ]);

            if ($i <= 18) {
                app(TagLifecycleService::class)->activate($tags[$i - 1]->public_token, $vehicle, $user);
            }

            // Some visitors left a message for the owner.
            if ($i % 4 === 0) {
                ContactRequest::create([
                    'vehicle_id' => $vehicle->id,
                    'tag_id' => $tags[$i - 1]->id,
                    'visitor_name' => 'زائر تجريبي',
                    'message' => 'السيارة تسد الطريق، يرجى التحريك.',
                    'reason' => 'blocking',
                    'status' => $i % 8 === 0 ? 'read' : 'new',
                    'last_activity_at' => now()->subHours($i),
                ]);
            }
        }

        // --- Subscriptions: a few Plus customers, one invited friend -------------------------
        $adminId = \App\Models\Admin::value('id');
        if ($plus && $adminId) {
            foreach ([1, 2, 3, 5, 8] as $i) {
                $subscriptions->grant($users[$i - 1], $plus, $adminId, 365);
            }
        }
        if ($plus) {
            $subscriptions->startTrial($users[10]);
        }

        // --- Vouchers: a batch sold to a reseller account ---------------------------------------
        $reseller = TradeCustomer::create(['name' => 'شركة البطاقات الرقمية (تجريبية)', 'phone' => '+9647700090001']);
        if ($plus) {
            $vouchers = app(VoucherService::class)->generate($plus, 25, 365, $reseller->name, 'دفعة تجريبية', null, null);
            $vouchers->update(['customer_id' => $reseller->id, 'unit_price' => 15000]);
        }

        // --- Messages from the public site's contact form --------------------------------------
        SiteMessage::create(['name' => 'سلمى', 'email' => 'salma@example.com', 'message' => 'أريد 30 ملصقًا لأسطول سيارات شركتي.', 'locale' => 'ar']);
        SiteMessage::create(['name' => 'Omar', 'phone' => '+9647701112233', 'message' => 'Do you deliver tags to Erbil?', 'locale' => 'en', 'status' => 'read', 'read_at' => now()]);

        $this->command?->info('Demo data created: '.$users->count().' customers, '.Tag::count().' tags.');
    }
}
