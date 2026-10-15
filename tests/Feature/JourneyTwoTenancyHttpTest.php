<?php

namespace Tests\Feature;

use App\Mail\MailManager;
use App\Models\Account;
use App\Models\AccountUser;
use App\Models\EmailTemplate;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use App\Services\Onboarding\LandlordOnboardingService;
use Database\Seeders\TenantWelcomeEmailTemplateSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JourneyTwoTenancyHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_store_without_invite_leaves_login_off(): void
    {
        [$landlord, $accountId, $property, $tenant] = $this->household('Quiet');
        $before = AccountUser::query()->where('account_id', $accountId)->count();

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.tenancies.store'), $this->tenancyPayload($property, $tenant))
            ->assertRedirect(route('admin.tenancies.all'))
            ->assertSessionHas('tenancy_notice', 'Tenancy Added successfully!');

        $member = TenantMember::query()->where('user_id', $tenant->id)->first();
        $this->assertNotNull($member);
        $this->assertFalse((bool) $member->can_login);
        $this->assertSame($before, AccountUser::query()->where('account_id', $accountId)->count());
        $this->assertSame(0, (int) AccountUser::query()->where('user_id', $tenant->id)->value('can_login'));
    }

    public function test_store_with_invite_sends_one_mail_and_turns_login_on(): void
    {
        Mail::fake();
        config(['crm_notifications.enabled' => false]);
        $this->seed(TenantWelcomeEmailTemplateSeeder::class);

        [$landlord, $accountId, $property, $tenant] = $this->household('Invited');

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.tenancies.store'), $this->tenancyPayload($property, $tenant, true))
            ->assertRedirect(route('admin.tenancies.all'))
            ->assertSessionHas('tenancy_notice', 'Tenancy added. The invite was sent.');

        Mail::assertSent(MailManager::class, 1);
        $this->assertSame(1, (int) TenantMember::query()->where('user_id', $tenant->id)->value('can_login'));
        $this->assertSame(1, (int) AccountUser::query()->where('user_id', $tenant->id)->where('member_type', 'tenant')->value('can_login'));
    }

    public function test_store_with_invite_and_no_template_keeps_login_off(): void
    {
        Mail::fake();
        config(['crm_notifications.enabled' => false]);
        EmailTemplate::query()->where('identifier', 'tenant_welcome')->delete();

        [$landlord, $accountId, $property, $tenant] = $this->household('No Template');

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.tenancies.store'), $this->tenancyPayload($property, $tenant, true))
            ->assertRedirect(route('admin.tenancies.all'))
            ->assertSessionHas('tenancy_notice', 'Tenancy added, but the invite failed.');

        Mail::assertNothingSent();
        $this->assertSame(0, (int) TenantMember::query()->where('user_id', $tenant->id)->value('can_login'));
        $this->assertSame(0, (int) AccountUser::query()->where('user_id', $tenant->id)->value('can_login'));
    }

    public function test_complete_json_uses_the_recorded_invite_not_the_request(): void
    {
        [$landlord, $accountId, $property] = $this->household('Complete');
        Account::query()->whereKey($accountId)->update([
            'onboarding_property_id' => $property->id,
            'onboarding_completed_at' => null,
            'onboarding_step' => 1,
        ]);

        $this->actingAs($landlord)->withSession([
            'current_account_id' => $accountId,
            LandlordOnboardingService::INVITE_SESSION => [
                'invited' => true,
                'sent' => true,
                'error' => null,
            ],
        ])->postJson(route('admin.onboarding.landlord.complete'), [
            'invited' => false,
            'sent' => false,
            'invite_error' => 'posted-from-the-browser',
        ])
            ->assertOk()
            ->assertJsonPath('data.invite.invited', true)
            ->assertJsonPath('data.invite.sent', true)
            ->assertJsonPath('data.invite.error', null);
    }

    public function test_second_active_tenancy_archives_the_first_and_closes_that_login(): void
    {
        [$landlord, $accountId, $property, $tenant] = $this->household('Displaced');
        $this->openLet($accountId, $property, $tenant);
        $incoming = $this->membership($accountId, 'Incoming Tenant');

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.tenancies.store'), $this->tenancyPayload($property, $incoming))
            ->assertRedirect(route('admin.tenancies.all'));

        $this->assertSame('Archived', Tenancy::query()->where('property_id', $property->id)->whereHas('tenantMembers', fn ($query) => $query->where('user_id', $tenant->id))->value('status'));
        $this->assertSame(0, (int) AccountUser::query()->where('user_id', $tenant->id)->value('can_login'));
        $this->assertSame('disabled', AccountUser::query()->where('user_id', $tenant->id)->value('status'));
    }

    public function test_deleting_the_only_tenancy_removes_login_and_a_second_home_keeps_it(): void
    {
        [$landlord, $accountId, $property, $tenant] = $this->household('Only Home');
        $only = $this->openLet($accountId, $property, $tenant);
        AccountUser::query()->where('user_id', $tenant->id)->update(['can_login' => 1, 'status' => 'active']);

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.tenancies.delete', $only->id))
            ->assertRedirect();

        $this->assertNull(Tenancy::query()->find($only->id));
        $membership = AccountUser::query()->where('user_id', $tenant->id)->first();
        $this->assertFalse($membership->status === 'active' && (bool) $membership->can_login);

        $kept = $this->membership($accountId, 'Two Homes');
        $homeA = $this->property($accountId, $landlord->id, '10 Kept Road', 'E4 1AA');
        $homeB = $this->property($accountId, $landlord->id, '11 Kept Road', 'E4 1AB');
        $drop = $this->openLet($accountId, $homeA, $kept);
        $this->openLet($accountId, $homeB, $kept);
        AccountUser::query()->where('user_id', $kept->id)->update(['can_login' => 1, 'status' => 'active']);

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.tenancies.delete', $drop->id));

        $keptMembership = AccountUser::query()->where('user_id', $kept->id)->first();
        $this->assertSame('active', $keptMembership->status);
        $this->assertSame(1, (int) $keptMembership->can_login);
    }

    public function test_resend_invalidates_the_previous_token_and_revoke_ends_the_session(): void
    {
        [$landlord, $accountId, $property, $tenant] = $this->household('Token');
        $this->openLet($accountId, $property, $tenant);
        AccountUser::query()->where('user_id', $tenant->id)->update(['can_login' => 1, 'status' => 'active']);
        TenantMember::query()->where('user_id', $tenant->id)->update(['can_login' => 1]);
        $original = $tenant->password;
        $plain = Password::broker()->createToken($tenant);

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.portal-access.resend', $tenant))
            ->assertRedirect(route('admin.portal-access.index'));

        $this->post(route('password.reset'), [
            'token' => $plain,
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertSessionHasErrors('token');
        $this->assertSame($original, $tenant->fresh()->password);

        $sessionId = 'journey-two-session-'.$tenant->id;
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $tenant->id,
            'payload' => base64_encode('journey-two'),
            'last_activity' => time(),
        ]);

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.portal-access.revoke', $tenant))
            ->assertRedirect(route('admin.portal-access.index'));

        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->get(route('tenant.rent'))
            ->assertRedirect(route('login'));
    }

    public function test_archived_tenancy_does_not_ask_for_confirmation_and_another_household_cannot_be_confirmed(): void
    {
        [$landlord, $accountId, $property, $tenant] = $this->household('Confirm');
        $archived = $this->openLet($accountId, $property, $tenant);
        $archived->update(['status' => 'Archived']);
        AccountUser::query()->where('user_id', $tenant->id)->update(['can_login' => 1, 'status' => 'active']);

        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->get(route('backend.home'))
            ->assertOk()
            ->assertDontSee('Check these tenancy details', false);

        $other = $this->membership($accountId, 'Other Household');
        $otherHome = $this->property($accountId, $landlord->id, '40 Other Lane', 'N4 4AA');
        $otherLet = $this->openLet($accountId, $otherHome, $other);

        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->post(route('tenant.tenancy.confirm'), ['tenancy_id' => $otherLet->id])
            ->assertSessionHasErrors('tenancy_id');

        $this->assertDatabaseMissing('tenant_members', [
            'tenancy_id' => $otherLet->id,
            'details_status' => 'confirmed',
        ]);
    }

    public function test_people_index_says_a_contact_does_not_grant_a_login(): void
    {
        [$landlord, $accountId] = $this->household('People');

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->get(route('admin.people.index'))
            ->assertOk()
            ->assertSee('Adding a contact does not grant a login.', false);
    }

    /**
     * @return array{0: User, 1: int, 2: Property, 3: User}
     */
    private function household(string $label): array
    {
        $landlord = $this->user($label.' Landlord', 'Landlord');
        $accountId = (int) DB::table('accounts')->insertGetId([
            'owner_user_id' => $landlord->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->attach($accountId, $landlord, 'owner', true);
        $tenant = $this->membership($accountId, $label.' Tenant');
        $property = $this->property($accountId, $landlord->id, $label.' House', 'SW1A 1AA');

        return [$landlord, $accountId, $property, $tenant];
    }

    private function membership(int $accountId, string $name): User
    {
        $user = $this->user($name, 'Tenant');
        $this->attach($accountId, $user, 'tenant', false);

        return $user;
    }

    private function user(string $name, string $roleName): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid().'@resisquare.test',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function attach(int $accountId, User $user, string $memberType, bool $canLogin): void
    {
        DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => $memberType,
            'access_level' => 'full',
            'can_login' => $canLogin ? 1 : 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function property(int $accountId, int $createdBy, string $line1, string $postcode): Property
    {
        return Property::create([
            'account_id' => $accountId,
            'created_by' => $createdBy,
            'line_1' => $line1.' '.uniqid(),
            'city' => 'London',
            'postcode' => $postcode,
        ]);
    }

    private function openLet(int $accountId, Property $property, User $tenant): Tenancy
    {
        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1000,
            'deposit' => 1000,
            'move_in' => now()->toDateString(),
        ]);
        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
        ]);

        return $tenancy;
    }

    /**
     * @return array<string, mixed>
     */
    private function tenancyPayload(Property $property, User $tenant, bool $invite = false): array
    {
        return [
            'property_id' => $property->id,
            'status' => 'Active',
            'move_in' => now()->toDateString(),
            'rent' => 1200,
            'deposit' => 1200,
            'user_id' => [$tenant->id],
            'is_main_person' => $tenant->id,
            'invite' => $invite ? 1 : 0,
        ];
    }
}
