<?php

namespace Tests\Unit;

use App\Enums\CrmNotificationEvent;
use Tests\TestCase;
use App\Console\Commands\SendDueCrmNotifications;
use Carbon\CarbonImmutable;

class CrmNotificationCatalogTest extends TestCase
{
    public function test_every_event_has_a_complete_catalog_definition(): void
    {
        $events = config('crm_notifications.events', []);

        foreach (CrmNotificationEvent::cases() as $event) {
            $this->assertArrayHasKey($event->value, $events);
            $definition = $events[$event->value];
            $this->assertNotEmpty($definition['category']);
            $this->assertContains($definition['priority'], ['normal', 'high', 'critical']);
            $this->assertNotEmpty($definition['channels']);
            $this->assertNotEmpty($definition['subject']);
            $this->assertNotEmpty($definition['message']);
            $this->assertIsArray($definition['placeholders']);
            $this->assertArrayHasKey('recipient_resolver', $definition);
            $this->assertArrayHasKey('deep_link', $definition);
        }
    }

    public function test_required_delivery_channels_cannot_be_omitted_from_defaults(): void
    {
        foreach (config('crm_notifications.events', []) as $definition) {
            foreach ($definition['locked_channels'] ?? [] as $lockedChannel) {
                $this->assertContains($lockedChannel, $definition['channels']);
            }
        }
    }

    public function test_uk_deadline_day_calculation_is_stable_across_dst_changes(): void
    {
        $command = new SendDueCrmNotifications();
        $method = new \ReflectionMethod($command, 'calendarDaysUntil');

        $spring = $method->invoke($command, CarbonImmutable::parse('2026-03-28 23:30', 'Europe/London'), '2026-03-30', 'Europe/London');
        $autumn = $method->invoke($command, CarbonImmutable::parse('2026-10-24 23:30', 'Europe/London'), '2026-10-26', 'Europe/London');

        $this->assertSame(2, $spring);
        $this->assertSame(2, $autumn);
    }
}
