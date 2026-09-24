<?php

namespace Tests\Feature;

use App\Services\Ops\ProductionOpsConfig;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LaunchOpsConfigHttpTest extends TestCase
{
    public function test_assert_ops_fails_when_production_queue_is_sync(): void
    {
        config([
            'queue.default' => 'sync',
            'app.debug' => false,
            'app.url' => 'https://app.resisquare.test',
        ]);

        $this->artisan('launch:assert-ops', ['--env' => 'production'])
            ->assertFailed();
    }

    public function test_assert_ops_passes_for_local_with_sync_queue(): void
    {
        config([
            'queue.default' => 'sync',
            'app.debug' => true,
            'app.url' => 'http://localhost',
        ]);

        $this->artisan('launch:assert-ops', ['--env' => 'local'])
            ->assertSuccessful();

        $this->assertTrue(app(ProductionOpsConfig::class)->isReady('local'));
    }

    public function test_expected_schedule_signatures_are_registered(): void
    {
        $expected = app(ProductionOpsConfig::class)->expectedScheduleSignatures();

        Artisan::call('schedule:list');
        $list = Artisan::output();

        foreach ($expected as $signature) {
            $this->assertStringContainsString($signature, $list);
        }
    }
}
