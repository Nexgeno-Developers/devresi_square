<?php

namespace App\Services\Notifications;

use App\Models\AccountUser;
use App\Models\ComplianceRecord;
use App\Models\Event;
use App\Models\Offer;
use App\Models\Property;
use App\Models\PropertyParticipant;
use App\Models\RepairIssue;
use App\Models\SysSaleInvoice;
use App\Models\Tenancy;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NotificationRecipientResolver
{
    public function resolve(string $eventKey, Model $subject, int $accountId, array $context): Collection
    {
        if (! empty($context['recipients'])) {
            return $this->normalise($context['recipients']);
        }

        $users = collect();
        $property = $this->propertyFor($subject);

        if ($subject instanceof Event) {
            $users = $users->merge($subject->users()->get());
        }
        if ($subject instanceof Offer) {
            $applicantIds = array_keys(json_decode((string) $subject->tenant_details, true) ?: []);
            $users = $users->merge(User::whereKey($applicantIds)->get());
        }
        if ($subject instanceof Tenancy) {
            $users = $users->merge($subject->tenantMembers()->with('user')->get()->pluck('user'));
            $users = $users->merge($subject->propertyManagers()->get());
        }
        if ($subject instanceof RepairIssue) {
            $users->push($subject->tenant);
            $users = $users->merge($subject->repairIssuePropertyManagers()->with('propertyManager')->get()->pluck('propertyManager'));
            if (str_contains($eventKey, 'contractor_assigned') && $subject->finalContractor) {
                $users->push($subject->finalContractor);
            }
        }
        if ($subject instanceof ComplianceRecord && $subject->responsibleUser) {
            $users->push($subject->responsibleUser);
        }
        if ($subject instanceof WorkOrder) {
            $repair = $subject->repairIssue;
            $users->push($repair?->tenant)->push($repair?->finalContractor);
            if ($repair) {
                $users = $users->merge($repair->repairIssuePropertyManagers()->with('propertyManager')->get()->pluck('propertyManager'));
            }
        }
        if ($subject instanceof SysSaleInvoice && $subject->user) {
            $users->push($subject->user);
        }

        if ($property && ! str_starts_with($eventKey, 'finance.')) {
            $participantTypes = str_contains($eventKey, 'compliance.renewed')
                ? ['owner', 'landlord', 'property_manager', 'tenant']
                : ['owner', 'landlord', 'property_manager'];
            $users = $users->merge(
                PropertyParticipant::query()->active()->forAccount($accountId)->forProperty($property->id)
                    ->whereIn('participant_type', $participantTypes)
                    ->with('user')->get()->pluck('user')
            );
        }

        if ($users->filter()->isEmpty() || ($context['include_account_admins'] ?? false)) {
            $adminIds = AccountUser::query()->where('account_id', $accountId)->where('status', 'active')
                ->whereIn('member_type', ['owner', 'admin'])->pluck('user_id');
            $users = $users->merge(User::whereKey($adminIds)->get());
        }

        return $this->normalise($users);
    }

    private function propertyFor(Model $subject): ?Property
    {
        if ($subject instanceof Property) {
            return $subject;
        }
        if ($subject instanceof ComplianceRecord || $subject instanceof Tenancy || $subject instanceof RepairIssue) {
            return $subject->property;
        }
        if ($subject instanceof Offer) {
            return $subject->property;
        }
        if ($subject instanceof WorkOrder) {
            return $subject->repairIssue?->property;
        }
        if ($subject instanceof SysSaleInvoice) {
            $linked = $subject->linkTo;
            if ($linked instanceof Property) return $linked;
            if ($linked instanceof Tenancy) return $linked->property;
        }
        if (method_exists($subject, 'property')) {
            return $subject->property;
        }
        return null;
    }

    private function normalise(mixed $recipients): Collection
    {
        return collect($recipients)->map(function ($recipient) {
            if ($recipient instanceof User) {
                return $recipient;
            }
            return is_numeric($recipient) ? User::find((int) $recipient) : null;
        })->filter()->unique('id')->values();
    }
}
