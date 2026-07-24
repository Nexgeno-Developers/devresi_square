<?php

namespace App\Services\AddressLookup\Providers;

use App\Services\AddressLookup\AddressLookupException;
use App\Services\AddressLookup\Contracts\AddressLookupProvider;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GetAddressProvider implements AddressLookupProvider
{
    public function __construct(private readonly array $config) {}

    public function search(string $query, int $limit): array
    {
        $parameters = [
            'api-key' => $this->apiKey(),
            'top' => min($limit, 6),
        ];

        if ($this->isCompleteUkPostcode($query)) {
            $parameters['all'] = 'true';
            $parameters['template'] = '{line_1}{line_2,, }{line_2}{town_or_city,, }{town_or_city}{postcode,, }{postcode}';
        }

        $response = Http::acceptJson()
            ->timeout($this->timeout())
            ->get($this->baseUrl().'/autocomplete/'.rawurlencode(trim($query)), $parameters);

        $this->ensureSuccessful($response);

        return collect($response->json('suggestions', []))
            ->take($limit)
            ->filter(fn ($suggestion) => is_array($suggestion) && ! empty($suggestion['id']))
            ->map(fn (array $suggestion) => [
                'id' => (string) $suggestion['id'],
                'label' => (string) ($suggestion['address'] ?? $suggestion['id']),
                'address' => null,
            ])
            ->values()
            ->all();
    }

    private function isCompleteUkPostcode(string $query): bool
    {
        $postcode = strtoupper(preg_replace('/\s+/', '', trim($query)) ?? '');

        return preg_match(
            '/^(GIR0AA|[A-Z]{1,2}[0-9][A-Z0-9]?[0-9][A-Z]{2})$/',
            $postcode
        ) === 1;
    }

    public function resolve(string $id): ?array
    {
        $response = Http::acceptJson()
            ->timeout($this->timeout())
            ->get($this->baseUrl().'/get/'.rawurlencode($id), [
                'api-key' => $this->apiKey(),
            ]);

        if ($response->status() === 404) {
            return null;
        }

        $this->ensureSuccessful($response);
        $result = $response->json();

        if (! is_array($result)) {
            return null;
        }

        $formatted = is_array($result['formatted_address'] ?? null)
            ? array_pad($result['formatted_address'], 5, '')
            : [];

        return [
            'line_1' => trim((string) ($result['line_1'] ?? $formatted[0] ?? '')),
            'line_2' => trim((string) ($result['line_2'] ?? $formatted[1] ?? '')),
            'city' => trim((string) ($result['town_or_city'] ?? $formatted[3] ?? '')),
            'county' => trim((string) ($result['county'] ?? $formatted[4] ?? '')),
            'postcode' => strtoupper(trim((string) ($result['postcode'] ?? ''))),
            'country' => trim((string) ($result['country'] ?? 'United Kingdom')),
            'country_code' => 'GB',
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
