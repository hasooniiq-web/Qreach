<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Electronic subscription payments: gateway configuration (credentials encrypted
 * at rest), the extra columns a real payment needs, an event log of everything a
 * gateway told us (deduplicated), and the permissions to manage it all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('driver')->unique();
            $table->string('name');
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_test_mode')->default(true);
            // Encrypted JSON (Laravel `encrypted:array` cast, keyed by APP_KEY) — never plain text.
            $table->longText('credentials')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('subscription_id')->constrained('subscription_plans')->nullOnDelete();
            $table->string('receipt_number')->nullable()->unique()->after('uuid');
            $table->text('checkout_url')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedInteger('refunded_amount_cents')->default(0);
            $table->timestamp('refunded_at')->nullable();
            // One gateway reference can only ever belong to one payment.
            $table->unique(['gateway', 'gateway_reference']);
        });

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway');
            // The provider's own event/transaction id: a repeat delivery is recognised and ignored.
            $table->string('event_key')->nullable();
            $table->string('type');
            $table->boolean('signature_valid')->default(true);
            $table->string('outcome')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['gateway', 'event_key']);
            $table->index('payment_id');
        });

        $this->addPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['gateway', 'gateway_reference']);
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['receipt_number', 'checkout_url', 'failure_reason', 'paid_at', 'refunded_amount_cents', 'refunded_at']);
        });
        Schema::dropIfExists('payment_gateways');
    }

    /** Idempotent: the seeder defines the same keys for fresh installs. */
    private function addPermissions(): void
    {
        $now = now();
        $keys = [
            ['payments.view', 'payments', 'المدفوعات - عرض'],
            ['payments.refund', 'payments', 'المدفوعات - استرداد'],
            ['payment_gateways.view', 'payment_gateways', 'بوابات الدفع - عرض'],
            ['payment_gateways.manage', 'payment_gateways', 'بوابات الدفع - إدارة'],
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
};
