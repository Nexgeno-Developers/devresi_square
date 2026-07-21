<?php

namespace App\Services\Saas;

use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\AccountUser;
use App\Models\Company;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Carbon;
use RuntimeException;

class AccountProvisioningService
{
    private const BILLING_CYCLES = ['monthly', 'annual'];

    public function provisionFromRegistration(Registration $registration, User $approvedUser, User $approvedBy): Account
    {
        $registration->loadMissing(['plan', 'account']);

        if ($registration->account_id || $registration->account) {
            throw new RuntimeException('This registration has already been provisioned.');
        }

        if ($approvedUser->hasRole('Super Admin')) {
            throw new RuntimeException('Super Admin users must not be provisioned into SaaS customer accounts.');
        }

        $plan = $registration->plan;
        if (! $plan) {
            throw new RuntimeException('Selected plan is not available.');
        }

        if (! $plan->is_active) {
            throw new RuntimeException('Selected plan is not active.');
        }

        if (! in_array($registration->billing_cycle, self::BILLING_CYCLES, true)) {
            throw new RuntimeException('Selected billing cycle is invalid.');
        }

        if ($registration->account_type !== $plan->target_account_type) {
            throw new RuntimeException('Selected account type does not match the selected plan.');
        }

        $now = now();
        $trialDays = (int) ($plan->trial_days ?? 7);
        $trialStartedAt = $trialDays > 0 ? $now->copy() : null;
        $trialEndsAt = $trialDays > 0 ? $now->copy()->addDays($trialDays) : null;
        $status = $trialDays > 0 ? 'trialing' : 'active';

        $account = Account::create([
            'owner_user_id' => $approvedUser->id,
            'account_type' => $registration->account_type,
            'account_name' => $this->accountName($registration),
            'billing_email' => $registration->email,
            'billing_phone' => $registration->phone,
            'currency' => $plan->currency ?: 'GBP',
            'status' => $status,
            'trial_started_at' => $trialStartedAt,
            'trial_ends_at' => $trialEndsAt,
        ]);

        AccountUser::create([
            'account_id' => $account->id,
            'user_id' => $approvedUser->id,
            'member_type' => 'owner',
            'access_level' => 'full',
            'can_login' => true,
            'status' => 'active',
            'created_by' => $approvedBy->id,
        ]);

        $this->createTrialSubscription($account, $registration, $now);
        $this->createOrUpdateCompany($account, $registration, $approvedUser, $approvedBy);

        $registration->update([
            'account_id' => $account->id,
        ]);

        return $account->load(['owner', 'accountUsers', 'currentSubscription.plan', 'company']);
    }

    private function createTrialSubscription(Account $account, Registration $registration, Carbon $now): AccountSubscription
    {
        $plan = $registration->plan;
        $trialDays = (int) ($plan->trial_days ?? 7);
        $status = $trialDays > 0 ? 'trialing' : 'active';
        $currentPeriodStart = $now->copy();
        $currentPeriodEnd = $registration->billing_cycle === 'annual'
            ? $now->copy()->addYear()
            : $now->copy()->addMonth();

        return AccountSubscription::create([
            'account_id' => $account->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $registration->billing_cycle,
            'status' => $status,
            'trial_started_at' => $trialDays > 0 ? $now->copy() : null,
            'trial_ends_at' => $trialDays > 0 ? $now->copy()->addDays($trialDays) : null,
            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
            'stripe_subscription_id' => null,
            'stripe_price_id' => $registration->billing_cycle === 'annual'
                ? $plan->stripe_annual_price_id
                : $plan->stripe_monthly_price_id,
            'price_at_signup_minor' => $registration->billing_cycle === 'annual'
                ? $plan->annual_price_minor
                : $plan->monthly_price_minor,
            'currency_at_signup' => $plan->currency ?: 'GBP',
            'plan_name_at_signup' => $plan->name,
            'cancel_at_period_end' => false,
        ]);
    }

    private function createOrUpdateCompany(Account $account, Registration $registration, User $owner, User $approvedBy): ?Company
    {
        $plan = $registration->plan;

        if (! $plan->allow_company_profile || $registration->account_type === 'landlord') {
            return null;
        }

        $companyType = match ($registration->account_type) {
            'estate_agent_company' => 'agency_company',
            'estate_agent_freelance' => 'freelance_profile',
            default => null,
        };

        if (! $companyType) {
            return null;
        }

        $company = Company::firstOrNew(['owner_user_id' => $owner->id]);
        $company->fill([
            'account_id' => $account->id,
            'owner_user_id' => $owner->id,
            'name' => $company->name ?: $this->companyName($registration),
            'company_type' => $companyType,
            'emails' => $registration->email ? [$registration->email] : null,
            'phones' => $registration->phone ? [$registration->phone] : null,
            'status' => 'active',
            'created_by' => $company->exists ? $company->created_by : $approvedBy->id,
            'updated_by' => $approvedBy->id,
        ]);
        $company->save();

        return $company;
    }

    private function accountName(Registration $registration): string
    {
        $name = $registration->full_name;

        return match ($registration->account_type) {
            'landlord' => trim($name . ' Landlord Account'),
            'estate_agent_freelance' => trim($name . ' Estate Agent Account'),
            'estate_agent_company' => trim($this->companyName($registration) . ' Account'),
            default => trim($name . ' Account'),
        };
    }

    private function companyName(Registration $registration): string
    {
        $businessName = $registration->business_name
            ?? $registration->company_name
            ?? $registration->agency_name
            ?? null;

        if ($businessName) {
            return trim((string) $businessName);
        }

        return $registration->account_type === 'estate_agent_company'
            ? trim($registration->full_name . ' Estate Agency Account')
            : trim($registration->full_name . ' Estate Agent Account');
    }
}
