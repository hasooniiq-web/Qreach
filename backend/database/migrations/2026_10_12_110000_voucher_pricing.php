<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Selling vouchers to resellers: a batch can carry the unit selling price (whole
 * dinars) and the reseller's account in the trade book, and the sale is recorded
 * there as a "voucher sale" entry so what each company owes sits next to the sticker
 * accounts and their payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voucher_batches', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_price')->nullable()->after('duration_days');
            $table->foreignId('customer_id')->nullable()->after('recipient')->constrained('trade_customers')->nullOnDelete();
        });

        Schema::table('trade_entries', function (Blueprint $table) {
            $table->foreignId('voucher_batch_id')->nullable()->after('batch_id')->constrained('voucher_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trade_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voucher_batch_id');
        });
        Schema::table('voucher_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn('unit_price');
        });
    }
};
