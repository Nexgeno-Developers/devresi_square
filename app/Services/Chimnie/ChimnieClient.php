<?php

namespace App\Services\Chimnie;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ChimnieClient
{
    public function searchByPostcode(string $postcode): array
    {
        $normalised = $this->normalisePostcode($postcode);

        if (! $this->looksLikeUkPostcode($normalised)) {
            throw ValidationException::withMessages([
                'postcode' => 'Enter a valid UK postcode, for example SW1A 1AA.',
            ]);
        }

        if (config('chimnie.test_mode', true)) {
            return $this->testSearch($normalised);
        }

        return $this->liveSearch($normalised);
    }

    public function findById(string $id, string $postcode): ?array
    {
        foreach ($this->searchByPostcode($postcode) as $property) {
            if (($property['id'] ?? null) === $id) {
                return $property;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function testSearch(string $postcode): array
    {
        $locality = $this->lookupLocality($postcode);
        $compact = str_replace(' ', '', $postcode);

        $fixtures = $this->famousFixtures()[$compact] ?? null;
        $rows = $fixtures ?: $this->syntheticFixtures($postcode, $locality);

        return collect($rows)
            ->map(function (array $row) use ($postcode, $locality) {
                $city = $row['city'] ?? $locality['city'];
                $county = $row['county'] ?? $locality['county'];
                $line1 = $row['line_1'];
                $line2 = $row['line_2'] ?? '';

                return [
                    'id' => $row['id'] ?? ('chimnie-test:'.$postcode.':'.md5($line1.$line2)),
                    'uprn' => $row['uprn'] ?? $this->syntheticUprn($postcode, $line1),
                    'label' => trim($line1.($line2 !== '' ? ', '.$line2 : '').', '.$city.', '.$postcode),
                    'line_1' => $line1,
                    'line_2' => $line2,
                    'city' => $city,
                    'county' => $county,
                    'postcode' => $postcode,
                    'country' => 'United Kingdom',
                    'country_code' => 'GB',
                    'specific_property_type' => $row['specific_property_type'] ?? 'flat',
                    'bedroom' => (string) ($row['bedroom'] ?? '2'),
                    'bathroom' => (string) ($row['bathroom'] ?? '1'),
                    'reception' => (string) ($row['reception'] ?? '1'),
                    'tenure' => $row['tenure'] ?? 'Leasehold',
                    'epc_rating' => $row['epc_rating'] ?? 'C',
                    'council_tax_band' => $row['council_tax_band'] ?? 'D',
                    'square_meter' => (string) ($row['square_meter'] ?? '68'),
                    'floor' => $row['floor'] ?? '1',
                    'local_authority' => $row['local_authority'] ?? $locality['local_authority'],
                    'source' => 'test',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function liveSearch(string $postcode): array
    {
        $apiKey = (string) config('chimnie.api_key');

        if ($apiKey === '') {
            throw ValidationException::withMessages([
                'postcode' => 'Chimnie live mode needs CHIMNIE_API_KEY, or turn CHIMNIE_TEST_MODE back on.',
            ]);
        }

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('chimnie.timeout', 12))
                ->withToken($apiKey)
                ->get(rtrim((string) config('chimnie.base_url'), '/').'/v1/properties', [
                    'postcode' => $postcode,
                ]);
        } catch (RequestException $exception) {
            throw ValidationException::withMessages([
                'postcode' => 'Chimnie did not respond. Try again in a moment.',
            ]);
        }

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'postcode' => 'Chimnie could not look up that postcode right now.',
            ]);
        }

        $payload = $response->json();
        $rows = $payload['data'] ?? $payload['results'] ?? $payload['properties'] ?? $payload;

        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => $this->normaliseLiveRow($row, $postcode))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normaliseLiveRow(array $row, string $postcode): array
    {
        $address = is_array($row['address'] ?? null) ? $row['address'] : $row;
        $line1 = (string) ($address['line_1'] ?? $address['line1'] ?? $address['address_line_1'] ?? $row['paon'] ?? '');
        $line2 = (string) ($address['line_2'] ?? $address['line2'] ?? $address['address_line_2'] ?? $row['street'] ?? '');
        $city = (string) ($address['city'] ?? $address['town'] ?? $address['post_town'] ?? '');
        $resolvedPostcode = strtoupper((string) ($address['postcode'] ?? $row['postcode'] ?? $postcode));

        return [
            'id' => (string) ($row['id'] ?? $row['uprn'] ?? $line1.'|'.$resolvedPostcode),
            'uprn' => (string) ($row['uprn'] ?? ''),
            'label' => (string) ($row['label'] ?? trim($line1.', '.$city.', '.$resolvedPostcode, ' ,')),
            'line_1' => $line1,
            'line_2' => $line2,
            'city' => $city,
            'county' => (string) ($address['county'] ?? ''),
            'postcode' => $resolvedPostcode,
            'country' => 'United Kingdom',
            'country_code' => 'GB',
            'specific_property_type' => (string) ($row['property_type'] ?? $row['specific_property_type'] ?? 'flat'),
            'bedroom' => (string) ($row['bedrooms'] ?? $row['bedroom'] ?? ''),
            'bathroom' => (string) ($row['bathrooms'] ?? $row['bathroom'] ?? ''),
            'reception' => (string) ($row['receptions'] ?? $row['reception'] ?? ''),
            'tenure' => (string) ($row['tenure'] ?? ''),
            'epc_rating' => (string) ($row['epc_rating'] ?? $row['epc'] ?? ''),
            'council_tax_band' => (string) ($row['council_tax_band'] ?? ''),
            'square_meter' => (string) ($row['internal_area'] ?? $row['square_meter'] ?? ''),
            'floor' => (string) ($row['floor'] ?? ''),
            'local_authority' => (string) ($row['local_authority'] ?? ''),
            'source' => 'live',
        ];
    }

    /**
     * @return array{city: string, county: string, local_authority: string}
     */
    private function lookupLocality(string $postcode): array
    {
        $fallback = [
            'city' => 'London',
            'county' => 'Greater London',
            'local_authority' => 'Unknown',
        ];

        try {
            $response = Http::acceptJson()
                ->timeout(6)
                ->get('https://api.postcodes.io/postcodes/'.rawurlencode($postcode));
        } catch (\Throwable) {
            return $fallback;
        }

        if ($response->failed()) {
            return $fallback;
        }

        $result = $response->json('result');

        if (! is_array($result)) {
            return $fallback;
        }

        $region = trim((string) ($result['region'] ?? ''));
        $city = strcasecmp($region, 'London') === 0
            ? 'London'
            : $this->firstFilled($result, ['post_town', 'admin_district', 'parish']);

        return [
            'city' => $city !== '' ? $city : $fallback['city'],
            'county' => $this->firstFilled($result, ['admin_county', 'admin_district']) ?: $fallback['county'],
            'local_authority' => $this->firstFilled($result, ['admin_district', 'parish']) ?: $fallback['local_authority'],
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function famousFixtures(): array
    {
        return [
            'SW1A1AA' => [
                [
                    'id' => 'chimnie-test:SW1A1AA:palace',
                    'uprn' => '100023336956',
                    'line_1' => 'Buckingham Palace',
                    'city' => 'London',
                    'county' => 'Greater London',
                    'specific_property_type' => 'house',
                    'bedroom' => '240',
                    'bathroom' => '78',
                    'reception' => '19',
                    'tenure' => 'Freehold',
                    'epc_rating' => 'G',
                    'council_tax_band' => 'H',
                    'square_meter' => '77000',
                    'floor' => 'Ground',
                    'local_authority' => 'Westminster',
                ],
                [
                    'id' => 'chimnie-test:SW1A1AA:mall',
                    'uprn' => '100023336957',
                    'line_1' => '1 The Mall',
                    'city' => 'London',
                    'county' => 'Greater London',
                    'specific_property_type' => 'flat',
                    'bedroom' => '3',
                    'bathroom' => '2',
                    'reception' => '1',
                    'tenure' => 'Leasehold',
                    'epc_rating' => 'C',
                    'council_tax_band' => 'G',
                    'square_meter' => '128',
                    'floor' => '2',
                    'local_authority' => 'Westminster',
                ],
            ],
            'E149RU' => [
                [
                    'id' => 'chimnie-test:E149RU:1',
                    'line_1' => 'Flat 12, 1 Baltimore Wharf',
                    'city' => 'London',
                    'specific_property_type' => 'flat',
                    'bedroom' => '2',
                    'bathroom' => '2',
                    'tenure' => 'Leasehold',
                    'epc_rating' => 'B',
                    'council_tax_band' => 'E',
                    'square_meter' => '74',
                    'floor' => '8',
                    'local_authority' => 'Tower Hamlets',
                ],
                [
                    'id' => 'chimnie-test:E149RU:2',
                    'line_1' => 'Flat 45, 1 Baltimore Wharf',
                    'city' => 'London',
                    'specific_property_type' => 'flat',
                    'bedroom' => '1',
                    'bathroom' => '1',
                    'tenure' => 'Leasehold',
                    'epc_rating' => 'B',
                    'council_tax_band' => 'D',
                    'square_meter' => '51',
                    'floor' => '14',
                    'local_authority' => 'Tower Hamlets',
                ],
                [
                    'id' => 'chimnie-test:E149RU:3',
                    'line_1' => 'Flat 108, 1 Baltimore Wharf',
                    'city' => 'London',
                    'specific_property_type' => 'flat',
                    'bedroom' => '3',
                    'bathroom' => '2',
                    'tenure' => 'Leasehold',
                    'epc_rating' => 'C',
                    'council_tax_band' => 'F',
                    'square_meter' => '96',
                    'floor' => '22',
                    'local_authority' => 'Tower Hamlets',
                ],
            ],
        ];
    }

    /**
     * @param  array{city: string, county: string, local_authority: string}  $locality
     * @return array<int, array<string, mixed>>
     */
    private function syntheticFixtures(string $postcode, array $locality): array
    {
        $seed = crc32(str_replace(' ', '', $postcode));
        $streets = ['High Street', 'Church Road', 'Victoria Road', 'Park Lane', 'Station Road', 'Queen Street'];
        $templates = [
            ['flat', 1, 1, 'Leasehold', 'C', 'D', '48', '1'],
            ['flat', 2, 1, 'Leasehold', 'B', 'E', '67', '3'],
            ['flat', 2, 2, 'Leasehold', 'C', 'E', '74', '5'],
            ['house', 3, 2, 'Freehold', 'D', 'D', '92', 'Ground'],
            ['house', 4, 2, 'Freehold', 'C', 'F', '128', 'Ground'],
            ['maisonette', 2, 1, 'Leasehold', 'D', 'C', '71', 'Ground'],
        ];

        $street = $streets[$seed % count($streets)];

        return collect($templates)
            ->values()
            ->map(function (array $template, int $index) use ($seed, $street, $locality, $postcode) {
                $number = 4 + (($seed + ($index * 7)) % 90);
                $isFlat = in_array($template[0], ['flat', 'maisonette'], true);
                $line1 = $isFlat
                    ? 'Flat '.($index + 1).', '.$number.' '.$street
                    : $number.' '.$street;

                return [
                    'id' => 'chimnie-test:'.str_replace(' ', '', $postcode).':'.$index,
                    'line_1' => $line1,
                    'city' => $locality['city'],
                    'county' => $locality['county'],
                    'specific_property_type' => $template[0],
                    'bedroom' => $template[1],
                    'bathroom' => $template[2],
                    'tenure' => $template[3],
                    'epc_rating' => $template[4],
                    'council_tax_band' => $template[5],
                    'square_meter' => $template[6],
                    'floor' => $template[7],
                    'local_authority' => $locality['local_authority'],
                ];
            })
            ->all();
    }

    private function syntheticUprn(string $postcode, string $line1): string
    {
        $numeric = (string) abs(crc32($postcode.'|'.$line1));

        return str_pad(substr($numeric, 0, 12), 12, '0');
    }

    private function normalisePostcode(string $postcode): string
    {
        $compact = strtoupper(preg_replace('/\s+/', '', trim($postcode)) ?? '');

        if (strlen($compact) < 5) {
            return $compact;
        }

        return substr($compact, 0, -3).' '.substr($compact, -3);
    }

    private function looksLikeUkPostcode(string $postcode): bool
    {
        return (bool) preg_match('/^[A-Z]{1,2}\d[A-Z\d]?\s\d[A-Z]{2}$/', $postcode);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function firstFilled(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
