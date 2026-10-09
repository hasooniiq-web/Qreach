<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payments\GatewayRegistry;
use App\Services\Payments\InvalidCallbackException;
use App\Services\Payments\PaymentService;
use App\Services\Payments\SandboxDriver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Unauthenticated endpoints a payment provider (or the customer's browser
 * coming back from it) talks to. Nothing here trusts the caller: a webhook is
 * accepted only if the driver can verify it, and the return page only *shows*
 * a message — it never changes a payment.
 */
class PaymentGatewayController extends Controller
{
    public function __construct(
        private readonly GatewayRegistry $gateways,
        private readonly PaymentService $payments,
    ) {}

    /** Server-to-server notification from the provider. */
    public function webhook(Request $request, string $driver): JsonResponse
    {
        $impl = $this->gateways->driver($driver);
        $gateway = $impl ? PaymentGateway::where('driver', $driver)->first() : null;
        abort_if(! $impl || ! $gateway || ! $impl->availableIn(app()->environment()), 404);

        try {
            $result = $impl->parseCallback($request, $gateway);
        } catch (InvalidCallbackException $e) {
            $this->payments->recordRejected($driver, $e->getMessage(), $request->getContent());

            return response()->json(['ok' => false], 400);
        }

        $outcome = $this->payments->applyResult($driver, $result);

        // 200 for every verified notification (even duplicates) so the provider stops retrying.
        return response()->json(['ok' => true, 'outcome' => $outcome]);
    }

    /** Where the customer lands after the provider's page. Display only. */
    public function return(string $uuid): View
    {
        $payment = Payment::where('uuid', $uuid)->first();

        return view('public.payment-return', ['status' => $payment?->status]);
    }

    /** The sandbox's own "pay" page (non-production only). */
    public function sandboxShow(string $reference): View
    {
        $payment = $this->sandboxPayment($reference);

        return view('public.payment-sandbox', ['payment' => $payment->load('plan')]);
    }

    /** The sandbox's buttons: build a signed notification and run it through the real webhook path. */
    public function sandboxComplete(Request $request, string $reference): RedirectResponse
    {
        $payment = $this->sandboxPayment($reference);
        $request->validate(['result' => ['required', 'in:pay,fail']]);

        $driver = new SandboxDriver;
        $gateway = PaymentGateway::where('driver', 'sandbox')->firstOrFail();
        [$body, $signature] = $driver->signedNotification(
            $gateway, $payment, $request->input('result') === 'pay' ? 'succeeded' : 'failed',
            $request->input('result') === 'pay' ? null : 'declined_by_customer'
        );

        $notification = Request::create('/', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        $notification->headers->set(SandboxDriver::SIGNATURE_HEADER, $signature);
        $this->payments->applyResult('sandbox', $driver->parseCallback($notification, $gateway));

        return redirect()->route('payments.return', $payment->uuid);
    }

    private function sandboxPayment(string $reference): Payment
    {
        abort_if(app()->environment('production'), 404);

        return Payment::where('gateway', 'sandbox')->where('gateway_reference', $reference)
            ->where('status', 'pending')->firstOrFail();
    }
}
