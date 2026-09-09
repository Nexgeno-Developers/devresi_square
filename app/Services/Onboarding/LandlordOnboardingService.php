<?php

namespace App\Services\Onboarding;

use App\Models\Account;
use App\Models\AccountUser;
use App\Models\Country;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Event;
use App\Models\EventSubType;
use App\Models\EventType;
use App\Models\OwnerGroup;
use App\Models\OwnerGroupUser;
use App\Models\Property;
use App\Models\PropertyParticipant;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\Upload;
use App\Models\User;
use App\Services\Chimnie\ChimnieClient;
use App\Services\Property\LandlordPropertyWizardService;
use App\Services\Property\UkOpenDataPropertyEnricher;
use App\Services\Portal\TenantInviteMailer;
use App\Services\Saas\AccountLimitService;
use App\Support\AccountMembership;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class LandlordOnboardingService
{
    public const PHOTO_ID = 'Photo ID';

    public const PROOF_OF_ADDRESS = 'Proof of Address';

    public const ADD_SESSION = 'landlord_adding_property';

    public const DRAFT_SESSION = 'landlord_add_draft_property_id';

    public function __construct(
        private readonly ChimnieClient $chimnie,
        private readonly LandlordPropertyWizardService $wizard,
        private readonly UkOpenDataPropertyEnricher $enricher,
        private readonly AccountLimitService $limits,
    ) {
    }

    public function shouldShow(?User $user, ?Account $account): bool
    {
        if (! $user || ! $account) {
            return false;
        }

        if (! $user->hasRole('Landlord') || $user->hasAnyRole(['Super Admin', 'Property Manager', 'Estate Agent'])) {
            return false;
        }

        if (! in_array($account->status, ['trialing', 'active', 'past_due'], true)) {
            return false;
        }

        return $this->hasNoProperties($account);
    }

    public function shouldIncludeOverlay(?User $user, ?Account $account): bool
    {
        return $this->shouldShow($user, $account) || $this->isAddingProperty($user, $account);
    }

    public function isAddingProperty(?User $user, ?Account $account): bool
    {
        if (! session(self::ADD_SESSION)) {
            return false;
        }

        if (! $user || ! $account) {
            return false;
        }

        return $user->hasRole('Landlord') && ! $user->hasAnyRole(['Super Admin', 'Property Manager', 'Estate Agent']);
    }

    public function startAddProperty(): void
    {
        session([self::ADD_SESSION => true]);
    }

    public function dismissAddProperty(): void
    {
        session()->forget([self::ADD_SESSION, self::DRAFT_SESSION]);
    }

    /**
     * Close add-property mode. If the account already has a home, first-run
     * overlay does not come back on the next dashboard or billing visit.
     */
    public function leaveAddFlow(?Account $account): void
    {
        $this->dismissAddProperty();

        if (! $account || $this->hasNoProperties($account) || $account->onboarding_completed_at) {
            return;
        }

        $account->update([
            'onboarding_completed_at' => now(),
            'onboarding_step' => max(3, (int) $account->onboarding_step),
        ]);
    }

    public function syncOverlaySessionForPage(?User $user, ?Account $account, bool $addingProperty = false): void
    {
        if (! $user || ! $account) {
            return;
        }

        if (! $user->hasRole('Landlord') || $user->hasAnyRole(['Super Admin', 'Property Manager', 'Estate Agent'])) {
            return;
        }

        if ($addingProperty) {
            $this->startAddProperty();

            return;
        }

        $this->leaveAddFlow($account);
    }

    public function canMutate(?User $user, ?Account $account): bool
    {
        if (! $user || ! $account) {
            return false;
        }

        if (! $user->hasRole('Landlord') || $user->hasAnyRole(['Super Admin', 'Property Manager', 'Estate Agent'])) {
            return false;
        }

        return $this->shouldShow($user, $account)
            || $this->isAddingProperty($user, $account)
            || $account->onboarding_completed_at === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function state(Account $account, User $user): array
    {
        $this->reopenIfEmpty($account);
        $property = $this->currentDraftProperty($account);
        $owners = $property ? $this->ownerPayload($property) : [];
        $tenancy = $property
            ? Tenancy::query()
                ->where('property_id', $property->id)
                ->with(['tenantMembers.user'])
                ->latest('id')
                ->first()
            : null;

        $leadMember = $tenancy?->tenantMembers
            ->firstWhere('is_main_person', true)
            ?? $tenancy?->tenantMembers->first();

        $occupants = collect($tenancy?->tenantMembers)
            ->filter(fn (TenantMember $member) => $member->user && ! $member->is_main_person)
            ->map(fn (TenantMember $member) => [
                'id' => $member->user->id,
                'name' => $member->user->name,
                'email' => $member->user->email,
                'phone' => $member->user->phone,
            ])
            ->values()
            ->all();

        return [
            'step' => $this->normalisedStep($account),
            'completed' => (bool) $account->onboarding_completed_at,
            'add_mode' => $this->isAddingProperty($user, $account),
            'test_mode' => (bool) config('chimnie.test_mode', true),
            'property' => $property ? $this->propertyPayload($property) : null,
            'owner' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'owners' => $owners,
            'tenancy' => $leadMember && $leadMember->user ? [
                'id' => $tenancy->id,
                'name' => $leadMember->user->name,
                'email' => $leadMember->user->email,
                'phone' => $leadMember->user->phone,
                'occupants' => $occupants,
            ] : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $postcode): array
    {
        return $this->chimnie->searchByPostcode($postcode);
    }

    /**
     * @return array<string, mixed>
     */
    public function saveProperty(Account $account, User $user, string $chimnieId, string $postcode): array
    {
        $record = $this->chimnie->findById($chimnieId, $postcode);

        if (! $record) {
            throw ValidationException::withMessages([
                'chimnie_id' => 'That property is no longer in the Chimnie results. Search again.',
            ]);
        }

        $record = $this->enricher->enrich($record);

        $countryId = Country::query()
            ->whereIn('code', ['UK', 'GB', 'GBR'])
            ->orderByRaw("CASE code WHEN 'UK' THEN 0 WHEN 'GB' THEN 1 ELSE 2 END")
            ->value('id')
            ?? Country::query()->where('name', 'like', 'United Kingdom%')->value('id')
            ?? Country::query()->orderBy('id')->value('id');

        if (! $countryId) {
            throw ValidationException::withMessages([
                'postcode' => 'The UK country record is missing, so the property cannot be saved yet.',
            ]);
        }

        $payload = $this->propertyAttributesFromRecord($record, $countryId);

        $property = DB::transaction(function () use ($account, $user, $payload) {
            $existing = $this->currentDraftProperty($account);

            if ($existing) {
                $property = $this->wizard->updateStep($existing, $payload, 3);
            } else {
                if (! $this->limits->canAddProperty($account)) {
                    throw ValidationException::withMessages([
                        'postcode' => 'Your current plan has reached the property limit.',
                    ]);
                }

                $property = $this->wizard->createDraft($payload);
                $property = $this->wizard->updateStep($property, $payload, 3);
            }

            $this->ensureOwnerGroup($property, $user);

            $account->update([
                'onboarding_property_id' => $property->id,
                'onboarding_step' => max(2, (int) $account->onboarding_step),
            ]);

            session([self::DRAFT_SESSION => $property->id]);

            return $property;
        });

        return array_merge($this->propertyPayload($property->fresh()), [
            'data_sources' => $record['data_sources'] ?? ['chimnie'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function saveDocument(User $user, string $kind, UploadedFile $file): array
    {
        $typeName = $kind === 'photo_id' ? self::PHOTO_ID : self::PROOF_OF_ADDRESS;
        $documentType = DocumentType::query()->firstOrCreate(
            ['name' => $typeName],
            ['description' => $kind === 'photo_id'
                ? 'Passport, driving licence, or national identity card.'
                : 'Utility bill, bank statement, or council tax letter dated within 3 months.']
        );

        $upload = Upload::storeFile($file);

        $document = Document::query()
            ->where('documentable_type', User::class)
            ->where('documentable_id', $user->id)
            ->where('document_type_id', $documentType->id)
            ->first();

        if ($document) {
            $document->update(['upload_ids' => (string) $upload->id]);
        } else {
            $document = Document::create([
                'account_id' => current_account_id(),
                'documentable_type' => User::class,
                'documentable_id' => $user->id,
                'upload_ids' => (string) $upload->id,
                'document_type_id' => $documentType->id,
                'created_by' => $user->id,
            ]);
        }

        $account = current_account();
        if ($account && (int) $account->onboarding_step < 3) {
            $account->update(['onboarding_step' => 3]);
        }

        return $this->documentPayload($user->fresh(), $typeName) ?? [
            'id' => $document->id,
            'name' => $upload->file_original_name,
        ];
    }

    /**
     * @param  array{name?: string, email?: string, phone?: string}  $lead
     * @param  array<int, array{name?: string, email?: string, phone?: string}>  $owners
     * @return array<string, mixed>
     */
    public function saveOwners(Account $account, User $landlord, array $lead, array $owners): array
    {
        $property = $this->requireProperty($account);
        $leadName = trim((string) ($lead['name'] ?? $landlord->name));
        $leadEmail = strtolower(trim((string) ($lead['email'] ?? $landlord->email)));
        $leadPhone = trim((string) ($lead['phone'] ?? ''));

        if ($leadName === '') {
            throw ValidationException::withMessages([
                'owner.name' => 'Confirm the owner name shown on the title.',
            ]);
        }

        if ($leadEmail !== strtolower((string) $landlord->email)) {
            throw ValidationException::withMessages([
                'owner.email' => 'The lead owner must use your login email. Add other names as co-owners.',
            ]);
        }

        DB::transaction(function () use ($property, $landlord, $owners, $account, $leadName, $leadPhone) {
            $landlord->update([
                'name' => $leadName,
                'phone' => $leadPhone !== '' ? $leadPhone : $landlord->phone,
            ]);

            $group = $this->ensureOwnerGroup($property, $landlord->fresh());
            $existingEmails = OwnerGroupUser::query()
                ->where('owner_group_id', $group->id)
                ->with('user')
                ->get()
                ->map(fn (OwnerGroupUser $row) => strtolower((string) $row->user?->email))
                ->filter()
                ->all();

            foreach ($owners as $index => $owner) {
                $name = trim((string) ($owner['name'] ?? ''));
                $email = strtolower(trim((string) ($owner['email'] ?? '')));
                $phone = trim((string) ($owner['phone'] ?? ''));

                if ($name === '' && $email === '' && $phone === '') {
                    continue;
                }

                if ($name === '' || $email === '') {
                    throw ValidationException::withMessages([
                        "owners.$index.email" => 'Each co-owner needs a name and email.',
                    ]);
                }

                if (in_array($email, $existingEmails, true) || $email === strtolower((string) $landlord->email)) {
                    continue;
                }

                $user = $this->findOrCreateContact($landlord, $name, $email, $phone, 'Owner', 'owner_contact');
                $this->attachOwner($group, $user, false);
                $this->attachParticipant($account, $property, $user, 'owner', $landlord);
                $existingEmails[] = $email;
            }

            $account->update(['onboarding_step' => max(3, (int) $account->onboarding_step)]);
        });

        return [
            'owner' => [
                'name' => $leadName,
                'email' => $leadEmail,
                'phone' => $leadPhone !== '' ? $leadPhone : $landlord->fresh()->phone,
            ],
            'owners' => $this->ownerPayload($property->fresh()),
        ];
    }

    /**
     * @param  array{name: string, email: string, phone?: string, occupants?: array<int, array{name?: string, email?: string, phone?: string}>}  $tenant
     * @return array<string, mixed>
     */
    public function saveTenancy(Account $account, User $landlord, array $tenant, bool $sendInvite = true): array
    {
        $property = $this->requireProperty($account);
        $name = trim((string) ($tenant['name'] ?? ''));
        $email = strtolower(trim((string) ($tenant['email'] ?? '')));
        $phone = trim((string) ($tenant['phone'] ?? ''));
        $occupants = is_array($tenant['occupants'] ?? null) ? $tenant['occupants'] : [];
        $rent = round((float) ($tenant['rent'] ?? 0), 2);
        $deposit = round((float) ($tenant['deposit'] ?? 0), 2);
        $frequency = in_array($tenant['frequency'] ?? '', ['Monthly', 'Weekly'], true)
            ? $tenant['frequency']
            : 'Monthly';
        $termMonths = max(1, min(36, (int) ($tenant['term_months'] ?? 12)));
        $moveIn = $tenant['move_in'] ?? now()->toDateString();

        if ($email === strtolower((string) $landlord->email)) {
            throw ValidationException::withMessages([
                'email' => 'Use the lead tenant’s email, not your own.',
            ]);
        }

        $household = array_merge([[
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'lead' => true,
        ]], array_map(function (array $row) {
            return [
                'name' => trim((string) ($row['name'] ?? '')),
                'email' => strtolower(trim((string) ($row['email'] ?? ''))),
                'phone' => trim((string) ($row['phone'] ?? '')),
                'lead' => false,
            ];
        }, $occupants));

        $invites = [];

        $terms = [
            'move_in' => $moveIn,
            'rent' => $rent,
            'deposit' => $deposit,
            'frequency' => $frequency,
            'term_months' => $termMonths,
        ];

        [$tenancy, $leadUser] = DB::transaction(function () use ($account, $landlord, $property, $household, $terms, &$invites) {
            $tenancy = Tenancy::query()
                ->where('property_id', $property->id)
                ->where('status', 'Active')
                ->latest('id')
                ->first();

            if (! $tenancy) {
                $tenancy = Tenancy::create(array_merge($terms, [
                    'account_id' => $account->id,
                    'property_id' => $property->id,
                    'status' => 'Active',
                ]));
            } else {
                $tenancy->update($terms);
            }

            $keepUserIds = [];
            $leadUser = null;

            foreach ($household as $index => $person) {
                if (($person['name'] ?? '') === '' && ($person['email'] ?? '') === '') {
                    continue;
                }

                if (($person['name'] ?? '') === '' || ($person['email'] ?? '') === '') {
                    $field = $person['lead'] ? 'email' : "occupants.$index.email";
                    throw ValidationException::withMessages([
                        $field => 'Each tenant needs a name and email.',
                    ]);
                }

                $wasNew = User::query()->where('email', $person['email'])->doesntExist();
                $user = $this->findOrCreateContact(
                    $landlord,
                    $person['name'],
                    $person['email'],
                    $person['phone'],
                    'Tenant',
                    'tenant'
                );
                $this->attachParticipant($account, $property, $user, 'tenant', $landlord);

                $selected = is_array($user->selected_properties)
                    ? $user->selected_properties
                    : (json_decode($user->selected_properties ?? '[]', true) ?: []);
                if (! in_array((int) $property->id, array_map('intval', $selected), true)) {
                    $selected[] = (int) $property->id;
                    $user->update(['selected_properties' => json_encode(array_values($selected))]);
                }

                TenantMember::query()->updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'tenancy_id' => $tenancy->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'is_main_person' => (bool) $person['lead'],
                        'group_id' => 'GROUP_'.$tenancy->id,
                        'can_login' => (bool) $user->accountUsers()->where('account_id', $account->id)->value('can_login'),
                    ]
                );

                if ($person['lead']) {
                    TenantMember::query()
                        ->where('tenancy_id', $tenancy->id)
                        ->where('user_id', '!=', $user->id)
                        ->update(['is_main_person' => false]);
                    $leadUser = $user;
                }

                $keepUserIds[] = $user->id;
                $invites[] = ['user' => $user, 'was_new' => $wasNew];
            }

            if ($keepUserIds !== []) {
                TenantMember::query()
                    ->where('tenancy_id', $tenancy->id)
                    ->whereNotIn('user_id', $keepUserIds)
                    ->delete();
            }

            $property->update(['letting_current_status' => 'let agreed']);

            return [$tenancy, $leadUser];
        });

        try {
            $this->scheduleMoveInEvent(
                $account,
                $landlord,
                $property,
                $tenancy,
                collect($invites)->map(fn (array $row) => $row['user']->id)->all()
            );
        } catch (\Throwable $e) {
            Log::warning('Landlord overlay could not create a move-in calendar event.', [
                'account_id' => $account->id,
                'property_id' => $property->id,
                'error' => $e->getMessage(),
            ]);
        }

        $invite = [
            'invited' => $sendInvite,
            'sent' => false,
            'error' => null,
        ];

        if ($sendInvite) {
            foreach ($invites as $row) {
                $mail = $this->sendTenantInvite($account, $row['user'], $property, $row['was_new']);
                if ($mail['sent']) {
                    $invite['sent'] = true;
                } elseif ($mail['error'] && $invite['error'] === null) {
                    $invite['error'] = $mail['error'];
                }
            }
        }

        $occupantPayload = collect($household)
            ->reject(fn (array $person) => $person['lead'] || ($person['email'] === '' && $person['name'] === ''))
            ->map(fn (array $person) => [
                'name' => $person['name'],
                'email' => $person['email'],
                'phone' => $person['phone'],
            ])
            ->values()
            ->all();

        return [
            'id' => $tenancy->id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'rent' => $tenancy->rent,
            'deposit' => $tenancy->deposit,
            'frequency' => $tenancy->frequency,
            'term_months' => $tenancy->term_months,
            'move_in' => optional($tenancy->move_in)?->toDateString(),
            'occupants' => $occupantPayload,
            'invited' => $invite['invited'],
            'sent' => $invite['sent'],
            'error' => $invite['error'],
        ];
    }

    /**
     * @return array{id: int, name: ?string, email: ?string, phone: ?string}|null
     */
    public function latestOnboardingTenancy(Account $account): ?array
    {
        $property = $this->onboardingProperty($account);

        if (! $property) {
            return null;
        }

        $tenancy = Tenancy::query()
            ->where('account_id', $account->id)
            ->where('property_id', $property->id)
            ->latest('id')
            ->first();

        if (! $tenancy) {
            return null;
        }

        $member = TenantMember::query()
            ->where('tenancy_id', $tenancy->id)
            ->orderByDesc('is_main_person')
            ->first();
        $tenant = $member ? User::query()->find($member->user_id) : null;

        return [
            'id' => $tenancy->id,
            'name' => $tenant?->name,
            'email' => $tenant?->email,
            'phone' => $tenant?->phone,
            'rent' => $tenancy->rent,
            'deposit' => $tenancy->deposit,
            'frequency' => $tenancy->frequency,
            'term_months' => $tenancy->term_months,
            'move_in' => optional($tenancy->move_in)?->toDateString(),
        ];
    }

    public function complete(Account $account, User $user): void
    {
        $this->requireProperty($account);

        $account->update([
            'onboarding_completed_at' => now(),
            'onboarding_step' => 3,
        ]);

        $this->dismissAddProperty();
    }

    public function saveStep(Account $account, int $step): void
    {
        $account->update([
            'onboarding_step' => max(1, min(3, $step)),
        ]);
    }

    public function hasNoProperties(Account $account): bool
    {
        return ! Property::query()->where('account_id', $account->id)->exists();
    }

    private function reopenIfEmpty(Account $account): void
    {
        if (! $this->hasNoProperties($account)) {
            return;
        }

        if (
            $account->onboarding_completed_at === null
            && (int) $account->onboarding_step === 1
            && ! $account->onboarding_property_id
        ) {
            return;
        }

        $account->forceFill([
            'onboarding_completed_at' => null,
            'onboarding_step' => 1,
            'onboarding_property_id' => null,
        ])->save();
    }

    private function requireProperty(Account $account): Property
    {
        $property = $this->currentDraftProperty($account);

        if (! $property) {
            throw ValidationException::withMessages([
                'property' => 'Add a property first.',
            ]);
        }

        return $property;
    }

    private function currentDraftProperty(Account $account): ?Property
    {
        $draftId = session(self::DRAFT_SESSION);

        if ($draftId) {
            return Property::query()
                ->where('account_id', $account->id)
                ->whereKey($draftId)
                ->first();
        }

        if ($account->onboarding_completed_at && session(self::ADD_SESSION)) {
            return null;
        }

        return $this->onboardingProperty($account);
    }

    private function normalisedStep(Account $account): int
    {
        $step = (int) ($account->onboarding_step ?: 1);

        if ($step >= 4) {
            return 3;
        }

        if ($step === 3 && ! $this->currentDraftProperty($account)?->id) {
            return 1;
        }

        return max(1, min(3, $step));
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function propertyAttributesFromRecord(array $record, int $countryId): array
    {
        $blank = static fn ($value) => $value === null || trim((string) $value) === '';

        return array_filter([
            'line_1' => $record['line_1'] ?? null,
            'line_2' => $blank($record['line_2'] ?? null) ? null : $record['line_2'],
            'city' => $record['city'] ?? null,
            'county' => $blank($record['county'] ?? null) ? null : $record['county'],
            'postcode' => $record['postcode'] ?? null,
            'country' => $countryId,
            'currency' => $blank($record['currency'] ?? null) ? 'GBP' : $record['currency'],
            'uprn' => $blank($record['uprn'] ?? null) ? null : $record['uprn'],
            'prop_name' => $record['line_1'] ?? null,
            'specific_property_type' => $blank($record['specific_property_type'] ?? null) ? 'flat' : $record['specific_property_type'],
            'property_type' => $blank($record['property_type'] ?? null) ? 'lettings' : $record['property_type'],
            'bedroom' => $blank($record['bedroom'] ?? null) ? '1' : (string) $record['bedroom'],
            'bathroom' => $blank($record['bathroom'] ?? null) ? '1' : (string) $record['bathroom'],
            'reception' => $blank($record['reception'] ?? null) ? null : (string) $record['reception'],
            'tenure' => $blank($record['tenure'] ?? null) ? null : $record['tenure'],
            'epc_rating' => $blank($record['epc_rating'] ?? null) ? null : $record['epc_rating'],
            'epc_required' => $blank($record['epc_required'] ?? null) ? null : (int) (bool) $record['epc_required'],
            'council_tax_band' => $blank($record['council_tax_band'] ?? null) ? null : $record['council_tax_band'],
            'square_meter' => $blank($record['square_meter'] ?? null) ? null : (string) $record['square_meter'],
            'square_feet' => $blank($record['square_feet'] ?? null) ? null : (string) $record['square_feet'],
            'floor' => $blank($record['floor'] ?? null) ? null : $record['floor'],
            'local_authority' => $blank($record['local_authority'] ?? null) ? null : $record['local_authority'],
            'letting_current_status' => $blank($record['letting_current_status'] ?? null) ? 'available' : $record['letting_current_status'],
            'useful_information' => isset($record['data_sources']) && is_array($record['data_sources'])
                ? 'Sources: '.implode(', ', $record['data_sources'])
                : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function onboardingProperty(Account $account): ?Property
    {
        if (! $account->onboarding_property_id) {
            return null;
        }

        return Property::query()
            ->where('account_id', $account->id)
            ->whereKey($account->onboarding_property_id)
            ->first();
    }

    private function ensureOwnerGroup(Property $property, User $landlord): OwnerGroup
    {
        $group = OwnerGroup::query()
            ->where('property_id', $property->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if (! $group) {
            $group = OwnerGroup::create([
                'property_id' => $property->id,
                'purchased_date' => now()->toDateString(),
                'status' => 'active',
                'added_by' => $landlord->id,
            ]);
        }

        $this->attachOwner($group, $landlord, true);

        return $group;
    }

    private function attachOwner(OwnerGroup $group, User $user, bool $isMain): void
    {
        $row = OwnerGroupUser::query()
            ->where('owner_group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if ($row) {
            if ($isMain && ! $row->is_main) {
                OwnerGroupUser::query()
                    ->where('owner_group_id', $group->id)
                    ->update(['is_main' => 0]);
                $row->update(['is_main' => 1]);
            }

            return;
        }

        if ($isMain) {
            OwnerGroupUser::query()
                ->where('owner_group_id', $group->id)
                ->update(['is_main' => 0]);
        }

        OwnerGroupUser::create([
            'owner_group_id' => $group->id,
            'user_id' => $user->id,
            'is_main' => $isMain ? 1 : 0,
            'added_by' => Auth::id(),
        ]);
    }

    private function attachParticipant(Account $account, Property $property, User $user, string $type, User $actor): void
    {
        PropertyParticipant::updateOrCreate(
            [
                'account_id' => $account->id,
                'property_id' => $property->id,
                'user_id' => $user->id,
                'participant_type' => $type,
            ],
            [
                'access_level' => 'view',
                'can_view_finance' => false,
                'can_view_documents' => $type === 'tenant',
                'can_upload_documents' => false,
                'status' => 'active',
                'created_by' => $actor->id,
            ]
        );
    }

    private function findOrCreateContact(
        User $actor,
        string $name,
        string $email,
        string $phone,
        string $roleName,
        string $memberType
    ): User {
        $existing = User::query()->where('email', $email)->first();

        if ($existing) {
            if (! $existing->hasRole($roleName) && $existing->id !== $actor->id) {
                $existing->assignRole($roleName);
            }

            $this->syncMembership($existing, $memberType, $actor);

            if ($phone !== '' && ! $existing->phone) {
                $existing->update(['phone' => $phone]);
            }

            return $existing;
        }

        $parts = preg_split('/\s+/', $name, 2) ?: [$name];
        $user = User::create([
            'name' => $name,
            'first_name' => $parts[0] ?? $name,
            'last_name' => $parts[1] ?? '',
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'password' => Hash::make(Str::random(32)),
            'status' => 1,
            'can_login' => true,
            'created_by' => $actor->id,
        ]);

        Role::findOrCreate($roleName, 'web');
        $user->assignRole($roleName);
        $this->syncMembership($user, $memberType, $actor);

        return $user;
    }

    private function syncMembership(User $user, string $memberType, User $actor): void
    {
        $account = current_account();

        if (! $account) {
            return;
        }

        $canLogin = $this->limits->canUseContactLogin($account);

        if (
            AccountMembership::isPortalType($memberType)
            && ! $this->limits->canAddPortalUser($account, $user)
        ) {
            throw ValidationException::withMessages([
                'email' => 'Your current plan has reached the tenant portal user limit.',
            ]);
        }

        AccountUser::updateOrCreate(
            [
                'account_id' => $account->id,
                'user_id' => $user->id,
            ],
            [
                'member_type' => $memberType,
                'access_level' => 'view',
                'can_login' => $canLogin,
                'status' => 'active',
                'created_by' => $actor->id,
            ]
        );
    }

    /**
     * Put move-in on the account diary so the household sees it in the tenant portal.
     *
     * @param  array<int, int>  $inviteUserIds
     */
    private function scheduleMoveInEvent(Account $account, User $landlord, Property $property, Tenancy $tenancy, array $inviteUserIds): void
    {
        $type = EventType::query()->firstOrCreate(
            ['name' => 'Move-In/Move-Out'],
            ['slug' => 'move-in-move-out', 'description' => 'Move-in and move-out']
        );
        $subType = EventSubType::query()->firstOrCreate(
            [
                'event_type_id' => $type->id,
                'name' => 'Move-In Scheduled',
            ],
            ['slug' => 'move-in-scheduled', 'description' => 'Move-in scheduled']
        );

        $address = trim(implode(', ', array_filter([
            $property->line_1,
            $property->city,
            $property->postcode,
        ])));
        $label = 'Move-in — '.($address !== '' ? $address : ($property->prop_name ?: 'Property'));

        $start = Carbon::parse($tenancy->move_in ?: now())->setTime(10, 0);
        $end = $start->copy()->addHour();

        $event = Event::query()
            ->forAccount($account->id)
            ->where('title', $label)
            ->whereHas('properties', fn ($query) => $query->where('properties.id', $property->id))
            ->first();

        $payload = [
            'account_id' => $account->id,
            'title' => $label,
            'type_id' => $type->id,
            'sub_type_id' => $subType->id,
            'status' => 'Scheduled',
            'diary_owner' => $landlord->id,
            'on_behalf_of' => $landlord->id,
            'location' => $label,
            'description' => 'Created when the tenancy was set up.',
            'start_datetime' => $start,
            'end_datetime' => $end,
        ];

        if ($event) {
            $event->update($payload);
        } else {
            $event = Event::create($payload);
        }

        $event->properties()->sync([$property->id]);
        $event->users()->sync(array_values(array_unique(array_map('intval', $inviteUserIds))));
    }

    /**
     * @return array{sent: bool, error: ?string}
     */
    private function sendTenantInvite(Account $account, User $user, Property $property, bool $wasNew): array
    {
        if (! $this->limits->canUseContactLogin($account)) {
            return [
                'sent' => false,
                'error' => 'Tenant login is not included on this plan.',
            ];
        }

        return app(TenantInviteMailer::class)->send($user, $property, $wasNew);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertyPayload(Property $property): array
    {
        return [
            'id' => $property->id,
            'line_1' => $property->line_1,
            'line_2' => $property->line_2,
            'city' => $property->city,
            'postcode' => $property->postcode,
            'uprn' => $property->uprn,
            'specific_property_type' => $property->specific_property_type,
            'bedroom' => $property->bedroom,
            'bathroom' => $property->bathroom,
            'tenure' => $property->tenure,
            'epc_rating' => $property->epc_rating,
            'council_tax_band' => $property->council_tax_band,
            'square_meter' => $property->square_meter,
            'square_feet' => $property->square_feet,
            'floor' => $property->floor,
            'local_authority' => $property->local_authority,
            'reception' => $property->reception,
            'letting_current_status' => $property->letting_current_status,
            'data_sources' => ['chimnie'],
            'label' => trim(implode(', ', array_filter([
                $property->line_1,
                $property->city,
                $property->postcode,
            ]))),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function documentPayload(User $user, string $typeName): ?array
    {
        $type = DocumentType::query()->where('name', $typeName)->first();

        if (! $type) {
            return null;
        }

        $document = Document::query()
            ->where('documentable_type', User::class)
            ->where('documentable_id', $user->id)
            ->where('document_type_id', $type->id)
            ->latest('id')
            ->first();

        if (! $document || ! $document->upload_ids) {
            return null;
        }

        $uploadId = (int) explode(',', (string) $document->upload_ids)[0];
        $upload = Upload::query()->find($uploadId);

        return [
            'id' => $document->id,
            'name' => $upload?->file_original_name ?: 'Uploaded file',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function ownerPayload(Property $property): array
    {
        $group = OwnerGroup::query()
            ->where('property_id', $property->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if (! $group) {
            return [];
        }

        return $group->ownerGroupUsers()
            ->with('user')
            ->get()
            ->filter(fn (OwnerGroupUser $row) => $row->user && ! $row->is_main)
            ->map(fn (OwnerGroupUser $row) => [
                'id' => $row->user->id,
                'name' => $row->user->name,
                'email' => $row->user->email,
                'phone' => $row->user->phone,
            ])
            ->values()
            ->all();
    }
}
