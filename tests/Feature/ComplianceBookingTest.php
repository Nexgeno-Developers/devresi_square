<?php

namespace Tests\Feature;

use App\Models\ComplianceBooking;
use App\Models\ComplianceJobOffer;
use App\Models\ComplianceType;
use App\Models\ContractorDocument;
use App\Models\ContractorProfile;
use App\Models\ContractorTrade;
use App\Models\PostcodeLocation;
use App\Models\Property;
use App\Models\User;
use App\Support\PostcodeGeo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComplianceBookingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('business_settings');
        config(['contractor_marketplace.geocode.enabled' => false]);
        Role::findOrCreate('Landlord', 'web');
        Role::findOrCreate('Contractor', 'web');
        Queue::fake();
        Mail::fake();
        foreach (['gas' => 'Gas Safety Certificate', 'epc' => 'Energy Performance Certificate (EPC)', 'eicr' => 'Electrical Installation Condition Report (EICR)'] as $alias => $name) {
            ComplianceType::query()->firstOrCreate(['alias' => $alias], ['name' => $name]);
        }
    }

    public function test_upload_label_stays_upload_and_book_now_sits_under_it(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $this->property($landlord, $accountId, '1 Upload Street');

        $this->landlord($landlord, $accountId)
            ->get(route('admin.compliance.index'))
            ->assertOk()
            ->assertSee('>Upload<', false)
            ->assertDontSee('Upload and share', false)
            ->assertSee('Book now', false);
    }

    public function test_book_now_opens_one_booking_and_a_second_click_returns_to_it(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $property = $this->property($landlord, $accountId, '2 Booking Street');
        $type = ComplianceType::query()->where('alias', 'gas')->firstOrFail();

        $first = $this->landlord($landlord, $accountId)->post(route('admin.compliance.bookings.store'), [
            'property_id' => $property->id,
            'compliance_type_id' => $type->id,
        ]);
        $booking = ComplianceBooking::query()->firstOrFail();
        $first->assertRedirect(route('admin.compliance.bookings.find', $booking));

        $this->landlord($landlord, $accountId)->post(route('admin.compliance.bookings.store'), [
            'property_id' => $property->id,
            'compliance_type_id' => $type->id,
        ])->assertRedirect(route('admin.compliance.bookings.find', $booking));

        $this->assertSame(1, ComplianceBooking::query()->count());
        $this->get(route('admin.compliance.index'))->assertSee('View booking', false)->assertSee('Choose a contractor', false);
    }

    public function test_a_gas_check_is_refused_to_a_contractor_without_gas_safe(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $property = $this->property($landlord, $accountId, '3 Gas Street');
        $type = ComplianceType::query()->where('alias', 'gas')->firstOrFail();
        $plumber = $this->contractor('Plumber', ['plumbing']);
        $booking = $this->openBooking($landlord, $accountId, $property, $type);

        $this->landlord($landlord, $accountId)
            ->post(route('admin.compliance.bookings.send', $booking), ['contractor_ids' => [$plumber->id]])
            ->assertSessionHas('error');

        $this->assertSame(0, ComplianceJobOffer::query()->count());
        $this->assertSame(ComplianceBooking::DRAFT, $booking->fresh()->status);
        $this->assertStringContainsString('Gas Safe', session('error'));
    }

    public function test_send_quote_and_accept_books_the_check_and_then_shows_the_address(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $property = $this->property($landlord, $accountId, '4 Award Street');
        $type = ComplianceType::query()->where('alias', 'gas')->firstOrFail();
        $engineer = $this->contractor('Engineer', ['gas']);
        $booking = $this->openBooking($landlord, $accountId, $property, $type);

        $this->landlord($landlord, $accountId)
            ->get(route('admin.compliance.bookings.find', $booking))
            ->assertOk()
            ->assertSee('Engineer Contractor', false)
            ->assertSee('Invite my own contractor', false);

        $this->landlord($landlord, $accountId)
            ->post(route('admin.compliance.bookings.send', $booking), ['contractor_ids' => [$engineer->id]])
            ->assertSessionHas('success');
        $offer = ComplianceJobOffer::query()->where('compliance_booking_id', $booking->id)->firstOrFail();
        $this->assertSame(ComplianceJobOffer::INVITED, $offer->status);

        $this->actingAs($engineer)->get(route('contractor.jobs.index'))
            ->assertOk()
            ->assertSee($type->name, false);

        $this->actingAs($engineer)->get(route('contractor.jobs.compliance.show', $offer))
            ->assertOk()
            ->assertDontSee('4 Award Street', false);

        $visit = now()->addDays(3)->setTime(9, 30);
        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.quote', $offer), [
            'pricing_type' => 'fixed',
            'amount' => 120,
            'materials' => 'included',
            'visit_slots' => [$visit->format('Y-m-d\TH:i')],
        ])->assertSessionHas('success');

        $this->landlord($landlord, $accountId)
            ->get(route('admin.compliance.bookings.show', $booking))
            ->assertOk()
            ->assertSee('£120.00', false)
            ->assertSee('Accept quote', false);

        $this->landlord($landlord, $accountId)
            ->post(route('admin.compliance.bookings.accept', [$booking, $offer]), ['revision' => 0])
            ->assertSessionHas('success');

        $this->assertSame(ComplianceBooking::BOOKED, $booking->fresh()->status);
        $this->assertSame(ComplianceJobOffer::JOB_ACCEPTED, $offer->fresh()->status);
        $this->assertNotNull($offer->fresh()->scheduled_for);

        $this->actingAs($engineer)->get(route('contractor.jobs.compliance.show', $offer))
            ->assertOk()
            ->assertSee('4 Award Street', false);

        $this->landlord($landlord, $accountId)->get(route('admin.compliance.index'))
            ->assertSee('Booked with', false)
            ->assertSee('View booking', false);

        $this->landlord($landlord, $accountId)->post(route('admin.compliance.bookings.store'), [
            'property_id' => $property->id,
            'compliance_type_id' => $type->id,
        ])->assertRedirect(route('admin.compliance.bookings.show', $booking));
        $this->assertSame(1, ComplianceBooking::query()->where('property_id', $property->id)->where('compliance_type_id', $type->id)->count());
    }

    private function openBooking(User $landlord, int $accountId, Property $property, ComplianceType $type): ComplianceBooking
    {
        $this->landlord($landlord, $accountId)->post(route('admin.compliance.bookings.store'), [
            'property_id' => $property->id,
            'compliance_type_id' => $type->id,
        ])->assertRedirect();

        return ComplianceBooking::query()->where('property_id', $property->id)->where('compliance_type_id', $type->id)->firstOrFail();
    }

    private function property(User $landlord, int $accountId, string $line): Property
    {
        $this->pin('M1 2AB', 53.4795, -2.2440);

        return Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => $line,
            'city' => 'Manchester',
            'postcode' => 'M1 2AB',
        ]);
    }

    private function contractor(string $label, array $trades): User
    {
        $user = User::create([
            'name' => ucfirst($label).' Contractor',
            'email' => strtolower($label).'-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole(Role::findOrCreate('Contractor', 'web'));
        $profile = ContractorProfile::create([
            'user_id' => $user->id,
            'trading_type' => 'sole_trader',
            'legal_name' => ucfirst($label).' Contractor',
            'vat_registered' => false,
            'cis_status' => 'unverified',
            'employs_staff' => false,
            'postcode' => 'M1 1AA',
            'latitude' => 53.4808,
            'longitude' => -2.2426,
            'coverage_radius_miles' => 15,
            'phone' => '07123456789',
            'trades' => $trades,
            'approval_status' => ContractorProfile::APPROVED,
            'terms_accepted_at' => now(),
            'marketplace_accepted_at' => now(),
            'marketplace_terms_version' => config('contractor_marketplace.marketplace_terms_version'),
            'marketplace_commission_percent' => 0,
        ]);
        foreach ($trades as $trade) {
            ContractorTrade::create([
                'contractor_profile_id' => $profile->id,
                'trade' => $trade,
                'status' => ContractorTrade::APPROVED,
            ]);
        }
        ContractorDocument::create([
            'contractor_profile_id' => $profile->id,
            'type' => 'photo_id',
            'status' => ContractorDocument::APPROVED,
            'disk' => 'local',
            'path' => 'contractor-documents/test/photo_id.bin',
            'original_name' => 'photo_id.pdf',
            'submitted_at' => now(),
        ]);
        ContractorDocument::create([
            'contractor_profile_id' => $profile->id,
            'type' => 'public_liability',
            'status' => ContractorDocument::APPROVED,
            'cover_amount' => 2000000,
            'expires_at' => now()->addYear(),
            'disk' => 'local',
            'path' => 'contractor-documents/test/public_liability.bin',
            'original_name' => 'public_liability.pdf',
            'submitted_at' => now(),
        ]);
        if (in_array('gas', $trades, true)) {
            ContractorDocument::create([
                'contractor_profile_id' => $profile->id,
                'type' => 'gas_safe',
                'status' => ContractorDocument::APPROVED,
                'reference' => '123456',
                'expires_at' => now()->addYear(),
                'submitted_at' => now(),
            ]);
        }
        $this->pin('M1 1AA', 53.4808, -2.2426);

        return $user->fresh();
    }

    private function pin(string $postcode, float $lat, float $lng): void
    {
        PostcodeLocation::query()->updateOrCreate(
            ['postcode' => PostcodeGeo::normalise($postcode)],
            ['latitude' => $lat, 'longitude' => $lng]
        );
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $user = User::create([
            'name' => 'Compliance Landlord',
            'email' => 'compliance-landlord-'.uniqid().'@resisquare.test',
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

    private function landlord(User $landlord, int $accountId): static
    {
        return $this->actingAs($landlord)->withSession(['current_account_id' => $accountId]);
    }
}
