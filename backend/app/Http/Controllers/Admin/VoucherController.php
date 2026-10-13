<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\VoucherException;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\TradeCustomer;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Models\VoucherExport;
use App\Services\AuditLogger;
use App\Services\TradeLedger;
use App\Services\VoucherService;
use App\Support\Export\Labels;
use App\Support\Export\TableExport;
use App\Support\NumberInput;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Activation vouchers (staff). Reading needs vouchers.view; making a batch needs
 * vouchers.create; getting the serials out (file, print, CSV) needs vouchers.export
 * and is recorded with who took them and who they were for; revoking needs
 * vouchers.revoke. Lists only ever show the masked serial.
 */
class VoucherController extends Controller
{
    public function __construct(
        private readonly VoucherService $vouchers,
        private readonly TradeLedger $ledger,
    ) {}

    private function labels(): array
    {
        return [
            'available' => 'جاهزة (لم تُصدَّر)',
            'distributed' => 'سُلّمت (صُدّرت)',
            'redeemed' => 'مُستخدمة',
            'revoked' => 'ملغاة',
            'expired' => 'منتهية',
        ];
    }

    public function index(Request $request): View
    {
        $batches = VoucherBatch::query()->with('plan')->withCount([
            'vouchers as available_count' => fn ($q) => $q->where('status', Voucher::AVAILABLE),
            'vouchers as distributed_count' => fn ($q) => $q->where('status', Voucher::DISTRIBUTED),
            'vouchers as redeemed_count' => fn ($q) => $q->where('status', Voucher::REDEEMED),
            'vouchers as revoked_count' => fn ($q) => $q->where('status', Voucher::REVOKED),
        ])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('batch_code', 'like', '%'.$request->string('q').'%')
                ->orWhere('recipient', 'like', '%'.$request->string('q').'%')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $totals = [
            'total' => Voucher::count(),
            'redeemed' => Voucher::where('status', Voucher::REDEEMED)->count(),
            'distributed' => Voucher::where('status', Voucher::DISTRIBUTED)->count(),
            'available' => Voucher::where('status', Voucher::AVAILABLE)->count(),
        ];

        return view('admin.vouchers.index', compact('batches', 'totals'));
    }

    public function create(): View
    {
        return view('admin.vouchers.create', [
            'plans' => SubscriptionPlan::where('is_active', true)->where('price_cents', '>', 0)->orderBy('sort_order')->get(),
            'max' => VoucherService::MAX_PER_BATCH,
            'customers' => TradeCustomer::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'quantity' => NumberInput::clean($request->input('quantity')),
            'duration_days' => NumberInput::clean($request->input('duration_days')),
            'unit_price' => NumberInput::clean($request->input('unit_price')),
        ]);

        $data = $request->validate([
            'plan' => ['required', 'exists:subscription_plans,slug'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.VoucherService::MAX_PER_BATCH],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'recipient' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'unit_price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'customer_id' => ['nullable', 'integer', 'exists:trade_customers,id'],
        ], [], ['plan' => 'الباقة', 'quantity' => 'العدد', 'duration_days' => 'المدة', 'recipient' => 'الجهة', 'expires_at' => 'تاريخ الانتهاء', 'unit_price' => 'سعر القسيمة', 'customer_id' => 'حساب الشركة']);

        $admin = $request->user('admin');
        $billing = filled($data['customer_id'] ?? null) && filled($data['unit_price'] ?? null);
        if ($billing && ! $admin->hasPermission('trade.manage')) {
            return back()->withInput()->withErrors(['customer_id' => 'ليست لديك صلاحية تسجيل المبيعات في حسابات الشركات.']);
        }

        $plan = SubscriptionPlan::where('slug', $data['plan'])->firstOrFail();

        $batch = $this->vouchers->generate(
            $plan,
            (int) $data['quantity'],
            (int) $data['duration_days'],
            $data['recipient'] ?? null,
            $data['notes'] ?? null,
            isset($data['expires_at']) ? Carbon::parse($data['expires_at'])->endOfDay() : null,
            $request->user('admin')->id,
        );

        $message = 'تم إنشاء '.number_format($batch->quantity).' قسيمة.';

        if ($billing) {
            $customer = TradeCustomer::findOrFail((int) $data['customer_id']);
            $entry = $this->ledger->recordVoucherSale($customer, $batch, (int) $data['unit_price'], now(), null, $admin->id);
            $message .= ' وسُجّل على حساب «'.$customer->name.'» مبلغ '.number_format($entry->amount).' د.ع.';
        } elseif (filled($data['unit_price'] ?? null)) {
            $batch->update(['unit_price' => (int) $data['unit_price']]);
        }

        return redirect()->route('admin.vouchers.show', $batch)->with('status', $message);
    }

