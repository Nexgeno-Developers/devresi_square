<?php

namespace App\Services\AddressLookup;

use App\Services\AddressLookup\Contracts\AddressLookupProvider;
use App\Services\AddressLookup\Providers\GetAddressProvider;
use App\Services\AddressLookup\Providers\IdealPostcodesProvider;
use App\Services\AddressLookup\Providers\NominatimProvider;
use App\Services\AddressLookup\Providers\PostcodesIoProvider;

class AddressLookupManager
{
    private ?AddressLookupProvider $resolvedProvider = null;

    private ?array $resolvedConfig = null;

    /**
     * @return array{provider: string, full_address: bool, attribution: ?array, suggestions: array}
     */
    public function search(string $query): array
    {
        $config = $this->providerConfig();
        $limit = max(1, min((int) config('address_lookup.limit', 20), 100));

        return [
            'provider' => (string) ($config['name'] ?? config('address_lookup.default')),
            'full_address' => (bool) ($config['full_address'] ?? false),
            'attribution' => $config['attribution'] ?? null,
            'suggestions' => $this->provider()->search($query, $limit),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolve(string $id): ?array
    {
        return $this->provider()->resolve($id);
    }

    public function provider(): AddressLookupProvider
    {
        if ($this->resolvedProvider) {
            return $this->resolvedProvider;
        }

        $config = $this->providerConfig();

        $this->resolvedProvider = match ($config['driver'] ?? null) {
            'postcodes_io' => new PostcodesIoProvider($config),
            'getaddress' => new GetAddressProvider($config),
            'ideal_postcodes' => new IdealPostcodesProvider($config),
            'nominatim' => new NominatimProvider($config),
            default => throw new AddressLookupException('The selected address provider is not supported.'),
        };

        return $this->resolvedProvider;
    }

    private function providerConfig(): array
    {
        if ($this->resolvedConfig !== null) {
            return $this->resolvedConfig;
        }

        $providerName = (string) config('address_lookup.default', 'postcodes_io');
        $config = config("address_lookup.providers.{$providerName}");

        if (! is_array($config)) {
            throw new AddressLookupException('The selected address provider is not configured.');
        }

        return $this->resolvedConfig = $config;
    }
}
