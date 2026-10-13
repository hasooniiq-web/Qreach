<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Plan names and descriptions in the three app languages (entered in the dashboard,
 * the app picks by its language), and the one-time free trial of Plus: a customer's
 * first and only trial is remembered on the user row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('name_ckb', 100)->nullable()->after('name_en');
            $table->string('description_en', 255)->nullable()->after('description_ar');
            $table->string('description_ckb', 255)->nullable()->after('description_en');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('plus_trial_used_at')->nullable();
        });

        // Starting texts for the two plans that exist; the dashboard edits them from here on.
        $defaults = [
            'basic' => [
                'name_ckb' => 'بنەڕەتی',
                'description_en' => 'Use the app + messaging + show your name, phone and an emergency contact as you choose.',
                'description_ckb' => 'بەکارهێنانی ئەپ + پەیام + پیشاندانی ناو و ژمارە و کەسی کاتی ناکاو بە هەڵبژاردنی خۆت.',
            ],
            'plus' => [
                'name_ckb' => 'Plus',
                'description_en' => 'Everything in Basic + add accounts and contact methods (Instagram, Facebook, Snapchat, TikTok, WhatsApp...) and control what visitors see.',
                'description_ckb' => 'هەموو شتەکانی بنەڕەتی + زیادکردنی هەژمار و ڕێگەی پەیوەندی (ئینستاگرام، فەیسبووک، سناپ، تیکتۆک، واتساپ...) و کۆنتڕۆڵی ئەوەی سەردانکەر دەیبینێت.',
            ],
        ];

        foreach ($defaults as $slug => $values) {
            DB::table('subscription_plans')->where('slug', $slug)->update($values);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('plus_trial_used_at');
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['name_ckb', 'description_en', 'description_ckb']);
        });
    }
};
