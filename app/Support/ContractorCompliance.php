<?php

namespace App\Support;

use App\Models\ContractorDocument;
use App\Models\ContractorProfile;
use App\Models\ContractorTrade;
use App\Models\RepairIssue;

class ContractorCompliance
{
    public static function trades(): array
    {
        return config('contractor_marketplace.trades', []);
    }

    public static function documentSpec(string $type): array
    {
        return config('contractor_marketplace.documents.'.$type, []);
    }

    public static function postcodeDistrict(?string $postcode): string
    {
        $postcode = strtoupper(trim((string) $postcode));
        if ($postcode === '') {
            return '';
        }
        if (str_contains($postcode, ' ')) {
            return strtok($postcode, ' ') ?: '';
        }
        if (strlen($postcode) > 3) {
            return trim(substr($postcode, 0, -3));
        }

        return $postcode;
    }

    private static function repairText(RepairIssue $repair): string
    {
        $repair->loadMissing('repairCategory.parentCategory');
        $parts = [
            $repair->repairCategory?->name,
            $repair->repairCategory?->parentCategory?->name,
            is_string($repair->repair_navigation) ? $repair->repair_navigation : json_encode($repair->repair_navigation),
        ];

        return strtolower(implode(' ', array_filter($parts)));
    }

    /** Regulated trades are a hard gate: only contractors approved for that trade can be sent the repair. */
    public static function requiredTrade(RepairIssue $repair): ?string
    {
        $text = self::repairText($repair);

        return match (true) {
            (bool) preg_match('/\boil\b/', $text) => 'oil',
            (bool) preg_match('/\b(gas|boiler|gas safe)\b/', $text) => 'gas',
            (bool) preg_match('/\b(electric|electrical|wiring|fuse|socket)\b/', $text) => 'electrical',
            (bool) preg_match('/\b(air con|air conditioning|refrigerat\w*|f-gas)\b/', $text) => 'refrigeration',
            default => null,
        };
    }

    /** Default trade filter on Find a contractor; landlords can change it. */
    public static function suggestedTrade(RepairIssue $repair): ?string
    {
        if ($trade = self::requiredTrade($repair)) {
            return $trade;
        }
        $text = self::repairText($repair).' '.strtolower((string) $repair->description);

        return match (true) {
            (bool) preg_match('/\b(leak\w*|tap|toilet|pipe|drain|plumb\w*|sink|shower)\b/', $text) => 'plumbing',
            (bool) preg_match('/\b(roof|gutter|tile)\b/', $text) => 'roofing',
            (bool) preg_match('/\b(lock|locks|key)\b/', $text) => 'locks',
            (bool) preg_match('/\b(oven|hob|washing machine|fridge|dishwasher|appliance)\b/', $text) => 'appliances',
            default => null,
        };
    }

