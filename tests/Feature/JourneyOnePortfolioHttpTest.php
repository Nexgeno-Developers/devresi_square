<?php

namespace Tests\Feature;

use App\Models\AccountUser;
use App\Models\OwnerGroupUser;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use App\Services\Onboarding\LandlordOnboardingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JourneyOnePortfolioHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_other_account_property_lookup_hides_contact_details(): void
    {
        [$owner, $accountId] = $this->createLandlord('Lookup Owner');
        $secretEmail = 'secret-tenant-'.uniqid().'@resisquare.test';
        $property = $this->property($accountId, $owner->id, '99 Hidden Street', 'SW1A 9ZZ');
        $tenant = $this->membership($accountId, 'Tenant', 'tenant', 'Secret Tenant', $secretEmail);
        $this->linkTenant($accountId, $property, $tenant);

        [$otherOwner] = $this->createLandlord('Other Lookup');
        $intruders = [
            'landlord' => $otherOwner,
            'tenant' => $this->membership($this->accountIdOf($otherOwner), 'Tenant', 'tenant', 'Other Tenant'),
            'contractor' => $this->membership($this->accountIdOf($otherOwner), 'Contractor', 'contractor', 'Other Contractor'),
            'property_manager' => $this->membership($this->accountIdOf($otherOwner), 'Property Manager', 'property_manager', 'Other Manager'),
        ];

        foreach ($intruders as $intruder) {
            $response = $this->actingAs($intruder)
                ->withSession(['current_account_id' => $this->accountIdOf($intruder)])
                ->getJson(route('admin.getTenantsByProperty', $property->id));

            $response->assertForbidden();
            $body = $response->getContent();
            $this->assertStringNotContainsString($secretEmail, $body);
            $this->assertStringNotContainsString('Secret Tenant', $body);
            $this->assertStringNotContainsString('99 Hidden Street', $body);
        }
    }

    public function test_lookup_returns_the_new_name_after_a_profile_save(): void
    {
        [$owner, $accountId] = $this->createLandlord('Rename Owner');
        $property = $this->property($accountId, $owner->id, '1 Open Road', 'E1 1AA');
        $tenant = $this->membership($accountId, 'Tenant', 'tenant', 'Old Tenant', 'old-tenant-'.uniqid().'@resisquare.test');
        $tenant->forceFill(['first_name' => 'Old', 'last_name' => 'Tenant'])->save();
        $this->linkTenant($accountId, $property, $tenant);

        $session = ['current_account_id' => $accountId];

        $this->actingAs($owner)->withSession($session)
            ->getJson(route('admin.getTenantsByProperty', $property->id))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Old Tenant']);

        $this->actingAs($tenant)->withSession($session)
            ->post(route('tenant.profile.update'), [
                'first_name' => 'New',
                'last_name' => 'Tenant',
                'email' => $tenant->email,
                'phone' => '07000000000',
            ])
            ->assertRedirect(route('tenant.profile'));

        $this->actingAs($owner)->withSession($session)
            ->getJson(route('admin.getTenantsByProperty', $property->id))
            ->assertOk()
            ->assertJsonFragment(['name' => 'New Tenant'])
            ->assertJsonMissing(['name' => 'Old Tenant']);
    }

    public function test_staff_and_landlord_contact_share_the_owner_allowlist(): void
    {
        [$owner, $accountId] = $this->createLandlord('Wall Owner');
        $property = $this->property($accountId, $owner->id, '2 Allowlist Road', 'E2 2BB');
        $staff = $this->membership($accountId, 'Staff', 'staff', 'Wall Staff');
        $contact = $this->membership($accountId, 'Landlord', 'landlord', 'Wall Contact');
        $session = ['current_account_id' => $accountId];

        foreach ([$owner, $staff, $contact] as $user) {
            $this->actingAs($user)->withSession($session)
                ->get(route('admin.offers.index'))
                ->assertForbidden();

            $properties = $this->actingAs($user)->withSession($session)
                ->get(route('admin.properties.index'));
            if ($properties->status() === 302) {
                $properties = $this->actingAs($user)->withSession($session)
                    ->get($properties->headers->get('Location'));
            }
            $properties->assertOk();
            if ($user->id === $owner->id || $user->id === $staff->id) {
                $properties->assertSee($property->line_1, false);
            }

            $this->actingAs($user)->withSession($session)
                ->get(route('admin.tenancies.create'))
                ->assertOk();

            $this->actingAs($user)->withSession($session)
                ->get(route('admin.property_repairs.create'))
                ->assertOk();

            $this->actingAs($user)->withSession($session)
                ->get(route('admin.finance.index'))
                ->assertOk();

            $this->actingAs($user)->withSession($session)
                ->get(route('backend.billing.index'))
                ->assertOk();
        }
    }

    public function test_tenant_role_with_staff_membership_cannot_open_the_portal(): void
    {
        [$owner, $accountId] = $this->createLandlord('Portal Wall');
        $staff = $this->membership($accountId, 'Staff', 'staff', 'Role Staff');
        $staff->assignRole(Role::findOrCreate('Tenant', 'web'));
        $session = ['current_account_id' => $accountId];

        $this->actingAs($staff)->withSession($session)
            ->get(route('tenant.rent'))
            ->assertForbidden();

        $this->actingAs($staff)->withSession($session)
            ->get(route('backend.home'))
            ->assertForbidden();

        $tenant = $this->membership($accountId, 'Tenant', 'tenant', 'Real Tenant');
        $this->actingAs($tenant)->withSession($session)
            ->get(route('backend.home'))
            ->assertOk()
            ->assertSee('Tenant portal', false);
    }

    public function test_co_owner_email_does_not_create_a_user_or_a_role(): void
    {
        [$owner, $accountId] = $this->createLandlord('Co Owner Host');
        $property = $this->property($accountId, $owner->id, '4 Shared House', 'N1 1AA');
        $existing = User::create([
            'name' => 'Already Registered',
            'email' => 'already-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $existingRoles = $existing->roles()->pluck('name')->all();
        $freshEmail = 'new-co-owner-'.uniqid().'@resisquare.test';

        $response = $this->actingAs($owner)
            ->withSession([
                'current_account_id' => $accountId,
                LandlordOnboardingService::DRAFT_SESSION => $property->id,
            ])
            ->postJson(route('admin.onboarding.landlord.owners'), [
                'owner' => [
                    'name' => $owner->name,
                    'email' => $owner->email,
                    'phone' => '07111111111',
                ],
                'owners' => [
                    ['name' => 'Already Registered', 'email' => $existing->email, 'phone' => '07222222222'],
                    ['name' => 'Brand New', 'email' => $freshEmail, 'phone' => '07333333333'],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.owners.0.already_has_login', true);
        $response->assertJsonPath('data.owners.1.already_has_login', false);

        $existing->refresh();
        $this->assertSame($existingRoles, $existing->roles()->pluck('name')->all());
        $this->assertFalse(
            AccountUser::query()->where('account_id', $accountId)->where('user_id', $existing->id)->exists()
        );
        $this->assertNull(User::query()->where('email', $freshEmail)->first());
        $this->assertTrue(
            OwnerGroupUser::query()->where('contact_email', $freshEmail)->whereNull('user_id')->exists()
        );
        $this->assertTrue(
            OwnerGroupUser::query()->where('contact_email', $existing->email)->whereNull('user_id')->exists()
        );
    }

    public function test_two_live_properties_cannot_share_an_address(): void
    {
        [$owner, $accountId] = $this->createLandlord('Address Owner');
        $this->property($accountId, $owner->id, '8 Duplicate Lane', 'W1 1AA');

        $this->expectException(ValidationException::class);
        $this->property($accountId, $owner->id, '8 Duplicate Lane', 'W1 1AA');
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(string $name): array
    {
        $role = Role::findOrCreate('Landlord', 'web');
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        $accountId = \DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => 'owner',
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $accountId];
    }

    private function membership(int $accountId, string $roleName, string $memberType, string $name, ?string $email = null): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        $user = User::create([
            'name' => $name,
            'email' => $email ?: strtolower(str_replace(' ', '-', $name)).'-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        \DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => $memberType,
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function property(int $accountId, int $createdBy, string $line1, string $postcode): Property
    {
        return Property::create([
            'account_id' => $accountId,
            'created_by' => $createdBy,
            'line_1' => $line1,
            'city' => 'London',
            'postcode' => $postcode,
        ]);
    }

    private function linkTenant(int $accountId, Property $property, User $tenant): void
    {
        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1000,
        ]);

        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
        ]);
    }

    private function accountIdOf(User $user): int
    {
        return (int) AccountUser::query()->where('user_id', $user->id)->value('account_id');
    }
}
