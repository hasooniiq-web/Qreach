<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\PaymentGateway;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SecurityEventLogger;
use App\Services\SubscriptionManager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The only place a payment changes state.
 *
 * The rules that make it safe:
 *  - The amount charged is the plan's price read from the database when the
 *    payment is created. The app never sends a price.
 *  - A payment is "succeeded" only when the gateway says so through a verified
 *    channel (signed notification or a status query) AND its reference and
 *    amount/currency match what this server created. The app returning from
 *    the checkout page proves nothing and changes nothing.
 *  - Each state change runs under a row lock; a payment that already succeeded
 *    is never activated twice, and a repeated notification (same event id) is
 *    recorded and ignored.
 */
class PaymentService
{
    public function __construct(
        private readonly GatewayRegistry $gateways,
        private readonly SubscriptionManager $subscriptions,
    ) {}

    /**
     * Starts a payment for a plan. Calling it again with the same idempotency
     * key returns the same payment instead of creating a second one.
     *
     * @throws PaymentException when the plan or provider cannot be used
     */
    public function start(User $user, SubscriptionPlan $plan, string $driverKey, string $idempotencyKey): Payment
    {
        $existing = Payment::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            if ($existing->user_id !== $user->id) {
                throw new PaymentException('IDEMPOTENCY_CONFLICT', 'مفتاح الطلب مستخدم من قبل.', 409);
            }

            return $existing;
        }

        if (! $plan->is_active || $plan->isFree() || $plan->price_cents <= 0) {
            throw new PaymentException('PLAN_NOT_AVAILABLE', 'هذه الباقة غير متاحة للشراء.', 422);
        }

        $driver = $this->gateways->driver($driverKey);
        $gateway = $driver ? PaymentGateway::where('driver', $driverKey)->first() : null;
        $usable = $driver && $gateway && $gateway->is_enabled
            && $driver->availableIn(app()->environment()) && $this->gateways->isConfigured($driver, $gateway);
        if (! $usable) {
            throw new PaymentException('GATEWAY_UNAVAILABLE', 'وسيلة الدفع هذه غير متاحة حاليًا.', 422);
        }

