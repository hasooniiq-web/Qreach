<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A curated set of settings that actually change application behavior
 * (OtpService, StoreTagBatchRequest) — not a generic, unused key/value
 * editor. Every setting shown here is read somewhere in real code.
 */
class SystemSettingController extends Controller
{
    private const DEFINITIONS = [
        'otp_code_ttl_minutes' => [
            'label' => 'مدة صلاحية رمز التحقق (دقائق)',
            'hint' => 'كم يبقى رمز التحقق المرسل بالرسالة صالحًا قبل أن ينتهي.',
            'default' => 5, 'min' => 1, 'max' => 30,
        ],
        'otp_resend_cooldown_seconds' => [
            'label' => 'مدة الانتظار قبل إعادة إرسال رمز التحقق (ثواني)',
            'hint' => 'الحد الأدنى بين طلبين متتاليين لرمز جديد لنفس الرقم، لمنع إساءة الاستخدام.',
            'default' => 60, 'min' => 15, 'max' => 600,
        ],
        'qr_batch_max_quantity' => [
            'label' => 'الحد الأقصى لعدد الرموز في الدفعة الواحدة',
            'hint' => 'يطبَّق عند إنشاء دفعة QR أو NFC جديدة.',
            'default' => 1000, 'min' => 1, 'max' => 20000,
        ],
    ];

    public function index(): View
    {
        $settings = collect(self::DEFINITIONS)->map(function ($definition, $key) {
            return $definition + ['key' => $key, 'value' => SystemSetting::get($key, $definition['default'])];
        });

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        // The form shows numbers with thousands separators (1,000); store plain integers.
        $request->merge(collect(self::DEFINITIONS)->keys()->mapWithKeys(
            fn ($key) => [$key => \App\Support\NumberInput::clean($request->input($key))]
        )->all());

        $rules = [];
        foreach (self::DEFINITIONS as $key => $definition) {
            $rules[$key] = ['required', 'integer', "min:{$definition['min']}", "max:{$definition['max']}"];
        }

        $validated = $request->validate($rules);

        foreach ($validated as $key => $value) {
            SystemSetting::set($key, $value, $request->user('admin')->id);
        }

        AuditLogger::log(
            'admin.settings_updated', 'admin', $request->user('admin')->id,
            'SystemSetting', null, 'success', $validated, $request
        );

        return back()->with('status', 'تم حفظ الإعدادات بنجاح.');
    }
}
