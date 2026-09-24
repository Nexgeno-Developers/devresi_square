<?php

namespace Tests\Unit;

use Tests\TestCase;

class BladeKitComponentsTest extends TestCase
{
    public function test_lw_components_render_kit_classes(): void
    {
        $hero = $this->blade('<x-lw.hero title="Finance" subtitle="Rent invoices" />');
        $this->assertStringContainsString('lw-hero', $hero);
        $this->assertStringContainsString('Finance', $hero);

        $btn = $this->blade('<x-lw.btn variant="secondary" href="/x">Save</x-lw.btn>');
        $this->assertStringContainsString('lw-btn-secondary', $btn);
        $this->assertStringContainsString('Save', $btn);

        $pill = $this->blade('<x-lw.pill tone="ok">Paid</x-lw.pill>');
        $this->assertStringContainsString('lw-pill-ok', $pill);

        $empty = $this->blade('<x-lw.empty title="No visits">Book an inspection.</x-lw.empty>');
        $this->assertStringContainsString('lw-empty', $empty);
        $this->assertStringContainsString('No visits', $empty);
    }

    public function test_landlord_workspace_css_ships_kit_and_bootstrap_ban(): void
    {
        $css = file_get_contents(public_path('asset/backend/css/landlord-workspace.css'));
        $this->assertNotFalse($css);
        foreach ([
            '.lw-btn-primary',
            '.lw-btn-secondary',
            '.lw-btn-danger',
            '.lw-pill-ok',
            '.lw-status-warn',
            '.lw-empty',
            '.lw-chip-filters',
            'body.landlord-workspace .btn-primary',
            'body.landlord-workspace .btn-warning',
            'body.landlord-workspace .btn-danger',
            'body.tenant-portal-shell .btn-primary',
        ] as $needle) {
            $this->assertStringContainsString($needle, $css, "Missing kit rule: {$needle}");
        }
    }
}
