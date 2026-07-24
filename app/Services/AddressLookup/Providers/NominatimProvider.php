<?php

namespace App\Services\AddressLookup\Providers;

use App\Services\AddressLookup\AddressLookupException;
use App\Services\AddressLookup\Contracts\AddressLookupProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NominatimProvider implements AddressLookupProvider
{
    public function __construct(private readonly array $config) {}

    public function search(string $query, int $limit): array
    {
        $cacheKey = 'address_lookup:nominatim:'.sha1(strtolower(trim($query)).":{$limit}");
        $cacheHours = max(1, (int) ($this->config['cache_hours'] ?? 24));

        return Cache::remember($cacheKey, now()->addHours($cacheHours), function () use ($query, $limit) {
            return Cache::lock('address_lookup:nominatim:request', 5)->block(5, function () use ($query, $limit) {
                $lastRequestAt = (float) Cache::get('address_lookup:nominatim:last_request_at', 0);
                $waitMicroseconds = (int) max(0, (1 - (microtime(true) - $lastRequestAt)) * 1_000_000);

                if ($waitMicroseconds > 0) {
                    usleep($waitMicroseconds);
                }

                $parameters = [
                    'q' => trim($query),
                    'format' => 'jsonv2',
                    'addressdetails' => 1,
                    'countrycodes' => 'gb',
                    'layer' => 'address',
                    'limit' => min($limit, 40),
                ];

                if (! empty($this->config['email'])) {
                    $parameters['email'] = $this->config['email'];
                }

                $response = Http::acceptJson()
                    ->withUserAgent((string) $this->config['user_agent'])
                    ->timeout($this->timeout())
                    ->get($this->baseUrl().'/search', $parameters);

                Cache::put('address_lookup:nominatim:last_request_at', microtime(true), now()->addMinute());

                if (! $response->successful()) {
                    throw new AddressLookupException('The OpenStreetMap address service is temporarily unavailable.');
                }

                return collect($response->json())
                    ->filter(fn ($result) => is_array($result))
                    ->take($limit)
                    ->map(function (array $result) {
                        $address = $this->normalise($result);

                        return [
                            'id' => 'nominatim:'.(string) ($result['place_id'] ?? sha1(json_encode($result))),
                            'label' => (string) ($result['display_name'] ?? implode(', ', array_filter($address))),
                            'address' => $address,
                        ];
                    })
                    ->values()
                    ->all();
            });
        });
    }

    public function resolve(string $id): ?array
    {
        return null;
    }

    private function normalise(array $result): array
    {
        $parts = is_array($result['address'] ?? null) ? $result['address'] : [];
        $road = $this->firstFilled($parts, ['road', 'pedestrian', 'residential', 'footway']);
        $houseNumber = trim((string) ($parts['house_number'] ?? ''));
        $building = $this->firstFilled($parts, ['building', 'house_name']);
        $lineOne = trim(implode(' ', array_filter([$houseNumber, $road])));

        if ($lineOne === '') {
            $lineOne = $building !== ''
                ? $building
                : trim((string) ($result['name'] ?? ''));
        }

        return [
            'line_1' => $lineOne,
            'line_2' => $this->firstFilled($parts, ['suburb', 'neighbourhood', 'quarter']),
            'city' => $this->firstFilled($parts, ['city', 'town', 'village', 'municipality']),
            'county' => $this->firstFilled($parts, ['county', 'state_district', 'state']),
            'postcode' => strtoupper(trim((string) ($parts['postcode'] ?? ''))),
            'country' => trim((string) ($parts['country'] ?? 'United Kingdom')),
            'country_code' => strtoupper(trim((string) ($parts['country_code'] ?? 'GB'))),
            'uprn' => null,
        ];
    }

    private function firstFilled(array $result, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($result[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function baseUrl(): string
    {
        return rtrim((string) $this->config['base_url'], '/');
    }

    private function timeout(): int
    {
        return max(1, (int) ($this->config['timeout'] ?? 8));
    }
}
