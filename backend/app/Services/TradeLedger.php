<?php

namespace App\Services;

use App\Models\TagBatch;
use App\Models\TradeCustomer;
use App\Models\TradeEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The accounts book for wholesale sticker sales. Money is whole dinars. Rules:
 *   - a sale can only hand over stickers the batch still has (checked under a row lock);
 *   - a payment gets the next receipt number;
 *   - nothing is edited or deleted — a wrong entry is voided with a reason, and
 *     voided entries drop out of stock and balances.
 */
class TradeLedger
{
    /** Stickers of a batch not yet handed to a customer. */
    public function remainingInBatch(TagBatch $batch): int
    {
        return max(0, $batch->quantity - $this->deliveredFromBatch($batch));
    }

    public function deliveredFromBatch(TagBatch $batch): int
    {
        return (int) TradeEntry::where('batch_id', $batch->id)
            ->where('type', TradeEntry::SALE)->whereNull('voided_at')->sum('quantity');
    }

    public function recordSale(
        TradeCustomer $customer,
        TagBatch $batch,
        int $quantity,
        int $unitPrice,
        Carbon $date,
        ?string $note,
        ?int $adminId,
    ): TradeEntry {
        return DB::transaction(function () use ($customer, $batch, $quantity, $unitPrice, $date, $note, $adminId) {
            // Serialize deliveries from the same batch so two staff members cannot both
            // take the last stickers.
            $locked = TagBatch::where('id', $batch->id)->lockForUpdate()->firstOrFail();
            $remaining = $this->remainingInBatch($locked);

            if ($quantity > $remaining) {
                throw ValidationException::withMessages([
                    'quantity' => "المتبقي في هذه الدفعة {$remaining} فقط.",
                ]);
            }

            $entry = TradeEntry::create([
                'customer_id' => $customer->id,
                'type' => TradeEntry::SALE,
                'batch_id' => $locked->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $quantity * $unitPrice,
                'occurred_on' => $date->toDateString(),
                'note' => $note,
                'created_by' => $adminId,
            ]);

            AuditLogger::log('trade.sale_recorded', 'admin', $adminId, 'TradeEntry', $entry->id, 'success', [
                'customer_id' => $customer->id, 'batch' => $locked->batch_code, 'quantity' => $quantity, 'amount' => $entry->amount,
            ]);

            return $entry;
        });
    }

    public function recordPayment(TradeCustomer $customer, int $amount, Carbon $date, ?string $note, ?int $adminId): TradeEntry
    {
        return DB::transaction(function () use ($customer, $amount, $date, $note, $adminId) {
            $entry = TradeEntry::create([
                'customer_id' => $customer->id,
                'type' => TradeEntry::PAYMENT,
                'amount' => $amount,
                'occurred_on' => $date->toDateString(),
                'note' => $note,
                'created_by' => $adminId,
            ]);

            // The receipt number is derived from the row id: unique and gap-free per
            // payment row, and it can never be handed out twice.
            $entry->update(['receipt_number' => sprintf('RC-%s-%06d', $date->format('Y'), $entry->id)]);

            AuditLogger::log('trade.payment_recorded', 'admin', $adminId, 'TradeEntry', $entry->id, 'success', [
                'customer_id' => $customer->id, 'amount' => $amount, 'receipt' => $entry->receipt_number,
            ]);

            return $entry->fresh();
        });
    }

    public function void(TradeEntry $entry, string $reason, ?int $adminId): TradeEntry
    {
        return DB::transaction(function () use ($entry, $reason, $adminId) {
            $entry = TradeEntry::where('id', $entry->id)->lockForUpdate()->firstOrFail();

            if ($entry->isVoided()) {
                return $entry;
            }

            $entry->update(['voided_at' => now(), 'void_reason' => $reason, 'voided_by' => $adminId]);

            AuditLogger::log('trade.entry_voided', 'admin', $adminId, 'TradeEntry', $entry->id, 'success', [
                'type' => $entry->type, 'amount' => $entry->amount, 'reason' => $reason,
            ]);

            return $entry;
        });
    }

    /**
     * Overview figures for the book.
     *
     * @return array{stock: int, delivered: int, sales: int, received: int, outstanding: int}
     */
    public function totals(): array
    {
        $produced = (int) TagBatch::sum('quantity');
        $delivered = (int) TradeEntry::where('type', TradeEntry::SALE)->whereNull('voided_at')->sum('quantity');
        $sales = (int) TradeEntry::where('type', TradeEntry::SALE)->whereNull('voided_at')->sum('amount');
        $received = (int) TradeEntry::where('type', TradeEntry::PAYMENT)->whereNull('voided_at')->sum('amount');

        return [
            'stock' => max(0, $produced - $delivered),
            'delivered' => $delivered,
            'sales' => $sales,
            'received' => $received,
            'outstanding' => $sales - $received,
        ];
    }
}
