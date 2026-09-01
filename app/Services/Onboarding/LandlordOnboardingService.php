<?php

namespace App\Services\Onboarding;

use App\Mail\MailManager;
use App\Models\Account;
use App\Models\AccountUser;
use App\Models\Country;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\EmailTemplate;
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
use App\Services\Saas\AccountLimitService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class LandlordOnboardingService
{
    public const PHOTO_ID = 'Photo ID';

    public const PROOF_OF_ADDRESS = 'Proof of Address';

    public function __construct(
        private readonly ChimnieClient $chimnie,
        private readonly LandlordPropertyWizardService $wizard,
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

        if ($account->onboarding_completed_at === null) {
            return true;
        }

        return $this->hasNoProperties($account);
    }

    /**
     * @return array<string, mixed>
     */
    public function state(Account $account, User $user): array
    {
        $this->reopenIfEmpty($account);
        $property = $this->onboardingProperty($account);
        $owners = $property ? $this->ownerPayload($property) : [];
        $tenancy = $property
            ? Tenancy::query()
                ->where('property_id', $property->id)
                ->with(['tenantMembers.user'])
                ->latest('id')
                ->first()
            : null;

        $tenant = $tenancy?->tenantMembers
            ->firstWhere('is_main_person', true)
            ?? $tenancy?->tenantMembers->first();

        return [
            'step' => max(1, min(4, (int) ($account->onboarding_step ?: 1))),
            'completed' => (bool) $account->onboarding_completed_at,
            'test_mode' => (bool) config('chimnie.test_mode', true),
            'property' => $property ? $this->propertyPayload($property) : null,
            'documents' => [
                'photo_id' => $this->documentPayload($user, self::PHOTO_ID),
                'proof_of_address' => $this->documentPayload($user, self::PROOF_OF_ADDRESS),
            ],
            'owners' => $owners,
            'tenancy' => $tenant && $tenant->user ? [
                'id' => $tenancy->id,
                'name' => $tenant->user->name,
                'email' => $tenant->user->email,
                'phone' => $tenant->user->phone,
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

        $payload = [
            'line_1' => $record['line_1'],
            'line_2' => $record['line_2'] ?: null,
            'city' => $record['city'],
            'county' => $record['county'] ?: null,
            'postcode' => $record['postcode'],
            'country' => $countryId,
            'uprn' => $record['uprn'] ?: null,
            'prop_name' => $record['line_1'],
            'specific_property_type' => $record['specific_property_type'] ?: 'flat',
            'property_type' => 'lettings',
            'bedroom' => $record['bedroom'] ?: '1',
            'bathroom' => $record['bathroom'] ?: '1',
            'reception' => $record['reception'] ?: null,
            'tenure' => $record['tenure'] ?: null,
            'epc_rating' => $record['epc_rating'] ?: null,
            'council_tax_band' => $record['council_tax_band'] ?: null,
            'square_meter' => $record['square_meter'] ?: null,
            'floor' => $record['floor'] ?: null,
            'local_authority' => $record['local_authority'] ?: null,
        ];

        $property = DB::transaction(function () use ($account, $user, $payload) {
            $existing = $this->onboardingProperty($account);

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

            return $property;
        });

        return $this->propertyPayload($property->fresh());
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
     * @param  array<int, array{name?: string, email?: string, phone?: string}>  $owners
     * @return array<int, array<string, mixed>>
     */
    public function saveOwners(Account $account, User $landlord, array $owners): array
    {
        $property = $this->requireProperty($account);

        DB::transaction(function () use ($property, $landlord, $owners, $account) {
            $group = $this->ensureOwnerGroup($property, $landlord);
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
                        "owners.$index.email" => 'Each owner needs a name and email.',
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

            $account->update(['onboarding_step' => max(4, (int) $account->onboarding_step)]);
        });

        return $this->ownerPayload($property->fresh());
    }

    /**
     * @param  array{name: string, email: string, phone?: string}  $tenant
     * @return array<string, mixed>
     */
    public function saveTenancy(Account $account, User $landlord, array $tenant): array
    {
        $property = $this->requireProperty($account);
        $name = trim((string) ($tenant['name'] ?? ''));
        $email = strtolower(trim((string) ($tenant['email'] ?? '')));
        $phone = trim((string) ($tenant['phone'] ?? ''));

        if ($email === strtolower((string) $landlord->email)) {
            throw ValidationException::withMessages([
                'email' => 'Use the tenant’s email, not your own.',
            ]);
        }

        $tenancy = DB::transaction(function () use ($account, $landlord, $property, $name, $email, $phone) {
            Tenancy::query()
                ->where('property_id', $property->id)
                ->where('status', 'Active')
                ->update(['status' => 'Archived']);

            $user = $this->findOrCreateContact($landlord, $name, $email, $phone, 'Tenant', 'tenant');
            $this->attachParticipant($account, $property, $user, 'tenant', $landlord);

            $selected = is_array($user->selected_properties)
                ? $user->selected_properties
                : (json_decode($user->selected_properties ?? '[]', true) ?: []);
            if (! in_array((int) $property->id, array_map('intval', $selected), true)) {
                $selected[] = (int) $property->id;
                $user->update(['selected_properties' => json_encode(array_values($selected))]);
            }

            $tenancy = Tenancy::create([
                'account_id' => $account->id,
                'property_id' => $property->id,
                'status' => 'Active',
                'move_in' => now()->toDateString(),
                'rent' => 0,
                'deposit' => 0,
                'frequency' => 'Monthly',
                'term_months' => 12,
            ]);

            TenantMember::create([
                'account_id' => $account->id,
                'tenancy_id' => $tenancy->id,
                'user_id' => $user->id,
                'is_main_person' => true,
                'group_id' => 'GROUP_'.$tenancy->id,
                'can_login' => (bool) $user->accountUsers()->where('account_id', $account->id)->value('can_login'),
            ]);

            $this->sendTenantWelcome($user, $property);

            return $tenancy;
        });

        return [
            'id' => $tenancy->id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ];
    }

    public function complete(Account $account, User $user): void
    {
        $this->requireProperty($account);

        if (! $this->documentPayload($user, self::PHOTO_ID) || ! $this->documentPayload($user, self::PROOF_OF_ADDRESS)) {
            throw ValidationException::withMessages([
                'documents' => 'Upload a photo ID and a proof of address before finishing.',
            ]);
        }

        $account->update([
            'onboarding_completed_at' => now(),
            'onboarding_step' => 4,
        ]);
    }

    public function saveStep(Account $account, int $step): void
    {
        $account->update([
            'onboarding_step' => max(1, min(4, $step)),
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
        $property = $this->onboardingProperty($account);

        if (! $property) {
            throw ValidationException::withMessages([
                'property' => 'Add a property first.',
            ]);
        }

        return $property;
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

    private function sendTenantWelcome(User $user, Property $property): void
    {
        try {
            $plainPassword = Str::random(10);
            $user->update(['password' => Hash::make($plainPassword)]);
            $resetLink = $user->createResetLink();
            $address = trim(implode(', ', array_filter([
                $property->line_1,
                $property->city,
                $property->postcode,
            ])));

            $placeholders = [
                'tenant_name' => $user->name ?? $user->email,
                'tenant_email' => $user->email,
                'tenant_password' => $plainPassword,
                'property_name' => $property->prop_name ?: $property->line_1,
                'property_address' => $address !== '' ? $address : '—',
                'move_in_date' => now()->toFormattedDateString(),
                'rent' => '—',
                'reset_link' => $resetLink,
                'login_url' => url('/admin/login'),
                'crm_name' => config('app.name'),
                'admin_email' => config('mail.from.address'),
            ];

            $template = EmailTemplate::getByIdentifier('tenant_account_created');

            if ($template) {
                $renderedHtml = $template->replace($placeholders, ['reset_link', 'login_url']);
                $subject = render_template($template->subject, $placeholders);
            } else {
                $subject = 'Welcome to '.config('app.name').' — Your tenant account';
                $renderedHtml = '<p>Hi '.e($placeholders['tenant_name']).',</p>'
                    .'<p>Your landlord has set up a tenancy for '.e($placeholders['property_address']).'.</p>'
                    .'<p>Email: '.e($placeholders['tenant_email']).' | Password: <strong>'.e($placeholders['tenant_password']).'</strong></p>'
                    .'<p><a href="'.e($placeholders['login_url']).'">Log in</a> or <a href="'.e($resetLink).'">choose a password</a>.</p>';
            }

            Mail::to($user->email)->send(new MailManager([
                'subject' => $subject,
                'content' => $renderedHtml,
                'attachments' => [],
            ]));
        } catch (\Throwable $exception) {
            Log::error('Landlord onboarding tenant welcome email failed: '.$exception->getMessage(), [
                'email' => $user->email,
                'user_id' => $user->id,
            ]);
        }
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
