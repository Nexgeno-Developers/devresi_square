<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('properties')
            || ! Schema::hasColumn('properties', 'account_id')
            || ! Schema::hasColumn('properties', 'property_identity_hash')
        ) {
            return;
        }

        DB::table('properties')
            ->select([
                'id',
                'account_id',
                'line_1',
                'line_2',
                'postcode',
                'country',
                'property_identity_hash',
            ])
            ->orderBy('id')
            ->chunkById(500, function ($properties) {
                foreach ($properties as $property) {
                    if ($property->account_id === null) {
                        continue;
                    }

                    $identityHash = $this->makeIdentityHash((array) $property);

                    if ($identityHash === null || $property->property_identity_hash === $identityHash) {
                        continue;
                    }

                    $canonicalIdentityAlreadyExists = DB::table('properties')
                        ->where('account_id', $property->account_id)
                        ->where('property_identity_hash', $identityHash)
                        ->where('id', '!=', $property->id)
                        ->exists();

                    if ($canonicalIdentityAlreadyExists) {
                        continue;
                    }

                    DB::table('properties')
                        ->where('id', $property->id)
                        ->update(['property_identity_hash' => $identityHash]);
                }
            });
    }

    public function down(): void
    {
        // Existing identity values are intentionally preserved.
    }

    private function makeIdentityHash(array $attributes): ?string
    {
        $address = strtolower(trim(implode(' ', array_filter([
            $attributes['line_1'] ?? null,
            $attributes['line_2'] ?? null,
        ], fn ($part) => $part !== null && trim((string) $part) !== ''))));
        $address = preg_replace('/[^\pL\pN]+/u', ' ', $address);
        $address = preg_replace('/\s+/', ' ', trim($address ?: '')) ?: '';

        $normalisedParts = [
            $address,
            preg_replace('/\s+/', '', strtolower(trim((string) ($attributes['postcode'] ?? '')))) ?: '',
            preg_replace('/\s+/', ' ', strtolower(trim((string) ($attributes['country'] ?? '')))) ?: '',
        ];

        if (count(array_filter($normalisedParts, fn ($part) => $part !== '')) === 0) {
            return null;
        }

        return hash('sha256', 'address:v2:'.implode('|', $normalisedParts));
    }
};
