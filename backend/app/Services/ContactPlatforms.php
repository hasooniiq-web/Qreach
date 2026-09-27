<?php

namespace App\Services;

/**
 * The contact platforms an owner can add (label, how a value is validated and
 * cleaned, and the link a visitor taps). One table drives the API validation,
 * the public scan page and the app's platform picker, so a platform is added in
 * one place. `phone` is the only Basic platform; the rest need Plus (that rule
 * lives in the plan's features, see PlanEntitlements).
 */
class ContactPlatforms
{
    /** @return array<string, array{label: string, hint: string}> */
    public static function all(): array
    {
        return [
            // No country code in the hint: the system is Iraq-only, and the
            // value is normalized (with or without the leading zero) below.
            'phone' => ['label' => 'اتصال هاتفي', 'hint' => '07701234567'],
            'whatsapp' => ['label' => 'واتساب', 'hint' => '07701234567'],
            'telegram' => ['label' => 'تيليغرام', 'hint' => 'username'],
            'instagram' => ['label' => 'إنستغرام', 'hint' => 'username'],
            'facebook' => ['label' => 'فيسبوك', 'hint' => 'username أو رابط الصفحة'],
            'snapchat' => ['label' => 'سناب شات', 'hint' => 'username'],
            'tiktok' => ['label' => 'تيك توك', 'hint' => 'username'],
            'twitter' => ['label' => 'إكس (تويتر)', 'hint' => 'username'],
            'linkedin' => ['label' => 'لينكدإن', 'hint' => 'username'],
            'youtube' => ['label' => 'يوتيوب', 'hint' => '@channel'],
            'website' => ['label' => 'الموقع الإلكتروني', 'hint' => 'https://example.com'],
            'email' => ['label' => 'البريد الإلكتروني', 'hint' => 'name@example.com'],
            'other' => ['label' => 'أخرى', 'hint' => ''],
        ];
    }

    public static function isKnown(string $type): bool
    {
        return array_key_exists($type, self::all());
    }

    public static function label(string $type, ?string $custom = null): string
    {
        return $type === 'other' && $custom ? $custom : (self::all()[$type]['label'] ?? 'معلومات إضافية');
    }

    /** Cleaned value to store, or null when it isn't valid for that platform. */
    public static function normalize(string $type, string $value): ?string
    {
        $value = trim($value);
        $handle = ltrim($value, '@');

        return match ($type) {
            'phone', 'whatsapp' => IraqiPhone::normalize($value),
            'telegram' => preg_match('/^[A-Za-z0-9_]{5,32}$/', $handle) ? $handle : null,
            'instagram' => preg_match('/^[A-Za-z0-9._]{1,30}$/', $handle) ? $handle : null,
            'facebook' => preg_match('/^[A-Za-z0-9.]{5,50}$/', $handle) || preg_match('#^https://(www\.)?(facebook|fb)\.com/[\w.\-/?=&%]+$#i', $value)
                ? ($handle === $value || str_starts_with($value, 'https://') ? $value : $handle) : null,
            'snapchat' => preg_match('/^[A-Za-z0-9._-]{3,15}$/', $handle) ? $handle : null,
            'tiktok' => preg_match('/^[A-Za-z0-9._]{2,24}$/', $handle) ? $handle : null,
            'twitter' => preg_match('/^[A-Za-z0-9_]{1,15}$/', $handle) ? $handle : null,
            'linkedin' => preg_match('/^[A-Za-z0-9\-]{3,100}$/', $handle) ? $handle : null,
            'youtube' => preg_match('/^[A-Za-z0-9._-]{3,30}$/', $handle) ? $handle : null,
            'website' => preg_match('#^https://[^\s<>"]{3,190}$#i', $value) && filter_var($value, FILTER_VALIDATE_URL) ? $value : null,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) && strlen($value) <= 120 ? $value : null,
            'other' => $value !== '' && mb_strlen($value) <= 200 && strip_tags($value) === $value ? $value : null,
            default => null,
        };
    }

    /** The link a visitor taps, or null when the value is display-only. */
    public static function href(string $type, string $value): ?string
    {
        $digits = preg_replace('/\D/', '', $value);

        return match ($type) {
            'phone' => 'tel:'.preg_replace('/[^\d+]/', '', $value),
            'whatsapp' => 'https://wa.me/'.$digits,
            'telegram' => 'https://t.me/'.$value,
            'instagram' => 'https://instagram.com/'.$value,
            'facebook' => str_starts_with($value, 'https://') ? $value : 'https://facebook.com/'.$value,
            'snapchat' => 'https://www.snapchat.com/add/'.$value,
            'tiktok' => 'https://www.tiktok.com/@'.$value,
            'twitter' => 'https://x.com/'.$value,
            'linkedin' => 'https://www.linkedin.com/in/'.$value,
            'youtube' => 'https://www.youtube.com/@'.$value,
            'website' => $value,
            'email' => 'mailto:'.$value,
            default => null,
        };
    }

    /** What to print on the button: handles get a leading @ where that is the convention. */
    public static function display(string $type, string $value): string
    {
        return in_array($type, ['instagram', 'telegram', 'snapchat', 'tiktok', 'twitter', 'youtube'], true) ? '@'.$value : $value;
    }
}
