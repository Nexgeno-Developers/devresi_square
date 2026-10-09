<?php

namespace App\Support;

use App\Models\PostcodeLocation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PostcodeGeo
{
    public static function normalise(?string $postcode): string
    {
        $clean = strtoupper(preg_replace('/\s+/', '', (string) $postcode));
        if (strlen($clean) < 5) {
            return $clean;
        }

        return substr($clean, 0, -3).' '.substr($clean, -3);
    }

    /** Letters before the first digit, e.g. "LS" for LS1 1AA. */
    public static function area(?string $postcode): string
    {
        return preg_match('/^[A-Z]+/', strtoupper(trim((string) $postcode)), $match) ? $match[0] : '';
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    /**
     * Saved coordinates only. Never calls the network.
     *
     * @return array{0: float, 1: float}|null
     */
    public static function cached(?string $postcode): ?array
    {
        $postcode = self::normalise($postcode);
        if ($postcode === '') {
            return null;
        }
        $cached = PostcodeLocation::query()->where('postcode', $postcode)->first();

        return $cached ? [(float) $cached->latitude, (float) $cached->longitude] : null;
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    public static function locate(?string $postcode): ?array
    {
        $postcode = self::normalise($postcode);
        if ($postcode === '') {
            return null;
        }
        if ($cached = self::cached($postcode)) {
            return $cached;
        }
        if (! config('contractor_marketplace.geocode.enabled')) {
            return null;
        }
        $miss = 'postcode-miss:'.$postcode;
        if (Cache::has($miss)) {
            return null;
        }

        try {
            $endpoint = rtrim((string) config('contractor_marketplace.geocode.endpoint'), '/');
            $response = Http::connectTimeout(2)->timeout(3)->get($endpoint.'/postcodes/'.rawurlencode($postcode));
            if (! $response->ok()) {
                $outward = strtok($postcode, ' ');
                $response = Http::connectTimeout(2)->timeout(3)->get($endpoint.'/outcodes/'.rawurlencode((string) $outward));
            }
            $lat = $response->json('result.latitude');
            $lng = $response->json('result.longitude');
        } catch (\Throwable) {
            return null;
        }

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            Cache::put($miss, 1, now()->addHours(12));

            return null;
        }
        PostcodeLocation::query()->updateOrCreate(['postcode' => $postcode], ['latitude' => $lat, 'longitude' => $lng]);

        return [(float) $lat, (float) $lng];
    }

    public static function miles(array $from, array $to): float
    {
        [$lat1, $lng1] = array_map('deg2rad', $from);
        [$lat2, $lng2] = array_map('deg2rad', $to);
        $a = sin(($lat2 - $lat1) / 2) ** 2 + cos($lat1) * cos($lat2) * sin(($lng2 - $lng1) / 2) ** 2;

        return round(3958.8 * 2 * asin(min(1, sqrt($a))), 1);
    }
}
