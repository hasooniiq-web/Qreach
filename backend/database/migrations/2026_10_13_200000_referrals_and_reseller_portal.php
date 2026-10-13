<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Invite a friend: every customer gets a personal invite code; when a friend who
 * entered it makes their first paid activation, the inviter is given free days.
 *
 * Reseller portal: each voucher batch gets an access code so the reseller can look
 * up how many of their vouchers were activated (counts only — no serials, no users).
 */
return new class extends Migration
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 12)->nullable()->unique();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            // One customer can be invited once, ever.
            $table->foreignId('referred_id')->unique()->constrained('users')->cascadeOnDelete();
            // pending (friend has not paid yet) | rewarded | capped (inviter reached the reward limit)
            $table->string('status', 12)->default('pending');
            $table->unsignedSmallInteger('reward_days')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();

            $table->index(['referrer_id', 'status']);
        });

        Schema::table('voucher_batches', function (Blueprint $table) {
            $table->string('portal_code', 12)->nullable();
        });

        // Batches that already exist get their code now.
        DB::table('voucher_batches')->whereNull('portal_code')->orderBy('id')->each(function ($batch) {
            DB::table('voucher_batches')->where('id', $batch->id)->update(['portal_code' => $this->randomCode(8)]);
        });
    }

    public function down(): void
    {
        Schema::table('voucher_batches', function (Blueprint $table) {
            $table->dropColumn('portal_code');
        });

        Schema::dropIfExists('referrals');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['referral_code']);
            $table->dropColumn('referral_code');
        });
    }

    private function randomCode(int $length): string
    {
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[random_int(0, 31)];
        }

        return $out;
    }
};
