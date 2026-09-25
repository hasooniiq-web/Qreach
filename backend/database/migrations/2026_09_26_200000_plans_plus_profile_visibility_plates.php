<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - Pro becomes Plus; Basic may now show name/phone/emergency contact, Plus adds
 *   any number of extra contact platforms (features JSON, enforced server-side).
 * - contact_methods.type is no longer a closed DB enum, so new platforms need no schema change.
 * - Profile visibility: the name is hidden unless the owner switches it on;
 *   an emergency contact is only shown when flagged public.
 * - Iraqi plate details on vehicles; a short reason code on visitor reports.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('alter table contact_methods drop constraint if exists contact_methods_type_check');
        }
        Schema::table('contact_methods', fn (Blueprint $t) => $t->string('type', 32)->change());

        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('plate_type', 16)->default('private');
            $table->string('plate_letter', 4)->nullable();
            $table->string('plate_governorate', 40)->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('show_name_publicly')->default(false);
        });

        Schema::table('emergency_contacts', function (Blueprint $table) {
            $table->boolean('is_public')->default(false);
        });

        Schema::table('contact_requests', function (Blueprint $table) {
            $table->string('reason', 20)->nullable();
        });

        DB::table('subscription_plans')->where('slug', 'pro')->update([
            'slug' => 'plus', 'name_ar' => 'بلس', 'name_en' => 'Plus',
            'description_ar' => 'كل ما في الأساسية + إضافة حسابات وطرق تواصل (إنستغرام، فيسبوك، سناب، تيك توك، واتساب...) والتحكم بما يراه الزائر.',
            'features' => json_encode([
                'messaging' => true, 'emergency_contact' => true, 'extra_contact_methods' => true,
                'public_contacts' => ['phone', 'whatsapp', 'telegram', 'instagram', 'facebook', 'snapchat', 'tiktok', 'twitter', 'linkedin', 'youtube', 'website', 'email', 'other'],
            ]),
        ]);
        DB::table('subscription_plans')->where('slug', 'basic')->update([
            'description_ar' => 'استخدام التطبيق + الرسائل + عرض اسمك ورقمك وجهة اتصال الطوارئ حسب اختيارك.',
            'features' => json_encode([
                'messaging' => true, 'emergency_contact' => true, 'extra_contact_methods' => false, 'public_contacts' => ['phone'],
            ]),
        ]);
    }

    public function down(): void
    {
        Schema::table('contact_requests', fn (Blueprint $t) => $t->dropColumn('reason'));
        Schema::table('emergency_contacts', fn (Blueprint $t) => $t->dropColumn('is_public'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('show_name_publicly'));
        Schema::table('vehicles', fn (Blueprint $t) => $t->dropColumn(['plate_type', 'plate_letter', 'plate_governorate']));
        DB::table('subscription_plans')->where('slug', 'plus')->update(['slug' => 'pro', 'name_ar' => 'الاحترافية', 'name_en' => 'Pro']);
    }
};
