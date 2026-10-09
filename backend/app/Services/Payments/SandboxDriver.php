<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * A stand-in provider for testing the whole flow (checkout page -> signed
 * server notification -> activation -> receipt -> refund) without a merchant
 * account. It is hosted by this application itself and is refused in
 * production: no money moves through it.
 *
 * Its "notifications" are signed exactly as a real provider's would be (HMAC-
 * SHA256 of the body with a shared secret), so the same verification path is
 * exercised.
 */
class SandboxDriver implements GatewayDriver
{
    public const SIGNATURE_HEADER = 'X-Sandbox-Signature';

    public function driver(): string
    {
        return 'sandbox';
    }

    public function label(): string
    {
        return 'بوابة تجريبية (اختبار فقط)';
    }

    public function credentialFields(): array
    {
        return [
            'signing_secret' => [
                'label' => 'مفتاح التوقيع',
                'secret' => true,
                'required' => true,
                'hint' => 'سلسلة عشوائية طويلة تُستخدم لتوقيع إشعارات البوابة والتحقق منها.',
            ],
        ];
    }

    public function availableIn(string $environment): bool
    {
        return $environment !== 'production';
    }

    public function supportsRefunds(): bool
    {
        return true;
    }

    public function createCheckout(Payment $payment, PaymentGateway $gateway): CheckoutSession
    {
        $reference = 'sbx_'.Str::lower(Str::random(24));

        return new CheckoutSession($reference, route('payments.sandbox.show', $reference));
    }

    public function parseCallback(Request $request, PaymentGateway $gateway): CallbackResult
    {
        $secret = $gateway->credential('signing_secret');
        $given = (string) $request->header(self::SIGNATURE_HEADER);
        $expected = $secret ? hash_hmac('sha256', $request->getContent(), $secret) : '';

        if ($expected === '' || ! hash_equals($expected, $given)) {
            throw new InvalidCallbackException('Bad signature.');
        }

        $data = json_decode($request->getContent(), true);
        if (! is_array($data) || ! isset($data['reference'], $data['status'], $data['amount'], $data['currency'])) {
            throw new InvalidCallbackException('Malformed payload.');
        }

        return new CallbackResult(
            reference: (string) $data['reference'],
            status: in_array($data['status'], ['succeeded', 'failed', 'pending'], true) ? $data['status'] : 'pending',
            amount: (int) $data['amount'],
            currency: (string) $data['currency'],
            eventKey: isset($data['event_id']) ? (string) $data['event_id'] : null,
            failureReason: $data['failure_reason'] ?? null,
            raw: $data,
        );
    }

    /**
     * Builds the signed body + signature that the sandbox checkout page "sends"
     * to the webhook.
     *
     * @return array{0: string, 1: string}
     */
    public function signedNotification(PaymentGateway $gateway, Payment $payment, string $status, ?string $failureReason = null): array
    {
        $body = json_encode([
            'event_id' => 'evt_'.Str::lower(Str::random(20)),
            'reference' => $payment->gateway_reference,
            'status' => $status,
            'amount' => $payment->amount_cents,
            'currency' => $payment->currency,
            'failure_reason' => $failureReason,
        ], JSON_UNESCAPED_UNICODE);

        return [$body, hash_hmac('sha256', $body, (string) $gateway->credential('signing_secret'))];
    }

    public function fetchStatus(Payment $payment, PaymentGateway $gateway): ?CallbackResult
    {
        return null; // the sandbox only ever reports through its own notification
    }

    public function refund(Payment $payment, int $amount, PaymentGateway $gateway): RefundResult
    {
        return new RefundResult(true, 'rf_'.Str::lower(Str::random(16)));
    }
}
