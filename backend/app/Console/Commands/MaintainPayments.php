<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Console\Command;

/**
 * Housekeeping for payments, safe to run every few minutes: asks the provider
 * about payments whose notification never arrived (for providers that can be
 * asked), then closes the ones nobody finished.
 */
class MaintainPayments extends Command
{
    protected $signature = 'payments:maintain';

    protected $description = 'Reconcile pending payments with their gateway and expire abandoned ones';

    public function handle(PaymentService $payments): int
    {
        $checked = 0;
        Payment::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(2))
            ->orderBy('id')->limit(200)->get()
            ->each(function (Payment $payment) use ($payments, &$checked) {
                $payments->reconcile($payment);
                $checked++;
            });

        $expired = $payments->expireStale();

        $this->info("Checked {$checked} pending payment(s); expired {$expired}.");

        return self::SUCCESS;
    }
}
