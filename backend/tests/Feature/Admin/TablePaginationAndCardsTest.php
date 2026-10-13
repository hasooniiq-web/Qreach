<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\TagBatch;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Every dashboard table that can grow is paged, and every table is the one shell that
 * becomes a list of cards on a phone.
 */
class TablePaginationAndCardsTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SubscriptionPlanSeeder::class);
        $this->withoutVite();

        $this->admin = Admin::factory()->create();
        $this->admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);
    }

    public function test_the_trade_batches_table_is_paged(): void
    {
        foreach (range(1, 14) as $i) {
            TagBatch::create(['batch_code' => 'B-'.$i, 'tag_type' => 'qr', 'quantity' => 10, 'status' => 'generated']);
        }

        $first = $this->actingAs($this->admin, 'admin')->get('/admin/trade');
        $first->assertOk()->assertSee('batches_page=2', false);

        $second = $this->actingAs($this->admin, 'admin')->get('/admin/trade?batches_page=2');
        $second->assertOk();
        // Newest first: page 1 holds B-14 … B-5, page 2 the four oldest.
        $first->assertSee('B-14')->assertDontSee('B-1<');
        $second->assertSee('B-4')->assertDontSee('B-14');
    }

    public function test_the_payment_event_log_is_paged(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::where('slug', 'plus')->firstOrFail();
        $payment = Payment::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'idempotency_key' => 'k-'.Str::random(12),
            'amount_cents' => 25000, 'currency' => 'IQD', 'status' => 'pending', 'gateway' => 'sandbox',
        ]);
        foreach (range(1, 20) as $i) {
            PaymentEvent::create([
                'payment_id' => $payment->id, 'gateway' => 'sandbox', 'event_key' => 'evt-'.$i,
                'type' => 'callback', 'signature_valid' => true, 'outcome' => 'ok-'.$i,
            ]);
        }

        $this->actingAs($this->admin, 'admin')->get('/admin/payments/'.$payment->id)
            ->assertOk()->assertSee('events_page=2', false)->assertSee('ok-20')->assertDontSee('ok-1<');
    }

    public function test_the_dev_otp_inbox_is_paged(): void
    {
        if (! Schema_hasDevInbox()) {
            $this->markTestSkipped('The dev OTP inbox table is not part of this schema.');
        }

        config(['services.dev_otp_inbox' => true]);
        foreach (range(1, 30) as $i) {
            DB::table('dev_otp_inbox')->insert(['phone' => '+96477000'.sprintf('%04d', $i), 'code' => '123456', 'sent_at' => now()->subMinutes($i), 'sent_count' => 1]);
        }

        $this->actingAs($this->admin, 'admin')->get('/admin/dev-otp')->assertOk()->assertSee('page=2', false);
    }

    public function test_every_dashboard_table_uses_the_card_shell(): void
    {
        $user = User::factory()->create(['name' => 'سارة']);

        $page = $this->actingAs($this->admin, 'admin')->get('/admin/users');

        $page->assertOk()
            ->assertSee('data-table-shell', false)
            ->assertSee('class="data-table', false)
            ->assertSee('سارة');
    }

    public function test_the_top_bar_sits_above_the_page_header_so_its_menus_are_clickable(): void
    {
        // The page header is z-30; the bar's dropdowns (appearance, alerts, account) must paint over it.
        $html = $this->actingAs($this->admin, 'admin')->get('/admin/vouchers')->assertOk()->getContent();

        $this->assertStringContainsString('sticky top-0 z-[35]', $html);
        $this->assertStringContainsString('relative z-30 mb-6', $html);
    }
}

function Schema_hasDevInbox(): bool
{
    return \Illuminate\Support\Facades\Schema::hasTable('dev_otp_inbox');
}
