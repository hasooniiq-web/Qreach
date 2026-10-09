<?php

namespace App\Services\Payments;

/** What a provider reported about one payment. `status` is succeeded | failed | pending. */
final class CallbackResult
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public readonly string $reference,
        public readonly string $status,
        public readonly int $amount,
        public readonly string $currency,
        public readonly ?string $eventKey = null,
        public readonly ?string $failureReason = null,
        public readonly array $raw = [],
    ) {}
}
