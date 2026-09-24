<?php

namespace Tests\Feature;

use App\Enums\CrmNotificationEvent;
use App\Models\ComplianceRecord;
use App\Models\ComplianceType;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComplianceHttpTest extends TestCase
{
    public function test_landlord_can_store_cert_with_served_evidence_and_sees_needs_you_plus_reminder(): void
    {
        config(['crm_notifications.enabled' => true]);

        [$landlord, $accountId] = $this->createLandlord();
        $session = ['current_account_id' => $accountId];

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '9 Gas Close',
            'city' => 'London',
            'postcode' => 'E2 2AA',
        ]);

        $gasType = ComplianceType::query()->where('alias', 'gas')->first()
            ?: ComplianceType::create([
                'name' => 'Gas Safety Certificate',
                'description' => 'Annual gas safety',
                'alias' => 'gas',
            ]);

        $this->actingAs($landlord)->withSession($session)
            ->postJson(route('admin.compliance.store'), [
                'compliance_type_id' => $gasType->id,
                'property_id' => $property->id,
                'issued_date' => now()->subYear()->toDateString(),
                'expiry_date' => now()->addDays(7)->toDateString(),
                'served_to_tenant_at' => now()->subMonths(11)->toDateString(),
                'served_notes' => 'Emailed PDF on move-in',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $record = ComplianceRecord::query()->where('property_id', $property->id)->first();
        $this->assertNotNull($record);
        $this->assertNull($record->completed_at);
        $this->assertSame('Emailed PDF on move-in', $record->served_notes);
        $this->assertTrue($record->needsAttention());

        $this->actingAs($landlord)->withSession($session)
            ->get(route('backend.dashboard'))
            ->assertOk()
            ->assertSee('need you', false);

        Artisan::call('crm-notifications:send-due', [
            '--account' => $accountId,
            '--at' => now()->toIso8601String(),
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'account_id' => $accountId,
            'identifier' => CrmNotificationEvent::ComplianceExpiring->value,
        ]);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');
        $user = User::create([
            'name' => 'Compliance Landlord',
            'email' => 'compliance-landlord-'.uniqid().'@resisquare.test',
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
}
