<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\GatewayRegistry;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Payment records for staff: what was paid, what failed and why, and refunds. */
class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly GatewayRegistry $gateways,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $q = trim($request->string('q')->toString());

        $payments = Payment::query()->with(['user', 'plan'])
            ->when(in_array($status, ['pending', 'succeeded', 'failed', 'refunded'], true), fn ($query) => $query->where('status', $status))
            ->when($request->filled('gateway'), fn ($query) => $query->where('gateway', $request->string('gateway')))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('receipt_number', 'like', "%{$q}%")
                ->orWhere('gateway_reference', 'like', "%{$q}%")
                ->orWhere('uuid', 'like', "%{$q}%")
                ->orWhereHas('user', fn ($u) => $u->where('phone', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"))))
            ->latest('id')->paginate(25)->withQueryString();

        $totals = [
            'succeeded' => (int) Payment::where('status', 'succeeded')->sum('amount_cents') - (int) Payment::where('status', 'succeeded')->sum('refunded_amount_cents'),
            'count_succeeded' => Payment::whereIn('status', ['succeeded', 'refunded'])->count(),
            'count_failed' => Payment::where('status', 'failed')->count(),
            'count_pending' => Payment::where('status', 'pending')->count(),
            'refunded' => (int) Payment::sum('refunded_amount_cents'),
        ];

        return view('admin.payments.index', [
            'payments' => $payments,
            'totals' => $totals,
            'status' => $status,
            'q' => $q,
            'gateways' => $this->gateways->all(),
        ]);
    }

    public function show(Payment $payment): View
    {
        $payment->load(['user', 'plan', 'subscription', 'events']);

        return view('admin.payments.show', [
            'payment' => $payment,
            'driver' => $this->gateways->driver($payment->gateway),
        ]);
    }

    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'integer', 'min:1']]);

        try {
            $this->payments->refund($payment, (int) $data['amount'], $request->user('admin')->id);
        } catch (PaymentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('status', 'تم تنفيذ الاسترداد.');
    }
}
