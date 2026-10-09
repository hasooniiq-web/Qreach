<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\Payments\GatewayDriver;
use App\Services\Payments\GatewayRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Where an authorized admin switches payment providers on and enters their
 * credentials. Secrets are write-only: stored encrypted, never displayed again
 * (the form only shows whether each one is set), and never written to the
 * audit log — only *which* settings changed.
 */
class PaymentGatewayController extends Controller
{
    public function __construct(private readonly GatewayRegistry $registry) {}

    public function index(): View
    {
        $rows = collect($this->registry->all())->map(function (GatewayDriver $driver) {
            $gateway = $this->registry->configFor($driver);

            return [
                'driver' => $driver,
                'gateway' => $gateway,
                'configured' => $this->registry->isConfigured($driver, $gateway),
                'allowed' => $driver->availableIn(app()->environment()),
            ];
        })->values();

        return view('admin.payment-gateways.index', ['rows' => $rows]);
    }

    public function edit(string $driver): View
    {
        $impl = $this->driverOrFail($driver);
        $gateway = $this->registry->configFor($impl);

        return view('admin.payment-gateways.edit', [
            'driver' => $impl,
            'gateway' => $gateway,
            'allowed' => $impl->availableIn(app()->environment()),
            'webhookUrl' => route('api.v1.payments.webhook', $impl->driver()),
            'set' => collect($impl->credentialFields())->map(fn ($f, $key) => $gateway->credential($key) !== null),
        ]);
    }

    public function update(Request $request, string $driver): RedirectResponse
    {
        $impl = $this->driverOrFail($driver);
        $gateway = $this->registry->configFor($impl);

        $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'is_test_mode' => ['nullable', 'boolean'],
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $credentials = $gateway->credentials ?? [];
        $changed = [];
        foreach (array_keys($impl->credentialFields()) as $key) {
            $value = trim((string) $request->input("credentials.$key", ''));
            // Blank means "keep the current value": a secret is never echoed back into the form.
            if ($value !== '') {
                $credentials[$key] = $value;
                $changed[] = $key;
            }
        }

        $enabled = $request->boolean('is_enabled');
        $gateway->credentials = $credentials;

        if ($enabled && ! $impl->availableIn(app()->environment())) {
            throw ValidationException::withMessages(['is_enabled' => 'هذه البوابة غير مسموح بها في بيئة الإنتاج.']);
        }
        if ($enabled && ! $this->registry->isConfigured($impl, $gateway)) {
            throw ValidationException::withMessages(['is_enabled' => 'أدخل كل بيانات الاعتماد المطلوبة قبل تفعيل البوابة.']);
        }

        $before = ['is_enabled' => $gateway->is_enabled, 'is_test_mode' => $gateway->is_test_mode];
        $gateway->fill([
            'is_enabled' => $enabled,
            'is_test_mode' => $request->boolean('is_test_mode'),
            'updated_by' => $request->user('admin')->id,
        ])->save();

        AuditLogger::log('payment_gateway.updated', 'admin', $request->user('admin')->id, 'PaymentGateway', $gateway->id, 'success', [
            'driver' => $impl->driver(),
            'enabled' => [$before['is_enabled'], $gateway->is_enabled],
            'test_mode' => [$before['is_test_mode'], $gateway->is_test_mode],
            // Names only — never the values.
            'credentials_changed' => $changed,
        ], $request);

        return redirect()->route('admin.payment-gateways.index')->with('status', 'تم حفظ إعدادات بوابة الدفع.');
    }

    private function driverOrFail(string $driver): GatewayDriver
    {
        $impl = $this->registry->driver($driver);
        abort_if(! $impl, 404);

        return $impl;
    }
}
