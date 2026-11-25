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

    public function test_live_search_lists_the_addresses_in_a_postcode_and_reuses_them(): void
    {
        $this->useLiveChimnie();
        Http::fake(['api.propertydatagateway.com/search/postcode/*' => Http::response([
            'addresses' => [
                'Rose Cottage, 15 Chimnie Walk, Chimnietown, Chimnieshire, CH1 1MN',
                'Prime Minister, 11, Downing Street, London, CH1 1MN',
            ],
            'session' => 'ac-session',
        ])]);

        $client = app(ChimnieClient::class);
        $rows = $client->searchByPostcode('ch11mn');
        $client->searchByPostcode('CH1 1MN');

        $this->assertSame('Rose Cottage', $rows[0]['line_1']);
        $this->assertSame('15 Chimnie Walk', $rows[0]['line_2']);
        $this->assertSame('Chimnietown', $rows[0]['city']);
        $this->assertSame('Chimnieshire', $rows[0]['county']);
        $this->assertSame('11 Downing Street', $rows[1]['line_2']);
        $this->assertSame('London', $rows[1]['city']);
        $this->assertSame('live', $rows[0]['source']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-key')
            && $request->url() === 'https://api.propertydatagateway.com/search/postcode/CH1%201MN');
    }

    public function test_choosing_a_live_address_fetches_its_details_once(): void
    {
        $this->useLiveChimnie();
        Http::fake([
            'api.propertydatagateway.com/search/postcode/*' => Http::response([
                'addresses' => ['Flat 6 Chimnie Court, Chimnietown, CH1 1MN'],
                'session' => 'ac-session',
            ]),
            'api.propertydatagateway.com/residential/address' => Http::response([
                'id' => '999000000006',
                'property' => [
                    'attributes' => ['indoor' => ['bedrooms' => 2, 'bathrooms' => 1, 'reception_rooms' => 1, 'floor_area' => 61]],
                    'bills' => ['energy' => ['energy_rating_current' => 'C'], 'tax' => ['council_tax_band' => 'B']],
                    'ownership' => ['lease_type' => 'leasehold'],
                ],
                'plus' => ['property' => ['attributes' => ['status' => ['property_class' => 'Flat', 'property_type' => 'Apartment', 'is_hmo' => false]]]],
            ]),
        ]);

        $client = app(ChimnieClient::class);
        $id = $client->searchByPostcode('CH1 1MN')[0]['id'];
        $client->findById($id, 'CH1 1MN');
        $record = $client->findById($id, 'CH1 1MN');

        $this->assertSame('999000000006', $record['uprn']);
        $this->assertSame('2', $record['bedroom']);
        $this->assertSame('flat', $record['specific_property_type']);
        $this->assertSame('Leasehold', $record['tenure']);
        $this->assertSame('61', $record['square_meter']);
        // One search plus one billed lookup, even though the property was chosen twice.
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request['autocomplete_session'] === 'ac-session'
            && ! str_contains($request['fields'], 'premium')
            && ! str_contains($request['fields'], 'ultra'));
    }

    public function test_live_address_can_still_be_saved_when_details_are_refused(): void
    {
        $this->useLiveChimnie();
        Http::fake([
            'api.propertydatagateway.com/search/postcode/*' => Http::response([
                'addresses' => ['1 Chimnie Street, Chimnietown, CH1 1MN'],
            ]),
            'api.propertydatagateway.com/residential/address' => Http::response(['message' => 'Payment required'], 402),
        ]);

        $client = app(ChimnieClient::class);
        $record = $client->findById($client->searchByPostcode('CH1 1MN')[0]['id'], 'CH1 1MN');

        $this->assertSame('1 Chimnie Street', $record['line_1']);
        $this->assertTrue($record['details_unavailable']);
    }

    public function test_live_search_explains_a_refused_key(): void
    {
        $this->useLiveChimnie();
        Http::fake(['api.propertydatagateway.com/*' => Http::response(['message' => 'Unauthorized'], 401)]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Property lookup is unavailable right now');

        app(ChimnieClient::class)->searchByPostcode('CH1 1MN');
    }

    private function useLiveChimnie(): void
    {
        config([
            'chimnie.test_mode' => false,
            'chimnie.api_key' => 'test-key',
            'chimnie.base_url' => 'https://api.propertydatagateway.com',
        ]);
    }

    public function test_invalid_postcode_is_rejected(): void
    {
        config(['chimnie.test_mode' => true]);

        $this->expectException(ValidationException::class);

        app(ChimnieClient::class)->searchByPostcode('not a postcode');
    }
}
