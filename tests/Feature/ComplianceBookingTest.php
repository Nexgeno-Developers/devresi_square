<?php

namespace Tests\Feature;

use App\Models\ComplianceBooking;
use App\Models\ComplianceJobOffer;
use App\Models\ComplianceJobPayment;
use App\Models\ComplianceRecord;
use App\Models\ComplianceType;
use App\Models\ContractorDocument;
use App\Models\ContractorProfile;
use App\Models\ContractorTrade;
use App\Models\PostcodeLocation;
use App\Models\Property;
use App\Models\User;
use App\Services\Contractors\ComplianceJobPaymentService;
use App\Services\Contractors\ContractorMarketplaceException;
use App\Support\ContractorSettings;
use App\Support\PostcodeGeo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
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
        $booking = ComplianceBooking::query()->where('property_id', $property->id)->where('compliance_type_id', $type->id)->firstOrFail();
        $first->assertRedirect(route('admin.compliance.bookings.find', $booking));

        $this->landlord($landlord, $accountId)->post(route('admin.compliance.bookings.store'), [
            'property_id' => $property->id,
            'compliance_type_id' => $type->id,
        ])->assertRedirect(route('admin.compliance.bookings.find', $booking));

        $this->assertSame(1, ComplianceBooking::query()->where('property_id', $property->id)->where('compliance_type_id', $type->id)->count());
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

        $this->assertSame(0, ComplianceJobOffer::query()->where('compliance_booking_id', $booking->id)->count());
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

    public function test_login_sends_a_content_security_policy(): void
    {
        $login = $this->get(route('login'));

        $this->assertStringContainsString("default-src 'self'", (string) $login->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('frame-ancestors', (string) $login->headers->get('Content-Security-Policy'));
    }

    public function test_contractor_updates_the_visit_and_the_landlord_confirms_completion(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $property = $this->property($landlord, $accountId, '5 Progress Street');
        $type = ComplianceType::query()->where('alias', 'gas')->firstOrFail();
        $engineer = $this->contractor('Progress', ['gas']);
        $other = $this->contractor('Other', ['gas']);
        $booking = $this->openBooking($landlord, $accountId, $property, $type);

        $this->landlord($landlord, $accountId)
            ->post(route('admin.compliance.bookings.send', $booking), ['contractor_ids' => [$engineer->id]])
            ->assertSessionHas('success');
        $offer = ComplianceJobOffer::query()->where('compliance_booking_id', $booking->id)->firstOrFail();
        $visit = now()->addDays(3)->setTime(9, 30);
        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.quote', $offer), [
            'pricing_type' => 'fixed',
            'amount' => 90,
            'materials' => 'included',
            'visit_slots' => [$visit->format('Y-m-d\TH:i')],
        ])->assertSessionHas('success');
        $this->landlord($landlord, $accountId)
            ->post(route('admin.compliance.bookings.accept', [$booking, $offer]), ['revision' => 0])
            ->assertSessionHas('success');

        $offer = $offer->fresh();
        $this->assertSame(ComplianceJobOffer::JOB_SCHEDULED, $offer->job_status);

        $job = $this->actingAs($engineer)->get(route('contractor.jobs.compliance.show', $offer));
        $job->assertOk()
            ->assertSee('Start work', false)
            ->assertSee('Mark complete', false)
            ->assertSee('Date on the certificate', false)
            ->assertSee('5 Progress Street', false);
        $this->assertStringContainsString("default-src 'self'", (string) $job->headers->get('Content-Security-Policy'));

        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.progress', $offer), [
            'step' => 'schedule',
            'scheduled_for' => now()->subHour()->format('Y-m-d\TH:i'),
        ])->assertSessionHas('error');
        $this->assertSame($visit->format('Y-m-d H:i'), $offer->fresh()->scheduled_for->format('Y-m-d H:i'));

        $this->actingAs($other)->post(route('contractor.jobs.compliance.progress', $offer), [
            'step' => 'start',
        ])->assertForbidden();

        $this->landlord($landlord, $accountId)
            ->post(route('admin.compliance.bookings.confirm', [$booking, $offer]))
            ->assertSessionHas('error');
        $this->assertSame(ComplianceBooking::BOOKED, $booking->fresh()->status);

        $moved = now()->addDays(4)->setTime(14, 15);
        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.progress', $offer), [
            'step' => 'schedule',
            'scheduled_for' => $moved->format('Y-m-d\TH:i'),
        ])->assertSessionHas('success');
        $this->assertSame($moved->format('Y-m-d H:i'), $offer->fresh()->scheduled_for->format('Y-m-d H:i'));

        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.progress', $offer), [
            'step' => 'start',
        ])->assertSessionHas('success');
        $this->assertSame(ComplianceJobOffer::JOB_IN_PROGRESS, $offer->fresh()->job_status);
        $this->assertNotNull($offer->fresh()->started_at);

        $this->landlord($landlord, $accountId)
            ->get(route('admin.compliance.bookings.show', $booking))
            ->assertOk()
            ->assertSee('In progress with', false)
            ->assertDontSee('Confirm complete', false);

        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.invoice.store', $offer))
            ->assertSessionHas('error');
        $this->assertStringContainsString('confirms', session('error'));

        $again = now()->addDays(5)->setTime(11, 0);
        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.progress', $offer), [
            'step' => 'schedule',
            'scheduled_for' => $again->format('Y-m-d\TH:i'),
        ])->assertSessionHas('success');
        $this->assertSame(ComplianceJobOffer::JOB_IN_PROGRESS, $offer->fresh()->job_status);
        $this->assertSame($again->format('Y-m-d H:i'), $offer->fresh()->scheduled_for->format('Y-m-d H:i'));

        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.progress', $offer), [
            'step' => 'complete',
            'completion_note' => 'Gas safe, no faults',
        ])->assertSessionHasErrors(['certificate', 'issued_date', 'expiry_date']);
        $this->assertSame(ComplianceJobOffer::JOB_IN_PROGRESS, $offer->fresh()->job_status);

        Storage::fake('local');
        $issued = '2026-11-01';
        $expiry = '2027-11-01';
        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.progress', $offer), [
            'step' => 'complete',
            'completion_note' => 'Gas safe, no faults',
            'issued_date' => $issued,
            'expiry_date' => $expiry,
            'certificate' => UploadedFile::fake()->create('gas-certificate.pdf', 20, 'application/pdf'),
        ])->assertSessionHas('success');
        $offer = $offer->fresh();
        $this->assertSame(ComplianceJobOffer::JOB_COMPLETED, $offer->job_status);
        $this->assertSame('Gas safe, no faults', $offer->completion_note);
        $this->assertSame(ComplianceBooking::BOOKED, $booking->fresh()->status);
        $record = ComplianceRecord::query()->findOrFail($offer->compliance_record_id);
        $this->assertSame($property->id, $record->property_id);
        $this->assertSame($type->id, $record->compliance_type_id);
        $this->assertSame($issued, $record->issued_date->toDateString());
        $this->assertSame($expiry, $record->expiry_date->toDateString());
        $this->assertTrue($record->hasFile());
        $this->assertNull($record->served_to_tenant_at);
        $this->assertDatabaseHas('documents', [
            'account_id' => $accountId,
            'documentable_id' => $property->id,
            'visibility' => 'private',
            'title' => $type->name,
        ]);
        $this->assertDatabaseHas('uploads', [
            'id' => (int) $record->photos,
            'account_id' => $accountId,
        ]);
        $gaps = collect(ComplianceRecord::certificateGapsForAccount($accountId));
        $this->assertFalse($gaps->contains(fn ($row) => $row['property']->id === $property->id && $row['type']->id === $type->id));

        $this->landlord($landlord, $accountId)
            ->get(route('admin.compliance.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Confirm complete', false)
            ->assertSee('Gas safe, no faults', false)
            ->assertSee('View certificate', false)
            ->assertSee('1 Nov 2027', false)
            ->assertSee('Completed by', false);

        $this->actingAs($engineer)->get(route('contractor.jobs.index', ['tab' => 'completed']))
            ->assertOk()
            ->assertSee($type->name, false);

        $this->landlord($landlord, $accountId)
            ->post(route('admin.compliance.bookings.confirm', [$booking, $offer]))
            ->assertSessionHas('success');
        $this->assertSame(ComplianceBooking::COMPLETED, $booking->fresh()->status);
        $this->assertSame(ComplianceJobOffer::JOB_CONFIRMED, $offer->fresh()->job_status);
        $this->assertNotNull($offer->fresh()->confirmed_at);

        $this->actingAs($engineer)->get(route('contractor.jobs.compliance.show', $offer))
            ->assertOk()
            ->assertSee('The landlord confirmed this check is complete.', false)
            ->assertDontSee('Start work', false);

        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.progress', $offer), [
            'step' => 'start',
        ])->assertSessionHas('error');

        $this->landlord($landlord, $accountId)->get(route('admin.compliance.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Completed with', false)
            ->assertDontSee('Confirm complete', false);

        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.invoice.store', $offer), [
            'notes' => 'Due on receipt',
        ])->assertSessionHas('error');
        $this->assertStringContainsString('bank', session('error'));

        $engineer->contractorProfile->forceFill([
            'bank_account_name' => 'Progress Contractor',
            'bank_sort_code' => '112233',
            'bank_account_number' => '12345678',
            'bank_name' => 'Test Bank',
        ])->save();

        $this->actingAs($engineer)->get(route('contractor.jobs.compliance.show', $offer))
            ->assertOk()
            ->assertSee('Issue invoice', false)
            ->assertSee('£90.00', false);

        $this->actingAs($other)->post(route('contractor.jobs.compliance.invoice.store', $offer))
            ->assertForbidden();

        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.invoice.store', $offer), [
            'notes' => 'Due on receipt',
        ])->assertRedirect(route('contractor.jobs.compliance.invoice', $offer));

        $offer = $offer->fresh();
        $number = 'CC-'.str_pad((string) $offer->id, 6, '0', STR_PAD_LEFT);
        $this->assertSame(ComplianceJobOffer::JOB_INVOICED, $offer->job_status);
        $this->assertSame($number, $offer->invoice->number);
        $this->assertSame('90.00', (string) $offer->invoice->gross_amount);
        $this->assertSame('Due on receipt', $offer->invoice->notes);
        $this->assertSame('issued', $offer->invoice->status);

        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.invoice.store', $offer))
            ->assertSessionHas('error');

        $this->actingAs($engineer)->get(route('contractor.jobs.compliance.invoice', $offer))
            ->assertOk()
            ->assertSee($number, false)
            ->assertSee('£90.00', false)
            ->assertSee('5 Progress Street', false)
            ->assertSee('Account number', false)
            ->assertSee('11-22-33', false);

        config(['services.stripe.secret' => '', 'services.stripe.webhook_secret' => 'whsec_compliance']);
        $this->landlord($landlord, $accountId)->get(route('admin.compliance.bookings.invoice', [$booking, $offer]))
            ->assertOk()
            ->assertSee($number, false)
            ->assertSee('£90.00', false)
            ->assertSee('5 Progress Street', false)
            ->assertSee('Gas Safety Certificate', false)
            ->assertSee('Card payments are not available yet.', false)
            ->assertDontSee('Account number', false);
        $this->landlord($landlord, $accountId)->post(route('admin.compliance.bookings.invoice.pay', [$booking, $offer]))
            ->assertSessionHasErrors('invoice');
        $this->assertSame('issued', $offer->invoice->fresh()->status);

        [$otherLandlord, $otherAccountId] = $this->createLandlord();
        $this->landlord($otherLandlord, $otherAccountId)->post(route('admin.compliance.bookings.invoice.pay', [$booking, $offer]))
            ->assertNotFound();

        $this->landlord($landlord, $accountId)->get(route('admin.compliance.bookings.invoice.return', [$booking, $offer]))
            ->assertRedirect(route('admin.compliance.bookings.invoice', [$booking, $offer]))
            ->assertSessionHas('error');
        $this->assertSame('issued', $offer->invoice->fresh()->status);

        config(['services.stripe.secret' => 'sk_test_compliance']);
        \DB::table('accounts')->where('id', $accountId)->update(['status' => 'suspended']);
        try {
            app(ComplianceJobPaymentService::class)->createCheckoutUrl($landlord, $offer->invoice);
            $this->fail('A suspended account was allowed to pay.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('paused', $exception->validator->errors()->first('invoice'));
        }
        $this->assertSame('issued', $offer->invoice->fresh()->status);
        \DB::table('accounts')->where('id', $accountId)->update(['status' => 'trialing']);

        ContractorSettings::put([
            'job_commission_percent' => '10',
            'job_commission_bearer' => 'landlord',
        ]);
        $split = app(ComplianceJobPaymentService::class)->breakdown(90);
        $this->assertEquals(9.0, $split['commission']);
        $this->assertEquals(99.0, $split['landlord_amount']);
        $this->assertEquals(90.0, $split['contractor_amount']);

        $this->landlord($landlord, $accountId)->get(route('admin.compliance.bookings.invoice', [$booking, $offer]))
            ->assertOk()
            ->assertSee('Pay £99.00', false)
            ->assertSee('You pay £99.00', false);

        $metadata = [
            'type' => 'compliance_job_payment',
            'compliance_job_invoice_id' => (string) $offer->invoice->id,
            'account_id' => (string) $offer->invoice->account_id,
            'landlord_user_id' => (string) $landlord->id,
            'gross_amount' => '90.00',
            'commission_percent' => '10.00',
            'commission_bearer' => 'landlord',
            'commission_amount' => '9.00',
            'landlord_amount' => '99.00',
            'contractor_amount' => '90.00',
        ];
        $unpaid = json_encode([
            'id' => 'evt_compliance_unpaid_'.$offer->invoice->id,
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_compliance_unpaid_'.$offer->invoice->id,
                'payment_status' => 'unpaid',
                'payment_intent' => 'pi_compliance_unpaid_'.$offer->invoice->id,
                'metadata' => $metadata,
            ]],
        ], JSON_THROW_ON_ERROR);
        $this->postStripe('/stripe/webhook', $unpaid, 'whsec_compliance')->assertOk();
        $this->assertSame('issued', $offer->invoice->fresh()->status);

        $paid = json_encode([
            'id' => 'evt_compliance_paid_'.$offer->invoice->id,
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_compliance_paid_'.$offer->invoice->id,
                'payment_status' => 'paid',
                'payment_intent' => 'pi_compliance_paid_'.$offer->invoice->id,
                'metadata' => $metadata,
            ]],
        ], JSON_THROW_ON_ERROR);
        $this->postStripe('/stripe/webhook', $paid, 'whsec_compliance')->assertOk();
        $this->postStripe('/stripe/webhook', $paid, 'whsec_compliance')->assertOk();
        $again = json_encode([
            'id' => 'evt_compliance_again_'.$offer->invoice->id,
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_compliance_again_'.$offer->invoice->id,
                'payment_status' => 'paid',
                'payment_intent' => 'pi_compliance_again_'.$offer->invoice->id,
                'metadata' => $metadata,
            ]],
        ], JSON_THROW_ON_ERROR);
        $this->postStripe('/stripe/webhook', $again, 'whsec_compliance')->assertOk();

        $offer->invoice->refresh();
        $this->assertSame('paid', $offer->invoice->status);
        $this->assertSame(1, ComplianceJobPayment::query()->where('compliance_job_invoice_id', $offer->invoice->id)->count());
        $payment = $offer->invoice->cardPayment;
        $this->assertEquals(99.0, (float) $payment->landlord_amount);
        $this->assertEquals(90.0, (float) $payment->contractor_amount);
        $this->assertEquals(9.0, (float) $payment->commission_amount);
        $this->assertSame(ComplianceJobPayment::PAYOUT_UNPAID, $payment->payout_status);
        $this->assertSame($landlord->id, (int) $payment->paid_by);
        $this->assertTrue(\DB::table('notifications')->where('notifiable_id', $engineer->id)->where('data', 'like', '%Invoice paid%')->exists());

        $this->landlord($landlord, $accountId)->get(route('admin.compliance.bookings.invoice', [$booking, $offer]))
            ->assertOk()
            ->assertSee('Charged £99.00', false)
            ->assertSee('Paid on', false)
            ->assertDontSee('Pay £99.00', false)
            ->assertDontSee('Account number', false);
        $this->actingAs($engineer)->get(route('contractor.jobs.compliance.invoice', $offer))
            ->assertOk()
            ->assertSee('Resisquare will transfer £90.00', false)
            ->assertSee('Account number', false);

        $service = app(ComplianceJobPaymentService::class);
        try {
            $service->markPayoutSent($payment, $landlord, '   ');
            $this->fail('A blank transfer reference was accepted.');
        } catch (ContractorMarketplaceException $exception) {
            $this->assertStringContainsString('reference', $exception->getMessage());
        }
        $service->markPayoutSent($payment, $landlord, 'BANK-REF-1');
        try {
            $service->markPayoutSent($payment->fresh(), $landlord, 'BANK-REF-2');
            $this->fail('A second transfer was recorded.');
        } catch (ContractorMarketplaceException $exception) {
            $this->assertStringContainsString('already recorded', $exception->getMessage());
        }
        $payment->refresh();
        $this->assertTrue($payment->payoutIsSent());
        $this->assertSame('BANK-REF-1', $payment->payout_reference);
        $this->assertSame($landlord->id, (int) $payment->payout_paid_by);
        $this->actingAs($engineer)->get(route('contractor.jobs.compliance.invoice', $offer))
            ->assertOk()
            ->assertSee('Resisquare sent £90.00', false)
            ->assertSee('BANK-REF-1', false);
        $this->assertTrue(\DB::table('notifications')->where('notifiable_id', $engineer->id)->where('data', 'like', '%Payment sent%')->exists());

        $this->landlord($landlord, $accountId)->get(route('admin.compliance.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Invoiced by', false)
            ->assertSee('View invoice '.$number, false);

        $this->landlord($landlord, $accountId)->get(route('admin.compliance.index'))
            ->assertSee('Book now', false);

        $this->landlord($landlord, $accountId)->post(route('admin.compliance.bookings.store'), [
            'property_id' => $property->id,
            'compliance_type_id' => $type->id,
        ])->assertRedirect();
        $rows = ComplianceBooking::query()->where('property_id', $property->id)->where('compliance_type_id', $type->id)->get();
        $this->assertCount(2, $rows);
        $this->assertTrue($rows->contains('status', ComplianceBooking::COMPLETED));
        $this->assertTrue($rows->contains('status', ComplianceBooking::DRAFT));
    }

    public function test_messages_extra_work_reschedule_rating_and_dispute(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $property = $this->property($landlord, $accountId, '8 Aftercare Street');
        $type = ComplianceType::query()->where('alias', 'gas')->firstOrFail();
        $engineer = $this->contractor('Aftercare', ['gas']);
        $other = $this->contractor('AftercareOther', ['gas']);
        $booking = $this->openBooking($landlord, $accountId, $property, $type);

        $this->landlord($landlord, $accountId)
            ->post(route('admin.compliance.bookings.send', $booking), ['contractor_ids' => [$engineer->id]])
            ->assertSessionHas('success');
        $offer = ComplianceJobOffer::query()->where('compliance_booking_id', $booking->id)->firstOrFail();

        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.message', $offer), [
            'body' => 'Email me at leak@example.com or 07111 222333',
        ])->assertRedirect();
        $this->landlord($landlord, $accountId)->get(route('admin.compliance.bookings.show', $booking))
            ->assertOk()
            ->assertSee('hidden until the job is accepted', false)
            ->assertDontSee('leak@example.com', false)
            ->assertDontSee('07111 222333', false);
        $this->actingAs($other)->post(route('contractor.jobs.compliance.message', $offer), [
            'body' => 'Not my job',
        ])->assertForbidden();

        $visit = now()->addDays(3)->setTime(9, 30);
        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.quote', $offer), [
            'pricing_type' => 'fixed',
            'amount' => 90,
            'materials' => 'included',
            'visit_slots' => [$visit->format('Y-m-d\TH:i')],
        ])->assertSessionHas('success');
        $this->landlord($landlord, $accountId)
            ->post(route('admin.compliance.bookings.accept', [$booking, $offer]), ['revision' => 0])
            ->assertSessionHas('success');

        // After award, questions close and the job chat (landlord, contractor, tenants) takes over.
        $chat = \App\Models\Conversation::query()->where('kind', \App\Models\Conversation::KIND_COMPLIANCE_JOB)->where('context_id', $offer->id)->firstOrFail();
        $this->actingAs($engineer)->post(route('contractor.messages.send', $chat), [
            'body' => 'Reach me at later@example.com',
        ])->assertRedirect();
        $this->landlord($landlord, $accountId)->get(route('admin.messages.show', $chat))
            ->assertOk()
            ->assertSee('later@example.com', false);
        $this->landlord($landlord, $accountId)->post(route('admin.messages.send', $chat), [
            'body' => 'See you at the property',
        ])->assertRedirect();
        $this->actingAs($engineer)->get(route('contractor.messages.show', $chat))
            ->assertOk()
            ->assertSee('See you at the property', false);

        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.variation', $offer), [
            'amount' => 20,
            'reason' => 'Extra flue',
        ])->assertSessionHas('success');
        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.variation', $offer), [
            'amount' => 5,
            'reason' => 'Another extra',
        ])->assertSessionHas('error');
        $variation = $offer->variations()->firstOrFail();
        $this->landlord($landlord, $accountId)->post(route('admin.compliance.bookings.variation', [$booking, $offer, $variation]), [
            'decision' => 'approve',
        ])->assertSessionHas('success');
        $this->assertSame('approved', $variation->fresh()->status);
        $this->actingAs($engineer)->get(route('contractor.jobs.compliance.show', $offer))
            ->assertOk()
            ->assertSee('£110.00', false)
            ->assertSee('Extra flue', false);

        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.variation', $offer), [
            'amount' => 10,
            'reason' => 'Hold this',
        ])->assertSessionHas('success');

        $first = now()->addDays(6)->setTime(10, 0);
        $second = now()->addDays(7)->setTime(15, 0);
        $this->landlord($landlord, $accountId)->post(route('admin.compliance.bookings.reschedule', [$booking, $offer]), [
            'slots' => [$first->format('Y-m-d\TH:i'), $second->format('Y-m-d\TH:i')],
            'note' => 'Away that morning',
        ])->assertSessionHas('success');
        $offer = $offer->fresh();
        $this->assertNotNull($offer->reschedule_requested_at);
        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.reschedule', $offer), [
            'slot' => $offer->reschedule_slots[0],
        ])->assertSessionHas('success');
        $offer = $offer->fresh();
        $this->assertNull($offer->reschedule_slots);
        $this->assertSame($first->format('Y-m-d H:i'), $offer->scheduled_for->format('Y-m-d H:i'));

        $this->landlord($landlord, $accountId)->post(route('admin.compliance.bookings.reschedule', [$booking, $offer]), [
            'slots' => [now()->addDays(8)->setTime(11, 0)->format('Y-m-d\TH:i')],
        ])->assertSessionHas('success');
        $counter = now()->addDays(9)->setTime(16, 0);
        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.progress', $offer), [
            'step' => 'schedule',
            'scheduled_for' => $counter->format('Y-m-d\TH:i'),
        ])->assertSessionHas('success');
        $offer = $offer->fresh();
        $this->assertNull($offer->reschedule_slots);
        $this->assertSame($counter->format('Y-m-d H:i'), $offer->scheduled_for->format('Y-m-d H:i'));

        Storage::fake('local');
        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.progress', $offer), [
            'step' => 'complete',
            'completion_note' => 'Certificate filed',
            'issued_date' => '2026-11-01',
            'expiry_date' => '2027-11-01',
            'certificate' => UploadedFile::fake()->create('gas-certificate.pdf', 20, 'application/pdf'),
        ])->assertSessionHas('success');
        $this->landlord($landlord, $accountId)
            ->post(route('admin.compliance.bookings.confirm', [$booking, $offer]))
            ->assertSessionHas('success');

        $engineer->contractorProfile->forceFill([
            'bank_account_name' => 'Aftercare Contractor',
            'bank_sort_code' => '112233',
            'bank_account_number' => '12345678',
            'bank_name' => 'Test Bank',
        ])->save();
        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.invoice.store', $offer))
            ->assertSessionHas('error');
        $this->assertStringContainsString('extra', session('error'));

        $pending = $offer->variations()->where('status', 'pending')->firstOrFail();
        $this->landlord($landlord, $accountId)->post(route('admin.compliance.bookings.variation', [$booking, $offer, $pending]), [
            'decision' => 'decline',
            'note' => 'Not needed',
        ])->assertSessionHas('success');
        $this->landlord($landlord, $accountId)->post(route('admin.compliance.bookings.rate', [$booking, $offer]), [
            'rating' => 4,
            'rating_comment' => 'Tidy job',
        ])->assertSessionHas('success');
        $this->actingAs($engineer)->get(route('contractor.jobs.compliance.show', $offer))
            ->assertOk()
            ->assertSee('4 out of 5', false)
            ->assertSee('Tidy job', false);

        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.invoice.store', $offer), [
            'notes' => 'Includes the flue',
        ])->assertRedirect();
        $invoice = $offer->invoice()->firstOrFail();
        $this->assertSame('110.00', (string) $invoice->gross_amount);
        $this->assertTrue(collect($invoice->lines)->contains(fn ($line) => str_contains((string) $line['description'], 'Extra flue')));
        $this->landlord($landlord, $accountId)->get(route('admin.compliance.bookings.invoice', [$booking, $offer]))
            ->assertOk()
            ->assertSee('£110.00', false)
            ->assertSee('Extra flue', false);

        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.cancel', $offer), [
            'reason' => 'Too late',
        ])->assertSessionHas('error');
        $this->assertSame(ComplianceJobOffer::JOB_ACCEPTED, $offer->fresh()->status);

        $this->landlord($landlord, $accountId)->post(route('admin.compliance.bookings.dispute', [$booking, $offer]), [
            'reason' => 'Certificate date is wrong',
        ])->assertSessionHas('success');
        $this->assertNotNull($offer->fresh()->disputed_at);
        $this->landlord($landlord, $accountId)->get(route('admin.compliance.bookings.show', $booking))
            ->assertSee('Disputed with', false);

        $admin = $this->superAdmin();
        $this->actingAs($admin)->get(route('admin.registrations.contractors.jobs'))
            ->assertOk()
            ->assertSee('Certificate date is wrong', false);
        $this->actingAs($admin)->post(route('admin.registrations.contractors.jobs.compliance.resolve', $offer), [
            'note' => 'Dates match the certificate',
        ])->assertSessionHas('success');
        $this->assertNull($offer->fresh()->disputed_at);
        $this->assertTrue(\DB::table('notifications')->where('notifiable_id', $engineer->id)->where('data', 'like', '%Dispute resolved%')->exists());
    }

    public function test_a_booked_check_can_be_cancelled_and_sent_again(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $property = $this->property($landlord, $accountId, '9 Cancel Street');
        $type = ComplianceType::query()->where('alias', 'gas')->firstOrFail();
        $engineer = $this->contractor('Cancel', ['gas']);
        $other = $this->contractor('CancelOther', ['gas']);
        $booking = $this->openBooking($landlord, $accountId, $property, $type);

        $this->landlord($landlord, $accountId)
            ->post(route('admin.compliance.bookings.send', $booking), ['contractor_ids' => [$engineer->id]])
            ->assertSessionHas('success');
        $offer = ComplianceJobOffer::query()->where('compliance_booking_id', $booking->id)->firstOrFail();
        $this->actingAs($engineer)->post(route('contractor.jobs.compliance.quote', $offer), [
            'pricing_type' => 'fixed',
            'amount' => 40,
            'materials' => 'included',
            'visit_slots' => [now()->addDays(2)->format('Y-m-d\TH:i')],
        ])->assertSessionHas('success');
        $this->landlord($landlord, $accountId)
            ->post(route('admin.compliance.bookings.accept', [$booking, $offer]), ['revision' => 0])
            ->assertSessionHas('success');

        $this->actingAs($other)->post(route('contractor.jobs.compliance.cancel', $offer), [
            'reason' => 'Not mine',
        ])->assertForbidden();
        $this->landlord($landlord, $accountId)->post(route('admin.compliance.bookings.cancel', [$booking, $offer]), [
            'reason' => 'No longer needed',
        ])->assertSessionHas('success');

        $this->assertSame(ComplianceJobOffer::CANCELLED, $offer->fresh()->status);
        $this->assertSame('No longer needed', $offer->fresh()->cancel_reason);
        $booking = $booking->fresh();
        $this->assertSame(ComplianceBooking::SENT, $booking->status);
        $this->assertNull($booking->awarded_offer_id);
        $this->landlord($landlord, $accountId)->get(route('admin.compliance.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Add contractors', false)
            ->assertSee('No longer needed', false);
        $this->actingAs($engineer)->get(route('contractor.jobs.compliance.show', $offer))
            ->assertOk()
            ->assertSee('This invitation is closed.', false)
            ->assertSee('No longer needed', false);
        $this->assertTrue(\DB::table('notifications')->where('notifiable_id', $engineer->id)->where('data', 'like', '%Check cancelled%')->exists());

        $again = $this->openBooking($landlord, $accountId, $property, $type);
        $this->assertSame($booking->id, $again->id);
    }

    private function superAdmin(): User
    {
        $role = Role::findOrCreate('Super Admin', 'web');
        $permission = Permission::findOrCreate('manage registrations', 'web');
        if (! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }
        $user = User::create([
            'name' => 'Compliance Reviewer',
            'email' => 'compliance-reviewer-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function postStripe(string $url, string $payload, string $secret): \Illuminate\Testing\TestResponse
    {
        $timestamp = time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return $this->call('POST', $url, [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $payload);
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
