<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turns the billing schema into a real Basic/Pro plan system: plans carry a
 * `features` JSON (what the plan is *allowed* to do — enforced server-side by
 * App\Services\PlanEntitlements), and customers can ask to upgrade. There is
 * no payment gateway, so an upgrade is a request an admin approves, not a
 * checkout.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->json('features')->nullable();
            $table->string('description_ar')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
        });

        // Earlier placeholder plans ("free"/"standard") become Basic/Pro so any
        // existing subscription rows keep pointing at the right plan.
        DB::table('subscription_plans')->where('slug', 'free')->update(['slug' => 'basic']);
        DB::table('subscription_plans')->where('slug', 'standard')->update(['slug' => 'pro']);

        Schema::create('plan_upgrade_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('subscription_plans');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('note', 500)->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_upgrade_requests');

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['features', 'description_ar', 'sort_order']);
        });
    }
};
