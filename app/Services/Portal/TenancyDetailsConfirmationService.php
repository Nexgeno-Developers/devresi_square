<?php

namespace App\Services\Portal;

use App\Enums\CrmNotificationEvent;
use App\Models\AccountUser;
use App\Models\Tenancy;
use App\Models\TenancyCorrectionRequest;
use App\Models\TenantMember;
use App\Models\User;
use App\Services\Notifications\CrmNotificationService;
use App\Support\AccountMembership;
use Illuminate\Validation\ValidationException;

class TenancyDetailsConfirmationService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CORRECTION_REQUESTED = 'correction_requested';

    public const FIELDS = [
        'address' => ['label' => 'Property address', 'editable' => false, 'type' => 'text'],
        'move_in' => ['label' => 'Move-in date', 'editable' => true, 'type' => 'date'],
        'move_out' => ['label' => 'Move-out date', 'editable' => true, 'type' => 'date'],
        'rent' => ['label' => 'Rent', 'editable' => true, 'type' => 'money'],
        'frequency' => ['label' => 'Rent frequency', 'editable' => true, 'type' => 'frequency'],
        'deposit' => ['label' => 'Deposit', 'editable' => true, 'type' => 'money'],
        'term_months' => ['label' => 'Term (months)', 'editable' => true, 'type' => 'integer'],
    ];

    public function __construct(private readonly CrmNotificationService $notifications)
    {
    }

    /**
     * @return array<string, array{key: string, label: string, value: mixed, display: string, editable: bool, type: string}>
     */
    public function detailsFor(Tenancy $tenancy): array
    {
        $property = $tenancy->property;
        $address = $property?->full_address ?: ($property?->prop_name ?: 'Not set');
        $values = [
            'address' => $address,
            'move_in' => $tenancy->move_in,
            'move_out' => $tenancy->move_out,
            'rent' => $tenancy->rent,
            'frequency' => $tenancy->frequency,
            'deposit' => $tenancy->deposit,
            'term_months' => $tenancy->term_months,
        ];

        $details = [];
        foreach (self::FIELDS as $key => $meta) {
            $value = $values[$key] ?? null;
            $details[$key] = [
                'key' => $key,
                'label' => $meta['label'],
                'value' => $value,
                'display' => $this->displayValue($key, $value),
                'editable' => $meta['editable'],
                'type' => $meta['type'],
            ];
        }

        return $details;
    }

    /**
     * @return array{mode: string, tenancy: Tenancy, member: TenantMember, details: array, pending_request: ?TenancyCorrectionRequest, review_note: ?string}|null
     */
    public function payloadFor(User $user, ?int $accountId): ?array
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('tenant_members', 'details_status')) {
            return null;
        }
        $member = $this->membershipNeedingAction($user, $accountId);
        if (! $member?->tenancy) {
            return null;
        }

        $tenancy = $member->tenancy;
        $pending = TenancyCorrectionRequest::query()
            ->when($accountId, fn ($query) => $query->forAccount($accountId))
            ->where('tenancy_id', $tenancy->id)
            ->where('requested_by', $user->id)
            ->pending()
            ->latest('id')
            ->first();

        $rejected = TenancyCorrectionRequest::query()
            ->when($accountId, fn ($query) => $query->forAccount($accountId))
            ->where('tenancy_id', $tenancy->id)
            ->where('requested_by', $user->id)
            ->where('status', TenancyCorrectionRequest::STATUS_REJECTED)
            ->latest('id')
            ->first();

        return [
            'mode' => $pending ? 'waiting' : 'confirm',
            'tenancy' => $tenancy,
            'member' => $member,
            'details' => $this->detailsFor($tenancy),
            'pending_request' => $pending,
            'review_note' => $pending ? null : $rejected?->landlord_note,
        ];
    }

    public function confirm(User $user, ?int $accountId, int $tenancyId): TenantMember
    {
        $member = $this->membershipFor($user, $accountId, $tenancyId);

        if (TenancyCorrectionRequest::query()
            ->where('tenancy_id', $tenancyId)
            ->where('requested_by', $user->id)
            ->pending()
            ->exists()) {
            throw ValidationException::withMessages([
                'tenancy_id' => 'Your landlord is still reviewing a correction request for this tenancy.',
            ]);
        }

        $member->forceFill([
            'details_status' => self::STATUS_CONFIRMED,
            'details_confirmed_at' => now(),
        ])->save();

        return $member->refresh();
    }

    /**
     * @param  array{message: string, fields: array<int, string>, suggested?: array<string, mixed>}  $payload
     */
    public function requestCorrection(User $user, ?int $accountId, int $tenancyId, array $payload): TenancyCorrectionRequest
    {
        $member = $this->membershipFor($user, $accountId, $tenancyId);
        $tenancy = $member->tenancy;

        if (TenancyCorrectionRequest::query()
            ->where('tenancy_id', $tenancy->id)
            ->where('requested_by', $user->id)
            ->pending()
            ->exists()) {
            throw ValidationException::withMessages([
                'tenancy_id' => 'You already have a correction request waiting for your landlord.',
            ]);
        }

        $selected = collect($payload['fields'] ?? [])
            ->filter(fn ($key) => is_string($key) && isset(self::FIELDS[$key]))
            ->unique()
            ->values();

        if ($selected->isEmpty()) {
            throw ValidationException::withMessages([
                'fields' => 'Choose at least one detail that needs correcting.',
            ]);
        }

        $details = $this->detailsFor($tenancy);
        $suggested = is_array($payload['suggested'] ?? null) ? $payload['suggested'] : [];
        $fields = $selected->map(function (string $key) use ($details, $suggested) {
            $suggestion = trim((string) ($suggested[$key] ?? ''));

            return [
                'key' => $key,
                'label' => $details[$key]['label'],
                'current' => $details[$key]['display'],
                'current_value' => $this->rawStoredValue($key, $details[$key]['value']),
                'suggested' => $suggestion !== '' ? $suggestion : null,
            ];
        })->all();

        $request = TenancyCorrectionRequest::create([
            'account_id' => $accountId ?? $tenancy->account_id,
            'tenancy_id' => $tenancy->id,
            'tenant_member_id' => $member->id,
            'requested_by' => $user->id,
            'status' => TenancyCorrectionRequest::STATUS_PENDING,
            'message' => $payload['message'],
            'fields' => $fields,
            'snapshot' => collect($details)->map(fn ($row) => [
                'label' => $row['label'],
                'display' => $row['display'],
            ])->all(),
        ]);

        $member->forceFill([
            'details_status' => self::STATUS_CORRECTION_REQUESTED,
            'details_confirmed_at' => null,
        ])->save();

        $this->notifyLandlords($tenancy, $request, $user);

        return $request;
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public function approve(User $reviewer, TenancyCorrectionRequest $request, array $changes = [], ?string $note = null): TenancyCorrectionRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'This correction request has already been reviewed.',
            ]);
        }

        $tenancy = $request->tenancy()->with('property')->firstOrFail();
        $apply = $this->validatedChanges($request, $changes);

        if ($apply !== []) {
            $tenancy->fill($apply)->save();
        }

        $request->forceFill([
            'status' => TenancyCorrectionRequest::STATUS_APPROVED,
            'landlord_note' => $note,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'applied_at' => $apply === [] ? null : now(),
        ])->save();

        $this->resetConfirmations($tenancy);
        $this->notifyTenant($tenancy, $request->fresh(), $reviewer, true);

        return $request->fresh();
    }

    public function reject(User $reviewer, TenancyCorrectionRequest $request, string $note): TenancyCorrectionRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'This correction request has already been reviewed.',
            ]);
        }

        $tenancy = $request->tenancy()->with('property')->firstOrFail();

        $request->forceFill([
            'status' => TenancyCorrectionRequest::STATUS_REJECTED,
            'landlord_note' => $note,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ])->save();

        TenantMember::query()
            ->where('tenancy_id', $tenancy->id)
            ->where('user_id', $request->requested_by)
            ->update([
                'details_status' => self::STATUS_PENDING,
                'details_confirmed_at' => null,
            ]);

        $this->notifyTenant($tenancy, $request->fresh(), $reviewer, false);

        return $request->fresh();
    }

    public function pendingCountForAccount(?int $accountId): int
    {
        if (! $accountId) {
            return 0;
        }

        return TenancyCorrectionRequest::query()->forAccount($accountId)->pending()->count();
    }

    public function latestPendingForAccount(?int $accountId): ?TenancyCorrectionRequest
    {
        if (! $accountId) {
            return null;
        }

        return TenancyCorrectionRequest::query()
            ->forAccount($accountId)
            ->pending()
            ->latest('id')
            ->first();
    }

    private function membershipNeedingAction(User $user, ?int $accountId): ?TenantMember
    {
        return TenantMember::query()
            ->where('user_id', $user->id)
            ->when($accountId, fn ($query) => $query->where('account_id', $accountId))
            ->where(function ($query) {
                $query->whereNull('details_status')
                    ->orWhere('details_status', self::STATUS_PENDING)
                    ->orWhere('details_status', self::STATUS_CORRECTION_REQUESTED);
            })
            ->with([
                'tenancy.property',
                'tenancy.tenancyType',
                'tenancy.tenantMembers.user',
            ])
            ->orderByDesc('id')
            ->get()
            ->first(fn (TenantMember $member) => (bool) $member->tenancy?->property);
    }

    private function membershipFor(User $user, ?int $accountId, int $tenancyId): TenantMember
    {
        $member = TenantMember::query()
            ->where('user_id', $user->id)
            ->where('tenancy_id', $tenancyId)
            ->when($accountId, fn ($query) => $query->where('account_id', $accountId))
            ->with([
                'tenancy.property',
            ])
            ->first();

        if (! $member?->tenancy || ! $member->tenancy->property) {
            throw ValidationException::withMessages([
                'tenancy_id' => 'That tenancy is not linked to your login.',
            ]);
        }

        return $member;
    }

    private function displayValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Not set';
        }

        return match ($key) {
            'move_in', 'move_out' => rs_date($value, 'Not set'),
            'rent', 'deposit' => '£'.number_format((float) $value, 2),
            'term_months' => ((int) $value).' months',
            default => (string) $value,
        };
    }

    private function rawStoredValue(string $key, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($key) {
            'move_in', 'move_out' => $value instanceof \DateTimeInterface
                ? $value->format('Y-m-d')
                : (string) $value,
            'rent', 'deposit' => number_format((float) $value, 2, '.', ''),
            'term_months' => (int) $value,
            default => $value,
        };
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function validatedChanges(TenancyCorrectionRequest $request, array $changes): array
    {
        $requestedKeys = collect($request->fields ?? [])->pluck('key')->all();
        $apply = [];

        foreach (self::FIELDS as $key => $meta) {
            if (! $meta['editable'] || ! in_array($key, $requestedKeys, true) || ! array_key_exists($key, $changes)) {
                continue;
            }

            $value = $changes[$key];
            if ($value === null || $value === '') {
                if (in_array($key, ['move_out', 'term_months'], true)) {
                    $apply[$key] = null;
                }
                continue;
            }

            $apply[$key] = match ($meta['type']) {
                'date' => $value,
                'money' => round((float) $value, 2),
                'integer' => (int) $value,
                default => $value,
            };
        }

        return $apply;
    }

    private function resetConfirmations(Tenancy $tenancy): void
    {
        TenantMember::query()
            ->where('tenancy_id', $tenancy->id)
            ->update([
                'details_status' => self::STATUS_PENDING,
                'details_confirmed_at' => null,
            ]);
    }

    private function notifyLandlords(Tenancy $tenancy, TenancyCorrectionRequest $request, User $tenant): void
    {
        $accountId = (int) ($tenancy->account_id ?: current_account_id());
        $adminIds = AccountUser::query()
            ->where('account_id', $accountId)
            ->where('status', 'active')
            ->whereIn('member_type', AccountMembership::WORKSPACE_OPERATOR_TYPES)
            ->pluck('user_id');

        $recipients = User::query()->whereKey($adminIds)->get()
            ->merge($tenancy->propertyManagers()->get())
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        $this->notifications->dispatch(
            CrmNotificationEvent::TenancyDetailsCorrectionRequested,
            $tenancy,
            [
                'account_id' => $accountId,
                'recipients' => $recipients,
                'exclude_actor' => true,
                'property_address' => $tenancy->property?->full_address ?: ($tenancy->property?->prop_name ?: 'your property'),
                'customer_name' => $tenant->name ?: $tenant->email,
                'action_url' => route('admin.tenancies.show', $tenancy->id),
                'milestone' => 'correction-requested-'.$request->id,
            ],
            $tenant,
        );
    }

    private function notifyTenant(Tenancy $tenancy, TenancyCorrectionRequest $request, User $reviewer, bool $approved): void
    {
        $accountId = (int) ($tenancy->account_id ?: current_account_id());
        $tenant = $request->requester;
        if (! $tenant) {
            return;
        }

        $this->notifications->dispatch(
            $approved
                ? CrmNotificationEvent::TenancyDetailsCorrectionApproved
                : CrmNotificationEvent::TenancyDetailsCorrectionRejected,
            $tenancy,
            [
                'account_id' => $accountId,
                'recipients' => [$tenant],
                'exclude_actor' => true,
                'property_address' => $tenancy->property?->full_address ?: ($tenancy->property?->prop_name ?: 'your home'),
                'customer_name' => $tenant->name ?: $tenant->email,
                'action_url' => route('tenant.tenancy'),
                'milestone' => 'correction-reviewed-'.$request->id,
            ],
            $reviewer,
        );
    }
}
