<?php

namespace App\Contracts;

interface SmsGatewayContract
{
    /**
     * Send a short plain-text message to a phone number. Implementations
     * must never log the message body at a level visible in production
     * (see LogSmsGateway, which is dev-only for exactly this reason).
     */
    public function send(string $phone, string $message): bool;
}
