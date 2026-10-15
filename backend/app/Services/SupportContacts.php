<?php

namespace App\Services;

use App\Models\SystemSetting;

/**
 * The WhatsApp numbers the support team answers on, set by an administrator and shown in
 * the app's support screen. Each is a label ("الدعم", "المبيعات"...) and an Iraqi number.
 */
class SupportContacts
{
    public const KEY = 'support_whatsapp';

    public const MAX = 4;

    /** @return list<array{label: string, phone: string, url: string}> */
    public function all(): array
    {
        $stored = SystemSetting::get(self::KEY, []);

        return collect(is_array($stored) ? $stored : [])
            ->filter(fn ($row) => is_array($row) && ! empty($row['phone']))
            ->map(fn ($row) => [
                'label' => (string) ($row['label'] ?? ''),
                'phone' => (string) $row['phone'],
                // wa.me wants digits only, with the country code.
                'url' => 'https://wa.me/'.preg_replace('/\D/', '', (string) $row['phone']),
            ])
            ->values()
            ->take(self::MAX)
            ->all();
    }

    /** @param  list<array{label?: ?string, phone?: ?string}>  $rows */
    public function save(array $rows, ?int $adminId = null): void
    {
        $clean = collect($rows)
            ->map(fn ($r) => [
                'label' => trim((string) ($r['label'] ?? '')),
                'phone' => IraqiPhone::normalize((string) ($r['phone'] ?? '')) ?? '',
            ])
            ->filter(fn ($r) => $r['phone'] !== '')
            ->values()
            ->take(self::MAX)
            ->all();

        SystemSetting::set(self::KEY, $clean, $adminId);
    }
}
