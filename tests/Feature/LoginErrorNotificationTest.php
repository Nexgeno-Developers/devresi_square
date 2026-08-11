<?php

namespace Tests\Feature;

use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class LoginErrorNotificationTest extends TestCase
{
    public function test_frontend_login_displays_failed_credentials_notification(): void
    {
        $this->withSession([
            'error' => 'The provided credentials do not match our records.',
        ])->view('frontend.login', ['errors' => new ViewErrorBag()])
            ->assertSee('Login failed')
            ->assertSee('The provided credentials do not match our records.')
            ->assertSee('role="alert"', false);
    }

    public function test_backend_login_displays_failed_credentials_notification(): void
    {
        $this->withSession([
            'error' => 'The provided credentials do not match our records.',
        ])->view('backend.login', ['errors' => new ViewErrorBag()])
            ->assertSee('Login failed')
            ->assertSee('The provided credentials do not match our records.')
            ->assertSee('role="alert"', false);
    }
}
