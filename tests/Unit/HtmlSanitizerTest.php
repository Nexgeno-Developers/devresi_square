<?php

namespace Tests\Unit;

use App\Services\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_strips_script_and_event_handlers(): void
    {
        $sanitizer = new HtmlSanitizer();

        $dirty = '<p>Hello</p><script>alert(1)</script><img src=x onerror="alert(1)"><a href="javascript:alert(1)">x</a>';
        $clean = $sanitizer->sanitize($dirty);

        $this->assertStringNotContainsString('<script', strtolower($clean));
        $this->assertStringNotContainsString('onerror', strtolower($clean));
        $this->assertStringNotContainsString('javascript:', strtolower($clean));
        $this->assertStringContainsString('<p>Hello</p>', $clean);
    }

    public function test_keeps_safe_formatting(): void
    {
        $sanitizer = new HtmlSanitizer();
        $clean = $sanitizer->sanitize('<p><strong>Bold</strong> and <em>italic</em></p><ul><li>One</li></ul>');

        $this->assertStringContainsString('<strong>Bold</strong>', $clean);
        $this->assertStringContainsString('<em>italic</em>', $clean);
        $this->assertStringContainsString('<li>One</li>', $clean);
    }
}