        $payment = Payment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'idempotency_key' => $idempotencyKey,
            // Server-side price, in the plan's currency's base unit (dinars for IQD).
            'amount_cents' => $plan->price_cents,
            'currency' => $plan->currency,
            'status' => 'pending',
            'gateway' => $driverKey,
            'metadata' => ['test_mode' => $gateway->is_test_mode],
        ]);

        try {
            $checkout = $driver->createCheckout($payment, $gateway);
        } catch (\Throwable $e) {
            Log::warning('Payment checkout creation failed', ['payment' => $payment->uuid, 'error' => $e->getMessage()]);
            $payment->update(['status' => 'failed', 'failure_reason' => 'checkout_error']);
            throw new PaymentException('GATEWAY_ERROR', 'تعذّر بدء عملية الدفع. حاول مرة أخرى.', 502);
        }

        $payment->update(['gateway_reference' => $checkout->reference, 'checkout_url' => $checkout->url]);

        AuditLogger::log('payment.started', 'customer', $user->id, 'Payment', $payment->id, 'success', [
            'plan' => $plan->slug, 'gateway' => $driverKey, 'amount' => $payment->amount_cents,
        ]);

        return $payment;
    }

    /**
     * Applies a provider-reported outcome. Safe to call any number of times for
     * the same event. Returns a short outcome label (also stored on the event).
     */
    public function applyResult(string $driverKey, CallbackResult $result, string $eventType = 'callback'): string
    {
        return DB::transaction(function () use ($driverKey, $result, $eventType) {
            $payment = Payment::where('gateway', $driverKey)
                ->where('gateway_reference', $result->reference)
                ->lockForUpdate()->first();

            // A repeat delivery of an event we already recorded: acknowledge, change nothing.
            if ($result->eventKey !== null
                && PaymentEvent::where('gateway', $driverKey)->where('event_key', $result->eventKey)->exists()) {
                return 'duplicate';
            }

            $outcome = $payment ? $this->transition($payment, $result) : 'unknown_reference';

            try {
                // Nested transaction = savepoint: a unique-key clash must not poison the outer one (PostgreSQL).
                DB::transaction(fn () => PaymentEvent::create([
                    'payment_id' => $payment?->id,
                    'gateway' => $driverKey,
                    'event_key' => $result->eventKey,
                    'type' => $eventType,
                    'signature_valid' => true,
                    'outcome' => $outcome,
                    'payload' => $result->raw,
                ]));
            } catch (UniqueConstraintViolationException) {
                return 'duplicate'; // two deliveries raced; the other one won
            }

            return $outcome;
        });
    }

    /** Records a notification that failed verification (never changes a payment). */
    public function recordRejected(string $driverKey, string $reason, ?string $body): void
    {
        PaymentEvent::create([
            'gateway' => $driverKey,
            'type' => 'callback',
            'signature_valid' => false,
            'outcome' => 'rejected: '.$reason,
            'payload' => ['body_sha256' => hash('sha256', (string) $body)],
        ]);

        SecurityEventLogger::log(SecurityEventLogger::PAYMENT_ANOMALY, 'warning', 'unknown', null, ['gateway' => $driverKey, 'reason' => $reason]);
    }

    private function transition(Payment $payment, CallbackResult $result): string
    {
        if (in_array($payment->status, ['succeeded', 'refunded'], true)) {
            return 'already_processed';
        }

        if ($result->status === 'pending') {
            return 'still_pending';
        }

        if ($result->status === 'failed') {
            if ($payment->status === 'pending') {
                $payment->update(['status' => 'failed', 'failure_reason' => Str::limit($result->failureReason ?: 'declined', 120, '')]);
                AuditLogger::log('payment.failed', 'system', null, 'Payment', $payment->id, 'failure', ['reason' => $payment->failure_reason]);
            }

            return 'failed';
        }

        // status === succeeded: the reported amount and currency must be exactly what we asked for.
        if ($result->amount !== (int) $payment->amount_cents || strtoupper($result->currency) !== strtoupper($payment->currency)) {
            $payment->update(['status' => 'failed', 'failure_reason' => 'amount_mismatch']);
            SecurityEventLogger::log(SecurityEventLogger::PAYMENT_ANOMALY, 'critical', 'unknown', null, [
                'payment_id' => $payment->id, 'expected' => $payment->amount_cents.' '.$payment->currency,
                'reported' => $result->amount.' '.$result->currency,
            ]);

            return 'amount_mismatch';
        }

        // A late success is honoured even after a plain decline, but never after a mismatch.
        if ($payment->status === 'failed' && $payment->failure_reason === 'amount_mismatch') {
            return 'blocked_after_mismatch';
        }

        $payment->forceFill([
            'status' => 'succeeded',
            'failure_reason' => null,
            'paid_at' => now(),
            'receipt_number' => $this->receiptNumber(),
        ])->save();

        $subscription = $this->subscriptions->activateFromPayment($payment);
        $payment->update(['subscription_id' => $subscription->id]);

        AuditLogger::log('payment.succeeded', 'system', null, 'Payment', $payment->id, 'success', [
            'plan_id' => $payment->plan_id, 'amount' => $payment->amount_cents, 'receipt' => $payment->receipt_number,
        ]);

        return 'succeeded';
    }

    /**
     * Asks the provider about a still-pending payment (when it offers such a
     * call) and applies the answer. A no-op for providers that only notify.
     */
    public function reconcile(Payment $payment): ?string
    {
        if ($payment->status !== 'pending') {
            return null;
        }

        $driver = $this->gateways->driver($payment->gateway);
        $gateway = PaymentGateway::where('driver', $payment->gateway)->first();
        if (! $driver || ! $gateway) {
            return null;
        }

        try {
            $result = $driver->fetchStatus($payment, $gateway);
        } catch (\Throwable $e) {
            Log::warning('Payment status query failed', ['payment' => $payment->uuid, 'error' => $e->getMessage()]);

            return null;
        }

        return $result ? $this->applyResult($payment->gateway, $result, 'status_check') : null;
    }

    /** Pending payments the customer abandoned are closed after this long. */
    public const PENDING_LIFETIME_MINUTES = 60;

    public function expireStale(): int
    {
        return Payment::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(self::PENDING_LIFETIME_MINUTES))
            ->update(['status' => 'failed', 'failure_reason' => 'expired']);
    }

    /**
     * Refunds all or part of a succeeded payment through the provider.
     *
     * @throws PaymentException
     */
    public function refund(Payment $payment, int $amount, int $adminId): Payment
    {
        return DB::transaction(function () use ($payment, $amount, $adminId) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($amount <= 0 || $amount > $payment->refundableCents()) {
                throw new PaymentException('INVALID_REFUND_AMOUNT', 'مبلغ الاسترداد غير صالح.', 422);
            }

            $driver = $this->gateways->driver($payment->gateway);
            $gateway = PaymentGateway::where('driver', $payment->gateway)->first();
            if (! $driver || ! $gateway || ! $driver->supportsRefunds()) {
                throw new PaymentException('REFUND_NOT_SUPPORTED', 'هذه البوابة لا تدعم الاسترداد من النظام. استرد المبلغ من لوحة مزوّد الدفع.', 422);
            }

            $result = $driver->refund($payment, $amount, $gateway);
            if (! $result->ok) {
                AuditLogger::log('payment.refund_failed', 'admin', $adminId, 'Payment', $payment->id, 'failure', ['amount' => $amount, 'error' => $result->error]);
                throw new PaymentException('REFUND_FAILED', 'رفضت البوابة عملية الاسترداد: '.($result->error ?: 'خطأ غير معروف'), 502);
            }

            $total = $payment->refunded_amount_cents + $amount;
            $payment->forceFill([
                'refunded_amount_cents' => $total,
                'refunded_at' => now(),
                'status' => $total >= $payment->amount_cents ? 'refunded' : 'succeeded',
            ])->save();

            PaymentEvent::create([
                'payment_id' => $payment->id, 'gateway' => $payment->gateway, 'type' => 'refund',
                'outcome' => 'refunded '.$amount, 'payload' => ['reference' => $result->reference, 'admin_id' => $adminId],
            ]);

            // A fully refunded purchase no longer entitles the customer to the plan it bought.
            if ($payment->status === 'refunded' && $payment->subscription && $payment->subscription->status === 'active') {
                $this->subscriptions->cancel($payment->subscription, $adminId);
            }

            AuditLogger::log('payment.refunded', 'admin', $adminId, 'Payment', $payment->id, 'success', [
                'amount' => $amount, 'total_refunded' => $total,
            ]);

            return $payment;
        });
    }

    private function receiptNumber(): string
    {
        do {
            $number = 'QR-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Payment::where('receipt_number', $number)->exists());

        return $number;
    }
}
