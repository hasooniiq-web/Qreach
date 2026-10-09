<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'super_admin'): Admin
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', $role)->value('id')]);

        return $admin;
    }

    public function test_gateway_pages_render_and_never_show_a_saved_secret(): void
    {
        $admin = $this->admin();
        PaymentGateway::create(['driver' => 'sandbox', 'name' => 'x', 'is_enabled' => true, 'credentials' => ['signing_secret' => 'super-secret-value-123']]);

        $this->actingAs($admin, 'admin')->get('/admin/payment-gateways')->assertOk()->assertSee('بوابات الدفع');
        $this->get('/admin/payment-gateways/sandbox')
            ->assertOk()->assertSee('محفوظ')->assertDontSee('super-secret-value-123')
            ->assertSee('/api/v1/payments/webhook/sandbox');
        $this->get('/admin/payment-gateways/nope')->assertNotFound();
    }

    public function test_saving_stores_secrets_encrypted_keeps_them_when_blank_and_audits_names_only(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $this->put('/admin/payment-gateways/sandbox', [
            'is_enabled' => '1', 'is_test_mode' => '1', 'credentials' => ['signing_secret' => 'first-secret-value'],
        ])->assertRedirect(route('admin.payment-gateways.index'));

        $gateway = PaymentGateway::where('driver', 'sandbox')->first();
        $this->assertTrue($gateway->is_enabled);
        $this->assertSame('first-secret-value', $gateway->credential('signing_secret'));
        $this->assertStringNotContainsString('first-secret-value', \DB::table('payment_gateways')->value('credentials'));

        // Blank = keep.
        $this->put('/admin/payment-gateways/sandbox', ['is_enabled' => '1', 'credentials' => ['signing_secret' => '']])->assertRedirect();
        $this->assertSame('first-secret-value', $gateway->fresh()->credential('signing_secret'));

        $log = AuditLog::where('action', 'payment_gateway.updated')->latest('id')->first();
        $this->assertStringNotContainsString('first-secret-value', json_encode($log->metadata));
        $this->assertContains('signing_secret', AuditLog::where('action', 'payment_gateway.updated')->oldest('id')->first()->metadata['credentials_changed']);
    }

    public function test_a_gateway_cannot_be_enabled_without_its_credentials(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->put('/admin/payment-gateways/sandbox', ['is_enabled' => '1'])
            ->assertSessionHasErrors('is_enabled');

        $this->assertFalse(PaymentGateway::where('driver', 'sandbox')->value('is_enabled'));
    }

    public function test_the_sandbox_driver_refuses_to_run_in_production(): void
    {
        $driver = app(\App\Services\Payments\GatewayRegistry::class)->driver('sandbox');

        $this->assertFalse($driver->availableIn('production'));
        $this->assertTrue($driver->availableIn('staging'));
    }

    public function test_staff_without_the_permission_cannot_open_or_change_gateways(): void
    {
        $support = $this->admin('support_admin');

        $this->actingAs($support, 'admin')->get('/admin/payment-gateways')->assertForbidden();
        $this->put('/admin/payment-gateways/sandbox', ['is_enabled' => '1'])->assertForbidden();
        $this->get('/admin/payments')->assertForbidden();
    }

    public function test_payments_list_filter_and_detail_render_with_a_refund_form(): void
    {
        $admin = $this->admin();
        $plan = SubscriptionPlan::create(['slug' => 'plus', 'name_ar' => 'بلس', 'price_cents' => 15000, 'currency' => 'IQD', 'billing_period_days' => 30, 'is_active' => true]);
        $user = User::factory()->create(['name' => 'عميل اختبار']);
        $payment = Payment::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'idempotency_key' => 'k-'.bin2hex(random_bytes(8)),
            'amount_cents' => 15000, 'currency' => 'IQD', 'status' => 'succeeded', 'gateway' => 'sandbox',
            'gateway_reference' => 'sbx_x', 'receipt_number' => 'QR-20261010-ABC123', 'paid_at' => now(),
        ]);
        Payment::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'idempotency_key' => 'k-'.bin2hex(random_bytes(8)),
            'amount_cents' => 15000, 'currency' => 'IQD', 'status' => 'failed', 'failure_reason' => 'amount_mismatch', 'gateway' => 'sandbox',
        ]);

        $this->actingAs($admin, 'admin')->get('/admin/payments')->assertOk()->assertSee('عميل اختبار')->assertSee('amount_mismatch');
        $this->get('/admin/payments?status=succeeded')->assertOk()->assertDontSee('amount_mismatch');
        $this->get('/admin/payments?q=QR-20261010')->assertOk()->assertSee('عميل اختبار');
        $this->get("/admin/payments/{$payment->id}")->assertOk()->assertSee('QR-20261010-ABC123')->assertSee('استرداد');
    }

    public function test_an_admin_refund_goes_through_the_service_and_is_validated(): void
    {
        $admin = $this->admin();
        PaymentGateway::create(['driver' => 'sandbox', 'name' => 'x', 'is_enabled' => true, 'credentials' => ['signing_secret' => 'abcdefghijklmnop']]);
        $plan = SubscriptionPlan::create(['slug' => 'plus', 'name_ar' => 'بلس', 'price_cents' => 15000, 'currency' => 'IQD', 'billing_period_days' => 30, 'is_active' => true]);
        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'idempotency_key' => 'k-'.bin2hex(random_bytes(8)),
            'amount_cents' => 15000, 'currency' => 'IQD', 'status' => 'succeeded', 'gateway' => 'sandbox', 'gateway_reference' => 'sbx_r',
        ]);

        $this->actingAs($admin, 'admin')->post("/admin/payments/{$payment->id}/refund", ['amount' => 99999])
            ->assertSessionHasErrors('amount');
        $this->assertSame(0, $payment->fresh()->refunded_amount_cents);

        $this->post("/admin/payments/{$payment->id}/refund", ['amount' => 5000])->assertSessionHas('status');
        $this->assertSame(5000, $payment->fresh()->refunded_amount_cents);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.refunded']);
    }
}
