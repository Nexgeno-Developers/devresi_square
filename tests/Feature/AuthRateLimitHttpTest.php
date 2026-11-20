<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthRateLimitHttpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login:ratelimit-probe@example.com|127.0.0.1');
    }

    public function test_repeated_wrong_passwords_are_throttled(): void
    {
        $payload = ['email' => 'ratelimit-probe@example.com', 'password' => 'not-the-password'];

        for ($i = 0; $i < 10; $i++) {
            $this->from(route('login'))->post(route('login.post'), $payload)
                ->assertSessionDoesntHaveErrors(['email' => 'Too many sign-in attempts. Please wait a minute and try again.']);
        }

        $this->from(route('login'))->post(route('login.post'), $payload)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Too many sign-in attempts. Please wait a minute and try again.']);
    }

    public function test_throttled_json_requests_get_a_429(): void
    {
        $payload = ['email' => 'ratelimit-probe@example.com', 'password' => 'not-the-password'];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('login.post'), $payload);
        }

        $this->postJson(route('login.post'), $payload)->assertStatus(429);
    }

    public function test_auth_routes_carry_throttle_middleware(): void
    {
        $expected = [
            'login.post' => 'throttle:login',
            'backend.login.post' => 'throttle:login',
            'password.reset' => 'throttle:login',
            'register.post' => 'throttle:auth-mail',
            'register.contractor.post' => 'throttle:auth-mail',
            'register.resend.otp' => 'throttle:auth-mail',
            'password.email' => 'throttle:auth-mail',
            'register.verify.otp.post' => 'throttle:otp',
            'form.submit' => 'throttle:public-form',
        ];

        foreach ($expected as $name => $middleware) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertNotNull($route, $name);
            $this->assertContains($middleware, $route->gatherMiddleware(), $name);
        }
    }

    public function test_sms_text_is_never_logged_when_sending_is_off(): void
    {
        \Illuminate\Support\Facades\Log::spy();

        (new \App\Services\SendSmsService)->sendSMS('+447700900123', 'Resisquare', 'Your code is 482913');

        \Illuminate\Support\Facades\Log::shouldNotHaveReceived('warning', [\Mockery::on(fn ($m) => str_contains((string) $m, '482913')), \Mockery::any()]);
        \Illuminate\Support\Facades\Log::shouldHaveReceived('info')->withArgs(fn ($m, $ctx = []) => ! str_contains($m.json_encode($ctx), '482913'));
    }
}
