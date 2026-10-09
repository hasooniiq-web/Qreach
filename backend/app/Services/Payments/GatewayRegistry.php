<?php

namespace App\Services\Payments;

use App\Models\PaymentGateway;

/**
 * The providers this build knows how to talk to, and which of them an admin
 * has configured and switched on.
 *
 * To add a real provider (ZainCash, FIB, Qi Card, ...): implement GatewayDriver
 * from that provider's own merchant documentation, then list the class below.
 */
class GatewayRegistry
{
    /** @var array<string, GatewayDriver> */
    private array $drivers = [];

    public function __construct()
    {
        foreach ([new SandboxDriver] as $driver) {
            $this->drivers[$driver->driver()] = $driver;
        }
    }

    /** @return array<string, GatewayDriver> */
    public function all(): array
    {
        return $this->drivers;
    }

    public function driver(string $key): ?GatewayDriver
    {
        return $this->drivers[$key] ?? null;
    }

    /** The persisted settings for a driver, created (disabled) on first use. */
    public function configFor(GatewayDriver $driver): PaymentGateway
    {
        return PaymentGateway::firstOrCreate(
            ['driver' => $driver->driver()],
            ['name' => $driver->label(), 'is_enabled' => false, 'is_test_mode' => true]
        );
    }

    /** Has every required credential been entered? */
    public function isConfigured(GatewayDriver $driver, PaymentGateway $gateway): bool
    {
        foreach ($driver->credentialFields() as $key => $field) {
            if ($field['required'] && $gateway->credential($key) === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Providers a customer may pay with right now: enabled, fully configured and
     * allowed in this environment.
     *
     * @return list<array{driver: GatewayDriver, gateway: PaymentGateway}>
     */
    public function available(): array
    {
        $out = [];
        foreach ($this->drivers as $driver) {
            if (! $driver->availableIn(app()->environment())) {
                continue;
            }
            $gateway = PaymentGateway::where('driver', $driver->driver())->first();
            if ($gateway && $gateway->is_enabled && $this->isConfigured($driver, $gateway)) {
                $out[] = ['driver' => $driver, 'gateway' => $gateway];
            }
        }

        return $out;
    }
}
