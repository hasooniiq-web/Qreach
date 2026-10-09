<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;

/**
 * What a payment provider integration must offer. A provider is added by
 * writing one class that implements this and registering it in
 * GatewayRegistry — nothing else in the app knows which provider is in use.
 *
 * Every method that learns the outcome of a payment (parseCallback,
 * fetchStatus) returns a CallbackResult; the amount and reference in it come
 * from the provider, and PaymentService re-checks both against what the
 * server itself created before activating anything.
 */
interface GatewayDriver
{
    /** Stable key stored in payments.gateway and used in the webhook URL. */
    public function driver(): string;

    /** Name shown to customers and admins. */
    public function label(): string;

    /**
     * Settings the admin must provide, by key.
     *
     * @return array<string, array{label: string, secret: bool, required: bool, hint?: string}>
     */
    public function credentialFields(): array;

    /** May this provider be used in the given application environment? */
    public function availableIn(string $environment): bool;

    public function supportsRefunds(): bool;

    /** Starts a payment on the provider's side and returns where the customer pays. */
    public function createCheckout(Payment $payment, PaymentGateway $gateway): CheckoutSession;

    /**
     * Verifies and decodes a server-to-server notification from the provider.
     *
     * @throws InvalidCallbackException when the signature/authentication is wrong
     */
    public function parseCallback(Request $request, PaymentGateway $gateway): CallbackResult;

    /** Asks the provider for a payment's state; null when the provider has no such call. */
    public function fetchStatus(Payment $payment, PaymentGateway $gateway): ?CallbackResult;

    public function refund(Payment $payment, int $amount, PaymentGateway $gateway): RefundResult;
}
