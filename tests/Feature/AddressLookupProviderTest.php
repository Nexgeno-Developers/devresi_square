<?php

namespace Tests\Feature;

use App\Services\AddressLookup\AddressLookupManager;
use App\Services\AddressLookup\Providers\GetAddressProvider;
use App\Services\AddressLookup\Providers\PostcodesIoProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddressLookupProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_postcodes_io_returns_free_postcode_and_locality_data_in_the_common_format(): void
    {
        Http::fake([
            'https://api.postcodes.io/postcodes*' => Http::response([
                'status' => 200,
                'result' => [[
                    'postcode' => 'E14 9RU',
                    'admin_district' => 'Tower Hamlets',
                    'admin_county' => null,
                    'region' => 'London',
                    'country' => 'England',
                ]],
            ]),
        ]);

        $provider = new PostcodesIoProvider([
            'base_url' => 'https://api.postcodes.io',
            'timeout' => 5,
        ]);

        $suggestions = $provider->search('E14 9RU', 20);

        $this->assertCount(1, $suggestions);
        $this->assertSame('E14 9RU', $suggestions[0]['address']['postcode']);
        $this->assertSame('London', $suggestions[0]['address']['city']);
        $this->assertSame('', $suggestions[0]['address']['line_1']);
        $this->assertSame('GB', $suggestions[0]['address']['country_code']);
    }

    public function test_active_provider_can_be_switched_to_ideal_postcodes_without_changing_consumers(): void
    {
        config([
            'address_lookup.default' => 'ideal_postcodes',
            'address_lookup.limit' => 20,
            'address_lookup.providers.ideal_postcodes' => [
                'driver' => 'ideal_postcodes',
                'name' => 'Ideal Postcodes',
                'full_address' => true,
                'base_url' => 'https://api.ideal-postcodes.co.uk/v1',
                'api_key' => 'test-key',
                'timeout' => 5,
                'attribution' => [
                    'label' => 'Ideal Postcodes',
                    'url' => 'https://ideal-postcodes.co.uk/',
                ],
            ],
        ]);

        Http::fake([
            'https://api.ideal-postcodes.co.uk/v1/postcodes/*' => Http::response([
                'result' => [[
                    'line_1' => 'Flat 60, Discovery Dock Apartments East',
                    'line_2' => '3 South Quay Square',
                    'post_town' => 'London',
                    'county' => 'Greater London',
                    'postcode' => 'E14 9RU',
                    'country_iso_2' => 'GB',
                    'uprn' => '100023456789',
                    'udprn' => '12345678',
                ]],
                'code' => 2000,
                'message' => 'Success',
            ]),
        ]);

        $result = (new AddressLookupManager)->search('E14 9RU');
        $address = $result['suggestions'][0]['address'];

        $this->assertSame('Ideal Postcodes', $result['provider']);
        $this->assertTrue($result['full_address']);
        $this->assertSame('Flat 60, Discovery Dock Apartments East', $address['line_1']);
        $this->assertSame('London', $address['city']);
        $this->assertSame('100023456789', $address['uprn']);
    }

    public function test_getaddress_suggestion_can_be_resolved_to_the_same_common_format(): void
    {
        Http::fake([
            'https://api.getaddress.io/autocomplete/*' => Http::response([
                'suggestions' => [[
                    'id' => 'address-token',
                    'address' => 'Flat 106, Kensington Apartments, London',
                ]],
            ]),
            'https://api.getaddress.io/get/*' => Http::response([
                'postcode' => 'E1 6NE',
                'formatted_address' => [
                    'Flat 106, Kensington Apartments',
                    '11 Commercial Street',
                    '',
                    'London',
                    'Greater London',
                ],
                'uprn' => '100098765432',
            ]),
        ]);

        $provider = new GetAddressProvider([
            'base_url' => 'https://api.getAddress.io',
            'api_key' => 'test-key',
            'timeout' => 5,
        ]);

        $suggestions = $provider->search('E1 6NE', 20);
        $address = $provider->resolve($suggestions[0]['id']);

        $this->assertNull($suggestions[0]['address']);
        $this->assertSame('Flat 106, Kensington Apartments', $address['line_1']);
        $this->assertSame('11 Commercial Street', $address['line_2']);
        $this->assertSame('E1 6NE', $address['postcode']);
        $this->assertSame('100098765432', $address['uprn']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/autocomplete/E1%206NE')
                && $request['all'] === 'true';
        });
    }
}
