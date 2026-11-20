<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests must never reach real services. Unfaked HTTP-client calls fail loudly,
        // and the Stripe SDK points at a closed local port so any call fails fast offline.
        Http::preventStrayRequests();
        \Stripe\Stripe::$apiBase = 'http://127.0.0.1:9';
        config(['services.sms.enabled' => false]);
    }
}
