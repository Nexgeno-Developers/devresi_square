<?php

namespace App\Services\Chimnie;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Chimnie Data API (https://docs.chimnie.com).
 *
 * A live lookup takes two steps: /search/postcode lists the addresses, then /residential/address
 * returns the data for the one the landlord picks. Property lookups are billed, so results are
 * cached and details are only requested for a chosen address.
 */
class ChimnieClient
{
    /**
     * Only the fields the property record uses. Leaving "fields" empty returns, and bills, every
     * tier up to Ultra; these stay within Plus, which is the minimum for an address lookup.
     */
    private const DETAIL_FIELDS = [
        'property.attributes.indoor.bedrooms',
        'property.attributes.indoor.bathrooms',
        'property.attributes.indoor.reception_rooms',
        'property.attributes.indoor.floor_area',
        'property.bills.tax.council_tax_band',
        'property.bills.energy.energy_rating_current',
        'property.ownership.lease_type',
        'plus.property.attributes.status.property_class',
        'plus.property.attributes.status.property_type',
        'plus.property.attributes.status.is_hmo',
    ];

    public function searchByPostcode(string $postcode): array
    {
        $normalised = $this->normalisePostcode($postcode);

        if (! $this->looksLikeUkPostcode($normalised)) {
            throw ValidationException::withMessages([
                'postcode' => 'Enter a valid UK postcode, for example SW1A 1AA.',
            ]);
        }

        if (config('chimnie.test_mode', false)) {
            return $this->testSearch($normalised);
        }

        return $this->liveSearch($normalised);
    }

    /**
     * The chosen property with its full data. In live mode this is the billed lookup; if Chimnie
     * cannot supply the data, the address alone comes back so the property can still be saved.
     */
    public function findById(string $id, string $postcode): ?array
    {
        foreach ($this->searchByPostcode($postcode) as $property) {
            if (($property['id'] ?? null) === $id) {
                return config('chimnie.test_mode', false) ? $property : $this->withLiveDetails($property);
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
        $compact = str_replace(' ', '', $postcode);
        $cacheKey = 'chimnie:search:'.$compact;

        // Choosing and saving a property both search again: reuse the list rather than pay twice.
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $response = $this->send('get', '/search/postcode/'.rawurlencode($postcode));

        if ($response->status() === 404) {
            return [];
        }

        if ($response->failed()) {
            $this->fail($response, 'postcode search');
        }

        $rows = collect($response->json('addresses'))
            ->filter(fn ($address) => is_string($address) && trim($address) !== '')
            ->map(fn (string $address) => $this->rowFromAddress($address, $postcode))
            ->values()
            ->all();

        if ($rows !== []) {
            Cache::put($cacheKey, $rows, now()->addHour());
        }

        // Passing this session to the address lookup makes the search free. Chimnie expires it after 5 minutes.
        $session = (string) $response->json('session');
        if ($session !== '') {
            Cache::put('chimnie:session:'.$compact, $session, now()->addSeconds(290));
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function withLiveDetails(array $row): array
    {
        $cacheKey = 'chimnie:property:'.md5((string) $row['label']);
        $payload = Cache::get($cacheKey);

        if (! is_array($payload)) {
            $body = [
                'address' => $row['label'],
                'fields' => implode(',', self::DETAIL_FIELDS),
            ];
            $session = Cache::pull('chimnie:session:'.str_replace(' ', '', (string) $row['postcode']));
            if ($session) {
                $body['autocomplete_session'] = $session;
            }

            try {
                $response = $this->send('post', '/residential/address', $body);
            } catch (ValidationException) {
                return $row + ['details_unavailable' => true];
            }

            // 404 means Chimnie holds no match, or none of these fields, for the address.
            if ($response->failed() && $response->status() !== 404) {
                $this->log($response, 'property lookup');

                return $row + ['details_unavailable' => true];
            }

            $payload = $response->successful() ? (array) $response->json() : [];
            Cache::put($cacheKey, $payload, now()->addDay());
        }

        $details = array_filter($this->detailsFromPayload($payload), fn (string $value) => $value !== '');

        return array_merge($row, $details, $details === [] ? ['details_unavailable' => true] : []);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    private function detailsFromPayload(array $payload): array
    {
        $value = function (string $path) use ($payload): string {
            $found = data_get($payload, $path);

            return is_scalar($found) ? trim((string) $found) : '';
        };
        $indoor = 'property.attributes.indoor.';
        $status = 'plus.property.attributes.status.';

        return [
            'uprn' => $value('id'),
            'bedroom' => $value($indoor.'bedrooms'),
            'bathroom' => $value($indoor.'bathrooms'),
            'reception' => $value($indoor.'reception_rooms'),
            'square_meter' => $value($indoor.'floor_area'),
            'council_tax_band' => strtoupper($value('property.bills.tax.council_tax_band')),
            'epc_rating' => strtoupper($value('property.bills.energy.energy_rating_current')),
            'tenure' => ucfirst(strtolower($value('property.ownership.lease_type'))),
            'specific_property_type' => $this->propertyType(
                $value($status.'property_class').' '.$value($status.'property_type'),
                (bool) data_get($payload, $status.'is_hmo'),
            ),
        ];
    }

    /**
     * Map Chimnie's wording onto the property types the app offers ("bunglow" is the stored spelling).
     */
    private function propertyType(string $description, bool $isHmo): string
    {
        if ($isHmo) {
            return 'hmo';
        }

        $description = strtolower($description);

        return match (true) {
            str_contains($description, 'bungalow') => 'bunglow',
            str_contains($description, 'maisonette') => 'maisonette',
            str_contains($description, 'studio') => 'studio',
            str_contains($description, 'flat'), str_contains($description, 'apartment') => 'flat',
            str_contains($description, 'house'),
            str_contains($description, 'detached'),
            str_contains($description, 'terrace') => 'house',
            default => '',
        };
    }

    /**
     * The postcode search returns each address as one line, for example
     * "Rose Cottage, 15 Chimnie Walk, Chimnietown, Chimnieshire, CH1 1MN".
     *
     * @return array<string, mixed>
     */
    private function rowFromAddress(string $address, string $postcode): array
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $address)), fn ($part) => $part !== ''));

        if ($parts !== [] && strcasecmp(str_replace(' ', '', end($parts)), str_replace(' ', '', $postcode)) === 0) {
            array_pop($parts);
        }

        $county = '';
        if (count($parts) >= 3 && preg_match('/shire$/i', (string) end($parts))) {
            $county = (string) array_pop($parts);
        }
        $city = count($parts) >= 2 ? (string) array_pop($parts) : '';

        // A house number on its own ("11, Downing Street") belongs to the street that follows it.
        $lines = [];
        for ($i = 0; $i < count($parts); $i++) {
            if (preg_match('/^\d+[A-Za-z]?(\s*-\s*\d+[A-Za-z]?)?$/', $parts[$i]) && isset($parts[$i + 1])) {
                $lines[] = $parts[$i].' '.$parts[++$i];
            } else {
                $lines[] = $parts[$i];
            }
        }

        return [
            'id' => 'chimnie:'.md5($address),
            'uprn' => '',
            'label' => $address,
            'line_1' => (string) ($lines[0] ?? $address),
            'line_2' => implode(', ', array_slice($lines, 1)),
            'city' => $city,
            'county' => $county,
            'postcode' => $postcode,
            'country' => 'United Kingdom',
            'country_code' => 'GB',
            'specific_property_type' => '',
            'bedroom' => '',
            'bathroom' => '',
            'reception' => '',
            'tenure' => '',
            'epc_rating' => '',
            'council_tax_band' => '',
            'square_meter' => '',
            'floor' => '',
            'local_authority' => '',
            'source' => 'live',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function send(string $method, string $path, array $data = []): Response
    {
        $apiKey = (string) config('chimnie.api_key');

        if ($apiKey === '') {
            throw ValidationException::withMessages([
                'postcode' => 'Chimnie live mode needs CHIMNIE_API_KEY, or turn CHIMNIE_TEST_MODE back on.',
            ]);
        }

        $url = rtrim((string) config('chimnie.base_url'), '/').$path;
        $request = Http::acceptJson()
            ->timeout((int) config('chimnie.timeout', 12))
            ->withToken($apiKey);

        try {
            return $method === 'post' ? $request->post($url, $data) : $request->get($url, $data);
        } catch (ConnectionException $exception) {
            Log::warning('Chimnie could not be reached: '.$exception->getMessage());

            throw ValidationException::withMessages([
                'postcode' => 'Chimnie did not respond. Try again in a moment.',
            ]);
        }
    }

    private function fail(Response $response, string $action): never
    {
        $this->log($response, $action);

        throw ValidationException::withMessages([
            'postcode' => match ($response->status()) {
                429 => 'Too many lookups just now. Wait a minute and try again.',
                401, 402, 403 => 'Property lookup is unavailable right now. Add the property manually.',
                default => 'Chimnie could not look up that postcode right now.',
            },
        ]);
    }

    private function log(Response $response, string $action): void
    {
        // The status explains the failure to us: 401 bad key, 402/403 credit, 429 rate limit.
        Log::warning('Chimnie '.$action.' failed', [
            'status' => $response->status(),
            'body' => mb_substr($response->body(), 0, 300),
        ]);
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
