<?php

namespace App\Services\Payments;

final class CheckoutSession
{
    public function __construct(public readonly string $reference, public readonly string $url) {}
}
