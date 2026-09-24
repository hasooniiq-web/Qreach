<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Section 13: custom messages must never allow HTML/JS. Rejects outright
 * rather than silently stripping tags — silent stripping can leave a
 * confusing, mangled message and hides the problem from the sender.
 */
class PlainTextOnly implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (strip_tags($value) !== $value) {
            $fail('لا يُسمح بإدخال وسوم HTML في هذا الحقل.');

            return;
        }

        if (preg_match('/(javascript:|data:text\/html|<script|on\w+\s*=)/i', $value)) {
            $fail('المحتوى المدخل غير مسموح به.');
        }
    }
}
