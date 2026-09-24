<?php

namespace Tests\Feature;

use App\Mail\MailManager;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PeopleInviteHttpTest extends TestCase
{
    public function test_people_hub_invite_resend_accept_and_tenant_home(): void
    {
        Mail::fake();

        [$landlord, $accountId] = $this->createLandlord();
        $session = ['current_account_id' => $accountId];

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '7 People Place',
            'city' => 'London',
            'postcode' => 'E3 3AA',
        ]);

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1000,
            'deposit' => 1000,
        ]);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.people.index'))
            ->assertOk()
            ->assertSee('data-people-hub="1"', false)
            ->assertSee('Invite a tenant', false);

        $email = 'people-invite-'.uniqid().'@resisquare.test';

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.portal-access.invite'), [
                'name' => 'People Tenant',
                'email' => $email,
                'tenancy_id' => $tenancy->id,
            ])
            ->assertRedirect(route('admin.portal-access.index'));

        $tenant = User::query()->where('email', $email)->first();
        $this->assertNotNull($tenant);

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $email]);
        $firstTokenHash = DB::table('password_reset_tokens')->where('email', $email)->value('token');

        Mail::assertSent(MailManager::class, function (MailManager $mail) use ($email) {
            return $mail->hasTo($email);
        });

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.portal-access.resend', $tenant))
            ->assertRedirect(route('admin.portal-access.index'));

        $secondTokenHash = DB::table('password_reset_tokens')->where('email', $email)->value('token');
        $this->assertNotNull($secondTokenHash);
        $this->assertNotSame($firstTokenHash, $secondTokenHash, 'Resend should issue a fresh reset token');

        Mail::assertSent(MailManager::class, 2);

        // Accept invite: set password via reset form using a freshly created token.
        $plainToken = 'test-reset-token-'.uniqid();
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($plainToken), 'created_at' => now()]
        );

        $this->post(route('password.reset'), [
            'token' => $plainToken,
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Password123!', $tenant->fresh()->password));

        // Login lands on tenant home. The staff dashboard never renders.
        foreach ([1, 2] as $attempt) {
            $this->post('/login', [
                'email' => $email,
                'password' => 'Password123!',
            ])->assertRedirect(route('backend.home'));

            $this->actingAs($tenant->fresh())->withSession(['current_account_id' => $accountId])
                ->get(route('backend.home'))
                ->assertOk()
                ->assertSee('Tenant portal', false)
                ->assertDontSee('Portfolio properties', false);

            $this->actingAs($tenant->fresh())->withSession(['current_account_id' => $accountId])
                ->get(route('backend.dashboard'))
                ->assertRedirect(route('backend.home'));

            $this->post('/logout');
        }
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');
        $user = User::create([
            'name' => 'People Landlord',
            'email' => 'people-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        $accountId = DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('account_users')->insert([
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
}
