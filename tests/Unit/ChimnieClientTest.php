<?php

namespace Tests\Unit;

use App\Services\Chimnie\ChimnieClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ChimnieClientTest extends TestCase
{
    public function test_test_mode_returns_fixture_properties_for_a_known_postcode(): void
    {
        config(['chimnie.test_mode' => true]);

        Http::fake([
            'api.postcodes.io/*' => Http::response([
                'result' => [
                    'postcode' => 'SW1A 1AA',
                    'region' => 'London',
                    'post_town' => 'LONDON',
                    'admin_district' => 'Westminster',
                    'admin_county' => null,
                ],
            ], 200),
        ]);

        $results = app(ChimnieClient::class)->searchByPostcode('sw1a1aa');

        $this->assertNotEmpty($results);
        $this->assertSame('test', $results[0]['source']);
        $this->assertSame('SW1A 1AA', $results[0]['postcode']);
        $this->assertNotEmpty($results[0]['line_1']);
        $this->assertSame('Buckingham Palace', $results[0]['line_1']);
    }

    public function test_invalid_postcode_is_rejected(): void
    {
        config(['chimnie.test_mode' => true]);

        $this->expectException(ValidationException::class);

        app(ChimnieClient::class)->searchByPostcode('not a postcode');
    }
}
