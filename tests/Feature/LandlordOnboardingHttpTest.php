<?php

namespace Tests\Feature;

use App\Mail\MailManager;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LandlordOnboardingHttpTest extends TestCase
{
    public function test_confirmed_landlord_sees_onboarding_overlay_on_dashboard(): void
    {
        [$user, $accountId] = $this->createConfirmedLandlord();

        $response = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.dashboard'));

        $response->assertOk();
        $response->assertSee('lob-root', false);
        $response->assertSee('Add your first property', false);
        $response->assertSee('Find the property', false);
        $response->assertSee('Test mode: try', false);
    }

    public function test_onboarding_search_returns_chimnie_test_results(): void
    {
        config(['chimnie.test_mode' => true]);
        Http::fake([
            'api.postcodes.io/*' => Http::response([
                'result' => [
                    'postcode' => 'SW1A 1AA',
                    'region' => 'London',
                    'post_town' => 'LONDON',
                    'admin_district' => 'Westminster',
                ],
            ], 200),
        ]);

        [$user, $accountId] = $this->createConfirmedLandlord();

        $response = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->getJson(route('admin.onboarding.landlord.search', ['postcode' => 'SW1A 1AA']));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $this->assertNotEmpty($response->json('data'));
        $this->assertSame('Buckingham Palace', $response->json('data.0.line_1'));
    }

    public function test_landlord_with_no_properties_sees_onboarding_on_properties_index(): void
    {
        [$user, $accountId] = $this->createConfirmedLandlord();

        $response = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('admin.properties.index'));

        $response->assertOk();
        $response->assertSee('lob-root', false);
        $response->assertSee('Add your first property', false);
    }

    public function test_completed_landlord_with_no_properties_still_sees_onboarding(): void
    {
        [$user, $accountId] = $this->createConfirmedLandlord();
        \DB::table('accounts')->where('id', $accountId)->update([
            'onboarding_completed_at' => now(),
        ]);

        $dashboard = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('backend.dashboard'));

        $dashboard->assertOk();
        $dashboard->assertSee('lob-root', false);

        $properties = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->get(route('admin.properties.index'));

        $properties->assertOk();
        $properties->assertSee('lob-root', false);
    }

    public function test_invite_creates_tenancy_and_sends_mail(): void
    {
        Mail::fake();
        [$user, $accountId] = $this->createConfirmedLandlord();
        $this->seedOnboardingProperty($user, $accountId);
        $email = 'invite-tenant-'.uniqid().'@resisquare.test';

        $response = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->postJson(route('admin.onboarding.landlord.tenancy'), [
                'name' => 'Alex Tenant',
                'email' => $email,
                'invite' => true,
            ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('data.invited', true);
        $response->assertJsonPath('data.sent', true);
        $response->assertJsonPath('data.email', $email);

        $this->assertTrue(
            Tenancy::query()->where('account_id', $accountId)->where('status', 'Active')->exists()
        );

        Mail::assertSent(MailManager::class, function (MailManager $mail) use ($email) {
            $content = (string) ($mail->array['content'] ?? '');

            return $mail->hasTo($email)
                && str_contains($content, 'email='.urlencode($email));
        });
    }

    public function test_existing_user_invite_does_not_change_password(): void
    {
        Mail::fake();
        [$user, $accountId] = $this->createConfirmedLandlord();
        $this->seedOnboardingProperty($user, $accountId);

        $existing = User::create([
            'name' => 'Existing Tenant',
            'email' => 'existing-tenant-'.uniqid().'@resisquare.test',
            'password' => bcrypt('KeepThisPassword1!'),
        ]);
        $passwordHash = $existing->password;

        $response = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->postJson(route('admin.onboarding.landlord.tenancy'), [
                'name' => 'Existing Tenant',
                'email' => $existing->email,
                'invite' => true,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.invited', true);
        $response->assertJsonPath('data.sent', true);

        $existing->refresh();
        $this->assertSame($passwordHash, $existing->password);

        Mail::assertSent(MailManager::class, function (MailManager $mail) use ($existing) {
            return $mail->hasTo($existing->email);
        });
    }

    public function test_skip_with_empty_fields_creates_no_tenancy_and_sends_no_mail(): void
    {
        Mail::fake();
        Storage::fake('public');
        [$user, $accountId] = $this->createConfirmedLandlord();
        $this->seedOnboardingProperty($user, $accountId);
        $this->seedOnboardingDocuments($user, $accountId);

        $response = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->postJson(route('admin.onboarding.landlord.complete'), []);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('data.tenancy', null);
        $response->assertJsonPath('data.invite.invited', false);
        $response->assertJsonPath('data.invite.sent', false);

        $this->assertFalse(
            Tenancy::query()->where('account_id', $accountId)->exists()
        );
        Mail::assertNothingSent();
    }

    public function test_skip_with_filled_fields_creates_tenancy_without_mail(): void
    {
        Mail::fake();
        [$user, $accountId] = $this->createConfirmedLandlord();
        $this->seedOnboardingProperty($user, $accountId);
        $email = 'skip-tenant-'.uniqid().'@resisquare.test';

        $response = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->postJson(route('admin.onboarding.landlord.tenancy'), [
                'name' => 'Skipped Tenant',
                'email' => $email,
                'invite' => false,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.invited', false);
        $response->assertJsonPath('data.sent', false);
        $response->assertJsonPath('data.email', $email);

        $this->assertTrue(
            Tenancy::query()->where('account_id', $accountId)->where('status', 'Active')->exists()
        );
        Mail::assertNothingSent();
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createConfirmedLandlord(): array
    {
        Permission::findOrCreate('create properties', 'web');
        Permission::findOrCreate('edit properties', 'web');
        $role = Role::findOrCreate('Landlord', 'web');
        $role->givePermissionTo(['create properties', 'edit properties']);

        $user = User::create([
            'name' => 'Onboarding Landlord',
            'email' => 'onboarding-test-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole($role);

        $accountId = \DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'onboarding_step' => 1,
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

        $planId = \DB::table('plans')
            ->where('target_account_type', 'landlord')
            ->where('allow_contact_login', 1)
            ->where('property_limit', '>', 0)
            ->value('id');

        if (! $planId) {
            $planId = \DB::table('plans')->insertGetId([
                'code' => 'onboarding-test-'.uniqid(),
                'name' => 'Onboarding Test',
                'target_account_type' => 'landlord',
                'monthly_price_minor' => 0,
                'annual_price_minor' => 0,
                'currency' => 'GBP',
                'trial_days' => 7,
                'property_limit' => 10,
                'branch_limit' => 0,
                'staff_limit' => 0,
                'property_manager_limit' => 0,
                'allow_contact_login' => 1,
                'is_active' => 1,
                'sort_order' => 99,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        \DB::table('account_subscriptions')->insert([
            'account_id' => $accountId,
            'plan_id' => $planId,
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $accountId];
    }

    private function seedOnboardingProperty(User $user, int $accountId): void
    {
        config(['chimnie.test_mode' => true]);

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->postJson(route('admin.onboarding.landlord.property'), [
                'chimnie_id' => 'chimnie-test:SW1A1AA:mall',
                'postcode' => 'SW1A 1AA',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    private function seedOnboardingDocuments(User $user, int $accountId): void
    {
        $session = ['current_account_id' => $accountId];

        $this->actingAs($user)
            ->withSession($session)
            ->post(route('admin.onboarding.landlord.documents'), [
                'kind' => 'photo_id',
                'file' => UploadedFile::fake()->image('photo-id.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->actingAs($user)
            ->withSession($session)
            ->post(route('admin.onboarding.landlord.documents'), [
                'kind' => 'proof_of_address',
                'file' => UploadedFile::fake()->image('proof.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertOk();
    }
}
