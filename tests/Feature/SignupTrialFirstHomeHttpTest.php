<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Registration;
use App\Models\User;
use App\Services\Onboarding\LandlordOnboardingService;
use App\Services\Saas\StripeCheckoutService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Launch Step 17: Signup → OTP → trial → first-home overlay (no stuck / no test-mode leak).
 */
class SignupTrialFirstHomeHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_otp_verify_without_stripe_lands_in_trial_with_first_home_overlay(): void
    {
        Mail::fake();
        Permission::findOrCreate('create properties', 'web');
        $role = Role::findOrCreate('Landlord', 'web');
        $role->givePermissionTo('create properties');

        $planId = $this->landlordPlanId();
        $email = 'signup.trial.'.uniqid().'@gmail.com';

        $this->postJson(route('register.post'), [
            'first_name' => 'Cold',
            'last_name' => 'Landlord',
            'email' => $email,
            'phone' => '',
            'type' => 'landlord',
            'verify_via' => 'email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'plan_id' => $planId,
            'billing_cycle' => 'monthly',
            'account_type' => 'landlord',
        ])->assertOk()->assertJson(['success' => true]);

        $registration = Registration::query()->where('email', $email)->latest('id')->firstOrFail();
        $this->assertNotEmpty($registration->otp_code);

        $this->mock(StripeCheckoutService::class, function ($mock) {
            $mock->shouldReceive('createPlanCheckoutSession')
                ->once()
                ->andThrow(new RuntimeException('Stripe unavailable in test'));
        });

        $response = $this->withSession(['reg_id' => $registration->id])
            ->post(route('register.verify.otp.post'), [
                'otp' => $registration->otp_code,
            ]);

        $response->assertRedirect(route('backend.dashboard'));

        $user = User::query()->where('email', $email)->firstOrFail();
        $account = Account::query()->where('owner_user_id', $user->id)->latest('id')->firstOrFail();

        $this->assertSame('trialing', $account->status);
        $this->assertTrue(
            app(LandlordOnboardingService::class)->shouldShow($user, $account),
            'First-home overlay should show for an empty trial landlord'
        );

        $dashboard = $this->actingAs($user)
            ->withSession(['current_account_id' => $account->id])
            ->get(route('backend.dashboard'));

        $dashboard->assertOk();
        $dashboard->assertSee('lob-root', false);
        $dashboard->assertSee('Add your first property', false);
        $dashboard->assertDontSee('Test mode:', false);
    }

    public function test_otp_verify_with_stripe_checkout_stays_on_trial_and_opens_checkout(): void
    {
        Mail::fake();
        Role::findOrCreate('Landlord', 'web');

        $planId = $this->landlordPlanId();
        $email = 'signup.checkout.'.uniqid().'@gmail.com';

        $this->postJson(route('register.post'), [
            'first_name' => 'Card',
            'last_name' => 'Required',
            'email' => $email,
            'phone' => '',
            'type' => 'landlord',
            'verify_via' => 'email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'plan_id' => $planId,
            'billing_cycle' => 'monthly',
            'account_type' => 'landlord',
        ])->assertOk();

        $registration = Registration::query()->where('email', $email)->latest('id')->firstOrFail();

        $this->mock(StripeCheckoutService::class, function ($mock) {
            $mock->shouldReceive('createPlanCheckoutSession')
                ->once()
                ->andReturn('https://checkout.stripe.test/session/test_123');
        });

        $response = $this->withSession(['reg_id' => $registration->id])
            ->post(route('register.verify.otp.post'), [
                'otp' => $registration->otp_code,
            ]);

        $response->assertRedirect('https://checkout.stripe.test/session/test_123');

        $user = User::query()->where('email', $email)->firstOrFail();
        $account = Account::query()->where('owner_user_id', $user->id)->latest('id')->firstOrFail();
        $this->assertSame('trialing', $account->status);

        $this->actingAs($user)
            ->withSession(['current_account_id' => $account->id])
            ->get(route('backend.dashboard'))
            ->assertOk();
    }

    public function test_wrong_otp_stays_on_the_code_step_and_shows_the_error(): void
    {
        Mail::fake();
        Role::findOrCreate('Landlord', 'web');

        $planId = $this->landlordPlanId();
        $email = 'signup.badotp.'.uniqid().'@gmail.com';

        $this->postJson(route('register.post'), $this->landlordRegistrationPayload($email, $planId))
            ->assertOk()
            ->assertJson(['success' => true]);

        $registration = Registration::query()->where('email', $email)->latest('id')->firstOrFail();

        $this->from(route('register', [
            'plan_id' => $planId,
            'billing_cycle' => 'monthly',
            'account_type' => 'landlord',
        ]))
            ->withSession(['reg_id' => $registration->id])
            ->followingRedirects()
            ->post(route('register.verify.otp.post'), [
                'otp' => '000000',
            ])
            ->assertOk()
            ->assertSee('Invalid OTP. Please try again.', false)
            ->assertSee('data-otp-restored="1"', false)
            ->assertSee('id="step-1" style="display:none;"', false)
            ->assertSee('Enter OTP', false);

        $this->assertNull(User::query()->where('email', $email)->first());
        $registration->refresh();
        $this->assertSame('pending', $registration->status);
        $this->assertNotEmpty($registration->otp_code);
    }

    public function test_expired_otp_stays_on_the_code_step_and_asks_for_a_new_code(): void
    {
        Mail::fake();
        Role::findOrCreate('Landlord', 'web');

        $planId = $this->landlordPlanId();
        $email = 'signup.expiredotp.'.uniqid().'@gmail.com';

        $this->postJson(route('register.post'), $this->landlordRegistrationPayload($email, $planId))
            ->assertOk()
            ->assertJson(['success' => true]);

        $registration = Registration::query()->where('email', $email)->latest('id')->firstOrFail();
        $registration->update(['otp_expires_at' => now()->subMinute()]);

        $this->from(route('register', [
            'plan_id' => $planId,
            'billing_cycle' => 'monthly',
            'account_type' => 'landlord',
        ]))
            ->withSession(['reg_id' => $registration->id])
            ->followingRedirects()
            ->post(route('register.verify.otp.post'), [
                'otp' => $registration->otp_code,
            ])
            ->assertOk()
            ->assertSee('OTP has expired. Please request a new one.', false)
            ->assertSee('data-otp-restored="1"', false)
            ->assertSee('id="step-1" style="display:none;"', false);

        $this->assertNull(User::query()->where('email', $email)->first());
    }

    private function landlordRegistrationPayload(string $email, int $planId): array
    {
        return [
            'first_name' => 'QA',
            'last_name' => 'Inventory',
            'email' => $email,
            'phone' => '',
            'type' => 'landlord',
            'verify_via' => 'email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'plan_id' => $planId,
            'billing_cycle' => 'monthly',
            'account_type' => 'landlord',
        ];
    }

    private function landlordPlanId(): int
    {
        $planId = \DB::table('plans')
            ->where('is_active', 1)
            ->where('target_account_type', 'landlord')
            ->value('id');

        if ($planId) {
            return (int) $planId;
        }

        return (int) \DB::table('plans')->insertGetId([
            'code' => 'step17-'.uniqid(),
            'name' => 'Step 17 Landlord',
            'target_account_type' => 'landlord',
            'monthly_price_minor' => 1900,
            'annual_price_minor' => 19000,
            'currency' => 'GBP',
            'trial_days' => 14,
            'property_limit' => 5,
            'branch_limit' => 0,
            'staff_limit' => 0,
            'property_manager_limit' => 0,
            'allow_contact_login' => 1,
            'is_active' => 1,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
