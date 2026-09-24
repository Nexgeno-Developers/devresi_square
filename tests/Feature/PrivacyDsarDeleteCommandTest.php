<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Launch Step 15: DSAR deletion command can be dry-run and live-anonymised once.
 */
class PrivacyDsarDeleteCommandTest extends TestCase
{
    public function test_dry_run_does_not_mutate_user(): void
    {
        $user = User::create([
            'name' => 'DSAR Subject',
            'email' => 'dsar-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        $this->artisan('privacy:dsar-delete', [
            'userId' => $user->id,
            '--dry-run' => true,
        ])->assertSuccessful();

        $user->refresh();
        $this->assertSame('DSAR Subject', $user->name);
        $this->assertStringContainsString('@resisquare.test', $user->email);
    }

    public function test_force_anonymises_user_and_revokes_account_login(): void
    {
        $user = User::create([
            'name' => 'DSAR Live',
            'email' => 'dsar-live-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

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

        $this->artisan('privacy:dsar-delete', [
            'userId' => $user->id,
            '--account' => $accountId,
            '--force' => true,
        ])->assertSuccessful();

        $user->refresh();
        $this->assertStringStartsWith('Deleted User', $user->name);
        $this->assertStringContainsString('@invalid.resisquare.local', $user->email);
        $this->assertSame(0, (int) $user->status);

        $membership = DB::table('account_users')
            ->where('account_id', $accountId)
            ->where('user_id', $user->id)
            ->first();
        $this->assertNotNull($membership);
        $this->assertSame(0, (int) $membership->can_login);
    }
}
