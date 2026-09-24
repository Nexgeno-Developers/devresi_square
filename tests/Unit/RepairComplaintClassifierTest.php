<?php

namespace Tests\Unit;

use App\Services\Repairs\RepairComplaintClassifier;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Tests\TestCase;

class RepairComplaintClassifierTest extends TestCase
{
    private function classifier(): RepairComplaintClassifier
    {
        return new RepairComplaintClassifier();
    }

    public function test_gas_leak_is_a_24_hour_make_safe_priority(): void
    {
        $at = CarbonImmutable::parse('2026-06-02 09:00', 'Europe/London');
        $result = $this->classifier()->classify('gas_leak', $at);

        $this->assertSame('critical', $result['priority']);
        $this->assertTrue($result['emergency_access']);
        $this->assertSame('make_safe_24', $result['snapshot']['clock']);
        $this->assertNull($result['snapshot']['season']);
        $this->assertSame(
            '2026-06-03 09:00',
            $result['make_safe_due_at']->timezone('Europe/London')->format('Y-m-d H:i')
        );
    }

    public function test_contained_leak_is_a_72_hour_priority_with_written_notice(): void
    {
        $at = CarbonImmutable::parse('2026-06-02 09:00', 'Europe/London');
        $result = $this->classifier()->classify('contained_leak', $at);

        $this->assertSame('critical', $result['priority']);
        $this->assertFalse($result['emergency_access']);
        $this->assertSame('hours_72', $result['snapshot']['clock']);
        $this->assertNull($result['make_safe_due_at']);
        $this->assertSame(48, $result['snapshot']['warn_hours']);
        $this->assertSame(
            '2026-06-05 09:00',
            $result['sla_due_at']->timezone('Europe/London')->format('Y-m-d H:i')
        );
    }

    public function test_heating_follows_the_london_season_boundary(): void
    {
        $classifier = $this->classifier();

        $winter = $classifier->classify('heating_loss', CarbonImmutable::parse('2026-04-30 23:30', 'Europe/London'));
        $summer = $classifier->classify('heating_loss', CarbonImmutable::parse('2026-05-01 00:30', 'Europe/London'));
        $lateSummer = $classifier->classify('heating_loss', CarbonImmutable::parse('2026-09-30 18:00', 'Europe/London'));
        $october = $classifier->classify('heating_loss', CarbonImmutable::parse('2026-10-01 00:15', 'Europe/London'));

        $this->assertSame('winter', $winter['snapshot']['season']);
        $this->assertSame('make_safe_24', $winter['snapshot']['clock']);
        $this->assertTrue($winter['emergency_access']);

        $this->assertSame('summer', $summer['snapshot']['season']);
        $this->assertSame('hours_72', $summer['snapshot']['clock']);
        $this->assertFalse($summer['emergency_access']);

        $this->assertSame('summer', $lateSummer['snapshot']['season']);
        $this->assertSame('winter', $october['snapshot']['season']);
        $this->assertTrue($october['emergency_access']);
    }

    public function test_open_summer_heating_upgrades_from_1_october_and_winter_does_not_downgrade(): void
    {
        $classifier = $this->classifier();
        $summer = $classifier->classify('heating_loss', CarbonImmutable::parse('2026-07-15 12:00', 'Europe/London'));
        $upgrade = $classifier->heatingUpgrade($summer['snapshot'], CarbonImmutable::parse('2026-10-03 09:00', 'Europe/London'));

        $this->assertNotNull($upgrade);
        $this->assertSame('winter', $upgrade['snapshot']['season']);
        $this->assertSame('make_safe_24', $upgrade['snapshot']['clock']);
        $this->assertSame(
            '2026-10-01 00:00',
            $upgrade['snapshot']['clock_started_at'] ? CarbonImmutable::parse($upgrade['snapshot']['clock_started_at'])->timezone('Europe/London')->format('Y-m-d H:i') : ''
        );
        $this->assertSame(
            '2026-10-02 00:00',
            $upgrade['make_safe_due_at']->timezone('Europe/London')->format('Y-m-d H:i')
        );

        $this->assertNull($classifier->heatingUpgrade($upgrade['snapshot'], CarbonImmutable::parse('2026-05-02 09:00', 'Europe/London')));
        $this->assertNull($classifier->heatingUpgrade($summer['snapshot'], CarbonImmutable::parse('2026-07-20 09:00', 'Europe/London')));
    }

    public function test_unknown_complaint_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->classifier()->classify('dripping_tap');
    }
}
