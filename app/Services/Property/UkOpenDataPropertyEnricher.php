<?php

namespace App\Services\Property;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UkOpenDataPropertyEnricher
{
    /**
     * Fill Chimnie gaps from free UK sources: Postcodes.io, Find that Postcode, and EPC Open Data.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    public function enrich(array $record): array
    {
        $postcode = strtoupper(trim((string) ($record['postcode'] ?? '')));
        $sources = array_values(array_filter([
            ($record['source'] ?? '') !== '' ? (string) $record['source'] : 'chimnie',
        ]));

        if ($postcode !== '') {
            $locality = $this->postcodesIo($postcode);
            if ($locality !== []) {
                $sources[] = 'postcodes.io';
                $record = $this->fillBlanks($record, $locality);
            }

            $findThat = $this->findThatPostcode($postcode);
            if ($findThat !== []) {
                $sources[] = 'findthatpostcode';
                $record = $this->fillBlanks($record, $findThat);
            }

            $epc = $this->bestEpc($postcode, (string) ($record['line_1'] ?? ''));
            if ($epc !== []) {
                $sources[] = 'epc.open-data';
                $record = $this->fillBlanks($record, $epc);
            }
        }

        $sqm = $this->numericOrNull($record['square_meter'] ?? null);
        if ($sqm !== null && $this->blank($record['square_feet'] ?? null)) {
            $record['square_feet'] = (string) (int) round($sqm * 10.7639);
        }

        if (! $this->blank($record['epc_rating'] ?? null) && $this->blank($record['epc_required'] ?? null)) {
            $record['epc_required'] = 1;
        }

        if ($this->blank($record['property_type'] ?? null)) {
            $record['property_type'] = 'lettings';
        }

        if ($this->blank($record['letting_current_status'] ?? null)) {
            $record['letting_current_status'] = 'available';
        }

        if ($this->blank($record['currency'] ?? null)) {
            $record['currency'] = 'GBP';
        }

        $record['data_sources'] = array_values(array_unique($sources));

        return $record;
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function fillBlanks(array $record, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            if ($key === 'data_sources' || $this->blank($value)) {
                continue;
            }

            if ($this->blank($record[$key] ?? null)) {
                $record[$key] = $value;
            }
        }

        return $record;
    }

    /**
     * @return array<string, mixed>
     */
    private function postcodesIo(string $postcode): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout((int) config('uk_open_data.postcodes.timeout', 8))
                ->get(rtrim((string) config('uk_open_data.postcodes.base_url'), '/').'/postcodes/'.rawurlencode($postcode));
        } catch (\Throwable $exception) {
            Log::info('Postcodes.io lookup failed: '.$exception->getMessage());

            return [];
        }

        if ($response->failed()) {
            return [];
        }

        $result = $response->json('result');

        if (! is_array($result)) {
            return [];
        }

        $region = trim((string) ($result['region'] ?? ''));
        $city = strcasecmp($region, 'London') === 0
            ? 'London'
            : $this->firstFilled($result, ['post_town', 'admin_district', 'parish']);

        return array_filter([
            'city' => $city,
            'county' => $this->firstFilled($result, ['admin_county', 'admin_district']),
            'local_authority' => $this->firstFilled($result, ['admin_district', 'parish']),
        ], fn ($value) => ! $this->blank($value));
    }

    /**
     * @return array<string, mixed>
     */
    private function findThatPostcode(string $postcode): array
    {
        $compact = str_replace(' ', '', $postcode);

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('uk_open_data.find_that_postcode.timeout', 8))
                ->get(rtrim((string) config('uk_open_data.find_that_postcode.base_url'), '/').'/postcodes/'.$compact.'.json');
        } catch (\Throwable $exception) {
            Log::info('Find that Postcode lookup failed: '.$exception->getMessage());

            return [];
        }

        if ($response->failed()) {
            return [];
        }

        $attributes = $response->json('data.attributes');

        if (! is_array($attributes)) {
            $attributes = $response->json('data');
        }

        if (! is_array($attributes)) {
            return [];
        }

        return array_filter([
            'local_authority' => $this->firstFilled($attributes, ['laua_name', 'admin_district', 'name']),
            'county' => $this->firstFilled($attributes, ['cty_name', 'admin_county', 'rgn_name']),
            'city' => $this->firstFilled($attributes, ['place', 'post_town', 'laua_name']),
        ], fn ($value) => ! $this->blank($value));
    }

    /**
     * @return array<string, mixed>
     */
    private function bestEpc(string $postcode, string $line1): array
    {
        $email = trim((string) config('uk_open_data.epc.email'));
        $apiKey = trim((string) config('uk_open_data.epc.api_key'));

        if ($email === '' || $apiKey === '') {
            return [];
        }

        try {
            $response = Http::acceptJson()
                ->withBasicAuth($email, $apiKey)
                ->timeout((int) config('uk_open_data.epc.timeout', 10))
                ->get(rtrim((string) config('uk_open_data.epc.base_url'), '/').'/api/v1/domestic/search', [
                    'postcode' => $postcode,
                ]);
        } catch (\Throwable $exception) {
            Log::info('EPC Open Data lookup failed: '.$exception->getMessage());

            return [];
        }

        if ($response->failed()) {
            return [];
        }

        $rows = $response->json('rows') ?? $response->json('data') ?? $response->json();

        if (! is_array($rows)) {
            return [];
        }

        $best = null;
        $bestScore = -1;
        $needle = $this->normaliseAddress($line1);

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $address = $this->normaliseAddress((string) ($row['address'] ?? $row['address1'] ?? ''));
            similar_text($needle, $address, $percent);
            $score = $needle === '' ? 0 : (float) $percent;

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $row;
            }
        }

        if (! $best || ($needle !== '' && $bestScore < 28)) {
            $best = is_array($rows[0] ?? null) ? $rows[0] : null;
        }

        if (! is_array($best)) {
            return [];
        }

        $type = strtolower((string) ($best['property-type'] ?? $best['property_type'] ?? ''));
        $mappedType = match (true) {
            str_contains($type, 'flat') || str_contains($type, 'maisonette') => str_contains($type, 'maisonette') ? 'maisonette' : 'flat',
            str_contains($type, 'bungalow') => 'bungalow',
            str_contains($type, 'house') || str_contains($type, 'park') => 'house',
            default => $type !== '' ? $type : null,
        };

        $area = $best['total-floor-area'] ?? $best['total_floor_area'] ?? null;
        $rating = $best['current-energy-rating'] ?? $best['current_energy_rating'] ?? $best['epc_rating'] ?? null;
        $rooms = $best['number-habitable-rooms'] ?? $best['number_habitable_rooms'] ?? null;

        return array_filter([
            'specific_property_type' => $mappedType,
            'square_meter' => $area !== null && $area !== '' ? (string) $area : null,
            'epc_rating' => $rating !== null && $rating !== '' ? strtoupper((string) $rating) : null,
            'epc_required' => $rating ? 1 : null,
            'bedroom' => $rooms !== null && $rooms !== '' ? (string) max(1, (int) $rooms - 1) : null,
            'floor' => $this->firstFilled($best, ['floor-level', 'floor_level', 'built-form']),
        ], fn ($value) => ! $this->blank($value));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function firstFilled(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '' && strcasecmp($value, 'null') !== 0) {
                return $value;
            }
        }

        return '';
    }

    private function blank(mixed $value): bool
    {
        return $value === null || trim((string) $value) === '';
    }

    private function numericOrNull(mixed $value): ?float
    {
        if ($this->blank($value) || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function normaliseAddress(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
