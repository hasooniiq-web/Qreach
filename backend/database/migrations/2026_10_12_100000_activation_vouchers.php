<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Activation vouchers: single-use 16-digit serials (printed or shown as a QR) that
 * switch a paid plan on for a number of days when entered in the app. They are
 * generated in batches, handed to resellers (digital-card companies), and every
 * export and redemption is recorded.
 *
 * The serial itself is stored encrypted (so staff can export it) with a keyed hash
 * next to it for lookup — a database dump alone does not reveal usable serials.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 32)->unique();
            $table->foreignId('plan_id')->constrained('subscription_plans');
            $table->unsignedInteger('duration_days');
            $table->unsignedInteger('quantity');
            $table->string('recipient', 160)->nullable();      // the reseller / company it is meant for
            $table->string('notes', 500)->nullable();
            $table->timestamp('expires_at')->nullable();       // vouchers cannot be redeemed after this
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('voucher_batches')->cascadeOnDelete();
            $table->char('code_hash', 64)->unique();           // HMAC-SHA256 of the 16 digits, for lookup
            $table->text('code');                              // the 16 digits, encrypted
            $table->char('last4', 4);                          // for masked display / search
            // available -> distributed (exported/handed over) -> redeemed | revoked
            $table->string('status', 16)->default('available');
            $table->timestamp('exported_at')->nullable();
            $table->string('delivered_to', 160)->nullable();
            $table->foreignId('redeemed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('redeemed_at')->nullable();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoke_reason', 255)->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['batch_id', 'status']);
            $table->index('last4');
            $table->index('redeemed_by');
        });

        // One row per export: when, by whom, in what format, how many, and to whom it went.
        Schema::create('voucher_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('voucher_batches')->cascadeOnDelete();
            $table->foreignId('exported_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('format', 12);
            $table->unsignedInteger('count');
            $table->string('recipient', 160)->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('batch_id');
        });

        $now = now();
        $keys = [
            ['vouchers.view', 'vouchers', 'قسائم التفعيل - عرض'],
            ['vouchers.create', 'vouchers', 'قسائم التفعيل - إنشاء دفعة'],
            ['vouchers.export', 'vouchers', 'قسائم التفعيل - تصدير وطباعة'],
            ['vouchers.revoke', 'vouchers', 'قسائم التفعيل - إلغاء'],
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
        Schema::dropIfExists('voucher_exports');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('voucher_batches');
        DB::table('permissions')->where('group', 'vouchers')->delete();
    }
};
