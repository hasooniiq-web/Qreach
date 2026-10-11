<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // The customer asked not to renew: the plan keeps working until the period
            // ends (the remaining days are theirs), then it is simply not renewed.
            $table->timestamp('renewal_canceled_at')->nullable()->after('canceled_at');
        });

        // One row per reminder actually sent, so a daily job can never send the same
        // reminder twice for the same period (a renewal moves the period end and
        // therefore starts a fresh set).
        Schema::create('subscription_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 24);
            $table->timestamp('period_ends_at');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['subscription_id', 'kind', 'period_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_notices');
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('renewal_canceled_at');
        });
    }
};
