<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Most admin tests act as an already-signed-in admin; the mandatory-TOTP rule has its own tests.
        config(['admin.require_totp' => false]);
    }
}
