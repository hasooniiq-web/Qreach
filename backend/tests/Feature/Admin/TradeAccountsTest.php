<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Models\TradeCustomer;
use App\Models\TradeEntry;
use App\Services\QrBatchService;
use App\Services\TradeLedger;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TradeAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'super_admin'): Admin
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', $role)->value('id')]);

        return $admin;
    }

    private function customer(): TradeCustomer
    {
        return TradeCustomer::create(['name' => 'سوبر ماركت النور', 'phone' => '07701234567']);
    }

    public function test_the_example_from_the_client_one_thousand_stickers_a_million_dinars_paid(): void
    {
        $admin = $this->admin();
        $batch = app(QrBatchService::class)->createBatch(1000, $admin->id);
        $customer = $this->customer();
        $ledger = app(TradeLedger::class);

        // 1,000 stickers at 5,000 IQD = 5,000,000; the customer pays 1,000,000.
        $ledger->recordSale($customer, $batch, 1000, 5000, now(), null, $admin->id);
        $payment = $ledger->recordPayment($customer, 1_000_000, now(), null, $admin->id);

        $this->assertSame(4_000_000, $customer->balance());
        $this->assertStringStartsWith('RC-'.now()->format('Y').'-', $payment->receipt_number);
        $this->assertSame(0, $ledger->remainingInBatch($batch));
        $this->assertSame(4_000_000, $ledger->totals()['outstanding']);
    }

    public function test_a_sale_cannot_hand_over_more_than_the_batch_has_left(): void
    {
        $admin = $this->admin();
        $batch = app(QrBatchService::class)->createBatch(100, $admin->id);
        $customer = $this->customer();
        $ledger = app(TradeLedger::class);

        $ledger->recordSale($customer, $batch, 60, 1000, now(), null, $admin->id);

        try {
            $ledger->recordSale($customer, $batch, 41, 1000, now(), null, $admin->id);
            $this->fail('Over-delivery must be refused.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('40', $e->errors()['quantity'][0]);
        }

        $this->assertSame(40, $ledger->remainingInBatch($batch));
        $ledger->recordSale($customer, $batch, 40, 1000, now(), null, $admin->id);
        $this->assertSame(0, $ledger->remainingInBatch($batch));
    }

    public function test_voiding_an_entry_returns_the_stock_and_the_balance(): void
    {
        $admin = $this->admin();
        $batch = app(QrBatchService::class)->createBatch(50, $admin->id);
        $customer = $this->customer();
        $ledger = app(TradeLedger::class);

        $sale = $ledger->recordSale($customer, $batch, 30, 2000, now(), null, $admin->id);
        $this->assertSame(60_000, $customer->balance());
        $this->assertSame(20, $ledger->remainingInBatch($batch));

        $ledger->void($sale, 'خطأ في الكمية', $admin->id);

        $this->assertSame(0, $customer->balance());
        $this->assertSame(50, $ledger->remainingInBatch($batch));
        // The row is kept, with its reason — nothing is deleted.
        $this->assertDatabaseHas('trade_entries', ['id' => $sale->id, 'void_reason' => 'خطأ في الكمية']);
    }

    public function test_the_pages_work_end_to_end(): void
    {
        $admin = $this->admin();
        $batch = app(QrBatchService::class)->createBatch(200, $admin->id);

        $this->actingAs($admin, 'admin');

        $this->get('/admin/trade')->assertOk()->assertSee('المخزون والحسابات');

        $this->post('/admin/trade/customers', ['name' => 'محل الأمين', 'phone' => '0770'])->assertRedirect();
        $customer = TradeCustomer::firstOrFail();

        $this->post("/admin/trade/customers/{$customer->id}/sales", [
            'batch_id' => $batch->id, 'quantity' => '100', 'unit_price' => '4,000',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->post("/admin/trade/customers/{$customer->id}/payments", ['amount' => '150,000'])
            ->assertRedirect();
        $payment = TradeEntry::where('type', 'payment')->firstOrFail();

        $this->get("/admin/trade/customers/{$customer->id}")->assertOk()
            ->assertSee('250,000')->assertSee($payment->receipt_number);
        $this->get("/admin/trade/receipts/{$payment->id}")->assertOk()
            ->assertSee($payment->receipt_number)->assertSee('150,000')->assertSee('250,000');

        // Over-delivery is refused with a message on the form.
        $this->post("/admin/trade/customers/{$customer->id}/sales", [
            'batch_id' => $batch->id, 'quantity' => '101', 'unit_price' => '4000',
        ])->assertSessionHasErrors('quantity');
    }

    public function test_only_staff_with_the_permission_can_open_or_change_the_book(): void
    {
        $support = $this->admin('support_admin');
        $customer = $this->customer();

        $this->actingAs($support, 'admin')->get('/admin/trade')->assertForbidden();
        $this->actingAs($support, 'admin')->post("/admin/trade/customers/{$customer->id}/payments", ['amount' => '1000'])
            ->assertForbidden();
        $this->assertSame(0, TradeEntry::count());
    }
}
