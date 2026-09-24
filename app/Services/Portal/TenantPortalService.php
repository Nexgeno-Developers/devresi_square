<?php

namespace App\Services\Portal;

use App\Enums\CrmNotificationEvent;
use App\Models\Document;
use App\Models\Event;
use App\Models\Property;
use App\Models\RentInvoice;
use App\Models\RepairCategory;
use App\Models\RepairIssue;
use App\Models\RepairPhoto;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\Upload;
use App\Models\User;
use App\Services\Notifications\CrmNotificationService;
use App\Services\Repairs\RepairComplaintClassifier;
use App\Services\Repairs\RepairSlaRecorder;
use App\Services\SecureUploadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Builder;
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
                'tenancy.property',
            ])
            ->get()
            ->filter(function (TenantMember $member) {
                $tenancy = $member->tenancy;
                if (! $tenancy instanceof Tenancy) {
                    return false;
                }
                // Ended lets and soft-deleted homes stay off the portal.
                if ($tenancy->status !== 'Active' || ! $tenancy->property) {
                    return false;
                }

                return true;
            })
            ->map(fn (TenantMember $member) => $member->tenancy)
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
            ->with(['tenant', 'property'])
            ->when($accountId && ! $user->isSuperAdmin(), fn ($query) => $query->forAccount($accountId))
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

        $tenancies->loadMissing('tenantMembers');
        $hasHousehold = $tenancies->contains(function ($tenancy) {
            return $tenancy->property_id && $tenancy->tenantMembers->pluck('user_id')->filter()->isNotEmpty();
        });
        if (! $hasHousehold) {
            return collect();
        }

        return RepairIssue::query()
            ->with(['property', 'repairPhotos', 'repairCategory'])
            ->when($accountId && ! $user->isSuperAdmin(), fn ($query) => $query->forAccount($accountId))
            ->where(function ($outer) use ($tenancies) {
                foreach ($tenancies as $tenancy) {
                    $memberIds = $tenancy->tenantMembers
                        ->pluck('user_id')
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->all();
                    if ($memberIds === [] || ! $tenancy->property_id) {
                        continue;
                    }

                    $outer->orWhere(function ($row) use ($tenancy, $memberIds) {
                        $row->where('property_id', $tenancy->property_id)
                            ->whereIn('tenant_id', $memberIds);
                        if ($tenancy->move_in) {
                            $row->where('created_at', '>=', \Carbon\Carbon::parse($tenancy->move_in)->startOfDay());
                        }
                        if ($tenancy->move_out) {
                            $row->where('created_at', '<=', \Carbon\Carbon::parse($tenancy->move_out)->endOfDay());
                        }
                    });
                }
            })
            ->whereHas('property')
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    /**
     * @param  Collection<int, Tenancy>  $tenancies
     */
    public function documentsFor(User $user, ?int $accountId, Collection $tenancies): LengthAwarePaginator
    {
        return $this->visibleDocumentsQuery($user, $accountId, $tenancies)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function documentVisibleTo(User $user, ?int $accountId, Document $document): bool
    {
        if (! $document->isSharedWithTenant()) {
            return false;
        }

        return $this->visibleDocumentsQuery($user, $accountId, $this->tenanciesFor($user, $accountId))
            ->whereKey($document->id)
            ->exists();
    }

    /**
     * @param  Collection<int, Tenancy>  $tenancies
     */
    private function visibleDocumentsQuery(User $user, ?int $accountId, Collection $tenancies): Builder
    {
        $propertyIds = $this->propertyIds($tenancies)->all();
        $tenancyIds = $tenancies->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
        $visibilityFilter = \Illuminate\Support\Facades\Schema::hasColumn('documents', 'visibility');

        $query = Document::query()
            ->with('documentType')
            ->when($accountId && ! $user->isSuperAdmin(), fn ($inner) => $inner->forAccount($accountId));

        return $query->where(function ($outer) use ($propertyIds, $tenancyIds, $user, $visibilityFilter) {
            if ($propertyIds !== []) {
                $outer->where(function ($propertyDocs) use ($propertyIds, $visibilityFilter) {
                    $propertyDocs->whereIn('documentable_type', $this->documentableTypes(Property::class, 'Property'))
                        ->whereIn('documentable_id', $propertyIds);
                    if ($visibilityFilter) {
                        $propertyDocs->whereIn('visibility', Document::TENANT_VISIBILITIES);
                    }
                });
            }

            if ($tenancyIds !== []) {
                $outer->orWhere(function ($tenancyDocs) use ($tenancyIds, $visibilityFilter) {
                    $tenancyDocs->whereIn('documentable_type', $this->documentableTypes(Tenancy::class, 'Tenancy'))
                        ->whereIn('documentable_id', $tenancyIds);
                    if ($visibilityFilter) {
                        $tenancyDocs->whereIn('visibility', Document::TENANT_VISIBILITIES);
                    }
                });
            }

            $outer->orWhere(function ($own) use ($user, $visibilityFilter) {
                $own->whereIn('documentable_type', $this->documentableTypes(User::class, 'User'))
                    ->where('documentable_id', $user->id);
                if ($visibilityFilter) {
                    $own->whereIn('visibility', Document::TENANT_VISIBILITIES);
                }
            });
        });
    }

    /**
     * @return array<int, string>
     */
    private function documentableTypes(string $class, string $short): array
    {
        return array_values(array_unique(array_filter([
            $class,
            $short,
            class_exists($class) ? (new $class)->getMorphClass() : null,
        ])));
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
                    $query->orWhere(function ($visible) use ($propertyIds) {
                        $visible->where('visible_to_tenant', true)
                            ->whereHas('properties', fn ($properties) => $properties->whereIn('properties.id', $propertyIds));
                    });
                }
            })
            ->orderBy('start_datetime')
            ->limit(50)
            ->get();
    }

    /**
     * @param  array{property_id?: int, description: string, priority?: string, repair_category_id?: int, complaint_code?: string, tenant_availability?: string, access_details?: string, photo?: UploadedFile|null}  $payload
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

        $property = Property::query()->find($propertyId);
        if (! $property) {
            throw ValidationException::withMessages([
                'property_id' => 'That property could not be found.',
            ]);
        }

        $photo = $payload['photo'] ?? null;
        if (! $photo instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'photo' => 'Add a photo of the issue so your landlord can triage it.',
            ]);
        }

        $categoryId = isset($payload['repair_category_id']) ? (int) $payload['repair_category_id'] : 0;
        $category = $categoryId > 0
            ? RepairCategory::query()
                ->whereKey($categoryId)
                ->whereNull('parent_id')
                ->where(function ($query) {
                    $query->where('status', 1)->orWhereNull('status');
                })
                ->first()
            : null;

        if ($categoryId > 0 && ! $category) {
            throw ValidationException::withMessages([
                'repair_category_id' => 'Choose an area from the list.',
            ]);
        }

        if (! $category) {
            $category = RepairCategory::query()
                ->where('name', 'Tenant reported')
                ->whereNull('parent_id')
                ->where('level', 1)
                ->first();
        }

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

        $classifier = app(RepairComplaintClassifier::class);
        $complaintCode = trim((string) ($payload['complaint_code'] ?? ''));
        $classification = $complaintCode !== '' ? $classifier->classify($complaintCode) : null;

        $priority = $classification['priority'] ?? (
            in_array($payload['priority'] ?? '', ['low', 'medium', 'high', 'critical'], true)
                ? $payload['priority']
                : 'medium'
        );

        $repair = RepairIssue::create([
            'account_id' => $accountId,
            'property_id' => $propertyId,
            'tenant_id' => $user->id,
            'repair_category_id' => $category->id,
            'repair_navigation' => json_encode(['level_1' => (string) $category->id]),
            'description' => $payload['description'],
            'access_details' => filled($payload['access_details'] ?? null) ? $payload['access_details'] : null,
            'priority' => $priority,
            'sub_status' => 'Pending',
            'status' => 'Pending',
            'reference_number' => generateReferenceNumber(RepairIssue::class, 'reference_number', 'RESISQRPR'),
            'created_by' => $user->id,
            'complaint_code' => $classification['complaint_code'] ?? null,
            'classification_snapshot' => $classification['snapshot'] ?? null,
            'sla_due_at' => $classification['sla_due_at'] ?? null,
            'make_safe_due_at' => $classification['make_safe_due_at'] ?? null,
            'emergency_access' => $classification['emergency_access'] ?? false,
            'reported_at' => $classification['reported_at'] ?? null,
            'tenant_availability' => filled($payload['tenant_availability'] ?? null)
                ? \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', (string) $payload['tenant_availability'])
                : null,
        ]);

        if ($classification) {
            app(RepairSlaRecorder::class)->reported($repair, $classification, $user->id);
        }

        $stored = app(SecureUploadService::class)->store($photo);
        $upload = new Upload;
        $upload->account_id = $accountId;
        $upload->file_original_name = pathinfo($stored['original_name'], PATHINFO_FILENAME) ?: 'repair-photo';
        $upload->extension = $stored['extension'];
        $upload->file_name = $stored['path'];
        $upload->user_id = $user->id;
        $upload->type = $stored['type'];
        $upload->file_size = $stored['size'];
        $upload->save();

        RepairPhoto::create([
            'repair_issue_id' => $repair->id,
            'photos' => (string) $upload->id,
        ]);

        app(CrmNotificationService::class)->dispatch(
            CrmNotificationEvent::RepairReported,
            $repair,
            [
                'account_id' => $accountId,
                'include_account_admins' => true,
                'repair_reference' => $repair->reference_number,
                'repair_priority' => $priority,
                'property_address' => $property->full_address ?: $property->prop_name,
                'milestone' => 'reported-'.$repair->id,
                ...$repair->notificationLinks(),
            ],
            $user,
        );

        return $repair->fresh(['repairPhotos', 'property', 'repairCategory']);
    }
}
