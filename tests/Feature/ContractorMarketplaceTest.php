<?php

namespace Tests\Feature;

use App\Mail\MailManager;
use App\Models\ContractorDocument;
use App\Models\ContractorProfile;
use App\Models\ContractorTrade;
use App\Models\Property;
use App\Models\RepairCategory;
use App\Models\RepairContractorRequest;
use App\Models\RepairIssue;
use App\Models\RepairJobInvoice;
use App\Models\RepairJobMessage;
use App\Models\RepairJobOffer;
use App\Models\Registration;
use App\Models\RepairJobVariation;
use App\Models\User;
use App\Services\Contractors\ContractorDirectory;
use App\Services\Contractors\ContractorMarketplaceException;
use App\Services\Contractors\ContractorSms;
use App\Services\Contractors\RepairJobOfferService;
use App\Services\SendSmsService;
use Mockery\MockInterface;
use App\Support\ContractorCompliance;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContractorMarketplaceTest extends TestCase
{
    use DatabaseTransactions;

    private MockInterface $sms;

    protected function setUp(): void
    {
        parent::setUp();
        config(['contractor_marketplace.geocode.enabled' => false]);
        Queue::fake();
        Mail::fake();
        $this->sms = $this->spy(SendSmsService::class);
    }

    public function test_messages_hide_contact_details_until_award_then_flow_both_ways(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId);
        $contractor = $this->contractor('asker');
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => [$contractor->id],
            'urgency' => 'routine',
        ])->assertSessionHas('success');
        $offer = RepairJobOffer::query()->where('repair_issue_id', $repair->id)->firstOrFail();

        $this->actingAs($contractor)->post(route('contractor.jobs.message', $offer), [
            'body' => 'Is the stopcock reachable? Call me on 07123 456789 or bob@example.com',
        ])->assertRedirect(route('contractor.jobs.show', $offer).'#messages');
        $message = RepairJobMessage::query()->where('repair_job_offer_id', $offer->id)->firstOrFail();
        $this->assertTrue((bool) $message->masked);
        $this->assertStringNotContainsString('07123', $message->body);
        $this->assertStringNotContainsString('bob@example.com', $message->body);
        $this->assertStringContainsString('stopcock', $message->body);

        $this->actingAs($contractor)->post(route('contractor.jobs.quote', $offer), [
            'pricing_type' => 'fixed', 'amount' => 100, 'materials' => 'included',
            'visit_slots' => [$repair->fresh()->tenant_availability->toIso8601String()],
        ])->assertSessionHas('success');

        $this->landlord($landlord, $accountId)->get(route('admin.property_repairs.show', $repair))
            ->assertOk()->assertSee('stopcock', false)->assertSee('1 new', false);
        $this->assertNotNull($message->fresh()->read_at);

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.message', $offer), ['body' => 'Yes, under the sink.'])
            ->assertSessionHas('success');
        $this->actingAs($contractor)->get(route('contractor.jobs.show', $offer))->assertOk()->assertSee('Yes, under the sink.', false);

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.accept', $offer), ['revision' => 0])->assertSessionHas('success');
        $this->actingAs($contractor)->post(route('contractor.jobs.message', $offer), ['body' => 'Running late, my number is 07123 456789']);
        $this->assertStringContainsString('07123 456789', RepairJobMessage::query()->where('repair_job_offer_id', $offer->id)->latest('id')->value('body'));

        $stranger = $this->contractor('stranger');
        $this->actingAs($stranger)->post(route('contractor.jobs.message', $offer), ['body' => 'Hello'])->assertForbidden();
    }

    public function test_extra_work_completion_photos_invoice_payment_and_rating(): void
    {
        Storage::fake('local');
        [$landlord, $accountId, $repair, $contractor, $offer] = $this->awardedJob();

        $this->actingAs($contractor)->post(route('contractor.jobs.variation', $offer), ['net_amount' => 50, 'reason' => 'Corroded valve needs replacing'])
            ->assertSessionHas('success');
        $this->actingAs($contractor)->post(route('contractor.jobs.variation', $offer), ['net_amount' => 10, 'reason' => 'Another'])
            ->assertSessionHas('error');
        $variation = RepairJobVariation::query()->where('repair_job_offer_id', $offer->id)->firstOrFail();
        $this->assertSame(RepairJobVariation::PENDING, $variation->status);

        $this->landlord($landlord, $accountId)->get(route('admin.property_repairs.show', $repair))
            ->assertOk()->assertSee('Corroded valve needs replacing', false)->assertSee('Approve £50.00', false);
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.variation', [$offer, $variation]), ['decision' => 'approve'])
            ->assertSessionHas('success');
        $this->assertSame(RepairJobVariation::APPROVED, $variation->fresh()->status);
        $this->assertSame(150.0, $offer->fresh()->load('variations')->finalGross());

        $this->actingAs($contractor)->post(route('contractor.jobs.progress', $offer), [
            'step' => 'complete',
            'completion_note' => 'Replaced valve and washer',
            'photos' => [UploadedFile::fake()->image('after.jpg'), UploadedFile::fake()->image('after2.jpg')],
        ])->assertSessionHas('success');
        $offer->refresh();
        $this->assertSame(RepairJobOffer::JOB_COMPLETED, $offer->job_status);
        $this->assertCount(2, $offer->completion_photos);
        Storage::disk('local')->assertExists($offer->completion_photos[0]);
        $this->landlord($landlord, $accountId)->get(route('admin.property_repairs.job_offers.completion_photo', [$offer, 0]))->assertOk();
        $this->landlord($landlord, $accountId)->get(route('admin.property_repairs.job_offers.completion_photo', [$offer, 5]))->assertNotFound();

        $this->actingAs($contractor)->post(route('contractor.jobs.invoice.store', $offer))
            ->assertSessionHas('error', 'You can invoice once the landlord confirms the job is done.');
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.complete', $offer))->assertSessionHas('success');

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.rate', $offer), ['rating' => 5, 'rating_comment' => 'Tidy and quick'])
            ->assertSessionHas('success');
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.rate', $offer), ['rating' => 1])
            ->assertSessionHas('error');
        $this->assertSame(5, (int) $offer->fresh()->rating);
        $this->assertSame(5.0, app(ContractorDirectory::class)->performance([$contractor->id])[$contractor->id]['rating']);

        $this->actingAs($contractor)->post(route('contractor.jobs.invoice.store', $offer), ['notes' => 'Sort code 00-00-00'])
            ->assertRedirect(route('contractor.jobs.invoice', $offer));
        $invoice = RepairJobInvoice::query()->where('repair_job_offer_id', $offer->id)->firstOrFail();
        $this->assertSame(150.0, (float) $invoice->gross_amount);
        $this->assertCount(2, $invoice->lines);
        $this->assertSame(RepairJobOffer::JOB_INVOICED, $offer->fresh()->job_status);
        $this->assertSame(RepairContractorRequest::COMPLETED, $repair->fresh()->contractorRequest->status);
        $this->actingAs($contractor)->post(route('contractor.jobs.invoice.store', $offer))->assertSessionHas('error');

        $this->actingAs($contractor)->get(route('contractor.jobs.invoice', $offer))
            ->assertOk()->assertSee($invoice->number, false)->assertSee('£150.00', false)->assertDontSee('Tidy and quick', false);
        $this->landlord($landlord, $accountId)->get(route('admin.property_repairs.job_offers.invoice', $offer))
            ->assertOk()->assertSee('Mark as paid', false)->assertSee('Sort code 00-00-00', false);
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.invoice.paid', $offer))->assertSessionHas('success');
        $this->assertSame(RepairJobInvoice::PAID, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);
    }

    public function test_estimate_invoice_respects_the_cap(): void
    {
        [, , , $contractor, $offer] = $this->awardedJob([
            'pricing_type' => 'hourly', 'hourly_rate' => 50, 'estimated_hours' => 2, 'price_cap' => 150, 'materials' => 'included',
        ]);
        $offer->forceFill(['job_status' => RepairJobOffer::JOB_CONFIRMED])->save();

        $this->actingAs($contractor)->post(route('contractor.jobs.invoice.store', $offer), ['final_net' => 200])
            ->assertSessionHas('error');
        $this->actingAs($contractor)->post(route('contractor.jobs.invoice.store', $offer), ['final_net' => 140])
            ->assertRedirect(route('contractor.jobs.invoice', $offer));
        $this->assertSame(140.0, (float) $offer->fresh()->invoice->gross_amount);
    }

    public function test_tenant_and_landlord_can_ask_to_move_the_visit_and_contractor_picks(): void
    {
        [$landlord, $accountId, , $contractor, $offer] = $this->awardedJob();
        $this->assertNotNull($offer->scheduled_for);

        $link = URL::temporarySignedRoute('repair_visit.confirm', now()->addDays(7), ['offer' => $offer->id]);
        $this->get($link)->assertOk()->assertSee('Can’t make it?', false);
        $slot = now()->addDays(3)->setTime(10, 0);
        $this->post($link, [
            'action' => 'reschedule',
            'slots' => [$slot->format('Y-m-d\TH:i'), now()->subDay()->format('Y-m-d\TH:i'), ''],
            'note' => 'At work that day',
        ])->assertSessionHas('success');
        $offer->refresh();
        $this->assertSame('tenant', $offer->reschedule_requested_by);
        $this->assertCount(1, $offer->reschedule_slots);

        $this->actingAs($contractor)->get(route('contractor.jobs.show', $offer))
            ->assertOk()->assertSee('The tenant can’t make the visit.', false)->assertSee($slot->format('D j M, H:i'), false);
        $this->actingAs($contractor)->post(route('contractor.jobs.reschedule', $offer), ['slot' => $offer->reschedule_slots[0]])
            ->assertSessionHas('success');
        $offer->refresh();
        $this->assertTrue($offer->scheduled_for->equalTo($slot));
        $this->assertNotNull($offer->tenant_confirmed_at);
        $this->assertNull($offer->reschedule_requested_at);

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.reschedule', $offer), [
            'slots' => [now()->addDays(4)->setTime(14, 0)->format('Y-m-d\TH:i')],
        ])->assertSessionHas('success');
        $counter = now()->addDays(5)->setTime(8, 30);
        $this->actingAs($contractor)->post(route('contractor.jobs.reschedule', $offer), ['slot' => $counter->format('Y-m-d\TH:i')])
            ->assertSessionHas('success');
        $offer->refresh();
        $this->assertTrue($offer->scheduled_for->equalTo($counter));
        $this->assertNull($offer->tenant_confirmed_at);
    }

    public function test_emergency_requests_and_first_offer_are_texted(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $landlord->forceFill(['phone' => '07999 111222'])->save();
        $repair = $this->repair($landlord, $accountId);
        $contractor = $this->contractor('nightowl');

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => [$contractor->id],
            'urgency' => 'emergency',
        ])->assertSessionHas('success');
        $this->sms->shouldHaveReceived('sendSMS')->withArgs(fn ($to, $from, $text) => $to === '+447123456789' && str_contains($text, 'EMERGENCY'))->once();

        $offer = RepairJobOffer::query()->where('repair_issue_id', $repair->id)->firstOrFail();
        $this->actingAs($contractor)->post(route('contractor.jobs.quote', $offer), [
            'pricing_type' => 'fixed', 'amount' => 180, 'materials' => 'included',
            'proposed_slot' => now()->addHours(3)->format('Y-m-d\TH:i'),
        ])->assertSessionHas('success');
        $this->sms->shouldHaveReceived('sendSMS')->withArgs(fn ($to, $from, $text) => $to === '+447999111222' && str_contains($text, 'Emergency repair'))->once();

        $this->assertSame('+447123456789', ContractorSms::normalise('07123 456789'));
        $this->assertSame('+447123456789', ContractorSms::normalise('+44 7123 456789'));
        $this->assertNull(ContractorSms::normalise('12345'));
    }

    public function test_landlord_finds_sends_compares_and_accepts_with_version_check(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId);
        $winner = $this->contractor('winner');
        $other = $this->contractor('other');

        $this->actingAs($winner)->get(route('contractor.jobs.index'))
            ->assertOk()->assertSee('No new jobs', false)->assertDontSee('Clear Cache', false);

        $this->landlord($landlord, $accountId)->get(route('admin.property_repairs.marketplace.find', $repair))
            ->assertOk()->assertSee('Winner Contractor', false)->assertSee('Insured until', false);

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => [$winner->id, $other->id],
            'urgency' => 'routine',
        ])->assertRedirect()->assertSessionHas('success');

        $request = RepairContractorRequest::query()->where('repair_issue_id', $repair->id)->firstOrFail();
        $this->assertSame(RepairContractorRequest::SENT, $request->status);
        $this->assertSame(2, $request->offers()->count());

        $offer = RepairJobOffer::query()->where('repair_issue_id', $repair->id)->where('contractor_user_id', $winner->id)->firstOrFail();
        $this->actingAs($winner)->get(route('contractor.jobs.show', $offer))
            ->assertOk()
            ->assertDontSee('12 Secret Road', false)
            ->assertDontSee('Tina', false)
            ->assertSee('M1', false);
        $this->assertSame(RepairJobOffer::VIEWED, $offer->fresh()->status);

        $slot = $repair->fresh()->tenant_availability->toIso8601String();
        $this->actingAs($winner)->post(route('contractor.jobs.quote', $offer), [
            'pricing_type' => 'fixed',
            'amount' => 120,
            'materials' => 'included',
            'visit_slots' => [$slot],
        ])->assertRedirect()->assertSessionHas('success');
        $offer->refresh();
        $this->assertSame(RepairJobOffer::OFFER_SENT, $offer->status);
        $this->assertSame(120.0, (float) $offer->gross_amount);

        $otherOffer = RepairJobOffer::query()->where('repair_issue_id', $repair->id)->where('contractor_user_id', $other->id)->firstOrFail();
        $this->actingAs($other)->post(route('contractor.jobs.quote', $otherOffer), [
            'pricing_type' => 'callout_hourly',
            'call_out_fee' => 50,
            'hourly_rate' => 40,
            'estimated_hours' => 2,
            'materials' => 'excluded',
            'proposed_slot' => now()->addDays(2)->setTime(13, 0)->format('Y-m-d\TH:i'),
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame(130.0, (float) $otherOffer->fresh()->gross_amount);

        $this->landlord($landlord, $accountId)->get(route('admin.property_repairs.job_offers.confirm', $offer))
            ->assertOk()->assertSee('Accept this offer?', false)->assertSee('£120.00', false);

        $this->actingAs($winner)->post(route('contractor.jobs.quote', $offer), [
            'pricing_type' => 'fixed',
            'amount' => 110,
            'materials' => 'included',
            'visit_slots' => [$slot],
        ])->assertSessionHas('success');
        $this->assertSame(1, (int) $offer->fresh()->revision);

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.accept', $offer), ['revision' => 0])
            ->assertSessionHas('error', 'This offer was just updated. Check the new price, then accept.');
        $this->assertSame(RepairJobOffer::OFFER_SENT, $offer->fresh()->status);

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.accept', $offer), ['revision' => 1])
            ->assertSessionHas('success');

        $offer->refresh();
        $this->assertSame(RepairJobOffer::JOB_ACCEPTED, $offer->status);
        $this->assertSame(RepairJobOffer::JOB_SCHEDULED, $offer->job_status);
        $this->assertSame($offer->id, (int) $repair->fresh()->awarded_offer_id);
        $this->assertSame(RepairJobOffer::NOT_SELECTED, $otherOffer->fresh()->status);
        $this->assertSame(RepairContractorRequest::AWARDED, $request->fresh()->status);
        Mail::assertSent(MailManager::class, fn (MailManager $mail) => $mail->hasTo($winner->email)
            && collect($mail->array['attachments'] ?? [])->contains(fn ($a) => ($a['name'] ?? null) === 'repair-visit.ics'));

        $this->actingAs($winner)->get(route('contractor.jobs.show', $offer))
            ->assertOk()->assertSee('12 Secret Road', false)->assertSee('Tina', false)->assertSee('Key in the lockbox', false);

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.accept', $otherOffer), ['revision' => 0])
            ->assertSessionHas('error');
    }

    public function test_gas_repair_is_a_hard_block_for_a_plumber(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId, ['category' => 'Gas boiler '.uniqid(), 'description' => 'Boiler will not light']);
        $plumber = $this->contractor('plumber');

        $this->assertSame('gas', ContractorCompliance::requiredTrade($repair));
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => [$plumber->id],
            'urgency' => 'routine',
        ])->assertSessionHas('error');
        $this->assertSame(0, RepairJobOffer::query()->where('repair_issue_id', $repair->id)->count());

        $gasEngineer = $this->contractor('gasman', ['gas']);
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => [$gasEngineer->id],
            'urgency' => 'routine',
        ])->assertSessionHas('success');
    }

    public function test_a_send_is_capped_unless_it_is_an_emergency(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId);
        $ids = collect(range(1, 6))->map(fn ($i) => $this->contractor('cap'.$i)->id)->all();

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => $ids,
            'urgency' => 'routine',
        ])->assertSessionHas('error');
        $this->assertSame(0, RepairJobOffer::query()->where('repair_issue_id', $repair->id)->count());

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => $ids,
            'urgency' => 'emergency',
        ])->assertSessionHas('success');
        $this->assertSame(6, RepairJobOffer::query()->where('repair_issue_id', $repair->id)->count());
        $this->assertTrue(RepairJobOffer::query()->where('repair_issue_id', $repair->id)->first()->invite_expires_at->lte(now()->addHours(3)));
    }

    public function test_contractor_onboarding_business_basics_documents_and_submit(): void
    {
        Storage::fake('local');
        $user = $this->contractorUser('newbie');

        $this->actingAs($user)->get(route('contractor.profile.edit'))->assertOk()->assertSee('Step 2 of 3', false);

        $this->actingAs($user)->post(route('contractor.profile.business'), [
            'trading_type' => 'sole_trader',
            'trades' => ['gas', 'plumbing'],
            'postcode' => 'm1 1aa',
            'coverage_radius_miles' => 15,
            'vat_registered' => 0,
        ])->assertRedirect(route('contractor.profile.edit'));

        $profile = $user->fresh()->contractorProfile;
        $this->assertSame('M1 1AA', $profile->postcode);
        $this->assertSame(2, $profile->tradeApprovals()->where('status', ContractorTrade::PENDING)->count());
        $this->actingAs($user)->get(route('contractor.profile.edit'))->assertOk()->assertSee('Gas Safe', false);

        $this->actingAs($user)->post(route('contractor.profile.documents'), [
            'docs' => ['photo_id' => ['file' => UploadedFile::fake()->image('id.jpg')]],
            'submit' => 1,
        ])->assertSessionHas('error');
        $this->assertSame(ContractorProfile::DRAFT, $profile->fresh()->approval_status);

        $this->actingAs($user)->post(route('contractor.profile.documents'), [
            'docs' => [
                'public_liability' => [
                    'file' => UploadedFile::fake()->create('pl.pdf', 100, 'application/pdf'),
                    'cover_amount' => 2000000,
                    'expires_at' => now()->addYear()->toDateString(),
                ],
                'gas_safe' => ['reference' => '123456', 'expires_at' => now()->addMonths(6)->toDateString()],
            ],
            'submit' => 1,
        ])->assertRedirect(route('contractor.jobs.index'));

        $profile->refresh();
        $this->assertSame(ContractorProfile::SUBMITTED, $profile->approval_status);
        $id = $profile->document('photo_id');
        $this->assertTrue($id->encrypted);
        $this->assertStringNotContainsString('JFIF', Storage::disk('local')->get($id->path));
        $this->actingAs($user)->get(route('contractor.jobs.index'))
            ->assertOk()
            ->assertSee("You're registered", false)
            ->assertSee('contractor-hold-body', false)
            ->assertSee('Jobs unlock after approval', false)
            ->assertDontSee('Replace file', false);
        $this->actingAs($user)->get(route('contractor.profile.edit'))
            ->assertRedirect(route('contractor.jobs.index'));

        ContractorProfile::query()->where('company_number', 'TS123456')->update(['company_number' => null]);
        $profile->forceFill(['company_number' => 'TS123456'])->save();
        $second = $this->contractorUser('copycat');
        $this->actingAs($second)->post(route('contractor.profile.business'), [
            'trading_type' => 'limited_company',
            'company_number' => 'TS123456',
            'trades' => ['plumbing'],
            'postcode' => 'M1 1AA',
            'coverage_radius_miles' => 15,
            'vat_registered' => 0,
        ])->assertSessionHasErrors('company_number');
    }

    public function test_registration_page_shows_contractor_documents_for_review(): void
    {
        $admin = $this->superAdmin();
        $user = $this->contractorUser('docs-on-reg');
        $profile = ContractorProfile::create([
            'user_id' => $user->id,
            'trading_type' => 'sole_trader',
            'legal_name' => 'Docs Onreg',
            'postcode' => 'E14 9RU',
            'coverage_radius_miles' => 15,
            'trades' => ['general', 'refrigeration'],
            'approval_status' => ContractorProfile::SUBMITTED,
            'submitted_at' => now(),
        ]);
        ContractorCompliance::syncTrades($profile, ['general', 'refrigeration']);
        $this->doc($profile, 'photo_id', [
            'status' => ContractorDocument::PENDING,
            'original_name' => 'passport.jpg',
            'mime' => 'image/jpeg',
        ]);
        $registration = Registration::create([
            'first_name' => 'Docs',
            'last_name' => 'Onreg',
            'email' => $user->email,
            'type' => 'contractor',
            'status' => 'verified',
            'user_id' => $user->id,
            'otp_verified_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.registrations.show', $registration))
            ->assertOk()
            ->assertSee('Documents to review', false)
            ->assertSee('Photo ID', false)
            ->assertSee('passport.jpg', false)
            ->assertSee('F-Gas certificate', false)
            ->assertSee('Not uploaded', false)
            ->assertDontSee('Permissions will be available after the registration is approved.', false);
    }

    public function test_one_decision_approves_the_whole_contractor_pack(): void
    {
        $admin = $this->superAdmin();
        $user = $this->contractorUser('pack');
        $profile = ContractorProfile::create([
            'user_id' => $user->id,
            'trading_type' => 'sole_trader',
            'legal_name' => 'Pack Contractor',
            'postcode' => 'E14 9RU',
            'coverage_radius_miles' => 15,
            'trades' => ['general', 'refrigeration'],
            'approval_status' => ContractorProfile::SUBMITTED,
            'submitted_at' => now(),
        ]);
        ContractorCompliance::syncTrades($profile, ['general', 'refrigeration']);
        $this->doc($profile, 'photo_id', ['status' => ContractorDocument::PENDING]);
        $this->doc($profile, 'public_liability', [
            'status' => ContractorDocument::PENDING,
            'cover_amount' => 2000000,
            'expires_at' => now()->addYear(),
        ]);
        $this->doc($profile, 'fgas', [
            'status' => ContractorDocument::PENDING,
            'reference' => 'FG-1',
            'expires_at' => now()->addYear(),
        ]);

        $this->actingAs($admin)->post(route('admin.registrations.contractors.pack', $profile), [
            'decision' => 'reject',
        ])->assertSessionHasErrors('review_note');

        $this->actingAs($admin)->post(route('admin.registrations.contractors.pack', $profile), [
            'decision' => 'approve',
        ])->assertSessionHas('success');

        $profile->refresh()->load(['documents', 'tradeApprovals']);
        $this->assertSame(ContractorProfile::APPROVED, $profile->approval_status);
        $this->assertSame(ContractorDocument::APPROVED, $profile->document('photo_id')->status);
        $this->assertSame(ContractorDocument::APPROVED, $profile->document('fgas')->status);
        $this->assertTrue($profile->tradeApprovals->every(fn ($trade) => $trade->status === ContractorTrade::APPROVED));
    }

    public function test_admin_decides_per_document_and_per_trade_then_approves(): void
    {
        $admin = $this->superAdmin();
        $user = $this->contractorUser('review');
        $profile = ContractorProfile::create([
            'user_id' => $user->id,
            'trading_type' => 'sole_trader',
            'legal_name' => 'Review Contractor',
            'postcode' => 'M1 1AA',
            'trades' => ['gas', 'plumbing'],
            'approval_status' => ContractorProfile::SUBMITTED,
            'submitted_at' => now()->subDays(5),
        ]);
        ContractorCompliance::syncTrades($profile, ['gas', 'plumbing']);
        $photo = $this->doc($profile, 'photo_id', ['status' => ContractorDocument::PENDING]);
        $pl = $this->doc($profile, 'public_liability', ['status' => ContractorDocument::PENDING, 'cover_amount' => 2000000, 'expires_at' => now()->addYear()]);
        $gas = $this->doc($profile, 'gas_safe', ['status' => ContractorDocument::PENDING, 'reference' => '555555', 'path' => null]);

        $this->actingAs($admin)->get(route('admin.registrations.contractors.index'))->assertOk()->assertSee('Review Contractor', false)->assertSee('over SLA', false);
        $this->actingAs($admin)->get(route('admin.registrations.contractors.show', $profile))->assertOk()->assertSee('Check the register', false);

        $this->actingAs($admin)->post(route('admin.registrations.contractors.document.decide', [$profile, $photo]), [
            'decision' => 'reject',
            'reason_code' => 'unreadable',
        ])->assertSessionHas('success');
        $this->assertSame(ContractorDocument::REJECTED, $photo->fresh()->status);

        $this->actingAs($admin)->post(route('admin.registrations.contractors.changes', $profile))->assertSessionHas('success');
        $this->assertSame(ContractorProfile::CHANGES, $profile->fresh()->approval_status);
        Mail::assertSent(MailManager::class, fn (MailManager $mail) => $mail->hasTo($user->email) && str_contains($mail->array['content'], 'Unreadable'));

        $this->actingAs($admin)->post(route('admin.registrations.contractors.trade.decide', [$profile, 'gas']), ['decision' => 'approved'])
            ->assertSessionHas('error');

        $photo->forceFill(['status' => ContractorDocument::PENDING])->save();
        foreach ([$photo, $pl] as $doc) {
            $this->actingAs($admin)->post(route('admin.registrations.contractors.document.decide', [$profile, $doc]), ['decision' => 'approve'])
                ->assertSessionHas('success');
        }
        $this->actingAs($admin)->post(route('admin.registrations.contractors.document.decide', [$profile, $gas]), ['decision' => 'approve'])
            ->assertSessionHas('error');
        $this->actingAs($admin)->post(route('admin.registrations.contractors.document.decide', [$profile, $gas]), [
            'decision' => 'approve',
            'expires_at' => now()->addMonths(8)->toDateString(),
        ])->assertSessionHas('success');

        $this->actingAs($admin)->post(route('admin.registrations.contractors.trade.decide', [$profile, 'gas']), ['decision' => 'approved'])->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.registrations.contractors.trade.decide', [$profile, 'plumbing']), ['decision' => 'rejected', 'reason' => 'No plumbing evidence'])->assertSessionHas('success');

        $this->actingAs($admin)->post(route('admin.registrations.contractors.approve', $profile))->assertSessionHas('success');
        $profile->refresh()->load(['documents', 'tradeApprovals']);
        $this->assertSame(ContractorProfile::APPROVED, $profile->approval_status);
        $this->assertSame(['gas'], ContractorCompliance::approvedTrades($profile));
        $this->assertSame(['Insured until '.now()->addYear()->format('M Y'), 'Gas Safe verified'], ContractorCompliance::badges($profile));
    }

    public function test_renewal_keeps_the_contractor_working_and_replaces_the_document_on_approval(): void
    {
        Storage::fake('local');
        $admin = $this->superAdmin();
        $contractor = $this->contractor('renewer');
        $profile = $contractor->contractorProfile;

        $this->actingAs($contractor)->post(route('contractor.profile.documents'), [
            'docs' => ['public_liability' => [
                'file' => UploadedFile::fake()->create('new-pl.pdf', 50, 'application/pdf'),
                'cover_amount' => 5000000,
                'expires_at' => now()->addYears(2)->toDateString(),
            ]],
            'submit' => 1,
        ])->assertRedirect();

        $renewal = $profile->documents()->where('type', 'public_liability.renewal')->firstOrFail();
        $this->assertSame(ContractorDocument::PENDING, $renewal->status);
        $this->assertTrue(ContractorCompliance::canReceiveWork($profile->fresh(['documents', 'tradeApprovals'])));

        $this->actingAs($admin)->post(route('admin.registrations.contractors.document.decide', [$profile, $renewal]), ['decision' => 'approve'])
            ->assertSessionHas('success');
        $live = $profile->documents()->where('type', 'public_liability')->firstOrFail();
        $this->assertSame(now()->addYears(2)->toDateString(), $live->expires_at->toDateString());
        $this->assertSame(5000000.0, (float) $live->cover_amount);
        $this->assertFalse($profile->documents()->where('type', 'public_liability.renewal')->exists());
    }

    public function test_sweep_reminds_pauses_lapsed_insurance_and_withdraws_open_offers(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId);
        $contractor = $this->contractor('lapsing');
        $profile = $contractor->contractorProfile;
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => [$contractor->id],
            'urgency' => 'routine',
        ])->assertSessionHas('success');
        $offer = RepairJobOffer::query()->where('repair_issue_id', $repair->id)->firstOrFail();

        $photo = $profile->document('photo_id');
        $photo->forceFill(['expires_at' => now()->addDays(10)])->save();
        $profile->document('public_liability')->forceFill(['expires_at' => now()->subDay()])->save();

        $this->artisan('contractors:sweep')->assertSuccessful();

        $profile->refresh();
        $this->assertNotNull($profile->paused_at);
        $this->assertSame('Paused', $profile->statusLabel());
        $this->assertSame(RepairJobOffer::WITHDRAWN, $offer->fresh()->status);
        $this->assertSame(14, (int) $photo->fresh()->reminder_stage);
        $this->assertNotNull($profile->document('public_liability')->expired_notified_at);
    }

    public function test_inspection_first_then_firm_price_reconfirm_and_cancel_after_award(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId);
        $contractor = $this->contractor('inspector');
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => [$contractor->id],
            'urgency' => 'urgent',
        ])->assertSessionHas('success');
        $offer = RepairJobOffer::query()->where('repair_issue_id', $repair->id)->firstOrFail();
        $slot = $repair->fresh()->tenant_availability->toIso8601String();

        $this->actingAs($contractor)->post(route('contractor.jobs.quote', $offer), [
            'pricing_type' => 'inspection',
            'inspection_fee' => 0,
            'materials' => 'included',
            'visit_slots' => [$slot],
        ])->assertSessionHas('success');
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.accept', $offer), ['revision' => 0])->assertSessionHas('success');
        $this->assertSame(RepairJobOffer::INSPECTION_ACCEPTED, $offer->fresh()->status);
        $this->assertNull($repair->fresh()->awarded_offer_id);
        $this->actingAs($contractor)->get(route('contractor.jobs.show', $offer))->assertSee('12 Secret Road', false);

        $this->actingAs($contractor)->post(route('contractor.jobs.quote', $offer), [
            'pricing_type' => 'fixed',
            'amount' => 200,
            'materials' => 'included',
            'visit_slots' => [$slot],
        ])->assertSessionHas('success');
        $this->assertSame(RepairJobOffer::OFFER_SENT, $offer->fresh()->status);
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.accept', $offer), ['revision' => (int) $offer->fresh()->revision])->assertSessionHas('success');
        $this->assertSame(RepairJobOffer::JOB_ACCEPTED, $offer->fresh()->status);

        $this->actingAs($contractor)->post(route('contractor.jobs.cancel', $offer), ['reason' => 'Van broke down'])->assertSessionHas('success');
        $this->assertSame(RepairJobOffer::CANCELLED, $offer->fresh()->status);
        $this->assertNull($repair->fresh()->awarded_offer_id);
        $this->assertSame(RepairContractorRequest::AWAITING, $repair->fresh()->contractorRequest->status);

        $second = $this->contractor('reconfirmer');
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => [$second->id],
            'urgency' => 'routine',
        ])->assertSessionHas('success');
        $late = RepairJobOffer::query()->where('repair_issue_id', $repair->id)->where('contractor_user_id', $second->id)->firstOrFail();
        $this->actingAs($second)->post(route('contractor.jobs.quote', $late), [
            'pricing_type' => 'fixed', 'amount' => 90, 'materials' => 'included', 'visit_slots' => [$slot],
        ])->assertSessionHas('success');
        $late->forceFill(['offer_expires_at' => now()->subMinute()])->save();

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.accept', $late), ['revision' => 0])
            ->assertSessionHas('error', 'This offer has expired. Ask the contractor to reconfirm it.');
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.reconfirm', $late))->assertSessionHas('success');
        $this->actingAs($second)->post(route('contractor.jobs.reconfirm', $late))->assertSessionHas('success');
        $this->assertSame(RepairJobOffer::OFFER_SENT, $late->fresh()->status);
        $this->assertTrue($late->fresh()->offer_expires_at->isFuture());
    }

    public function test_offer_price_applies_vat_and_validates_cap(): void
    {
        $contractor = $this->contractor('vatman');
        $profile = $contractor->contractorProfile;
        $profile->forceFill(['vat_registered' => true, 'vat_number' => 'GB123456789'])->save();
        $service = app(RepairJobOfferService::class);

        $priced = $service->price($profile, [
            'pricing_type' => 'callout_hourly', 'call_out_fee' => 50, 'hourly_rate' => 40, 'estimated_hours' => 2,
            'materials' => 'estimate', 'materials_estimate' => 20, 'price_cap' => 200, 'vat_rate' => 20,
        ]);
        $this->assertSame(150.0, $priced['net_amount']);
        $this->assertSame(30.0, $priced['vat_amount']);
        $this->assertSame(180.0, $priced['gross_amount']);
        $this->assertTrue($priced['price_is_estimate']);

        $this->expectException(ContractorMarketplaceException::class);
        $service->price($profile, ['pricing_type' => 'hourly', 'hourly_rate' => 40, 'estimated_hours' => 3, 'price_cap' => 100, 'materials' => 'included']);
    }

    public function test_edits_after_send_flag_offers_and_cancel_closes_invites(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId);
        $a = $this->contractor('edit-a');
        $b = $this->contractor('edit-b');
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => [$a->id, $b->id],
            'urgency' => 'routine',
        ])->assertSessionHas('success');
        $offerA = RepairJobOffer::query()->where('repair_issue_id', $repair->id)->where('contractor_user_id', $a->id)->firstOrFail();
        $this->actingAs($a)->post(route('contractor.jobs.quote', $offerA), [
            'pricing_type' => 'fixed', 'amount' => 75, 'materials' => 'included',
            'visit_slots' => [$repair->fresh()->tenant_availability->toIso8601String()],
        ])->assertSessionHas('success');

        $repair->fresh()->update(['description' => 'Leaking tap and the pipe under it']);
        $this->assertNotNull($offerA->fresh()->details_changed_at);

        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.marketplace.close', $repair), ['outcome' => 'cancelled', 'reason' => 'Tenant fixed it'])
            ->assertSessionHas('success');
        $this->assertSame(RepairJobOffer::NOT_SELECTED, $offerA->fresh()->status);
        $this->assertSame(RepairJobOffer::WITHDRAWN, RepairJobOffer::query()->where('repair_issue_id', $repair->id)->where('contractor_user_id', $b->id)->value('status'));
        $this->assertSame(RepairContractorRequest::CANCELLED, $repair->fresh()->contractorRequest->status);
    }

    public function test_legacy_routes_are_hidden_and_old_contractors_can_claim_an_account(): void
    {
        config(['contractor_marketplace.legacy_module_enabled' => false]);
        $this->get(URL::signedRoute('repair-quotes.show', ['assignment' => 1, 'token' => 'x']))->assertNotFound();

        $admin = $this->superAdmin();
        $legacy = User::create([
            'name' => 'Old Plumber',
            'first_name' => 'Old',
            'email' => 'legacy-'.uniqid().'@resisquare.test',
            'password' => bcrypt('unknown-'.uniqid()),
            'status' => 1,
        ]);
        $legacy->assignRole(Role::findOrCreate('Contractor', 'web'));

        $this->actingAs($admin)->post(route('admin.registrations.contractors.legacy'))->assertSessionHas('success');
        $profile = $legacy->fresh()->contractorProfile;
        $this->assertTrue($profile->legacy_contractor);
        $this->assertNotNull($profile->claim_invited_at);
        Mail::assertSent(MailManager::class, fn (MailManager $mail) => $mail->hasTo($legacy->email));

        auth()->logout();
        $link = URL::temporarySignedRoute('contractor.claim', now()->addDay(), ['user' => $legacy->id]);
        $this->get($link)->assertOk()->assertSee('Claim your account', false);
        $this->post($link, ['password' => 'new-password-1', 'password_confirmation' => 'new-password-1', 'terms' => 1])
            ->assertRedirect(route('contractor.profile.edit', ['step' => 'business']));
        $this->assertAuthenticatedAs($legacy->fresh());
        $this->assertNotNull($profile->fresh()->terms_accepted_at);
        $this->get($link)->assertNotFound();
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Landlord accepted a £100 fixed offer for the tenant's slot.
     *
     * @param  array<string, mixed>  $quote
     * @return array{0: User, 1: int, 2: RepairIssue, 3: User, 4: RepairJobOffer}
     */
    private function awardedJob(array $quote = []): array
    {
        [$landlord, $accountId] = $this->createLandlord();
        $repair = $this->repair($landlord, $accountId);
        $contractor = $this->contractor('awarded');
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.store', $repair), [
            'contractor_ids' => [$contractor->id],
            'urgency' => 'routine',
        ])->assertSessionHas('success');
        $offer = RepairJobOffer::query()->where('repair_issue_id', $repair->id)->firstOrFail();
        $this->actingAs($contractor)->post(route('contractor.jobs.quote', $offer), ($quote ?: ['pricing_type' => 'fixed', 'amount' => 100, 'materials' => 'included']) + [
            'visit_slots' => [$repair->fresh()->tenant_availability->toIso8601String()],
        ])->assertSessionHas('success');
        $this->landlord($landlord, $accountId)->post(route('admin.property_repairs.job_offers.accept', $offer), ['revision' => 0])->assertSessionHas('success');

        return [$landlord, $accountId, $repair, $contractor, $offer->fresh()];
    }

    private function landlord(User $landlord, int $accountId): static
    {
        return $this->actingAs($landlord)->withSession(['current_account_id' => $accountId]);
    }

    private function contractorUser(string $label): User
    {
        $user = User::create([
            'name' => ucfirst($label).' Contractor',
            'email' => $label.'-'.uniqid().'@resisquare.test',
            'phone' => '07123456789',
            'password' => bcrypt('password'),
            'status' => 1,
            'can_login' => 1,
        ]);
        $user->assignRole(Role::findOrCreate('Contractor', 'web'));

        return $user;
    }

    /**
     * Approved, in-date contractor based in M1.
     *
     * @param  array<int, string>  $trades
     */
    private function contractor(string $label, array $trades = ['plumbing', 'general']): User
    {
        $user = $this->contractorUser($label);
        $profile = ContractorProfile::create([
            'user_id' => $user->id,
            'trading_type' => 'sole_trader',
            'legal_name' => ucfirst($label).' Contractor',
            'vat_registered' => false,
            'cis_status' => 'unverified',
            'employs_staff' => false,
            'postcode' => 'M1 1AA',
            'coverage_radius_miles' => 15,
            'phone' => '07123456789',
            'trades' => $trades,
            'approval_status' => ContractorProfile::APPROVED,
            'terms_accepted_at' => now(),
        ]);
        foreach ($trades as $trade) {
            ContractorTrade::create(['contractor_profile_id' => $profile->id, 'trade' => $trade, 'status' => ContractorTrade::APPROVED]);
        }
        $this->doc($profile, 'photo_id');
        $this->doc($profile, 'public_liability', ['cover_amount' => 2000000, 'expires_at' => now()->addYear()]);
        if (in_array('gas', $trades, true)) {
            $this->doc($profile, 'gas_safe', ['reference' => '123456', 'expires_at' => now()->addYear(), 'path' => null]);
        }

        return $user->fresh();
    }

    private function doc(ContractorProfile $profile, string $type, array $attributes = []): ContractorDocument
    {
        return ContractorDocument::create($attributes + [
            'contractor_profile_id' => $profile->id,
            'type' => $type,
            'status' => ContractorDocument::APPROVED,
            'disk' => 'local',
            'path' => 'contractor-documents/test/'.$type.'.bin',
            'original_name' => $type.'.pdf',
            'submitted_at' => now(),
        ]);
    }

    private function superAdmin(): User
    {
        $role = Role::findOrCreate('Super Admin', 'web');
        $permission = Permission::findOrCreate('manage registrations', 'web');
        if (! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }
        $user = User::create([
            'name' => 'Contractor Reviewer',
            'email' => 'reviewer-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        return $user;
    }

    /**
     * @param  array<string, string>  $overrides
     */
    private function repair(User $landlord, int $accountId, array $overrides = []): RepairIssue
    {
        $tenant = User::create([
            'name' => 'Tenant Person',
            'first_name' => 'Tina',
            'last_name' => 'Tenant',
            'email' => 'tenant-'.uniqid().'@resisquare.test',
            'phone' => '07000000000',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $tenant->assignRole(Role::findOrCreate('Tenant', 'web'));
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '12 Secret Road',
            'city' => 'Manchester',
            'postcode' => 'M1 2AB',
        ]);
        $category = RepairCategory::create(['name' => $overrides['category'] ?? 'General maintenance '.uniqid(), 'level' => 0, 'description' => 'Test', 'status' => 0]);

        return RepairIssue::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'repair_category_id' => $category->id,
            'repair_navigation' => json_encode(['area' => 'kitchen']),
            'description' => $overrides['description'] ?? 'Leaking tap under the sink',
            'tenant_id' => $tenant->id,
            'tenant_availability' => now()->addDay()->setTime(9, 0),
            'access_details' => 'Key in the lockbox',
            'priority' => 'medium',
            'status' => 'Pending',
            'sub_status' => 'Pending',
            'reference_number' => 'REP-'.uniqid(),
        ]);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $user = User::create([
            'name' => 'Marketplace Landlord',
            'email' => 'market-landlord-'.uniqid().'@resisquare.test',
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
}
