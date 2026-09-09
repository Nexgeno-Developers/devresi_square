<?php

namespace Tests\Unit;

use App\Services\Property\UkOpenDataPropertyEnricher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UkOpenDataPropertyEnricherTest extends TestCase
{
    public function test_fills_blank_chimnie_fields_from_open_data(): void
    {
        config([
            'uk_open_data.epc.email' => 'epc@example.test',
            'uk_open_data.epc.api_key' => 'secret',
        ]);

        Http::fake([
            'api.postcodes.io/*' => Http::response([
                'result' => [
                    'region' => 'London',
                    'post_town' => 'LONDON',
                    'admin_district' => 'Westminster',
                    'admin_county' => 'Greater London',
                ],
            ], 200),
            'findthatpostcode.uk/*' => Http::response([
                'data' => ['attributes' => ['laua_name' => 'Westminster']],
            ], 200),
            'epc.opendatacommunities.org/*' => Http::response([
                'rows' => [[
                    'address' => '1 THE MALL',
                    'property-type' => 'Flat',
                    'current-energy-rating' => 'B',
                    'total-floor-area' => 88,
                ]],
            ], 200),
        ]);

        $enriched = app(UkOpenDataPropertyEnricher::class)->enrich([
            'postcode' => 'SW1A 1AA',
            'line_1' => '1 The Mall',
            'source' => 'chimnie',
        ]);

        $this->assertSame('London', $enriched['city']);
        $this->assertSame('B', $enriched['epc_rating']);
        $this->assertSame('88', $enriched['square_meter']);
        $this->assertSame('flat', $enriched['specific_property_type']);
        $this->assertContains('epc.open-data', $enriched['data_sources']);
        $this->assertContains('postcodes.io', $enriched['data_sources']);
    }
}
