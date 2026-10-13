<?php

namespace Tests\Feature\Public;

use App\Models\Admin;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Services\VoucherService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * The public site: the landing page in three languages and the reseller's
 * batch look-up (counts only, behind batch number + access code).
 */
class LandingAndResellerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
        RateLimiter::clear('reseller-portal-fail:127.0.0.1');
        $this->withoutVite();
    }

    private function plus(): SubscriptionPlan
    {
        return SubscriptionPlan::where('slug', 'plus')->firstOrFail();
    }

    private function batch(int $quantity = 10): VoucherBatch
    {
        return app(VoucherService::class)->generate($this->plus(), $quantity, 365, 'شركة النور', null, null, null);
    }

    // ---- Landing ---------------------------------------------------------------

    public function test_the_landing_page_is_arabic_and_rtl_by_default(): void
    {
        $this->withHeaders(['Accept-Language' => ''])->get('/')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl"', false)
            ->assertSee('ملصق واحد على سيارتك')
            ->assertSee('hreflang="en"', false);
    }

    public function test_the_language_switch_gives_english_ltr_and_kurdish_rtl(): void
    {
        $this->get('/?lang=en')->assertOk()
            ->assertSee('<html lang="en" dir="ltr"', false)
            ->assertSee('One tag on your car');

        $this->get('/?lang=ckb')->assertOk()
            ->assertSee('<html lang="ckb" dir="rtl"', false)
            ->assertSee('یەک ستیکەر لەسەر ئۆتۆمبێلەکەت');
    }

    public function test_the_chosen_language_is_remembered_and_unknown_codes_are_ignored(): void
    {
        $this->get('/?lang=en')->assertCookie('qreach_lang');
        $this->withUnencryptedCookie('qreach_lang', 'en')->get('/')->assertSee('One tag on your car');
        $this->get('/?lang=xx')->assertOk()->assertDontSee('lang="xx"', false);
    }

    public function test_plan_prices_and_texts_come_from_the_dashboard_per_language(): void
    {
        $this->plus()->update([
            'price_cents' => 31000, 'name_en' => 'Plus Annual', 'description_en' => 'Everything unlocked',
            'name_ar' => 'بلس السنوية', 'description_ar' => 'كل شيء مفتوح',
        ]);

        $this->get('/?lang=en')->assertSee('31,000')->assertSee('Plus Annual')->assertSee('Everything unlocked');
        $this->get('/?lang=ar')->assertSee('31,000')->assertSee('بلس السنوية')->assertSee('كل شيء مفتوح');
    }

    public function test_the_trial_is_advertised_only_while_it_is_on(): void
    {
        $this->get('/?lang=en')->assertSee('Try Plus free for 7 days');

        SystemSetting::set('plus_trial_days', 0);
        Cache::flush();
        $this->get('/?lang=en')->assertDontSee('Try Plus free');
    }

    public function test_store_buttons_appear_only_when_configured(): void
    {
        $this->get('/?lang=en')->assertDontSee('Download on the App Store');

        config(['landing.ios_url' => 'https://apps.apple.com/app/id123']);
        $this->get('/?lang=en')->assertSee('Download on the App Store')->assertSee('https://apps.apple.com/app/id123');
    }

    public function test_the_faq_says_the_basic_service_is_free_for_ten_years_in_every_language(): void
    {
        $this->get('/?lang=ar')->assertSee('كم مدة الخدمة المجانية؟')->assertSee('عشر سنوات');
        $this->get('/?lang=en')->assertSee('How long is the free service?')->assertSee('free for ten years');
        $this->get('/?lang=ckb')->assertSee('بێبەرامبەر')->assertSee('دە ساڵ');
    }

    public function test_the_admin_login_still_works_at_its_own_address(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    // ---- Reseller look-up ---------------------------------------------------------

    public function test_the_form_is_public_in_three_languages(): void
    {
        $this->get('/reseller?lang=ar')->assertOk()->assertSee('رقم الدفعة')->assertSee('رمز الاطلاع');
        $this->get('/reseller?lang=en')->assertOk()->assertSee('Batch number');
        $this->get('/reseller?lang=ckb')->assertOk()->assertSee('ژمارەی دەستە');
    }

    public function test_the_right_batch_number_and_code_show_counts_only(): void
    {
        $batch = $this->batch(10);
        $svc = app(VoucherService::class);
        $codes = $batch->vouchers()->get()->map(fn (Voucher $v) => (string) $v->fresh()->code);
        foreach ($codes->take(3) as $code) {
            $svc->redeem(User::factory()->create(['name' => 'سري جدًا']), $code);
        }

        $response = $this->post('/reseller?lang=en', ['batch' => strtolower($batch->batch_code), 'code' => strtolower($batch->portalCodeFormatted())]);

        $response->assertOk()
            ->assertSee('Activated')->assertSee('Remaining')
            ->assertSee('30% of the batch is activated');
        $body = $response->getContent();
        $this->assertStringNotContainsString('سري جدًا', $body);
        foreach ($codes as $code) {
            $this->assertStringNotContainsString($code, $body, 'A voucher serial must never be shown.');
        }
    }

    public function test_a_wrong_code_and_an_unknown_batch_look_the_same(): void
    {
        $batch = $this->batch(2);

        $wrongCode = $this->post('/reseller?lang=en', ['batch' => $batch->batch_code, 'code' => 'AAAAAAAA']);
        $unknown = $this->post('/reseller?lang=en', ['batch' => 'VB-20000101-999', 'code' => $batch->portal_code]);

        $wrongCode->assertStatus(422)->assertSee('The batch number or access code is not correct');
        $unknown->assertStatus(422)->assertSee('The batch number or access code is not correct');
    }

    public function test_repeated_wrong_attempts_lock_the_address_out_even_for_the_right_code(): void
    {
        $batch = $this->batch(2);

        // Eight real wrong answers inside the hour (the per-minute throttle is set aside here) ...
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        foreach (range(1, 8) as $i) {
            $this->post('/reseller', ['batch' => $batch->batch_code, 'code' => 'BADBADBA'])->assertStatus(422);
        }
        $this->assertSame(1, \App\Models\SecurityEvent::where('event_type', \App\Services\SecurityEventLogger::QR_GUESSING)->count());

        // ... and even the right code is refused until the hour has passed.
        $this->post('/reseller', ['batch' => $batch->batch_code, 'code' => $batch->portal_code])
            ->assertStatus(429);
    }

    public function test_new_and_existing_batches_have_an_access_code_and_the_dashboard_shows_it(): void
    {
        $batch = $this->batch(1);
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{8}$/', $batch->portal_code);

        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        $this->actingAs($admin, 'admin')->get('/admin/vouchers/batches/'.$batch->id)
            ->assertOk()->assertSee('بوابة الموزّع')->assertSee($batch->portalCodeFormatted());

        $old = $batch->portal_code;
        $this->actingAs($admin, 'admin')->post('/admin/vouchers/batches/'.$batch->id.'/portal-code')->assertRedirect();
        $this->assertNotSame($old, $batch->fresh()->portal_code);

        // The old code no longer opens the batch.
        $this->post('/reseller', ['batch' => $batch->batch_code, 'code' => $old])->assertStatus(422);
    }
}
