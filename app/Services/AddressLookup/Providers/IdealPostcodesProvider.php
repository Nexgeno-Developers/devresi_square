<?php

namespace App\Services\AddressLookup\Providers;

use App\Services\AddressLookup\AddressLookupException;
use App\Services\AddressLookup\Contracts\AddressLookupProvider;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class IdealPostcodesProvider implements AddressLookupProvider
{
    public function __construct(private readonly array $config) {}

    public function search(string $query, int $limit): array
    {
        $response = Http::acceptJson()
            ->timeout($this->timeout())
            ->get($this->baseUrl().'/postcodes/'.rawurlencode(strtoupper(trim($query))), [
                'api_key' => $this->apiKey(),
                'limit' => $limit,
            ]);

        if ($response->status() === 404) {
            return [];
        }

        $this->ensureSuccessful($response);

        return collect($response->json('result', []))
            ->take($limit)
            ->map(function (array $result) {
                $address = $this->normalise($result);

                return [
                    'id' => (string) ($result['udprn'] ?? $result['uprn'] ?? sha1(json_encode($address))),
                    'label' => implode(', ', array_filter([
                        $address['line_1'],
                        $address['line_2'],
                        $address['city'],
                        $address['postcode'],
                    ])),
                    'address' => $address,
                ];
            })
            ->values()
            ->all();
    }

    public function resolve(string $id): ?array
    {
        return null;
    }

    private function normalise(array $result): array
    {
        return [
            'line_1' => trim((string) ($result['line_1'] ?? '')),
            'line_2' => trim((string) ($result['line_2'] ?? '')),
            'city' => trim((string) ($result['post_town'] ?? '')),
            'county' => trim((string) ($result['county'] ?? '')),
            'postcode' => strtoupper(trim((string) ($result['postcode'] ?? ''))),
            'country' => trim((string) ($result['country'] ?? 'United Kingdom')),
            'country_code' => strtoupper(trim((string) ($result['country_iso_2'] ?? 'GB'))),
            'uprn' => $this->nullableString($result['uprn'] ?? null),
        ];
    }

    private function apiKey(): string
    {
        $key = trim((string) ($this->config['api_key'] ?? ''));

        if ($key === '') {
            throw new AddressLookupException('The selected address provider has not been configured.');
        }

        return $key;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function ensureSuccessful(Response $response): void
    {
        if (! $response->successful()) {
            throw new AddressLookupException('The address service is temporarily unavailable.');
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
