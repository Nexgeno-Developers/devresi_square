<?php

namespace App\Console\Commands;

use App\Models\ContractorDocument;
use App\Models\ContractorProfile;
use App\Models\ContractorTrade;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class FridayDemoAccountsCommand extends Command
{
    protected $signature = 'friday:demo-accounts';

    protected $description = 'Create the Friday landlord, tenant, and contractor logins. Refuses production.';

    public const PASSWORD = 'Friday-demo-2026';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Refusing to create demo logins in production.');

            return self::FAILURE;
        }

        DB::transaction(function (): void {
            $landlord = $this->user('friday.landlord@resisquare.test', 'Friday Landlord', 'Friday', 'Landlord');
            $landlord->syncRoles([Role::findOrCreate('Landlord', 'web')]);

            $accountId = DB::table('accounts')->where('owner_user_id', $landlord->id)->value('id');
            if (! $accountId) {
                $accountId = DB::table('accounts')->insertGetId([
                    'owner_user_id' => $landlord->id,
                    'account_name' => 'Friday Portfolio',
                    'account_type' => 'landlord',
                    'status' => 'trialing',
                    'trial_ends_at' => now()->addDays(7),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('account_users')->updateOrInsert(
                ['account_id' => $accountId, 'user_id' => $landlord->id],
                [
                    'member_type' => 'owner',
                    'access_level' => 'full',
                    'can_login' => 1,
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $property = Property::query()->where('account_id', $accountId)->where('line_1', '14 Friday Close')->first();
            if (! $property) {
                $property = Property::create([
                    'account_id' => $accountId,
                    'created_by' => $landlord->id,
                    'line_1' => '14 Friday Close',
                    'city' => 'Manchester',
                    'postcode' => 'M1 2AB',
                ]);
            }

            $tenancy = Tenancy::query()->where('property_id', $property->id)->where('status', 'Active')->first();
            if (! $tenancy) {
                $tenancy = Tenancy::create([
                    'account_id' => $accountId,
                    'property_id' => $property->id,
                    'status' => 'Active',
                    'rent' => 1200,
                    'frequency' => 'Monthly',
                    'move_in' => now()->subMonth()->toDateString(),
                ]);
            }

            $tenant = $this->user('friday.tenant@resisquare.test', 'Friday Tenant', 'Friday', 'Tenant');
            $tenant->syncRoles([Role::findOrCreate('Tenant', 'web')]);
            DB::table('account_users')->updateOrInsert(
                ['account_id' => $accountId, 'user_id' => $tenant->id],
                [
                    'member_type' => 'tenant',
                    'access_level' => 'full',
                    'can_login' => 1,
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            TenantMember::query()->updateOrCreate(
                ['tenancy_id' => $tenancy->id, 'user_id' => $tenant->id],
                [
                    'account_id' => $accountId,
                    'is_main_person' => true,
                    'can_login' => true,
                ]
            );

            $contractor = $this->user('friday.contractor@resisquare.test', 'Friday Contractor', 'Friday', 'Contractor');
            $contractor->syncRoles([Role::findOrCreate('Contractor', 'web')]);
            $profile = ContractorProfile::query()->firstOrCreate(
                ['user_id' => $contractor->id],
                [
                    'trading_type' => 'sole_trader',
                    'legal_name' => 'Friday Contractor',
                    'vat_registered' => false,
                    'cis_status' => 'unverified',
                    'employs_staff' => false,
                    'postcode' => 'M1 1AA',
                    'latitude' => 53.4808,
                    'longitude' => -2.2426,
                    'coverage_radius_miles' => 15,
                    'phone' => '07123450000',
                    'trades' => ['plumbing', 'general'],
                    'approval_status' => ContractorProfile::APPROVED,
                    'terms_accepted_at' => now(),
                ]
            );
            foreach (['plumbing', 'general'] as $trade) {
                ContractorTrade::query()->firstOrCreate(
                    ['contractor_profile_id' => $profile->id, 'trade' => $trade],
                    ['status' => ContractorTrade::APPROVED]
                );
            }
            foreach (['photo_id', 'public_liability'] as $type) {
                ContractorDocument::query()->firstOrCreate(
                    ['contractor_profile_id' => $profile->id, 'type' => $type],
                    [
                        'status' => ContractorDocument::APPROVED,
                        'disk' => 'local',
                        'path' => 'contractor-documents/friday/'.$type.'.bin',
                        'original_name' => $type.'.pdf',
                        'cover_amount' => $type === 'public_liability' ? 2000000 : null,
                        'expires_at' => $type === 'public_liability' ? now()->addYear() : null,
                        'submitted_at' => now(),
                    ]
                );
            }
        });

        $this->info('Friday logins (password '.self::PASSWORD.'):');
        $this->line('Landlord    friday.landlord@resisquare.test');
        $this->line('Tenant      friday.tenant@resisquare.test');
        $this->line('Contractor  friday.contractor@resisquare.test');

        return self::SUCCESS;
    }

    private function user(string $email, string $name, string $first, string $last): User
    {
        $user = User::query()->where('email', $email)->first();
        if ($user) {
            $user->forceFill([
                'name' => $name,
                'first_name' => $first,
                'last_name' => $last,
                'password' => bcrypt(self::PASSWORD),
                'status' => 1,
                'can_login' => 1,
            ])->save();

            return $user;
        }

        return User::create([
            'name' => $name,
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'password' => bcrypt(self::PASSWORD),
            'status' => 1,
            'can_login' => 1,
            'phone' => '07123450000',
        ]);
    }
}
