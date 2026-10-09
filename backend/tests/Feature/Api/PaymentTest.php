<?php

namespace Tests\Feature\Api;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\PaymentGateway;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PlanEntitlements;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use App\Services\Payments\SandboxDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The payment rules that matter for money: the server sets the price, only a
 * verified notification with the right amount activates a plan, and nothing is
 * ever activated or refunded twice.
 */
class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-signing-secret-0123456789';

    private SubscriptionPlan $plus;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plus = SubscriptionPlan::create([
            'slug' => 'plus', 'name_ar' => 'بلس', 'price_cents' => 15000, 'currency' => 'IQD',
            'billing_period_days' => 30, 'is_active' => true,
        ]);
        $this->user = User::factory()->create();

        PaymentGateway::create([
            'driver' => 'sandbox', 'name' => 'sandbox', 'is_enabled' => true, 'is_test_mode' => true,
            'credentials' => ['signing_secret' => self::SECRET],
        ]);
    }

    private function headers(?User $user = null): array
    {
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.($user ?? $this->user)->createToken('t')->plainTextToken];
    }

    private function startPayment(array $extra = []): Payment
    {
        $res = $this->postJson('/api/v1/payments', ['plan' => 'plus', 'gateway' => 'sandbox'] + $extra, $this->headers())
            ->assertCreated();

        return Payment::where('uuid', $res->json('payment.id'))->firstOrFail();
    }

    /** POSTs a notification to the real webhook route, signed like the sandbox provider signs it. */
    private function notify(Payment $payment, string $status, array $override = [], ?string $signature = null)
    {
        $body = json_encode(array_merge([
            'event_id' => 'evt_'.bin2hex(random_bytes(6)),
            'reference' => $payment->gateway_reference,
            'status' => $status,
            'amount' => $payment->amount_cents,
            'currency' => $payment->currency,
        ], $override));

        return $this->call('POST', '/api/v1/payments/webhook/sandbox', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_'.str_replace('-', '_', strtoupper(SandboxDriver::SIGNATURE_HEADER)) => $signature ?? hash_hmac('sha256', $body, self::SECRET),
        ], $body);
    }

    public function test_only_enabled_and_configured_methods_are_offered(): void
    {
        $this->getJson('/api/v1/payments/methods', $this->headers())
            ->assertOk()->assertJsonPath('data.0.id', 'sandbox')->assertJsonPath('data.0.test_mode', true);

        PaymentGateway::where('driver', 'sandbox')->update(['is_enabled' => false]);
        $this->getJson('/api/v1/payments/methods', $this->headers())->assertOk()->assertJsonCount(0, 'data');

        PaymentGateway::where('driver', 'sandbox')->update(['is_enabled' => true]);
        $this->postJson('/api/v1/payments', ['plan' => 'plus', 'gateway' => 'no-such-provider'], $this->headers())
            ->assertStatus(422)->assertJsonPath('error.code', 'GATEWAY_UNAVAILABLE');
    }

    public function test_the_sandbox_is_refused_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->getJson('/api/v1/payments/methods', $this->headers())->assertOk()->assertJsonCount(0, 'data');
        $this->postJson('/api/v1/payments', ['plan' => 'plus', 'gateway' => 'sandbox'], $this->headers())
            ->assertStatus(422)->assertJsonPath('error.code', 'GATEWAY_UNAVAILABLE');
    }

    public function test_the_price_comes_from_the_plan_never_from_the_client(): void
    {
        $payment = $this->startPayment(['amount' => 1, 'price' => 1, 'amount_cents' => 1]);

        $this->assertSame(15000, $payment->amount_cents);
        $this->assertSame('IQD', $payment->currency);
        $this->assertSame('pending', $payment->status);
        $this->assertNotNull($payment->checkout_url);
    }

    public function test_free_or_inactive_plans_cannot_be_purchased(): void
    {
        SubscriptionPlan::create(['slug' => 'basic', 'name_ar' => 'أساسية', 'price_cents' => 0, 'currency' => 'IQD', 'billing_period_days' => 365, 'is_active' => true]);
        $this->postJson('/api/v1/payments', ['plan' => 'basic', 'gateway' => 'sandbox'], $this->headers())
            ->assertStatus(422)->assertJsonPath('error.code', 'PLAN_NOT_AVAILABLE');

        $this->plus->update(['is_active' => false]);
        $this->postJson('/api/v1/payments', ['plan' => 'plus', 'gateway' => 'sandbox'], $this->headers())
            ->assertStatus(422);
    }

    public function test_the_same_idempotency_key_returns_the_same_payment(): void
    {
        $key = 'attempt-'.bin2hex(random_bytes(8));
        $a = $this->startPayment(['idempotency_key' => $key]);
        $b = $this->startPayment(['idempotency_key' => $key]);

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, Payment::count());

        // Another account cannot reuse (or probe) someone else's key.
        $this->postJson('/api/v1/payments', ['plan' => 'plus', 'gateway' => 'sandbox', 'idempotency_key' => $key], $this->headers(User::factory()->create()))
            ->assertStatus(409);
    }

    public function test_a_verified_success_activates_the_plan_and_issues_a_receipt(): void
    {
        $payment = $this->startPayment();
        $this->assertFalse(app(PlanEntitlements::class)->planFor($this->user)->slug === 'plus');

        $this->notify($payment, 'succeeded')->assertOk()->assertJsonPath('outcome', 'succeeded');

        $payment->refresh();
        $this->assertSame('succeeded', $payment->status);
        $this->assertNotNull($payment->receipt_number);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('plus', app(PlanEntitlements::class)->planFor($this->user)->slug);
        $this->assertSame($payment->subscription_id, $this->user->subscriptions()->first()->id);

        $this->getJson("/api/v1/payments/{$payment->uuid}", $this->headers())
            ->assertOk()->assertJsonPath('payment.status', 'succeeded')
            ->assertJsonPath('payment.receipt_number', $payment->receipt_number);
    }

    public function test_a_notification_with_a_bad_signature_changes_nothing(): void
    {
        $payment = $this->startPayment();

        $this->notify($payment, 'succeeded', [], 'deadbeef')->assertStatus(400);
        $this->notify($payment, 'succeeded', [], '')->assertStatus(400);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame(0, $this->user->subscriptions()->count());
        $this->assertSame(2, PaymentEvent::where('signature_valid', false)->count());
    }

    public function test_a_repeated_notification_never_activates_or_extends_twice(): void
    {
        $payment = $this->startPayment();
        $eventId = 'evt_same';

        $this->notify($payment, 'succeeded', ['event_id' => $eventId])->assertOk()->assertJsonPath('outcome', 'succeeded');
        $endsAt = $this->user->subscriptions()->first()->current_period_ends_at;

        $this->notify($payment, 'succeeded', ['event_id' => $eventId])->assertOk()->assertJsonPath('outcome', 'duplicate');
        // Even a *different* event id for an already-paid payment is a no-op.
        $this->notify($payment, 'succeeded')->assertOk()->assertJsonPath('outcome', 'already_processed');

        $this->assertSame(1, $this->user->subscriptions()->count());
        $this->assertEquals($endsAt, $this->user->subscriptions()->first()->current_period_ends_at);
        $this->assertSame(1, Payment::where('status', 'succeeded')->count());
    }

    public function test_a_wrong_amount_or_currency_is_rejected_and_flagged(): void
    {
        $payment = $this->startPayment();

        $this->notify($payment, 'succeeded', ['amount' => 100])->assertOk()->assertJsonPath('outcome', 'amount_mismatch');

        $payment->refresh();
        $this->assertSame('failed', $payment->status);
        $this->assertSame('amount_mismatch', $payment->failure_reason);
        $this->assertSame(0, $this->user->subscriptions()->count());
        $this->assertDatabaseHas('security_events', ['event_type' => 'PAYMENT_ANOMALY', 'severity' => 'critical']);

        // A later "correct" success cannot revive a payment that was tampered with.
        $this->notify($payment, 'succeeded')->assertOk()->assertJsonPath('outcome', 'blocked_after_mismatch');
        $this->assertSame(0, $this->user->subscriptions()->count());

        $other = $this->startPayment(['idempotency_key' => 'second-attempt-'.bin2hex(random_bytes(4))]);
        $this->notify($other, 'succeeded', ['currency' => 'USD'])->assertOk()->assertJsonPath('outcome', 'amount_mismatch');
    }

    public function test_a_failure_notification_marks_the_payment_failed_without_activating(): void
    {
        $payment = $this->startPayment();

        $this->notify($payment, 'failed', ['failure_reason' => 'insufficient_funds'])->assertOk();

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('insufficient_funds', $payment->fresh()->failure_reason);
        $this->assertSame(0, $this->user->subscriptions()->count());
    }

    public function test_a_notification_for_an_unknown_reference_is_acknowledged_but_ignored(): void
    {
        $ghost = new Payment(['gateway_reference' => 'sbx_nothing', 'amount_cents' => 15000, 'currency' => 'IQD']);

        $this->notify($ghost, 'succeeded')->assertOk()->assertJsonPath('outcome', 'unknown_reference');
        $this->assertSame(0, Payment::count());
    }

    public function test_a_customer_cannot_read_someone_elses_payment(): void
    {
        $payment = $this->startPayment();

        $this->getJson("/api/v1/payments/{$payment->uuid}", $this->headers(User::factory()->create()))->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->getJson("/api/v1/payments/{$payment->uuid}")->assertUnauthorized();
    }

    public function test_the_sandbox_checkout_pages_complete_a_payment_through_the_signed_path(): void
    {
        $payment = $this->startPayment();

        $this->get(route('payments.sandbox.show', $payment->gateway_reference))
            ->assertOk()->assertSee('بوابة تجريبية')->assertSee('15,000');

        $this->post(route('payments.sandbox.complete', $payment->gateway_reference), ['result' => 'pay'])
            ->assertRedirect(route('payments.return', $payment->uuid));

        $this->assertSame('succeeded', $payment->fresh()->status);
        $this->get(route('payments.return', $payment->uuid))->assertOk()->assertSee('تمت عملية الدفع');
    }

    public function test_the_return_page_alone_never_changes_a_payment(): void
    {
        $payment = $this->startPayment();

        $this->get(route('payments.return', $payment->uuid))->assertOk()->assertSee('جارٍ التحقق');

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame(0, $this->user->subscriptions()->count());
    }

    public function test_refunds_are_partial_then_full_and_a_full_refund_ends_the_plan(): void
    {
        $payment = $this->startPayment();
        $this->notify($payment, 'succeeded')->assertOk();
        $service = app(PaymentService::class);

        $payment = $service->refund($payment->fresh(), 5000, 1);
        $this->assertSame('succeeded', $payment->status);
        $this->assertSame(5000, $payment->refunded_amount_cents);
        $this->assertSame('plus', app(PlanEntitlements::class)->planFor($this->user)->slug);

        try {
            $service->refund($payment, 20000, 1);
            $this->fail('Over-refund must be rejected.');
        } catch (PaymentException $e) {
            $this->assertSame('INVALID_REFUND_AMOUNT', $e->errorCode);
        }

        $payment = $service->refund($payment, 10000, 1);
        $this->assertSame('refunded', $payment->status);
        $this->assertSame(15000, $payment->refunded_amount_cents);
        $this->assertSame('basic', app(PlanEntitlements::class)->planFor($this->user)->slug);

        $this->expectException(PaymentException::class);
        $service->refund($payment, 1, 1); // nothing left to refund
    }

    public function test_stale_pending_payments_are_closed(): void
    {
        $payment = $this->startPayment();
        $payment->forceFill(['created_at' => now()->subHours(2)])->save();

        $this->assertSame(1, app(PaymentService::class)->expireStale());

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('expired', $payment->fresh()->failure_reason);
    }

    public function test_gateway_credentials_are_encrypted_at_rest(): void
    {
        $raw = \DB::table('payment_gateways')->where('driver', 'sandbox')->value('credentials');

        $this->assertStringNotContainsString(self::SECRET, $raw);
        $this->assertSame(self::SECRET, PaymentGateway::where('driver', 'sandbox')->first()->credential('signing_secret'));
    }
}
