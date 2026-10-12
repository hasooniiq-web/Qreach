<?php

namespace Tests\Feature\Api;

use App\Models\Admin;
use App\Models\PlanUpgradeRequest;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Models\VoucherExport;
use App\Services\PlanEntitlements;
use App\Services\VoucherService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Activation vouchers: 16-digit single-use serials that switch Plus on, made in
 * the dashboard and redeemed from the app (or by staff on a user's page).
 */
class VoucherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    private function plus(): SubscriptionPlan
    {
        return SubscriptionPlan::where('slug', 'plus')->firstOrFail();
    }

    private function admin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        return $admin;
    }

    private function batch(int $quantity = 3, int $days = 365, ?\DateTimeInterface $expires = null, ?string $recipient = 'شركة النور'): VoucherBatch
    {
        return app(VoucherService::class)->generate($this->plus(), $quantity, $days, $recipient, null, $expires, null);
    }

    /** The plain 16 digits of a voucher (decrypted from the database). */
    private function codeOf(Voucher $voucher): string
    {
        return (string) $voucher->fresh()->code;
    }

    private function redeem(User $user, string $code)
    {
        return $this->actingAs($user, 'sanctum')->postJson('/api/v1/subscription/redeem', ['code' => $code]);
    }

    // ---- Serials ---------------------------------------------------------------

    public function test_serials_are_sixteen_digits_unique_and_carry_a_valid_check_digit(): void
    {
        $batch = $this->batch(200);
        $codes = $batch->vouchers()->get()->map(fn (Voucher $v) => $this->codeOf($v));

        $this->assertCount(200, $codes);
        $this->assertCount(200, $codes->unique());
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^\d{16}$/', $code);
            $this->assertTrue(VoucherService::isValid($code), $code);
        }
    }

    public function test_a_mistyped_serial_fails_the_check_digit(): void
    {
        $code = $this->codeOf($this->batch(1)->vouchers()->first());
        $typo = substr($code, 0, 7).(((int) $code[7] + 1) % 10).substr($code, 8);

        $this->assertTrue(VoucherService::isValid($code));
        $this->assertFalse(VoucherService::isValid($typo));
    }

    public function test_input_accepts_spaces_dashes_and_arabic_indic_digits(): void
    {
        $code = '4539578763621486'; // a Luhn-valid 16-digit number
        $this->assertTrue(VoucherService::isValid($code));
        $this->assertSame($code, VoucherService::normalize('4539 5787 6362 1486'));
        $this->assertSame($code, VoucherService::normalize('4539-5787-6362-1486'));
        $this->assertSame($code, VoucherService::normalize('٤٥٣٩ ٥٧٨٧ ٦٣٦٢ ١٤٨٦'));
        $this->assertNull(VoucherService::normalize('4539 5787 6362 148'));
        $this->assertSame('4539 5787 6362 1486', VoucherService::format($code));
    }

    public function test_the_serial_is_not_stored_in_clear_text(): void
    {
        $voucher = $this->batch(1)->vouchers()->first();
        $code = $this->codeOf($voucher);

        $raw = \DB::table('vouchers')->where('id', $voucher->id)->first();
        $this->assertStringNotContainsString($code, (string) $raw->code);
        $this->assertSame(VoucherService::hash($code), $raw->code_hash);
        $this->assertSame(substr($code, -4), $raw->last4);
    }

    // ---- Redeeming ---------------------------------------------------------------

    public function test_a_valid_voucher_switches_plus_on_for_its_duration(): void
    {
        $user = User::factory()->create();
        $voucher = $this->batch(1, 365)->vouchers()->first();

        $response = $this->redeem($user, VoucherService::format($this->codeOf($voucher)));

        $response->assertOk()->assertJsonPath('plan.slug', 'plus')->assertJsonPath('days', 365);
        $this->assertSame('plus', app(PlanEntitlements::class)->planFor($user)->slug);

        $voucher->refresh();
        $this->assertSame('redeemed', $voucher->status);
        $this->assertSame($user->id, $voucher->redeemed_by);
        $this->assertNotNull($voucher->redeemed_at);

        $ends = app(PlanEntitlements::class)->activeSubscription($user)->current_period_ends_at;
        $this->assertTrue($ends->between(now()->addDays(364), now()->addDays(366)));

        // It also appears in the approved-upgrades history with its source.
        $this->assertSame(1, PlanUpgradeRequest::where('user_id', $user->id)->where('status', 'approved')->count());
    }

    public function test_a_voucher_cannot_be_used_twice(): void
    {
        $voucher = $this->batch(1)->vouchers()->first();
        $code = $this->codeOf($voucher);

        $this->redeem(User::factory()->create(), $code)->assertOk();
        $this->redeem(User::factory()->create(), $code)->assertStatus(422)->assertJsonPath('error.code', 'VOUCHER_USED');
    }

    public function test_redeeming_extends_a_plan_the_customer_already_has(): void
    {
        $user = User::factory()->create();
        $this->redeem($user, $this->codeOf($this->batch(1, 30)->vouchers()->first()))->assertOk();
        $this->redeem($user, $this->codeOf($this->batch(1, 30)->vouchers()->first()))->assertOk();

        $this->assertSame(1, $user->subscriptions()->count());
        $ends = $user->subscriptions()->first()->current_period_ends_at;
        $this->assertTrue($ends->between(now()->addDays(59), now()->addDays(61)));
    }

    public function test_unknown_expired_and_revoked_vouchers_are_refused_with_distinct_codes(): void
    {
        $user = User::factory()->create();

        // Unknown but well-formed.
        $payload = '123456789012345';
        $unknown = $payload.VoucherService::checkDigit($payload);
        $this->redeem($user, $unknown)->assertStatus(422)->assertJsonPath('error.code', 'VOUCHER_INVALID');

        // Malformed.
        $this->redeem($user, '1234')->assertStatus(422)->assertJsonPath('error.code', 'VOUCHER_INVALID_FORMAT');

        // Expired batch.
        $expired = $this->batch(1, 365, now()->subDay());
        $this->redeem($user, $this->codeOf($expired->vouchers()->first()))
            ->assertStatus(422)->assertJsonPath('error.code', 'VOUCHER_EXPIRED');

        // Revoked.
        $voucher = $this->batch(1)->vouchers()->first();
        app(VoucherService::class)->revoke($voucher, 'تسرّب', null);
        $this->redeem($user, $this->codeOf($voucher))->assertStatus(422)->assertJsonPath('error.code', 'VOUCHER_REVOKED');

        $this->assertSame('basic', app(PlanEntitlements::class)->planFor($user)->slug);
    }

    public function test_repeated_guessing_locks_the_account_out(): void
    {
        $user = User::factory()->create();
        $payload = '999999999999999';
        $unknown = $payload.VoucherService::checkDigit($payload);

        for ($i = 0; $i < 10; $i++) {
            RateLimiter::clear('voucher-redeem:customer:'.$user->id);
            $this->redeem($user, $unknown)->assertStatus(422);
        }

        // Even a real voucher is refused while the lockout lasts.
        RateLimiter::clear('voucher-redeem:customer:'.$user->id);
        $real = $this->codeOf($this->batch(1)->vouchers()->first());
        $this->redeem($user, $real)->assertStatus(429)->assertJsonPath('error.code', 'VOUCHER_LOCKED');
    }

    public function test_attempts_are_limited_per_minute(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->redeem($user, '1234')->assertStatus(422);
        }
        $this->redeem($user, '1234')->assertStatus(429)->assertJsonPath('error.code', 'VOUCHER_THROTTLED');
    }

    public function test_redeeming_needs_a_logged_in_customer(): void
    {
        $this->postJson('/api/v1/subscription/redeem', ['code' => '1234 5678 9012 3456'])->assertStatus(401);
    }

    // ---- Dashboard ---------------------------------------------------------------

    public function test_the_dashboard_creates_a_batch_and_shows_masked_serials(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $this->post('/admin/vouchers', [
            'plan' => 'plus', 'quantity' => '50', 'duration_days' => '365', 'recipient' => 'شركة الأمين للبطاقات',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $batch = VoucherBatch::firstOrFail();
        $this->assertSame(50, $batch->vouchers()->count());
        $this->assertSame('شركة الأمين للبطاقات', $batch->recipient);

        $voucher = $batch->vouchers()->first();
        $page = $this->get("/admin/vouchers/batches/{$batch->id}")->assertOk()->assertSee('شركة الأمين للبطاقات')->getContent();
        $this->assertStringContainsString('•••• •••• •••• '.$voucher->last4, $page);
        $this->assertStringNotContainsString($this->codeOf($voucher), $page);
        $this->get('/admin/vouchers')->assertOk()->assertSee($batch->batch_code);
    }

    public function test_exporting_records_who_took_the_serials_and_who_they_were_for(): void
    {
        $admin = $this->admin();
        $batch = $this->batch(5, 365, null, 'شركة النور');
        $this->actingAs($admin, 'admin');

        $response = $this->get("/admin/vouchers/batches/{$batch->id}/export/csv");
        $response->assertOk();
        $csv = $response->streamedContent();
        foreach ($batch->vouchers()->get() as $v) {
            $this->assertStringContainsString($this->codeOf($v), $csv);
        }

        // Handed over: marked distributed to the recipient and logged.
        $this->assertSame(5, $batch->vouchers()->where('status', 'distributed')->where('delivered_to', 'شركة النور')->count());
        $log = VoucherExport::firstOrFail();
        $this->assertSame($batch->id, $log->batch_id);
        $this->assertSame($admin->id, $log->exported_by);
        $this->assertSame('csv', $log->format);
        $this->assertSame(5, $log->count);
        $this->assertSame('شركة النور', $log->recipient);

        $this->get("/admin/vouchers/batches/{$batch->id}")->assertOk()->assertSee('شركة النور');
    }

    public function test_excel_pdf_and_print_exports_work(): void
    {
        $admin = $this->admin();
        $batch = $this->batch(3);
        $this->actingAs($admin, 'admin');

        $xlsx = $this->get("/admin/vouchers/batches/{$batch->id}/export/xlsx")->assertOk();
        $this->assertSame('PK', substr($xlsx->getContent(), 0, 2));
        $this->get("/admin/vouchers/batches/{$batch->id}/export/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get("/admin/vouchers/batches/{$batch->id}/export/print")->assertOk()->assertSee('Qreach SmartTag');
    }

    public function test_the_export_needs_its_own_permission(): void
    {
        $support = Admin::factory()->create();
        $support->roles()->sync([Role::where('slug', 'support_admin')->value('id')]);
        $batch = $this->batch(2);
        $this->actingAs($support, 'admin');

        $this->get("/admin/vouchers/batches/{$batch->id}/export/csv")->assertForbidden();
        $this->get('/admin/vouchers')->assertForbidden();
    }

    public function test_staff_can_revoke_a_voucher_and_a_batch(): void
    {
        $admin = $this->admin();
        $batch = $this->batch(4);
        $first = $batch->vouchers()->first();
        $this->actingAs($admin, 'admin');

        $this->post("/admin/vouchers/{$first->id}/revoke", ['reason' => 'طُبعت بالخطأ'])->assertRedirect();
        $this->assertSame('revoked', $first->fresh()->status);

        // A redeemed voucher is not touched by a batch revoke.
        $redeemed = $batch->vouchers()->where('id', '!=', $first->id)->first();
        $this->redeem(User::factory()->create(), $this->codeOf($redeemed))->assertOk();
        $this->actingAs($admin, 'admin')->post("/admin/vouchers/batches/{$batch->id}/revoke", ['reason' => 'إيقاف الدفعة'])->assertRedirect();

        $this->assertSame(1, $batch->vouchers()->where('status', 'redeemed')->count());
        $this->assertSame(3, $batch->vouchers()->where('status', 'revoked')->count());
    }

    public function test_staff_can_activate_a_user_with_a_serial_from_the_user_page(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $voucher = $this->batch(1, 90)->vouchers()->first();
        $this->actingAs($admin, 'admin');

        $this->post("/admin/users/{$user->uuid}/voucher", ['code' => $this->codeOf($voucher)])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('plus', app(PlanEntitlements::class)->planFor($user)->slug);
        $this->assertSame($user->id, $voucher->fresh()->redeemed_by);

        // Used serial: a clear message, nothing changes.
        $other = User::factory()->create();
        $this->post("/admin/users/{$other->uuid}/voucher", ['code' => $this->codeOf($voucher)])->assertSessionHasErrors('code');
        $this->assertSame('basic', app(PlanEntitlements::class)->planFor($other)->slug);

        $this->get("/admin/users/{$user->uuid}")->assertOk()->assertSee('التفعيل بقسيمة');
    }

    public function test_the_batch_list_counts_each_state(): void
    {
        $admin = $this->admin();
        $batch = $this->batch(4);
        $this->redeem(User::factory()->create(), $this->codeOf($batch->vouchers()->first()))->assertOk();
        $this->actingAs($admin, 'admin');

        $counts = $batch->counts();
        $this->assertSame(1, $counts['redeemed']);
        $this->assertSame(3, $counts['available']);
        $this->get('/admin/vouchers')->assertOk()->assertSee($batch->batch_code)->assertSee('شركة النور');
    }
}
