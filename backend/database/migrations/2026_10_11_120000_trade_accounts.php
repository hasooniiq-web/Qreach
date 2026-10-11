<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A deliberately small accounts book for wholesale sticker sales (staff only):
 * customers (traders), their entries — a sale of N stickers from a batch at a
 * unit price, or a payment received — and receipts. Amounts are whole Iraqi
 * dinars. Entries are never edited or deleted; a mistake is voided with a
 * reason so the book always explains itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 32)->nullable();
            $table->string('notes', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('trade_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('trade_customers')->cascadeOnDelete();
            $table->string('type', 12); // sale | payment
            // Sale: how many stickers, from which batch, at what unit price.
            $table->foreignId('batch_id')->nullable()->constrained('tag_batches')->nullOnDelete();
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedBigInteger('unit_price')->nullable();
            // Whole dinars: quantity * unit_price for a sale, the amount received for a payment.
            $table->unsignedBigInteger('amount');
            $table->string('receipt_number', 24)->nullable()->unique();
            $table->date('occurred_on');
            $table->string('note', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 255)->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'occurred_on']);
            $table->index(['batch_id', 'type']);
        });

        $now = now();
        $keys = [
            ['trade.view', 'trade', 'المخزون والحسابات - عرض'],
            ['trade.manage', 'trade', 'المخزون والحسابات - تسجيل وإدارة'],
        ];
        foreach ($keys as [$key, $group, $nameAr]) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $key],
                ['group' => $group, 'name_ar' => $nameAr, 'name_en' => $key, 'created_at' => $now, 'updated_at' => $now]
            );
        }
        $superAdminId = DB::table('roles')->where('slug', 'super_admin')->value('id');
        if ($superAdminId) {
            foreach (DB::table('permissions')->whereIn('key', array_column($keys, 0))->pluck('id') as $permissionId) {
                DB::table('role_permission')->updateOrInsert(['role_id' => $superAdminId, 'permission_id' => $permissionId], []);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_entries');
        Schema::dropIfExists('trade_customers');
        DB::table('permissions')->whereIn('key', ['trade.view', 'trade.manage'])->delete();
    }
};
