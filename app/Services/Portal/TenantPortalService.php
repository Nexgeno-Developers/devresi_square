<?php

namespace App\Services\Portal;

use App\Models\Document;
use App\Models\Event;
use App\Models\Property;
use App\Models\RentInvoice;
use App\Models\RepairCategory;
use App\Models\RepairIssue;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TenantPortalService
{
    /**
     * @return Collection<int, Tenancy>
     */
    public function tenanciesFor(User $user, ?int $accountId): Collection
    {
        return TenantMember::query()
            ->where('user_id', $user->id)
            ->when($accountId, fn ($query) => $query->where('account_id', $accountId))
            ->with([
                'tenancy.tenancyType',
                'tenancy.tenancySubStatus',
                'tenancy.tenantMembers.user',
                'tenancy.property' => fn ($query) => $query->withTrashed(),
            ])
            ->get()
            ->map(fn (TenantMember $member) => $member->tenancy)
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * @param  Collection<int, Tenancy>  $tenancies
     * @return Collection<int, int>
     */
    public function propertyIds(Collection $tenancies): Collection
    {
        return $tenancies->pluck('property_id')->filter()->unique()->values();
    }

    /**
     * @param  Collection<int, Tenancy>  $tenancies
     */
    public function invoicesFor(User $user, ?int $accountId, Collection $tenancies): Collection
    {
        $tenancyIds = $tenancies->pluck('id')->all();

        if ($tenancyIds === []) {
            return collect();
        }

        return RentInvoice::query()
            ->when($accountId && ! $user->isSuperAdmin(), fn ($query) => $query->forAccount($accountId))
            ->where('tenant_user_id', $user->id)
            ->where('status', '!=', RentInvoice::STATUS_VOID)
            ->whereIn('tenancy_id', $tenancyIds)
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    /**
     * @param  Collection<int, Tenancy>  $tenancies
     */
    public function repairsFor(User $user, ?int $accountId, Collection $tenancies): Collection
    {
        $propertyIds = $this->propertyIds($tenancies)->all();

        if ($propertyIds === []) {
            return collect();
        }

        return RepairIssue::query()
            ->with(['property' => fn ($query) => $query->withTrashed()])
            ->when($accountId && ! $user->isSuperAdmin(), fn ($query) => $query->forAccount($accountId))
            ->whereIn('property_id', $propertyIds)
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    /**
     * @param  Collection<int, Tenancy>  $tenancies
     */
    public function documentsFor(User $user, ?int $accountId, Collection $tenancies): Collection
    {
        $propertyIds = $this->propertyIds($tenancies)->all();

        $query = Document::query()
            ->with('documentType')
            ->when($accountId && ! $user->isSuperAdmin(), fn ($query) => $query->forAccount($accountId));

        $visibilityFilter = \Illuminate\Support\Facades\Schema::hasColumn('documents', 'visibility');

        return $query
            ->where(function ($query) use ($propertyIds, $user, $visibilityFilter) {
                if ($propertyIds !== []) {
                    $query->where(function ($propertyDocs) use ($propertyIds, $visibilityFilter) {
                        $propertyDocs->where('documentable_type', Property::class)
                            ->whereIn('documentable_id', $propertyIds);
                        if ($visibilityFilter) {
                            $propertyDocs->whereIn('visibility', Document::TENANT_VISIBILITIES);
                        }
                    });
                }

                $query->orWhere(function ($own) use ($user, $visibilityFilter) {
                    $own->where('documentable_type', User::class)
                        ->where('documentable_id', $user->id);
                    if ($visibilityFilter) {
                        $own->whereIn('visibility', Document::TENANT_VISIBILITIES);
                    }
                });
            })
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();
    }

    /**
     * Appointments on the tenant's homes, or ones they were invited to.
     *
     * @param  Collection<int, Tenancy>  $tenancies
     */
    public function eventsFor(User $user, ?int $accountId, Collection $tenancies): Collection
    {
        $propertyIds = $this->propertyIds($tenancies)->all();

        return Event::query()
            ->with(['type', 'subType', 'properties'])
            ->when($accountId && ! $user->isSuperAdmin(), fn ($query) => $query->forAccount($accountId))
            ->where('status', '!=', 'Cancelled')
            ->where(function ($query) use ($user, $propertyIds) {
                $query->whereHas('users', fn ($users) => $users->where('users.id', $user->id));

                if ($propertyIds !== []) {
                    $query->orWhereHas('properties', fn ($properties) => $properties->whereIn('properties.id', $propertyIds));
                }
            })
            ->orderBy('start_datetime')
            ->limit(50)
            ->get();
    }

    /**
     * @param  array{property_id?: int, description: string, priority?: string}  $payload
     */
    public function raiseRepair(User $user, ?int $accountId, array $payload): RepairIssue
    {
        $tenancies = $this->tenanciesFor($user, $accountId);
        $propertyIds = $this->propertyIds($tenancies)->map(fn ($id) => (int) $id)->all();

        if ($propertyIds === []) {
            throw ValidationException::withMessages([
                'property_id' => 'No property is linked to your tenancy yet.',
            ]);
        }

        $propertyId = (int) ($payload['property_id'] ?? $propertyIds[0]);

        if (! in_array($propertyId, $propertyIds, true)) {
            throw ValidationException::withMessages([
                'property_id' => 'You can only report issues for your own property.',
            ]);
        }

        $category = RepairCategory::query()->orderBy('id')->first();

        if (! $category) {
            $category = RepairCategory::create([
                'name' => 'Tenant reported',
                'parent_id' => null,
                'level' => 1,
                'description' => 'Raised from the tenant portal.',
                'status' => 1,
                'position' => 0,
            ]);
        }

        $priority = in_array($payload['priority'] ?? '', ['low', 'medium', 'high', 'critical'], true)
            ? $payload['priority']
            : 'medium';

        return RepairIssue::create([
            'account_id' => $accountId,
            'property_id' => $propertyId,
            'tenant_id' => $user->id,
            'repair_category_id' => $category->id,
            'repair_navigation' => json_encode([$category->name]),
            'description' => $payload['description'],
            'priority' => $priority,
            'status' => 'Pending',
            'reference_number' => generateReferenceNumber(RepairIssue::class, 'reference_number', 'RESISQRPR'),
            'created_by' => $user->id,
        ]);
    }
}
