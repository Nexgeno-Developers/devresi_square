<?php

namespace App\Services\AddressLookup\Contracts;

interface AddressLookupProvider
{
    /**
     * @return array<int, array{id: string, label: string, address: ?array}>
     */
    public function search(string $query, int $limit): array;

    /**
     * Resolve a provider suggestion that did not include its full address.
     *
     * @return array<string, mixed>|null
     */
    public function resolve(string $id): ?array;
}
