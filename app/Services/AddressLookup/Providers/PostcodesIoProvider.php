<?php

namespace App\Services\AddressLookup\Providers;

use App\Services\AddressLookup\AddressLookupException;
use App\Services\AddressLookup\Contracts\AddressLookupProvider;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PostcodesIoProvider implements AddressLookupProvider
{
    public function __construct(private readonly array $config) {}

    public function search(string $query, int $limit): array
    {
        $cacheKey = 'address_lookup:postcodes_io:'.sha1(strtolower(trim($query)).":{$limit}");

        return Cache::remember($cacheKey, now()->addDay(), function () use ($query, $limit) {
            $response = Http::acceptJson()
                ->timeout($this->timeout())
                ->get($this->baseUrl().'/postcodes', [
                    'query' => trim($query),
                    'limit' => $limit,
                ]);

            $this->ensureSuccessful($response);

            return collect($response->json('result', []))
                ->take($limit)
                ->map(function (array $result) {
                    $address = $this->normalise($result);
                    $location = implode(', ', array_filter([$address['city'], $address['county']]));

                    return [
                        'id' => $address['postcode'],
                        'label' => $address['postcode'].($location !== '' ? " — {$location}" : ''),
                        'address' => $address,
                    ];
                })
                ->values()
                ->all();
        });
    }

    public function resolve(string $id): ?array
    {
        $response = Http::acceptJson()
            ->timeout($this->timeout())
            ->get($this->baseUrl().'/postcodes/'.rawurlencode($id));

        if ($response->status() === 404) {
            return null;
        }

        $this->ensureSuccessful($response);
        $result = $response->json('result');

        return is_array($result) ? $this->normalise($result) : null;
    }

    private function normalise(array $result): array
    {
        $country = trim((string) ($result['country'] ?? 'United Kingdom'));
        $region = trim((string) ($result['region'] ?? ''));
        $city = strcasecmp($region, 'London') === 0
            ? 'London'
            : $this->firstFilled($result, ['post_town', 'admin_district', 'parish']);

        return [
            'line_1' => '',
            'line_2' => '',
            'city' => $city,
            'county' => $this->firstFilled($result, ['admin_county', 'admin_district']),
            'postcode' => strtoupper(trim((string) ($result['postcode'] ?? ''))),
            'country' => $country !== '' ? $country : 'United Kingdom',
            'country_code' => 'GB',
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

    private function ensureSuccessful(Response $response): void
    {
        if (! $response->successful()) {
            throw new AddressLookupException('The postcode service is temporarily unavailable.');
        }
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
