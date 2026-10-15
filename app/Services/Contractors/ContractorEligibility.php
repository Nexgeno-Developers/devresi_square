<?php

namespace App\Services\Contractors;

use App\Models\ContractorProfile;
use App\Models\RepairIssue;
use App\Support\ContractorCompliance;
use App\Support\PostcodeGeo;

/** The same checks Find a contractor shows and Send applies. */
class ContractorEligibility
{
    /**
     * @return array{ok: bool, miles: ?float, reason: ?string}
     */
    public function assess(ContractorProfile $profile, RepairIssue $repair, ?string $trade = null): array
    {
        if ($reason = ContractorCompliance::blockReason($profile, $trade)) {
            return ['ok' => false, 'miles' => null, 'reason' => $reason];
        }

        $miles = $this->miles($profile, $repair->property?->postcode);
        if ($miles === null) {
            return ['ok' => false, 'miles' => null, 'reason' => 'We could not work out the distance to this property.'];
        }

        $radius = (int) ($profile->coverage_radius_miles ?: 0);
        if ($miles > $radius) {
            return [
                'ok' => false,
                'miles' => $miles,
                'reason' => 'Outside their coverage ('.$miles.' miles away, they cover '.$radius.').',
            ];
        }

        return ['ok' => true, 'miles' => $miles, 'reason' => null];
    }

    public function miles(ContractorProfile $profile, ?string $propertyPostcode): ?float
    {
        $origin = PostcodeGeo::locate($propertyPostcode);
        $point = ($profile->latitude !== null && $profile->longitude !== null)
            ? [(float) $profile->latitude, (float) $profile->longitude]
            : PostcodeGeo::locate($profile->postcode);
        if (! $origin || ! $point) {
            return null;
        }

        return PostcodeGeo::miles($origin, $point);
    }
}
