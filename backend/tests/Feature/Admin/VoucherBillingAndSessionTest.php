<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\TradeCustomer;
use App\Models\TradeEntry;
use App\Models\VoucherBatch;
use App\Services\TradeLedger;
use App\Services\VoucherService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Selling voucher batches to resellers (price + their account in the trade book),
 * and the dashboard session ending when the browser closes.
 */
class VoucherBillingAndSessionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'super_admin'): Admin
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SubscriptionPlanSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', $role)->value('id')]);

        return $admin;
    }

    public function test_a_batch_with_a_price_and_a_reseller_is_billed_to_that_account(): void
    {
        $admin = $this->admin();
        $company = TradeCustomer::create(['name' => 'شركة البطاقات الرقمية']);
        $this->actingAs($admin, 'admin');

        $this->post('/admin/vouchers', [
            'plan' => 'plus', 'quantity' => '1000', 'duration_days' => '365',
            'unit_price' => '15,000', 'customer_id' => $company->id, 'recipient' => 'شركة البطاقات الرقمية',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $batch = VoucherBatch::firstOrFail();
        $this->assertSame(15000, $batch->unit_price);
        $this->assertSame($company->id, $batch->customer_id);

        $entry = TradeEntry::where('voucher_batch_id', $batch->id)->firstOrFail();
        $this->assertSame('voucher_sale', $entry->type);
        $this->assertSame(1000, $entry->quantity);
        $this->assertSame(15_000_000, $entry->amount);
        $this->assertSame(15_000_000, $company->balance());

        // A payment reduces what the company owes, like any other customer.
        app(TradeLedger::class)->recordPayment($company, 5_000_000, now(), null, $admin->id);
        $this->assertSame(10_000_000, $company->fresh()->balance());

        // The sticker stock is untouched: vouchers are not stickers.
        $totals = app(TradeLedger::class)->totals();
        $this->assertSame(0, $totals['delivered']);
        $this->assertSame(15_000_000, $totals['sales']);

        $this->get("/admin/trade/customers/{$company->id}")->assertOk()->assertSee('قسائم تفعيل')->assertSee('15,000,000');
        $this->get("/admin/vouchers/batches/{$batch->id}")->assertOk()->assertSee('البيع والمستحقات')->assertSee('شركة البطاقات الرقمية');
    }

    public function test_an_unbilled_batch_can_be_billed_later_but_only_once(): void
    {
        $admin = $this->admin();
        $company = TradeCustomer::create(['name' => 'الموزّع']);
        $batch = app(VoucherService::class)->generate(
            SubscriptionPlan::where('slug', 'plus')->firstOrFail(), 10, 365, null, null, null, $admin->id
        );
        $this->actingAs($admin, 'admin');

        $this->post("/admin/vouchers/batches/{$batch->id}/bill", ['customer_id' => $company->id, 'unit_price' => '20000'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(200_000, $company->balance());

        $this->post("/admin/vouchers/batches/{$batch->id}/bill", ['customer_id' => $company->id, 'unit_price' => '20000'])
            ->assertSessionHasErrors('unit_price');
        $this->assertSame(1, TradeEntry::where('voucher_batch_id', $batch->id)->count());
    }

    public function test_voiding_the_sale_takes_it_off_the_account(): void
    {
        $admin = $this->admin();
        $company = TradeCustomer::create(['name' => 'الموزّع']);
        $batch = app(VoucherService::class)->generate(
            SubscriptionPlan::where('slug', 'plus')->firstOrFail(), 5, 365, null, null, null, $admin->id
        );
        $ledger = app(TradeLedger::class);
        $entry = $ledger->recordVoucherSale($company, $batch, 10000, now(), null, $admin->id);
        $this->assertSame(50_000, $company->balance());

        $ledger->void($entry, 'سعر خاطئ', $admin->id);

        $this->assertSame(0, $company->fresh()->balance());
        // …and it can be billed again with the right price.
        $ledger->recordVoucherSale($company, $batch->fresh(), 12000, now(), null, $admin->id);
        $this->assertSame(60_000, $company->fresh()->balance());
    }

    public function test_billing_a_reseller_needs_the_trade_permission(): void
    {
        $this->admin(); // seeds roles, plans and permissions
        // vouchers.* but not trade.*: a role with only the voucher permissions.
        $role = Role::create(['slug' => 'voucher_only', 'name_ar' => 'قسائم فقط', 'name_en' => 'Vouchers only', 'is_system' => false]);
        $role->permissions()->sync(\App\Models\Permission::whereIn('key', ['vouchers.view', 'vouchers.create'])->pluck('id'));
        $limited = Admin::factory()->create();
        $limited->roles()->sync([$role->id]);
        $company = TradeCustomer::create(['name' => 'الموزّع']);
        $this->actingAs($limited, 'admin');

        $this->post('/admin/vouchers', [
            'plan' => 'plus', 'quantity' => '5', 'duration_days' => '30', 'unit_price' => '1000', 'customer_id' => $company->id,
        ])->assertSessionHasErrors('customer_id');

        $this->assertSame(0, VoucherBatch::count());
    }

    // ---- Session -------------------------------------------------------------

    public function test_the_dashboard_session_cookie_ends_when_the_browser_closes(): void
    {
        $this->assertTrue(config('session.expire_on_close'));

        $response = $this->get('/admin/login');
        $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === config('session.cookie'));

        $this->assertNotNull($cookie, 'No session cookie was issued.');
        // 0 = a session cookie: the browser forgets it when it closes.
        $this->assertSame(0, $cookie->getExpiresTime());
    }

    public function test_there_is_no_remember_me_on_the_admin_login(): void
    {
        $this->get('/admin/login')->assertOk()->assertDontSee('name="remember"', false)->assertDontSee('تذكرني');
    }

    public function test_signing_in_never_issues_a_remember_cookie(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create(['password' => \Illuminate\Support\Facades\Hash::make('Secret-Pass-123')]);
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        $response = $this->post('/admin/login', ['email' => $admin->email, 'password' => 'Secret-Pass-123', 'remember' => '1']);

        $names = collect($response->headers->getCookies())->map(fn ($c) => $c->getName())->all();
        $this->assertEmpty(array_filter($names, fn ($n) => str_starts_with($n, 'remember_')));
    }
}
