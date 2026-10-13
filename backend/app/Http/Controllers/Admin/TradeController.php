<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TagBatch;
use App\Models\TradeCustomer;
use App\Models\TradeEntry;
use App\Services\TradeLedger;
use App\Support\NumberInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Stock of stickers and a simple accounts book for wholesale customers (staff
 * only). Reading needs `trade.view`; recording, voiding and adding customers
 * needs `trade.manage`.
 */
class TradeController extends Controller
{
    use \App\Http\Controllers\Concerns\ExportsTables;

    public function __construct(private readonly TradeLedger $ledger) {}

    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $customers = TradeCustomer::query()
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')
                ->orWhere('phone', 'like', '%'.$request->string('q').'%'))
            ->withSum(['entries as sales_total' => fn ($q) => $q->whereIn('type', TradeEntry::CHARGES)->whereNull('voided_at')], 'amount')
            ->withSum(['entries as paid_total' => fn ($q) => $q->where('type', TradeEntry::PAYMENT)->whereNull('voided_at')], 'amount')
            ->withSum(['entries as delivered_qty' => fn ($q) => $q->where('type', TradeEntry::SALE)->whereNull('voided_at')], 'quantity')
            ->orderBy('name');

        if ($export = $this->exportRows($request, 'trade-customers', 'المخزون والحسابات — العملاء', ['العميل', 'الهاتف', 'المُسلَّم', 'مجموع البيع', 'المدفوع', 'المتبقي عليه'], $customers,
            fn ($c) => [
                (string) $c->name,
                (string) ($c->phone ?: '—'),
                (int) $c->delivered_qty,
                (int) $c->sales_total,
                (int) $c->paid_total,
                (int) $c->sales_total - (int) $c->paid_total,
            ],
            summary: [
                'في المخزون' => $this->ledger->totals()['stock'],
                'مُسلَّم للتجار' => $this->ledger->totals()['delivered'],
                'مجموع البيع (د.ع)' => $this->ledger->totals()['sales'],
                'المتبقي على التجار (د.ع)' => $this->ledger->totals()['outstanding'],
            ],
            filters: $this->filterLines($request, ['q' => 'بحث']))) {
            return $export;
        }

        $customers = $customers->paginate(25)->withQueryString();

        $batches = TagBatch::query()->latest('id')->paginate(10, ['*'], 'batches_page')->withQueryString()
            ->through(fn (TagBatch $b) => [
                'batch' => $b,
                'delivered' => $this->ledger->deliveredFromBatch($b),
                'remaining' => $this->ledger->remainingInBatch($b),
            ]);

        return view('admin.trade.index', [
            'customers' => $customers,
            'totals' => $this->ledger->totals(),
            'batches' => $batches,
        ]);
    }

    public function createCustomer(): View
    {
        return view('admin.trade.customer-create');
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], ['name' => 'الاسم', 'phone' => 'الهاتف', 'notes' => 'الملاحظات']);

        $customer = TradeCustomer::create($data + ['created_by' => $request->user('admin')->id]);

        return redirect()->route('admin.trade.customers.show', $customer)->with('status', 'تمت إضافة العميل.');
    }

    public function showCustomer(Request $request, TradeCustomer $customer): View|\Illuminate\Http\Response
    {
        $entriesQuery = $customer->entries()->with('batch', 'voucherBatch')->orderByDesc('occurred_on')->orderByDesc('id');

        if ($export = $this->exportRows($request, 'account-'.$customer->id, 'كشف حساب: '.$customer->name, ['التاريخ', 'القيد', 'رقم الوصل', 'الدفعة', 'الكمية', 'سعر الستيكر', 'المبلغ', 'ملاحظة'], $entriesQuery,
            fn ($e) => [
                $e->occurred_on->format('Y-m-d'),
                ($e->type === 'sale' ? 'تسليم ملصقات' : ($e->type === 'voucher_sale' ? 'قسائم تفعيل' : 'دفعة مستلمة')).($e->isVoided() ? ' (مُلغى: '.$e->void_reason.')' : ''),
                (string) ($e->receipt_number ?: '—'),
                (string) ($e->batch?->batch_code ?? $e->voucherBatch?->batch_code ?? '—'),
                $e->quantity !== null ? (int) $e->quantity : '—',
                $e->unit_price !== null ? (int) $e->unit_price : '—',
                ($e->isCharge() ? '+' : '−').number_format((int) $e->amount),
                (string) ($e->note ?: ''),
            ],
            summary: ['الرصيد (د.ع)' => $customer->balance()],
            filters: ['الهاتف: '.($customer->phone ?: '—')])) {
            return $export;
        }

        $entries = $entriesQuery->paginate(40);

        $batches = TagBatch::query()->latest('id')->limit(30)->get()
            ->map(fn (TagBatch $b) => ['batch' => $b, 'remaining' => $this->ledger->remainingInBatch($b)])
            ->filter(fn ($row) => $row['remaining'] > 0)
            ->values();

        $lastPrice = TradeEntry::where('type', TradeEntry::SALE)->whereNull('voided_at')
            ->orderByDesc('id')->value('unit_price');

        return view('admin.trade.customer', [
            'customer' => $customer,
            'entries' => $entries,
            'balance' => $customer->balance(),
            'batches' => $batches,
            'lastPrice' => $lastPrice,
        ]);
    }

    public function storeSale(Request $request, TradeCustomer $customer): RedirectResponse
    {
        $request->merge([
            'quantity' => NumberInput::clean($request->input('quantity')),
            'unit_price' => NumberInput::clean($request->input('unit_price')),
        ]);

        $data = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:tag_batches,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'unit_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'occurred_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], ['batch_id' => 'الدفعة', 'quantity' => 'الكمية', 'unit_price' => 'سعر الستيكر']);

        $entry = $this->ledger->recordSale(
            $customer,
            TagBatch::findOrFail($data['batch_id']),
            (int) $data['quantity'],
            (int) $data['unit_price'],
            Carbon::parse($data['occurred_on'] ?? now()),
            $data['note'] ?? null,
            $request->user('admin')->id,
        );

        return redirect()->route('admin.trade.customers.show', $customer)
            ->with('status', 'سُجّل تسليم '.number_format($entry->quantity).' ستيكر بمبلغ '.number_format($entry->amount).' د.ع.');
    }

    public function storePayment(Request $request, TradeCustomer $customer): RedirectResponse
    {
        $request->merge(['amount' => NumberInput::clean($request->input('amount'))]);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:100000000000'],
            'occurred_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], ['amount' => 'المبلغ']);

        $entry = $this->ledger->recordPayment(
            $customer,
            (int) $data['amount'],
            Carbon::parse($data['occurred_on'] ?? now()),
            $data['note'] ?? null,
            $request->user('admin')->id,
        );

        return redirect()->route('admin.trade.receipt', $entry)->with('status', 'سُجّلت الدفعة وصدر وصل القبض '.$entry->receipt_number.'.');
    }

    public function receipt(TradeEntry $entry): View
    {
        abort_unless($entry->type === TradeEntry::PAYMENT, 404);

        $entry->load('customer');

        return view('admin.trade.receipt', [
            'entry' => $entry,
            // The customer's position right after this payment (entries up to and including it).
            'balanceAfter' => (int) TradeEntry::where('customer_id', $entry->customer_id)->whereNull('voided_at')
                ->where('id', '<=', $entry->id)->whereIn('type', TradeEntry::CHARGES)->sum('amount')
                - (int) TradeEntry::where('customer_id', $entry->customer_id)->whereNull('voided_at')
                    ->where('id', '<=', $entry->id)->where('type', TradeEntry::PAYMENT)->sum('amount'),
        ]);
    }

    public function void(Request $request, TradeEntry $entry): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']], [], ['reason' => 'السبب']);

        $this->ledger->void($entry, $data['reason'], $request->user('admin')->id);

        return back()->with('status', 'تم إلغاء القيد وأُخرج من الحساب والمخزون.');
    }
}
