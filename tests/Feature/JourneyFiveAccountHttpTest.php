<?php

namespace Tests\Feature;

use App\Enums\CrmNotificationEvent;
use App\Mail\MailManager;
use App\Models\Account;
use App\Models\AuditEvent;
use App\Models\ComplianceRecord;
use App\Models\ComplianceType;
use App\Models\ContractorDocument;
use App\Models\ContractorProfile;
use App\Models\ContractorTrade;
use App\Models\Document;
use App\Models\EmailChangeToken;
use App\Models\Notes;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\PostcodeLocation;
use App\Models\Property;
use App\Models\RentInvoice;
use App\Models\RepairCategory;
use App\Models\RepairIssue;
use App\Models\RepairJobOffer;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\Upload;
use App\Models\User;
use App\Services\Finance\RentFinanceService;
use App\Services\Finance\RentStripeCheckoutService;
use App\Services\Notifications\CrmNotificationService;
use App\Services\Portal\TenantInviteMailer;
use App\Services\Saas\PortalAccessService;
use App\Support\PostcodeGeo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JourneyFiveAccountHttpTest extends TestCase
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

    public function test_a_new_email_waits_for_confirmation_and_ends_other_sessions(): void
    {
        [$tenant, $accountId] = $this->createTenant('Email');
        $old = $tenant->email;
        $next = 'confirmed-'.uniqid().'@resisquare.test';
        $sessionId = 'other-email-'.uniqid();
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $tenant->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'journey-five',
            'payload' => 'a:0:{}',
            'last_activity' => time(),
        ]);

        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->post(route('tenant.profile.update'), [
                'first_name' => 'Nina',
                'last_name' => 'Tenant',
                'email' => $next,
                'phone' => '07111222333',
            ])
            ->assertRedirect(route('tenant.profile'));

        $tenant->refresh();
        $this->assertSame('Nina', $tenant->first_name);
        $this->assertSame('07111222333', $tenant->phone);
        $this->assertSame($old, $tenant->email);
        $this->assertSame($next, $tenant->pending_email);
        $this->assertNull($tenant->email_verified_at);
        $this->assertDatabaseHas('sessions', ['id' => $sessionId]);

        Auth::logout();
        $this->assertTrue(Auth::attempt(['email' => $old, 'password' => 'password']));
        Auth::logout();
        $this->assertFalse(Auth::attempt(['email' => $next, 'password' => 'password']));

        $link = null;
        Mail::assertSent(MailManager::class, function (MailManager $mail) use ($next, &$link) {
            if (! $mail->hasTo($next)) {
                return false;
            }
            if (preg_match('/href="([^"]+)"/', (string) ($mail->array['content'] ?? ''), $match)) {
                $link = html_entity_decode($match[1]);
            }

            return $link !== null;
        });

        $this->get($link)->assertRedirect(route('login'));
        $tenant->refresh();
        $this->assertSame($next, $tenant->email);
        $this->assertNull($tenant->pending_email);
        $this->assertNotNull($tenant->email_verified_at);
        $this->assertSame(0, EmailChangeToken::query()->where('user_id', $tenant->id)->count());
        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);

        Auth::logout();
        $this->assertTrue(Auth::attempt(['email' => $next, 'password' => 'password']));
        Auth::logout();
        $this->assertFalse(Auth::attempt(['email' => $old, 'password' => 'password']));

        $this->get($link)->assertNotFound();

        $expired = EmailChangeToken::query()->create([
            'user_id' => $tenant->id,
            'email' => 'late-'.uniqid().'@resisquare.test',
            'token_hash' => hash('sha256', 'expiredtokenjourneyfive'),
            'expires_at' => now()->subHour(),
        ]);
        $tenant->forceFill(['pending_email' => $expired->email])->save();
        $this->get(route('email.confirm', ['token' => 'expiredtokenjourneyfive']))->assertStatus(410);
        $this->assertSame($next, $tenant->fresh()->email);
    }

    public function test_internal_notes_stay_off_the_portal_and_the_pre_award_job(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->tenantMember($accountId, 'Note');
        $secret = 'INTERNAL-NOTE-J5-'.uniqid();
        $repair = $this->repair($landlord, $accountId, $tenant, '14 Note Lane');
        Notes::query()->forceCreate([
            'account_id' => $accountId,
            'property_id' => $repair->property_id,
            'user_id' => $landlord->id,
            'noteable_type' => Property::class,
            'noteable_id' => $repair->property_id,
            'content' => $secret,
            'type' => 'General',
            'visibility' => 'private',
            'created_by' => $landlord->id,
        ]);
        $contractor = $this->contractor('Notes');
        $this->send($landlord, $accountId, $repair, [$contractor->id]);
        $offer = $this->offerFor($repair, $contractor);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($tenant)->withSession($session)
            ->get(route('backend.home'))
            ->assertOk()
            ->assertDontSee($secret, false);
        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.maintenance'))
            ->assertOk()
            ->assertDontSee($secret, false);
        $this->actingAs($contractor)
            ->get(route('contractor.jobs.show', $offer))
            ->assertOk()
            ->assertDontSee($secret, false)
            ->assertDontSee('14 Note Lane', false);
    }

    public function test_a_certificate_without_a_file_is_not_provided_yet(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => 'Cert House '.uniqid(),
            'city' => 'Leeds',
            'postcode' => 'LS1 1AA',
        ]);
        $gas = ComplianceType::query()->firstOrCreate(
            ['alias' => 'gas'],
            ['name' => 'Gas Safety Certificate', 'description' => 'Annual gas safety']
        );
        ComplianceRecord::create([
            'property_id' => $property->id,
            'compliance_type_id' => $gas->id,
            'issued_date' => now()->subMonth()->toDateString(),
            'expiry_date' => now()->addYears(2)->toDateString(),
            'photos' => null,
        ]);

        $row = collect(ComplianceRecord::certificateGapsForAccount($accountId))
            ->first(fn (array $gap) => (int) $gap['property']->id === (int) $property->id && $gap['type']->alias === 'gas');
        $this->assertNotNull($row);
        $this->assertSame('Missing', $row['state']);
        $this->assertSame('Missing', $row['record']->attentionLabel());

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->get(route('admin.compliance.index'))
            ->assertOk()
            ->assertSee('Missing', false);
    }

    public function test_a_contractor_visit_hides_the_street_until_inspection_is_accepted(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->tenantMember($accountId, 'Visit');
        $street = '14 Willow Cut '.uniqid();
        $repair = $this->repair($landlord, $accountId, $tenant, $street);
        $first = $this->contractor('VisitA');
        $second = $this->contractor('VisitB');
        $this->send($landlord, $accountId, $repair, [$first->id, $second->id]);
        $offerA = $this->offerFor($repair, $first);
        $offerB = $this->offerFor($repair, $second);

        Mail::assertSent(MailManager::class, function (MailManager $mail) use ($street) {
            $body = (string) ($mail->array['content'] ?? '');

            return str_contains($body, 'Manchester M1')
                && ! str_contains($body, $street)
                && ! str_contains($body, 'M1 2AB');
        });

        $this->actingAs($first)->get(route('contractor.jobs.show', $offerA))
            ->assertOk()
            ->assertDontSee($street, false)
            ->assertSee($offerA->invite_expires_at->timezone(config('app.timezone'))->format('D j M, H:i'), false);

        $slot = $repair->tenant_availability->toIso8601String();
        $this->quoteInspection($first, $offerA, $slot);
        $this->quoteInspection($second, $offerB, $slot);
        $this->landlord($landlord, $accountId)
            ->post(route('admin.property_repairs.job_offers.accept', $offerA), ['revision' => 0])
            ->assertSessionHas('success');

        $offerA->refresh();
        $offerB->refresh();
        $visit = now()->addDays(3)->setTime(11, 30);
        $offerA->forceFill(['scheduled_for' => $visit])->save();

        $this->actingAs($first)->get(route('contractor.jobs.show', $offerA))
            ->assertOk()
            ->assertSee($street, false)
            ->assertSee('£', false);
        $this->actingAs($first)->get(route('contractor.jobs.index'))
            ->assertOk()
            ->assertSee($street, false)
            ->assertSee('visit '.$visit->timezone(config('app.timezone'))->format('D j M, H:i'), false);
        $this->actingAs($second)->get(route('contractor.jobs.show', $offerB))
            ->assertOk()
            ->assertDontSee($street, false);
        $this->actingAs($second)->get(route('contractor.jobs.index'))
            ->assertOk()
            ->assertDontSee($street, false);
    }

    public function test_launch_events_notify_by_email_and_in_app(): void
    {
        config(['crm_notifications.enabled' => true]);
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->tenantMember($accountId, 'Notice');
        [, $tenancy] = $this->letFor($accountId, $landlord, $tenant);
        $events = [
            CrmNotificationEvent::TenancyDetailsCorrectionRequested,
            CrmNotificationEvent::TenancyDetailsCorrectionApproved,
            CrmNotificationEvent::TenancyDetailsCorrectionRejected,
            CrmNotificationEvent::FinanceInvoiceIssued,
            CrmNotificationEvent::FinancePaymentReceived,
            CrmNotificationEvent::FinancePaymentFailed,
            CrmNotificationEvent::RepairReported,
            CrmNotificationEvent::RepairJobInvited,
            CrmNotificationEvent::RepairJobOfferSent,
            CrmNotificationEvent::RepairJobAccepted,
            CrmNotificationEvent::RepairJobNotSelected,
            CrmNotificationEvent::RepairJobCompleted,
            CrmNotificationEvent::ComplianceExpiring,
            CrmNotificationEvent::AppointmentReminder,
        ];
        $notifications = app(CrmNotificationService::class);
        foreach ($events as $event) {
            $notifications->dispatch($event, $tenant, [
                'account_id' => $accountId,
                'recipients' => [$tenant],
                'milestone' => 'journey-five-'.$event->value,
                'property_address' => 'Manchester',
                'invoice_number' => 'INV-J5',
                'payment_amount' => '£10.00',
                'notice_subject' => 'Update',
                'repair_reference' => 'REP-J5',
                'compliance_type' => 'Gas safety',
                'appointment_title' => 'Visit',
                'appointment_at' => '1 Jan 2026, 10:00',
            ]);
            $this->assertTrue(
                NotificationLog::query()->where('identifier', $event->value)->where('channel', 'email')->where('notifiable_id', $tenant->id)->exists(),
                $event->value.' email'
            );
            $this->assertTrue(
                NotificationLog::query()->where('identifier', $event->value)->where('channel', 'system')->where('notifiable_id', $tenant->id)->exists(),
                $event->value.' in-app'
            );
        }

        $property = $tenancy->property;
        app(TenantInviteMailer::class)->send($tenant, $property, false);
        $this->assertTrue(NotificationLog::query()->where('identifier', 'tenant.portal_invite')->where('channel', 'system')->where('notifiable_id', $tenant->id)->exists());
        Mail::assertSent(MailManager::class, fn (MailManager $mail) => $mail->hasTo($tenant->email));

        $invoice = app(RentFinanceService::class)->createInvoice($accountId, [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => 80,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
        ]);
        app(RentStripeCheckoutService::class)->noteFailed((object) [
            'id' => 'cs_test_failed',
            'metadata' => ['type' => 'rent_payment', 'rent_invoice_id' => (string) $invoice->id],
        ]);
        $invoice->refresh();
        $this->assertSame(RentInvoice::STATUS_ISSUED, $invoice->status);
        $this->assertEquals(80.0, (float) $invoice->balance);
        $this->assertTrue(NotificationLog::query()->where('identifier', 'finance.payment_failed')->where('channel', 'email')->exists());
    }

    public function test_security_notices_still_send_when_preferences_are_off(): void
    {
        [$tenant, $accountId] = $this->createTenant('Prefs');
        foreach (config('crm_notifications.events', []) as $key => $definition) {
            NotificationPreference::query()->create([
                'account_id' => $accountId,
                'user_id' => $tenant->id,
                'event_key' => $key,
                'email_enabled' => false,
                'in_app_enabled' => false,
            ]);
        }
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $tenant->id,
            'line_1' => 'Pref House '.uniqid(),
            'city' => 'York',
            'postcode' => 'YO1 1AA',
        ]);
        $next = 'pref-'.uniqid().'@resisquare.test';
        $sessionId = 'other-password-'.uniqid();
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $tenant->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'journey-five',
            'payload' => 'a:0:{}',
            'last_activity' => time(),
        ]);

        app(TenantInviteMailer::class)->send($tenant, $property, true);
        Mail::assertSent(MailManager::class, fn (MailManager $mail) => $mail->hasTo($tenant->email));

        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->post(route('tenant.profile.update'), [
                'first_name' => 'Pref',
                'last_name' => 'Tenant',
                'email' => $next,
                'phone' => '07000111222',
            ])
            ->assertRedirect(route('tenant.profile'));
        Mail::assertSent(MailManager::class, fn (MailManager $mail) => $mail->hasTo($next));
        $this->assertSame($tenant->email, $tenant->fresh()->email);

        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->post(route('tenant.profile.password'), [
                'current_password' => 'password',
                'new_password' => 'ChangedPass1!',
                'new_password_confirmation' => 'ChangedPass1!',
            ])
            ->assertRedirect(route('tenant.profile'));
        Mail::assertSent(MailManager::class, fn (MailManager $mail) => ($mail->array['subject'] ?? '') === 'Your password was changed');
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $tenant->id,
            'account_id' => $accountId,
            'event_key' => 'security.password_changed',
        ]);
        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);

        config(['crm_notifications.enabled' => true]);
        app(CrmNotificationService::class)->dispatch(CrmNotificationEvent::TenancyActivated, $tenant, [
            'account_id' => $accountId,
            'recipients' => [$tenant],
            'milestone' => 'locked-tenancy',
            'property_address' => 'York',
            'move_in_date' => '1 Jan 2026',
        ]);
        $this->assertTrue(NotificationLog::query()->where('identifier', 'tenancy.activated')->where('channel', 'email')->where('notifiable_id', $tenant->id)->exists());
    }

    public function test_invite_revoke_void_payment_and_approval_are_audited(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $account = Account::query()->findOrFail($accountId);
        $tenant = $this->person('Audit tenant');
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => 'Audit House '.uniqid(),
            'city' => 'Bristol',
            'postcode' => 'BS1 1AA',
        ]);
        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 900,
            'frequency' => 'Monthly',
            'move_in' => '2024-01-01',
        ]);
        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => false,
        ]);

        $this->actingAs($landlord);
        app(PortalAccessService::class)->inviteTenant($account, $property, $tenant, $tenancy, $landlord);
        $invite = AuditEvent::query()->where('action', 'portal_invite')->where('subject_id', $tenant->id)->first();
        $this->assertNotNull($invite);
        $this->assertSame($landlord->id, (int) $invite->actor_user_id);
        $this->assertFalse((bool) $invite->before['can_login']);
        $this->assertTrue((bool) $invite->after['can_login']);

        app(PortalAccessService::class)->revokePortalAccess($account, $tenant);
        $revoke = AuditEvent::query()->where('action', 'portal_revoke')->where('subject_id', $tenant->id)->first();
        $this->assertNotNull($revoke);
        $this->assertSame($landlord->id, (int) $revoke->actor_user_id);
        $this->assertSame('disabled', $revoke->after['status']);
        $this->assertFalse((bool) $revoke->after['can_login']);

        $finance = app(RentFinanceService::class);
        $payable = $finance->createInvoice($accountId, [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => 50,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
        ]);
        $finance->recordPayment($payable, [
            'amount' => 50,
            'paid_at' => now()->toDateString(),
            'method' => 'bank_transfer',
        ], $landlord->id);
        $payment = AuditEvent::query()->where('action', 'rent_payment')->where('subject_id', $payable->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame($landlord->id, (int) $payment->actor_user_id);
        $this->assertSame('issued', $payment->before['status']);
        $this->assertSame('paid', $payment->after['status']);

        $voidable = $finance->createInvoice($accountId, [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => 40,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
        ]);
        $finance->voidInvoice($voidable);
        $void = AuditEvent::query()->where('action', 'invoice_void')->where('subject_id', $voidable->id)->first();
        $this->assertNotNull($void);
        $this->assertSame($landlord->id, (int) $void->actor_user_id);
        $this->assertSame('issued', $void->before['status']);
        $this->assertSame('void', $void->after['status']);

        $contractor = $this->contractor('AuditCo');
        $profile = $contractor->contractorProfile;
        $profile->forceFill(['approval_status' => ContractorProfile::SUBMITTED])->save();
        $this->actingAs($this->superAdmin())
            ->post(route('admin.registrations.contractors.approve', $profile), [])
            ->assertRedirect();
        $approval = AuditEvent::query()->where('action', 'contractor_approval')->where('subject_id', $profile->id)->first();
        $this->assertNotNull($approval);
        $this->assertNotNull($approval->actor_user_id);
        $this->assertSame(ContractorProfile::SUBMITTED, $approval->before['approval_status']);
        $this->assertSame(ContractorProfile::APPROVED, $approval->after['approval_status']);
    }

    public function test_another_accounts_document_is_not_a_file_and_contractor_files_are_reviewer_only(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->tenantMember($accountId, 'Docs');
        $this->letFor($accountId, $landlord, $tenant);
        [$otherLandlord, $otherAccountId] = $this->createLandlord();
        $otherProperty = Property::create([
            'account_id' => $otherAccountId,
            'created_by' => $otherLandlord->id,
            'line_1' => 'Other House '.uniqid(),
            'city' => 'Bath',
            'postcode' => 'BA1 1AA',
        ]);
        $upload = Upload::create([
            'account_id' => $otherAccountId,
            'file_original_name' => 'private-cert',
            'file_name' => 'private:uploads/private/secret.bin',
            'user_id' => $otherLandlord->id,
            'extension' => 'bin',
            'type' => 'document',
            'file_size' => 13,
        ]);
        Storage::disk('local')->put('uploads/private/secret.bin', 'PRIVATE-BYTES');
        Storage::disk('public')->put('uploads/private/secret.bin', 'PUBLIC-DECOY');
        $document = Document::create([
            'account_id' => $otherAccountId,
            'documentable_type' => Property::class,
            'documentable_id' => $otherProperty->id,
            'upload_ids' => (string) $upload->id,
            'title' => 'Other account certificate',
            'visibility' => 'portal',
            'created_by' => $otherLandlord->id,
        ]);

        $denied = $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->get(route('tenant.documents.download', $document));
        $denied->assertNotFound();
        $this->assertStringNotContainsString('PRIVATE-BYTES', (string) $denied->getContent());

        $profile = $this->contractor('Files')->contractorProfile;
        $file = ContractorDocument::query()->where('contractor_profile_id', $profile->id)->first();
        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->get(route('admin.registrations.contractors.document', [$profile, $file]))
            ->assertForbidden();
        $this->actingAs($this->superAdmin())
            ->get(route('admin.registrations.contractors.document', [$profile, $file]))
            ->assertNotFound();
    }

    public function test_rent_page_uses_pounds_and_uk_dates(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->tenantMember($accountId, 'Money');
        [, $tenancy] = $this->letFor($accountId, $landlord, $tenant);
        $due = now()->addDays(9)->startOfDay();
        app(RentFinanceService::class)->createInvoice($accountId, [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => 1200,
            'issue_date' => now()->toDateString(),
            'due_date' => $due->toDateString(),
        ]);

        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->get(route('tenant.rent'))
            ->assertOk()
            ->assertSee('£1,200.00', false)
            ->assertSee($due->format('j M Y'), false)
            ->assertDontSee($due->format('Y-m-d'), false);
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
            'name' => 'Journey Landlord',
            'email' => 'j5-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole(Role::findOrCreate('Landlord', 'web'));
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

        return [$user, $accountId];
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createTenant(string $label): array
    {
        $user = $this->person($label);
        $user->assignRole(Role::findOrCreate('Tenant', 'web'));
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
            'member_type' => 'tenant',
            'access_level' => 'view',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $accountId];
    }

    private function tenantMember(int $accountId, string $label): User
    {
        $user = $this->person($label);
        $user->assignRole(Role::findOrCreate('Tenant', 'web'));
        DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => 'tenant',
            'access_level' => 'view',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function person(string $label): User
    {
        return User::create([
            'name' => $label.' Tenant',
            'first_name' => $label,
            'last_name' => 'Tenant',
            'email' => strtolower($label).'-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
            'can_login' => 1,
        ]);
    }

    /**
     * @return array{0: Property, 1: Tenancy}
     */
    private function letFor(int $accountId, User $landlord, User $tenant): array
    {
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => 'Let House '.uniqid(),
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
        ]);
        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1200,
            'frequency' => 'Monthly',
            'move_in' => '2024-01-01',
        ]);
        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
        ]);

        return [$property, $tenancy];
    }

    private function landlord(User $landlord, int $accountId): static
    {
        return $this->actingAs($landlord)->withSession(['current_account_id' => $accountId]);
    }

    private function contractor(string $label): User
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
            'latitude' => 53.4808,
            'longitude' => -2.2426,
            'coverage_radius_miles' => 15,
            'phone' => '07123456789',
            'trades' => ['plumbing', 'general'],
            'approval_status' => ContractorProfile::APPROVED,
            'terms_accepted_at' => now(),
        ]);
        foreach (['plumbing', 'general'] as $trade) {
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

    private function repair(User $landlord, int $accountId, User $tenant, string $line): RepairIssue
    {
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => $line,
            'city' => 'Manchester',
            'postcode' => 'M1 2AB',
        ]);
        Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 900,
            'frequency' => 'Monthly',
            'move_in' => '2024-01-01',
        ]);
        $tenancy = Tenancy::query()->where('property_id', $property->id)->first();
        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
        ]);
        $category = RepairCategory::create([
            'name' => 'General '.uniqid(),
            'level' => 0,
            'description' => 'Test',
            'status' => 0,
        ]);

        return RepairIssue::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'repair_category_id' => $category->id,
            'repair_navigation' => json_encode(['area' => 'kitchen']),
            'description' => 'Leaking tap under the sink',
            'tenant_id' => $tenant->id,
            'tenant_availability' => now()->addDay()->setTime(9, 0),
            'access_details' => 'Key in the lockbox',
            'priority' => 'medium',
            'status' => 'Pending',
            'sub_status' => 'Pending',
            'reference_number' => 'REP-'.uniqid(),
        ]);
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

    private function superAdmin(): User
    {
        $role = Role::findOrCreate('Super Admin', 'web');
        $permission = Permission::findOrCreate('manage registrations', 'web');
        if (! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }
        $user = User::create([
            'name' => 'Review Admin',
            'email' => 'j5-admin-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