    public function show(Request $request, VoucherBatch $voucherBatch): View
    {
        $voucherBatch->load('plan', 'creator');

        $vouchers = $voucherBatch->vouchers()->with('redeemer')
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = $request->string('status')->toString();
                if ($status === 'expired') {
                    // Unused vouchers of a batch whose date has passed.
                    return $q->whereIn('status', [Voucher::AVAILABLE, Voucher::DISTRIBUTED])
                        ->whereHas('batch', fn ($b) => $b->whereNotNull('expires_at')->where('expires_at', '<', now()));
                }

                return $q->where('status', $status);
            })
            ->when($request->filled('q'), fn ($q) => $q->where('last4', $request->string('q')->toString()))
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.vouchers.show', [
            'batch' => $voucherBatch,
            'vouchers' => $vouchers,
            'counts' => $voucherBatch->counts(),
            'exports' => $voucherBatch->exports()->with('admin')->limit(20)->get(),
            'sale' => $voucherBatch->saleEntry()->with('customer')->first(),
            'customers' => TradeCustomer::orderBy('name')->get(['id', 'name']),
            'labels' => $this->labels(),
        ]);
    }

    /**
     * Gets the serials out of the system. Every call is recorded (who, when, format,
     * how many, for whom) and marks the still-"ready" vouchers as handed over.
     */
    public function export(Request $request, VoucherBatch $voucherBatch, string $format): mixed
    {
        abort_unless(in_array($format, ['xlsx', 'pdf', 'csv', 'print'], true), 404);

        $key = 'voucher-export:'.$request->user('admin')->id;
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429, 'عدد مرات التصدير كبير. حاول بعد دقيقة.');
        RateLimiter::hit($key, 60);

        $recipient = trim((string) $request->query('recipient', '')) ?: $voucherBatch->recipient;
        $onlyUnused = $request->boolean('unused_only', true);

        $query = $voucherBatch->vouchers()
            ->when($onlyUnused, fn ($q) => $q->whereIn('status', [Voucher::AVAILABLE, Voucher::DISTRIBUTED]))
            ->orderBy('id');

        $perPrintPage = 200;
        if ($format === 'print') {
            $page = max(1, (int) $request->query('page', 1));
            $query->forPage($page, $perPrintPage);
        }

        $vouchers = $query->get();
        abort_if($vouchers->isEmpty(), 404, 'لا توجد قسائم للتصدير.');

        // Hand-over bookkeeping: first export marks them as delivered to the recipient.
        $ids = $vouchers->where('status', Voucher::AVAILABLE)->pluck('id');
        if ($ids->isNotEmpty()) {
            Voucher::whereIn('id', $ids)->update([
                'status' => Voucher::DISTRIBUTED, 'exported_at' => now(), 'delivered_to' => $recipient, 'updated_at' => now(),
            ]);
        }

        VoucherExport::create([
            'batch_id' => $voucherBatch->id,
            'exported_by' => $request->user('admin')->id,
            'format' => $format,
            'count' => $vouchers->count(),
            'recipient' => $recipient,
            'note' => $request->query('note') ? mb_substr((string) $request->query('note'), 0, 500) : null,
        ]);

        AuditLogger::log('voucher.exported', 'admin', $request->user('admin')->id, 'VoucherBatch', $voucherBatch->id, 'success', [
            'format' => $format, 'count' => $vouchers->count(), 'recipient' => $recipient,
        ], $request);

        $voucherBatch->loadMissing('plan');

        return match ($format) {
            'print' => $this->printView($voucherBatch, $vouchers),
            'csv' => $this->csv($voucherBatch, $vouchers),
            default => $this->tableFile($voucherBatch, $vouchers, $format, $recipient, $request),
        };
    }

    private function tableFile(VoucherBatch $batch, $vouchers, string $format, ?string $recipient, Request $request)
    {
        $export = new TableExport(
            key: 'vouchers-'.$batch->batch_code,
            title: 'قسائم التفعيل '.$batch->batch_code,
            headers: ['#', 'رقم القسيمة', 'الباقة', 'المدة (يوم)', 'الجهة المستلمة', 'الحالة'],
            rows: $vouchers->values()->map(fn (Voucher $v, int $i) => [
                $i + 1,
                $v->formattedCode(),
                (string) $batch->plan->name_ar,
                (int) $batch->duration_days,
                (string) ($recipient ?: '—'),
                Labels::status($v->status === Voucher::AVAILABLE ? Voucher::DISTRIBUTED : $v->status),
            ])->all(),
            summary: ['عدد القسائم' => $vouchers->count()],
            filters: array_values(array_filter([
                'الباقة: '.$batch->plan->name_ar.' — '.$batch->duration_days.' يوم',
                $recipient ? 'الجهة المستلمة: '.$recipient : null,
                $batch->expires_at ? 'صالحة حتى: '.Labels::date($batch->expires_at) : null,
            ])),
            generatedBy: (string) ($request->user('admin')->name ?? ''),
        );

        return $format === 'pdf' ? $export->pdf() : $export->xlsx();
    }

    private function csv(VoucherBatch $batch, $vouchers): StreamedResponse
    {
        return response()->streamDownload(function () use ($vouchers) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['serial']);
            foreach ($vouchers as $v) {
                // Digits only: no separators, so a card printer or import tool reads it as is.
                fputcsv($out, [(string) $v->code]);
            }
            fclose($out);
        }, $batch->batch_code.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Printable cards: logo, plan, duration, the serial and a QR holding the same serial. */
    private function printView(VoucherBatch $batch, $vouchers): View
    {
        $cards = $vouchers->map(function (Voucher $v) {
            $svg = (new Builder(writer: new SvgWriter))->build(
                data: (string) $v->code,
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 220,
                margin: 4,
            )->getString();

            return [
                'code' => $v->formattedCode(),
                'qr' => 'data:image/svg+xml;base64,'.base64_encode($svg),
            ];
        });

        return view('admin.vouchers.print', ['batch' => $batch, 'cards' => $cards]);
    }

    public function revoke(Request $request, Voucher $voucher): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']], [], ['reason' => 'السبب']);

        $ok = $this->vouchers->revoke($voucher, $data['reason'], $request->user('admin')->id);

        return back()->with('status', $ok ? 'أُلغيت القسيمة.' : 'لا يمكن إلغاء قسيمة مستخدمة أو ملغاة سابقًا.');
    }

    public function revokeBatch(Request $request, VoucherBatch $voucherBatch): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']], [], ['reason' => 'السبب']);

        $count = $this->vouchers->revokeBatch($voucherBatch, $data['reason'], $request->user('admin')->id);

        return back()->with('status', "أُلغيت {$count} قسيمة غير مستخدمة من الدفعة.");
    }

    /** Bills a batch that was created without a price: reseller account + unit price -> a sale in the trade book. */
    public function bill(Request $request, VoucherBatch $voucherBatch): RedirectResponse
    {
        abort_unless($request->user('admin')->hasPermission('trade.manage'), 403);

        $request->merge(['unit_price' => NumberInput::clean($request->input('unit_price'))]);
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:trade_customers,id'],
            'unit_price' => ['required', 'integer', 'min:0', 'max:100000000'],
        ], [], ['customer_id' => 'حساب الشركة', 'unit_price' => 'سعر القسيمة']);

        $customer = TradeCustomer::findOrFail((int) $data['customer_id']);
        $entry = $this->ledger->recordVoucherSale($customer, $voucherBatch, (int) $data['unit_price'], now(), null, $request->user('admin')->id);

        return back()->with('status', 'سُجّل على حساب «'.$customer->name.'» مبلغ '.number_format($entry->amount).' د.ع.');
    }

    /** A new access code for the reseller look-up page (the old one stops working at once). */
    public function rotatePortalCode(Request $request, VoucherBatch $voucherBatch): RedirectResponse
    {
        $voucherBatch->update(['portal_code' => VoucherBatch::newPortalCode()]);

        AuditLogger::log('voucher.portal_code_rotated', 'admin', $request->user('admin')->id, 'VoucherBatch', $voucherBatch->id, 'success', [], $request);

        return back()->with('status', 'أُنشئ رمز اطلاع جديد للدفعة. الرمز القديم لم يعد يعمل.');
    }

    public function updateRecipient(Request $request, VoucherBatch $voucherBatch): RedirectResponse
    {
        $data = $request->validate(['recipient' => ['nullable', 'string', 'max:160']], [], ['recipient' => 'الجهة']);

        $voucherBatch->update(['recipient' => $data['recipient'] ?: null]);
        AuditLogger::log('voucher.recipient_updated', 'admin', $request->user('admin')->id, 'VoucherBatch', $voucherBatch->id, 'success', [
            'recipient' => $data['recipient'] ?? null,
        ], $request);

        return back()->with('status', 'حُدّثت الجهة المستلمة.');
    }

    /** Staff activates a plan for a customer with a serial (the dashboard-side twin of the app's redeem screen). */
    public function redeemForUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:64']], [], ['code' => 'رقم القسيمة']);

        try {
            $voucher = $this->vouchers->redeem($user, $data['code'], 'admin', $request->user('admin')->id);
        } catch (VoucherException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('status', 'فُعّلت باقة «'.$voucher->batch->plan->name_ar.'» للمستخدم لمدة '.$voucher->batch->duration_days.' يومًا.');
    }
}