    public static function looksLikeEmergency(RepairIssue $repair): ?string
    {
        $text = self::repairText($repair).' '.strtolower((string) $repair->description);

        return match (true) {
            (bool) preg_match('/\b(smell gas|gas smell|gas leak|smell of gas)\b/', $text) => 'gas',
            (bool) preg_match('/\b(flood\w*|burst)\b/', $text) => 'water',
            (bool) preg_match('/\b(sparks?|exposed wir\w*|no power|power cut)\b/', $text) => 'electrical',
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function documentsForTrade(string $trade): array
    {
        return ContractorSettings::tradeDocuments()[$trade] ?? [];
    }

    /**
     * Required document types for this contractor, in display order.
     *
     * @return array<int, string>
     */
    public static function requiredDocuments(ContractorProfile $profile): array
    {
        $types = config('contractor_marketplace.base_documents', []);
        foreach ($profile->trades ?? [] as $trade) {
            $types = array_merge($types, self::documentsForTrade($trade));
        }
        if ($profile->employs_staff) {
            $types[] = ContractorDocument::EMPLOYERS_LIABILITY;
        }

        return array_values(array_unique($types));
    }

    /**
     * @return array<int, string>
     */
    public static function optionalDocuments(ContractorProfile $profile): array
    {
        return array_values(array_diff(config('contractor_marketplace.optional_documents', []), self::requiredDocuments($profile)));
    }

    /** What the contractor still has to provide before they can submit. */
    public static function submissionGaps(ContractorProfile $profile): array
    {
        $profile->loadMissing('documents');
        $gaps = self::basicsGaps($profile);
        foreach (self::requiredDocuments($profile) as $type) {
            if ($gap = self::uploadGap($profile, $type)) {
                $gaps[] = $gap;
            }
        }

        return $gaps;
    }

    public static function basicsGaps(ContractorProfile $profile): array
    {
        $gaps = [];
        if (blank($profile->trading_type)) {
            $gaps[] = 'Say if you are a sole trader or a limited company.';
        }
        if (empty($profile->trades)) {
            $gaps[] = 'Pick at least one trade.';
        }
        if (blank($profile->postcode)) {
            $gaps[] = 'Add your home postcode.';
        }
        if ($profile->trading_type === 'limited_company' && ! preg_match('/^[A-Z0-9]{8}$/', (string) $profile->company_number)) {
            $gaps[] = 'Add your 8-character Companies House number.';
        }
        if ($profile->vat_registered && ! preg_match('/^GB\d{9}(\d{3})?$/', (string) $profile->vat_number)) {
            $gaps[] = 'Add your VAT number, for example GB123456789.';
        }

        return $gaps;
    }

    public static function uploadGap(ContractorProfile $profile, string $type): ?string
    {
        $spec = self::documentSpec($type);
        $label = $spec['label'] ?? $type;
        $doc = $profile->document($type);
        if (! $doc || $doc->status === ContractorDocument::PURGED) {
            return 'Add your '.lcfirst($label).'.';
        }
        if ($doc->status === ContractorDocument::REJECTED) {
            return 'Replace your '.lcfirst($label).': '.($doc->rejected_reason ?: 'it was not accepted').'.';
        }
        if (($spec['file'] ?? false) && blank($doc->path)) {
            return 'Upload your '.lcfirst($label).'.';
        }
        if (! empty($spec['reference']) && blank($doc->reference)) {
            return 'Add your '.lcfirst($spec['reference']).'.';
        }
        if (! empty($spec['issuers']) && blank($doc->issuer)) {
            return 'Pick your '.lcfirst($label).'.';
        }
        if (($spec['cover'] ?? false) && $type === ContractorDocument::PUBLIC_LIABILITY && (float) $doc->cover_amount <= 0) {
            return 'Pick your insurance cover level.';
        }
        if ($type === ContractorDocument::PUBLIC_LIABILITY && ! $doc->expires_at) {
            return 'Add the date your insurance ends.';
        }

        return null;
    }

    public static function documentProblem(ContractorProfile $profile, string $type): ?string
    {
        $doc = $profile->document($type);
        $label = self::documentSpec($type)['label'] ?? $type;
        if (! $doc) {
            return $label.' is missing.';
        }
        if ($doc->status === ContractorDocument::REJECTED) {
            return $label.' was rejected.';
        }
        if ($doc->status !== ContractorDocument::APPROVED) {
            return $label.' is waiting for review.';
        }
        if ($doc->isExpired()) {
            return $label.' expired on '.$doc->expires_at->format('j M Y').'.';
        }
        if ((self::documentSpec($type)['expires'] ?? false) && ! $doc->expires_at) {
            return $label.' has no expiry date on file.';
        }

        return null;
    }

    /** Account-level checks that every trade depends on. */
    public static function profileBlock(ContractorProfile $profile): ?string
    {
        $profile->loadMissing(['documents', 'tradeApprovals']);
        if ($profile->approval_status === ContractorProfile::SUSPENDED) {
            return 'This contractor is suspended.';
        }
        if ($profile->approval_status === ContractorProfile::REMOVED) {
            return 'This contractor has been removed.';
        }
        if ($profile->approval_status !== ContractorProfile::APPROVED) {
            return 'This contractor is not approved.';
        }
        foreach (config('contractor_marketplace.base_documents', []) as $type) {
            if ($problem = self::documentProblem($profile, $type)) {
                return $problem;
            }
        }
        $pl = $profile->document(ContractorDocument::PUBLIC_LIABILITY);
        if ((float) $pl?->cover_amount < ContractorSettings::plMinimum()) {
            return 'Public liability cover is below £'.number_format(ContractorSettings::plMinimum(), 0).'.';
        }
        if ($profile->employs_staff) {
            if ($problem = self::documentProblem($profile, ContractorDocument::EMPLOYERS_LIABILITY)) {
                return $problem;
            }
            $el = $profile->document(ContractorDocument::EMPLOYERS_LIABILITY);
            if ((float) $el?->cover_amount < ContractorSettings::elMinimum()) {
                return 'Employers’ liability cover is below £'.number_format(ContractorSettings::elMinimum(), 0).'.';
            }
        }

        return null;
    }

    public static function tradeBlock(ContractorProfile $profile, string $trade): ?string
    {
        $profile->loadMissing(['documents', 'tradeApprovals']);
        $label = self::trades()[$trade] ?? $trade;
        $approval = $profile->tradeApproval($trade);
        if (! $approval || ! $profile->hasTrade($trade)) {
            return match ($trade) {
                'gas' => 'This repair needs a Gas Safe registered contractor.',
                'electrical' => 'This repair needs a registered electrical contractor.',
                default => 'Not approved for '.strtolower($label).'.',
            };
        }
        if ($approval->status !== ContractorTrade::APPROVED) {
            return ucfirst(strtolower($label)).' is not approved yet.';
        }
        foreach (self::documentsForTrade($trade) as $type) {
            if ($problem = self::documentProblem($profile, $type)) {
                return $problem;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public static function approvedTrades(ContractorProfile $profile): array
    {
        return array_values(array_filter(
            $profile->trades ?? [],
            fn (string $trade) => self::tradeBlock($profile, $trade) === null
        ));
    }

    public static function blockReason(ContractorProfile $profile, ?string $requiredTrade = null): ?string
    {
        if ($reason = self::profileBlock($profile)) {
            return $reason;
        }
        if ($requiredTrade) {
            return self::tradeBlock($profile, $requiredTrade);
        }

        return self::approvedTrades($profile) === [] ? 'No trade is approved yet.' : null;
    }

    public static function canReceiveWork(ContractorProfile $profile, ?string $requiredTrade = null): bool
    {
        return self::blockReason($profile, $requiredTrade) === null;
    }

    /** Everything stopping this contractor from working, for the admin screen. */
    public static function gaps(ContractorProfile $profile): array
    {
        $gaps = self::submissionGaps($profile);
        if ($block = self::profileBlock($profile)) {
            $gaps[] = $block;
        }
        foreach ($profile->trades ?? [] as $trade) {
            if ($block = self::tradeBlock($profile, $trade)) {
                $gaps[] = (self::trades()[$trade] ?? $trade).': '.$block;
            }
        }

        return array_values(array_unique($gaps));
    }

    /** Expiry-driven pause: approved, but insurance or ID lapsed. */
    public static function isPaused(ContractorProfile $profile): bool
    {
        return $profile->approval_status === ContractorProfile::APPROVED && self::profileBlock($profile) !== null;
    }

    /**
     * Landlords see these, never the files.
     *
     * @return array<int, string>
     */
    public static function badges(ContractorProfile $profile): array
    {
        $profile->loadMissing(['documents', 'tradeApprovals']);
        $badges = [];
        $pl = $profile->document(ContractorDocument::PUBLIC_LIABILITY);
        if ($pl?->isValid()) {
            $badges[] = 'Insured until '.$pl->expires_at->format('M Y');
        }
        $approved = self::approvedTrades($profile);
        if (in_array('gas', $approved, true)) {
            $badges[] = 'Gas Safe verified';
        }
        if (in_array('electrical', $approved, true)) {
            $badges[] = ($profile->document(ContractorDocument::ELECTRICAL_SCHEME)?->issuer ?: 'Electrical scheme').' registered';
        }
        if (in_array('oil', $approved, true)) {
            $badges[] = 'OFTEC registered';
        }
        if (in_array('refrigeration', $approved, true)) {
            $badges[] = 'F-Gas certified';
        }
        if ($profile->document(ContractorDocument::DBS)?->status === ContractorDocument::APPROVED) {
            $badges[] = 'DBS checked';
        }
        if ($profile->trading_type === 'limited_company') {
            $badges[] = 'Ltd company';
        }

        return $badges;
    }

    public static function hasBadge(ContractorProfile $profile, string $badge): bool
    {
        return match ($badge) {
            'gas_safe' => in_array('gas', self::approvedTrades($profile), true),
            'dbs' => $profile->document(ContractorDocument::DBS)?->status === ContractorDocument::APPROVED,
            default => false,
        };
    }

    public static function nextExpiry(ContractorProfile $profile): ?ContractorDocument
    {
        $profile->loadMissing('documents');

        return $profile->documents
            ->filter(fn (ContractorDocument $doc) => $doc->status === ContractorDocument::APPROVED && $doc->expires_at)
            ->sortBy('expires_at')
            ->first();
    }

    /** Keeps one approval row per selected trade; removing a trade drops its approval. */
    public static function syncTrades(ContractorProfile $profile, array $trades): void
    {
        $trades = array_values(array_unique(array_intersect($trades, array_keys(self::trades()))));
        $profile->forceFill(['trades' => $trades])->save();
        $profile->tradeApprovals()->whereNotIn('trade', $trades ?: ['__none__'])->delete();
        foreach ($trades as $trade) {
            $profile->tradeApprovals()->firstOrCreate(['trade' => $trade], ['status' => ContractorTrade::PENDING]);
        }
        $profile->unsetRelation('tradeApprovals');
    }
}
