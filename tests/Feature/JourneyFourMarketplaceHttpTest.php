<?php

namespace Tests\Feature;

use App\Mail\MailManager;
use App\Models\AccountContractor;
use App\Models\ContractorDocument;
use App\Models\ContractorProfile;
use App\Models\ContractorTrade;
use App\Models\PostcodeLocation;
use App\Models\Property;
use App\Models\RepairCategory;
use App\Models\RepairIssue;
use App\Models\RepairJobOffer;
use App\Models\User;
use App\Support\PostcodeGeo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JourneyFourMarketplaceHttpTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['contractor_marketplace.geocode.enabled' => false]);
        Mail::fake();
        $this->pin('M1 1AA', 53.4808, -2.2426);
        $this->pin('M1 2AB', 53.4795, -2.2440);
    }

    public function test_distance_and_a_favourite_outside_the_radius_are_skipped(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId);
        $near = $this->contractor('Near');
        $far = $this->contractor('Far', ['plumbing'], 51.5014, -0.1419, 5);
        AccountContractor::create([
            'account_id' => $accountId,
            'contractor_user_id' => $far->id,
            'favourite' => true,
            'email' => $far->email,
        ]);

        $find = $this->landlord($landlord, $accountId)->get(route('admin.property_repairs.marketplace.find', $repair));
        $find->assertOk()
            ->assertSee('Near Contractor', false)
            ->assertSee('Far Contractor', false)
            ->assertSee('Outside their area', false)
            ->assertDontSee('value="'.$far->id.'"', false);

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => [$far->id],
            'urgency' => 'routine',
        ])->assertSessionHas('error');
        $this->assertSame(0, RepairJobOffer::query()->where('repair_issue_id', $repair->id)->count());
        $this->assertStringContainsString('Outside their coverage', (string) session('error'));

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => [$near->id],
            'urgency' => 'routine',
        ])->assertSessionHas('success');
        $offer = RepairJobOffer::query()->where('repair_issue_id', $repair->id)->where('contractor_user_id', $near->id)->firstOrFail();
        $page = $this->actingAs($near)->get(route('contractor.jobs.show', $offer));
        $page->assertOk()
            ->assertSee('Manchester', false)
            ->assertSee('M1', false)
            ->assertDontSee('12 Secret Road', false)
            ->assertDontSee('Key in the lockbox', false)
            ->assertDontSee('07000000000', false)
            ->assertDontSee($repair->tenant->email, false);
        Mail::assertSent(MailManager::class, function (MailManager $mail) use ($near) {
            return $mail->hasTo($near->email)
                && str_contains((string) ($mail->array['content'] ?? ''), 'Manchester')
                && ! str_contains((string) ($mail->array['content'] ?? ''), '12 Secret Road');
        });
    }

    public function test_an_electrical_repair_skips_a_contractor_without_electrical_registration(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId, 'Electrical fault '.uniqid(), 'Socket is dead');
        $plumber = $this->contractor('Sparks');

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => [$plumber->id],
            'urgency' => 'routine',
        ])->assertSessionHas('error');
        $this->assertSame(0, RepairJobOffer::query()->where('repair_issue_id', $repair->id)->count());
        $this->assertStringContainsString('electrical', strtolower((string) session('error')));
    }

    public function test_one_inspection_accept_closes_the_other_and_cancel_needs_a_new_send(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId);
        $first = $this->contractor('First');
        $second = $this->contractor('Second');
        $slot = $repair->tenant_availability->toIso8601String();
        $this->send($landlord, $accountId, $repair, [$first->id, $second->id]);
        $offerA = $this->offerFor($repair, $first);
        $offerB = $this->offerFor($repair, $second);
        $this->quoteInspection($first, $offerA, $slot);
        $this->quoteInspection($second, $offerB, $slot);

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.accept', $offerA), ['revision' => 0])
            ->assertSessionHas('success');
        $this->assertSame(RepairJobOffer::INSPECTION_ACCEPTED, $offerA->fresh()->status);
        $this->assertSame(RepairJobOffer::NOT_SELECTED, $offerB->fresh()->status);
        $this->assertNull($repair->fresh()->awarded_offer_id);
        $this->actingAs($first)->get(route('contractor.jobs.show', $offerA))->assertOk()->assertSee('12 Secret Road', false);
        $this->actingAs($second)->get(route('contractor.jobs.show', $offerB))->assertOk()->assertDontSee('12 Secret Road', false);

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.cancel', $offerA), ['reason' => 'Visit no longer needed'])
            ->assertSessionHas('success');
        $this->assertSame(RepairJobOffer::CANCELLED, $offerA->fresh()->status);
        $this->actingAs($first)->get(route('contractor.jobs.show', $offerA))->assertOk()->assertDontSee('12 Secret Road', false);
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.accept', $offerB), ['revision' => 0])
            ->assertSessionHas('error');
        $this->assertSame(RepairJobOffer::NOT_SELECTED, $offerB->fresh()->status);

        $third = $this->contractor('Third');
        $this->send($landlord, $accountId, $repair, [$third->id]);
        $offerC = $this->offerFor($repair, $third);
        $this->actingAs($third)->get(route('contractor.jobs.show', $offerC))
            ->assertOk()
            ->assertDontSee('12 Secret Road', false)
            ->assertSee('M1', false);
    }

    public function test_a_decline_after_award_and_a_stale_revision_leave_the_award_alone(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId);
        $winner = $this->contractor('Winner');
        $other = $this->contractor('Other');
        $slot = $repair->tenant_availability->toIso8601String();
        $this->send($landlord, $accountId, $repair, [$winner->id, $other->id]);
        $offer = $this->offerFor($repair, $winner);
        $otherOffer = $this->offerFor($repair, $other);
        $this->actingAs($winner)->post(route('contractor.jobs.quote', $offer), [
            'pricing_type' => 'fixed', 'amount' => 80, 'materials' => 'included', 'visit_slots' => [$slot],
        ])->assertSessionHas('success');
        $this->actingAs($other)->post(route('contractor.jobs.quote', $otherOffer), [
            'pricing_type' => 'fixed', 'amount' => 90, 'materials' => 'included', 'visit_slots' => [$slot],
        ])->assertSessionHas('success');

        $this->actingAs($winner)->post(route('contractor.jobs.quote', $offer), [
            'pricing_type' => 'fixed', 'amount' => 110, 'materials' => 'included', 'visit_slots' => [$slot],
        ])->assertSessionHas('success');
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.accept', $offer), ['revision' => 0])
            ->assertSessionHas('error');
        $this->assertStringContainsString('£110.00', (string) session('error'));
        $this->assertNull($repair->fresh()->awarded_offer_id);
        $this->assertSame(RepairJobOffer::OFFER_SENT, $offer->fresh()->status);

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.accept', $offer), [
            'revision' => (int) $offer->fresh()->revision,
        ])->assertSessionHas('success');
        $this->assertSame($offer->id, (int) $repair->fresh()->awarded_offer_id);

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.decline', $offer))
            ->assertSessionHas('error');
        $this->assertSame(RepairJobOffer::JOB_ACCEPTED, $offer->fresh()->status);
        $this->assertSame($offer->id, (int) $repair->fresh()->awarded_offer_id);
        $this->assertNotSame(RepairJobOffer::NOT_SELECTED, $offer->fresh()->status);
    }

    public function test_a_tenant_and_a_contractor_cannot_open_find_or_the_landlord_desk(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->member($accountId, 'Tenant', 'tenant', 'Portal Tenant');
        $repair = $this->repair($landlord, $accountId, 'General maintenance '.uniqid(), 'Leaking tap', $tenant);
        $contractor = $this->contractor('Desk');

        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->get(route('admin.property_repairs.marketplace.find', $repair))
            ->assertForbidden();
        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.property_repairs.job_offers.store', $repair), [
                'contractor_ids' => [$contractor->id],
                'urgency' => 'routine',
            ])->assertForbidden();

        $this->actingAs($contractor)->get(route('admin.properties.index'))->assertForbidden();
        $this->actingAs($contractor)->get(route('tenant.rent'))->assertForbidden();
        $this->actingAs($contractor)->get(route('admin.finance.index'))->assertForbidden();
        $this->actingAs($contractor)->get(route('admin.offers.index'))->assertForbidden();
        $this->actingAs($contractor)->get(route('contractor.jobs.index'))->assertOk();
    }

    public function test_a_claim_link_works_once_and_fails_when_it_is_expired(): void
    {
        $admin = $this->superAdmin();
        $legacy = User::create([
            'name' => 'Claimed Plumber',
            'first_name' => 'Claimed',
            'email' => 'claim-'.uniqid().'@resisquare.test',
            'password' => bcrypt('old-password'),
            'status' => 1,
        ]);
        $legacy->assignRole(Role::findOrCreate('Contractor', 'web'));
        $this->actingAs($admin)->post(route('admin.registrations.contractors.legacy'))->assertSessionHas('success');

        $link = null;
        Mail::assertSent(MailManager::class, function (MailManager $mail) use (&$link, $legacy) {
            if (! $mail->hasTo($legacy->email)) {
                return false;
            }
            if (preg_match('/href="([^"]+)"/', (string) ($mail->array['content'] ?? ''), $match)) {
                $link = html_entity_decode($match[1]);
            }

            return $link !== null;
        });

        auth()->logout();
        $profile = $legacy->fresh()->contractorProfile;
        $profile->forceFill(['claim_expires_at' => now()->subHour()])->save();
        $this->get($link)->assertStatus(410);

        $profile->forceFill(['claim_expires_at' => now()->addHour()])->save();
        $this->get($link)->assertOk()->assertSee('Claim your account', false);
        $this->post($link, ['password' => 'new-password-1', 'password_confirmation' => 'new-password-1', 'terms' => 1])
            ->assertRedirect(route('contractor.profile.edit', ['step' => 'business']));
        $this->get($link)->assertNotFound();
    }

    public function test_a_new_radius_returns_an_approved_contractor_to_review(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId);
        $contractor = $this->contractor('Radius');
        $slot = $repair->tenant_availability->toIso8601String();
        $this->send($landlord, $accountId, $repair, [$contractor->id]);
        $offer = $this->offerFor($repair, $contractor);
        $this->actingAs($contractor)->post(route('contractor.jobs.quote', $offer), [
            'pricing_type' => 'fixed', 'amount' => 70, 'materials' => 'included', 'visit_slots' => [$slot],
        ])->assertSessionHas('success');
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.accept', $offer), ['revision' => 0])
            ->assertSessionHas('success');

        $this->actingAs($contractor)->post(route('contractor.profile.business'), $this->business([
            'phone' => '07911111111',
            'coverage_radius_miles' => 15,
        ]))->assertRedirect();
        $this->assertSame(ContractorProfile::APPROVED, $contractor->fresh()->contractorProfile->approval_status);
        $this->assertSame('07911111111', $contractor->fresh()->contractorProfile->phone);
        $this->assertSame(RepairJobOffer::JOB_ACCEPTED, $offer->fresh()->status);

        $this->actingAs($contractor)->post(route('contractor.profile.business'), $this->business([
            'coverage_radius_miles' => 50,
        ]))->assertRedirect();
        $this->assertSame(ContractorProfile::SUBMITTED, $contractor->fresh()->contractorProfile->approval_status);
        $this->assertSame($offer->id, (int) $repair->fresh()->awarded_offer_id);

        $next = $this->repair($landlord, $accountId, 'General maintenance '.uniqid(), 'Another leaking tap', null, '14 Other Road '.uniqid());
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $next), [
            'contractor_ids' => [$contractor->id],
            'urgency' => 'routine',
        ])->assertSessionHas('error');
        $this->assertSame(0, RepairJobOffer::query()->where('repair_issue_id', $next->id)->count());
    }

    public function test_employing_staff_requires_employers_liability_before_review(): void
    {
        Storage::fake('local');
        $user = User::create([
            'name' => 'Employer Contractor',
            'email' => 'employer-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
            'can_login' => 1,
        ]);
        $user->assignRole(Role::findOrCreate('Contractor', 'web'));

        $this->actingAs($user)->post(route('contractor.profile.business'), $this->business([
            'employs_staff' => 1,
        ]))->assertRedirect(route('contractor.profile.edit'));
        $this->actingAs($user)->get(route('contractor.profile.edit'))
            ->assertOk()
            ->assertSee('Employers', false);

        $this->actingAs($user)->post(route('contractor.profile.documents'), [
            'docs' => [
                'photo_id' => ['file' => UploadedFile::fake()->image('id.jpg')],
                'public_liability' => [
                    'file' => UploadedFile::fake()->create('pl.pdf', 100, 'application/pdf'),
                    'cover_amount' => 2000000,
                    'expires_at' => now()->addYear()->toDateString(),
                ],
            ],
            'submit' => 1,
        ])->assertSessionHas('error');
        $this->assertStringContainsString('employers', strtolower((string) session('error')));
        $this->assertSame(ContractorProfile::DRAFT, $user->fresh()->contractorProfile->approval_status);
    }

    public function test_company_numbers_are_unique_and_removal_frees_the_number(): void
    {
        $number = 'AB'.substr(str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT), 0, 6);
        $first = $this->contractor('Company');
        $first->contractorProfile->forceFill([
            'trading_type' => 'limited_company',
            'company_number' => $number,
        ])->save();

        $second = User::create([
            'name' => 'Copy Contractor',
            'email' => 'copy-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
            'can_login' => 1,
        ]);
        $second->assignRole(Role::findOrCreate('Contractor', 'web'));
        $this->actingAs($second)->post(route('contractor.profile.business'), $this->business([
            'trading_type' => 'limited_company',
            'company_number' => $number,
        ]))->assertSessionHasErrors('company_number');

        $thrown = false;
        try {
            ContractorProfile::create([
                'user_id' => $second->id,
                'trading_type' => 'limited_company',
                'company_number' => $number,
                'approval_status' => ContractorProfile::DRAFT,
            ]);
        } catch (QueryException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $this->actingAs($this->superAdmin())->post(route('admin.registrations.contractors.remove', $first->contractorProfile), [
            'review_note' => 'Closed the company.',
        ])->assertSessionHas('success');
        $this->assertNull($first->fresh()->contractorProfile->company_number);

        $this->actingAs($second)->post(route('contractor.profile.business'), $this->business([
            'trading_type' => 'limited_company',
            'company_number' => $number,
        ]))->assertSessionDoesntHaveErrors('company_number');
        $this->assertSame($number, $second->fresh()->contractorProfile->company_number);
    }

    public function test_emergency_send_rejects_sixteen_and_accepts_six(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId);
        $sixteen = collect(range(1, 16))->map(fn ($i) => $this->contractor('Em'.$i)->id)->all();

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => $sixteen,
            'urgency' => 'emergency',
        ])->assertSessionHas('error');
        $this->assertSame(0, RepairJobOffer::query()->where('repair_issue_id', $repair->id)->count());

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => array_slice($sixteen, 0, 6),
            'urgency' => 'emergency',
        ])->assertSessionHas('success');
        $this->assertSame(6, RepairJobOffer::query()->where('repair_issue_id', $repair->id)->count());
    }

    private function send(User $landlord, int $accountId, RepairIssue $repair, array $ids): void
    {
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => $ids,
            'urgency' => 'routine',
        ])->assertSessionHas('success');
    }

    private function offerFor(RepairIssue $repair, User $contractor): RepairJobOffer
    {
        return RepairJobOffer::query()
            ->where('repair_issue_id', $repair->id)
            ->where('contractor_user_id', $contractor->id)
            ->firstOrFail();
    }

    private function quoteInspection(User $contractor, RepairJobOffer $offer, string $slot): void
    {
        $this->actingAs($contractor)->post(route('contractor.jobs.quote', $offer), [
            'pricing_type' => 'inspection',
            'inspection_fee' => 0,
            'materials' => 'included',
            'visit_slots' => [$slot],
        ])->assertSessionHas('success');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function business(array $overrides = []): array
    {
        return $overrides + [
            'trading_type' => 'sole_trader',
            'trades' => ['plumbing', 'general'],
            'postcode' => 'M1 1AA',
            'coverage_radius_miles' => 15,
            'vat_registered' => 0,
            'phone' => '07123456789',
            'employs_staff' => 0,
        ];
    }

    private function pin(string $postcode, float $lat, float $lng): void
    {
        PostcodeLocation::query()->updateOrCreate(
            ['postcode' => PostcodeGeo::normalise($postcode)],
            ['latitude' => $lat, 'longitude' => $lng]
        );
    }

    private function landlord(User $landlord, int $accountId): static
    {
        return $this->actingAs($landlord)->withSession(['current_account_id' => $accountId]);
    }

    private function contractor(string $label, array $trades = ['plumbing', 'general'], float $lat = 53.4808, float $lng = -2.2426, int $radius = 15): User
    {
        $user = User::create([
            'name' => $label.' Contractor',
            'email' => strtolower($label).'-'.uniqid().'@resisquare.test',
            'phone' => '07123456789',
            'password' => bcrypt('password'),
            'status' => 1,
            'can_login' => 1,
        ]);
        $user->assignRole(Role::findOrCreate('Contractor', 'web'));
        $profile = ContractorProfile::create([
            'user_id' => $user->id,
            'trading_type' => 'sole_trader',
            'legal_name' => $label.' Contractor',
            'vat_registered' => false,
            'cis_status' => 'unverified',
            'employs_staff' => false,
            'postcode' => 'M1 1AA',
            'latitude' => $lat,
            'longitude' => $lng,
            'coverage_radius_miles' => $radius,
            'phone' => '07123456789',
            'trades' => $trades,
            'approval_status' => ContractorProfile::APPROVED,
            'terms_accepted_at' => now(),
        ]);
        foreach ($trades as $trade) {
            ContractorTrade::create([
                'contractor_profile_id' => $profile->id,
                'trade' => $trade,
                'status' => ContractorTrade::APPROVED,
            ]);
        }
        foreach (['photo_id', 'public_liability'] as $type) {
            ContractorDocument::create([
                'contractor_profile_id' => $profile->id,
                'type' => $type,
                'status' => ContractorDocument::APPROVED,
                'disk' => 'local',
                'path' => 'contractor-documents/test/'.$type.'.bin',
                'original_name' => $type.'.pdf',
                'cover_amount' => $type === 'public_liability' ? 2000000 : null,
                'expires_at' => $type === 'public_liability' ? now()->addYear() : null,
                'submitted_at' => now(),
            ]);
        }

        return $user->fresh();
    }

    private function repair(User $landlord, int $accountId, string $category = 'General maintenance', string $description = 'Leaking tap under the sink', ?User $tenant = null, string $line = '12 Secret Road'): RepairIssue
    {
        $tenant = $tenant ?: tap(User::create([
            'name' => 'Tina Tenant',
            'first_name' => 'Tina',
            'email' => 'tina-'.uniqid().'@resisquare.test',
            'phone' => '07000000000',
            'password' => bcrypt('password'),
            'status' => 1,
        ]), fn (User $user) => $user->assignRole(Role::findOrCreate('Tenant', 'web')));
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => $line,
            'city' => 'Manchester',
            'postcode' => 'M1 2AB',
        ]);
        $categoryRow = RepairCategory::create([
            'name' => $category.' '.uniqid(),
            'level' => 0,
            'description' => 'Test',
            'status' => 0,
        ]);

        return RepairIssue::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'repair_category_id' => $categoryRow->id,
            'repair_navigation' => json_encode(['area' => 'kitchen']),
            'description' => $description,
            'tenant_id' => $tenant->id,
            'tenant_availability' => now()->addDay()->setTime(9, 0),
            'access_details' => 'Key in the lockbox',
            'priority' => 'medium',
            'status' => 'Pending',
            'sub_status' => 'Pending',
            'reference_number' => 'REP-'.uniqid(),
        ]);
    }

    private function member(int $accountId, string $roleName, string $memberType, string $name): User
    {
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
            'can_login' => 1,
        ]);
        $user->assignRole(Role::findOrCreate($roleName, 'web'));
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

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $user = User::create([
            'name' => 'Journey Landlord',
            'email' => 'journey-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole(Role::findOrCreate('Landlord', 'web'));
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

    private function superAdmin(): User
    {
        $role = Role::findOrCreate('Super Admin', 'web');
        $permission = Permission::findOrCreate('manage registrations', 'web');
        if (! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }
        $user = User::create([
            'name' => 'Review Admin',
            'email' => 'review-admin-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
