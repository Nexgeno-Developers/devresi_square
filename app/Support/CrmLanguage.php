<?php

namespace App\Support;

class CrmLanguage
{
    public static function greeting(?string $name): string
    {
        $first = trim((string) strtok(trim((string) $name), ' '));
        if ($first === '') {
            return 'Your Portfolio — Everything that needs your attention, in one place.';
        }

        $hour = (int) now()->format('G');
        $hello = match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };

        return $hello.', '.$first.'. Here’s what needs your attention today.';
    }

    public static function tenancyStatus(?string $status): string
    {
        return match ($status) {
            'Terminated' => 'Ended',
            'Inactive' => 'Ending',
            'Archived' => 'Archived',
            'Active' => 'Active',
            'Draft' => 'Draft',
            default => $status ? (string) $status : '—',
        };
    }

    public static function repairStatus(?string $status): string
    {
        return match ($status) {
            'Pending' => 'Reviewing',
            'Reported' => 'Reported',
            'Under Process' => 'In Progress',
            'Work Completed' => 'Completed',
            'Invoice Received' => 'Invoiced',
            'Invoice Paid' => 'Invoice paid',
            'Closed' => 'Closed',
            'Contractor Assigned' => 'Contractor Assigned',
            'Visit Booked' => 'Visit Booked',
            'Awaiting Parts' => 'Awaiting Parts',
            'Awaiting Approval' => 'Awaiting Approval',
            default => $status ? (string) $status : 'Reported',
        };
    }

    public static function occupancy(?string $lettingStatus): string
    {
        return match (strtolower(trim((string) $lettingStatus))) {
            'let agreed', 'occupied' => 'Occupied',
            'available', 'vacant' => 'Vacant',
            'notice' => 'Notice',
            'onboarding' => 'Onboarding',
            'archived' => 'Archived',
            '' => 'Vacant',
            default => ucfirst((string) $lettingStatus),
        };
    }

    public static function complianceState(?string $state): string
    {
        return match ($state) {
            'Not provided yet', 'Missing expiry', 'Missing', 'Not provided' => 'Missing',
            'Due soon', 'Due Soon' => 'Due Soon',
            'Expired' => 'Expired',
            'On file', 'Current' => 'Current',
            'Not required', 'Not Required' => 'Not Required',
            default => $state ? (string) $state : 'Missing',
        };
    }

    public static function rentStatus(?string $label): string
    {
        return match ($label) {
            'Part paid', 'Part Paid' => 'Part Paid',
            'Void', 'Refunded' => 'Refunded',
            'Due', 'Paid', 'Overdue', 'Processing', 'Unallocated' => (string) $label,
            default => $label ? (string) $label : 'Due',
        };
    }
}
