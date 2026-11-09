<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetHttpTest extends TestCase
{
    public function test_reset_form_matches_sign_in_and_login_confirms_the_new_password(): void
    {
        $email = 'reset-confirm-'.uniqid().'@resisquare.test';
        $user = User::create([
            'name' => 'Reset Confirm',
            'email' => $email,
            'password' => Hash::make('old-password-1'),
            'status' => 1,
            'can_login' => 1,
        ]);

        $plainToken = 'reset-token-'.uniqid();
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($plainToken), 'created_at' => now()]
        );

        $this->get(route('password.reset.form', ['token' => $plainToken, 'email' => $email]))
            ->assertOk()
            ->assertSee('Reset password', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('aria-label="Show password"', false)
            ->assertSee('pw-wrap', false);

        $this->followingRedirects()
            ->post(route('password.reset'), [
                'token' => $plainToken,
                'password' => 'new-password-1',
                'password_confirmation' => 'new-password-1',
            ])
            ->assertOk()
            ->assertSee('Your password was updated. Sign in with the new one.', false)
            ->assertSee('Sign in', false);

        $this->assertTrue(Hash::check('new-password-1', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $email]);

        $this->post('/login', [
            'email' => $email,
            'password' => 'new-password-1',
        ])->assertRedirect();

        $user->delete();
    }

    public function test_mismatched_confirmation_stays_on_the_reset_form(): void
    {
        $email = 'reset-mismatch-'.uniqid().'@resisquare.test';
        $user = User::create([
            'name' => 'Reset Mismatch',
            'email' => $email,
            'password' => Hash::make('old-password-1'),
            'status' => 1,
            'can_login' => 1,
        ]);
        $original = $user->password;

        $plainToken = 'reset-mismatch-'.uniqid();
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($plainToken), 'created_at' => now()]
        );

        $this->from(route('password.reset.form', ['token' => $plainToken, 'email' => $email]))
            ->post(route('password.reset'), [
                'token' => $plainToken,
                'password' => 'new-password-1',
                'password_confirmation' => 'different-password',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('password');

        $this->assertSame($original, $user->fresh()->password);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $email]);

        $user->delete();
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }

    public function test_expired_reset_link_is_rejected_and_does_not_change_the_password(): void
    {
        $email = 'reset-expired-'.uniqid().'@resisquare.test';
        $user = User::create([
            'name' => 'Reset Expired',
            'email' => $email,
            'password' => Hash::make('old-password-1'),
            'status' => 1,
            'can_login' => 1,
        ]);
        $original = $user->password;

        $plainToken = 'reset-expired-'.uniqid();
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($plainToken), 'created_at' => now()->subMinutes(((int) config('auth.passwords.users.expire')) + 1)]
        );

        $this->get(route('password.reset.form', ['token' => $plainToken, 'email' => $email]))
            ->assertForbidden()
            ->assertSee('Invalid or expired reset link.', false);

        $this->assertSame($original, $user->fresh()->password);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $email]);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($plainToken), 'created_at' => now()->subMinutes(((int) config('auth.passwords.users.expire')) + 1)]
        );

        $this->from(route('password.reset.form', ['token' => $plainToken, 'email' => $email]))
            ->post(route('password.reset'), [
                'token' => $plainToken,
                'password' => 'new-password-1',
                'password_confirmation' => 'new-password-1',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('token');

        $this->assertSame($original, $user->fresh()->password);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $email]);

        $user->delete();
    }
}
