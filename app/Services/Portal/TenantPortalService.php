<?php

namespace App\Services\Portal;

use App\Models\Document;
use App\Models\Property;
use App\Models\RepairIssue;
use App\Models\SysSaleInvoice;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use Illuminate\Support\Collection;

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
        $propertyIds = $this->propertyIds($tenancies)->all();

        return SysSaleInvoice::query()
            ->when($accountId && ! $user->isSuperAdmin(), fn ($query) => $query->forAccount($accountId))
            ->where(function ($query) use ($user, $tenancyIds, $propertyIds) {
                $query->where(function ($charged) use ($user) {
                    $charged->where('charge_to_type', 'Tenant')
                        ->where('charge_to_id', $user->id);
                });

                if ($tenancyIds !== []) {
                    $query->orWhere(function ($linked) use ($tenancyIds, $user) {
                        $linked->where('link_to_type', 'Tenancy')
                            ->whereIn('link_to_id', $tenancyIds)
                            ->where(function ($owner) use ($user) {
                                $owner->whereNull('charge_to_id')
                                    ->orWhere(function ($toTenant) use ($user) {
                                        $toTenant->where('charge_to_type', 'Tenant')
                                            ->where('charge_to_id', $user->id);
                                    });
                            });
                    });
                }

                if ($propertyIds !== []) {
                    $query->orWhere(function ($propertyLinked) use ($propertyIds, $user) {
                        $propertyLinked->where('link_to_type', 'Property')
                            ->whereIn('link_to_id', $propertyIds)
                            ->where('charge_to_type', 'Tenant')
                            ->where('charge_to_id', $user->id);
                    });
                }
            })
            ->orderByDesc('invoice_date')
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
                            $propertyDocs->whereIn('visibility', ['shared', 'portal']);
                        }
                    });
                }

                $query->orWhere(function ($own) use ($user, $visibilityFilter) {
                    $own->where('documentable_type', User::class)
                        ->where('documentable_id', $user->id);
                    if ($visibilityFilter) {
                        $own->whereIn('visibility', ['shared', 'portal']);
                    }
                });
            })
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();
    }
}
