<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationJob;
use App\Models\NotificationLog;
use App\Models\Property;
use App\Models\RentInvoice;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression cover for the production-hardening fixes on fix/reported-site-issues.
 */
class ProductionHardeningHttpTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();
    }

    // 1
    public function test_landlord_cannot_grant_themselves_super_admin(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $superRole = Role::findOrCreate('Super Admin', 'web');
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.users.store'), [
                'step' => 1,
                'user_id' => $landlord->id,
                'role_ids' => [$superRole->id],
            ])
            ->assertForbidden();
        $this->assertFalse($landlord->fresh()->hasRole('Super Admin'));

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.users.update', $landlord->id), [
                'first_name' => 'Land',
                'last_name' => 'Lord',
                'phone' => '07123456789',
                'email' => $landlord->email,
                'address_line_1' => '1 Street',
                'postcode' => 'SW1A 1AA',
                'city' => 'London',
                'country' => 'UK',
                'status' => 1,
                'role_ids' => [$superRole->id],
            ])
            ->assertForbidden();
        $this->assertFalse($landlord->fresh()->hasRole('Super Admin'));
    }

    // 2
    public function test_landlord_cannot_edit_a_user_from_another_account(): void
    {
        [$landlordA, $accountA] = $this->createLandlord();
        [, $accountB] = $this->createLandlord();
        $foreign = $this->createMember($accountB, 'Tenant', 'tenant');
        $originalName = $foreign->name;
        $originalEmail = $foreign->email;

        $this->actingAs($landlordA)->withSession(['current_account_id' => $accountA])
            ->post(route('admin.users.store'), [
                'step' => 2,
                'user_id' => $foreign->id,
                'first_name' => 'Hijacked',
                'last_name' => 'Name',
                'phone' => '07000000000',
                'email' => 'hijacked-'.uniqid().'@resisquare.test',
                'address_line_1' => '1 Evil Road',
                'postcode' => 'E1 1AA',
                'city' => 'London',
            ])
            ->assertForbidden();

        $foreign->refresh();
        $this->assertSame($originalName, $foreign->name);
        $this->assertSame($originalEmail, $foreign->email);
        $this->assertFalse(DB::table('account_users')
            ->where('account_id', $accountA)->where('user_id', $foreign->id)->exists());
    }

    // 3
    public function test_landlord_delete_removes_contact_from_account_but_keeps_the_login(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->createMember($accountId, 'Tenant', 'tenant');

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.users.delete', $tenant->id))
            ->assertOk();

        $this->assertNotNull(User::find($tenant->id));
        $this->assertFalse(DB::table('account_users')
            ->where('account_id', $accountId)->where('user_id', $tenant->id)->exists());
    }

    // 4
    public function test_tenant_cannot_change_landlord_bank_details_but_can_change_their_own(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->createMember($accountId, 'Tenant', 'tenant');
        $session = ['current_account_id' => $accountId];

        $bankId = DB::table('bank_details')->insertGetId([
            'account_id' => $accountId,
            'user_id' => $landlord->id,
            'account_name' => 'Real Landlord',
            'account_no' => '11112222',
            'sort_code' => '11-22-33',
            'bank_name' => 'Landlord Bank',
            'is_active' => 1,
            'is_primary' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'account_name' => 'Tenant Takeover',
            'account_no' => '99998888',
            'sort_code' => '99-88-77',
            'bank_name' => 'Evil Bank',
        ];

        $this->actingAs($tenant)->withSession($session)
            ->post(route('admin.bank_details.store'), $payload + [
                'user_id' => $landlord->id,
                'bank_detail_id' => $bankId,
            ])
            ->assertForbidden();

        $bank = DB::table('bank_details')->find($bankId);
        $this->assertSame('11112222', $bank->account_no);
        $this->assertSame('Real Landlord', $bank->account_name);

        $this->actingAs($tenant)->withSession($session)
            ->post(route('admin.bank_details.store'), $payload + ['user_id' => $tenant->id])
            ->assertOk();
        $this->assertTrue(DB::table('bank_details')
            ->where('user_id', $tenant->id)->where('account_no', '99998888')->exists());

        $this->actingAs($tenant)->withSession($session)
            ->post(route('admin.bank_details.delete', $bankId))
            ->assertForbidden();
        $this->assertNotNull(DB::table('bank_details')->find($bankId));
        $this->assertSame('11112222', DB::table('bank_details')->find($bankId)->account_no);
    }

    // 5
    public function test_platform_only_routes_are_super_admin_only(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->createMember($accountId, 'Tenant', 'tenant');
        $contractor = $this->createContractor();
        $session = ['current_account_id' => $accountId];

        foreach (['otp.index', 'sms-templates.index', 'backend.accounting.gl_accounts.index'] as $route) {
            $this->actingAs($contractor)->get(route($route))->assertForbidden();
            $this->actingAs($tenant)->withSession($session)->get(route($route))->assertForbidden();
        }

        $before = DB::table('tenancy_types')->count();
        $this->actingAs($tenant)->withSession($session)
            ->post(route('admin.tenancy_types.store'), ['name' => 'Tenant-made type', 'type' => 'Tenant-made type'])
            ->assertForbidden();
        $this->assertSame($before, DB::table('tenancy_types')->count());

        // Shared lookups stay readable for workspace operators; only writes are platform-only.
        // A landlord-plan owner is still refused here by the separate landlord allowlist
        // (config/landlord_mvp.php enabled_route_name_prefixes has no admin.job_types.), so the
        // platform lookup rule is exercised with an agency owner.
        [$agent, $agencyAccount] = $this->createLandlord('estate_agent_company');
        $lookup = $this->actingAs($agent)->withSession(['current_account_id' => $agencyAccount])
            ->get(route('admin.job_types.getAll'));
        $this->assertNotSame(403, $lookup->status());
        $this->actingAs($agent)->withSession(['current_account_id' => $agencyAccount])
            ->post(route('admin.job_types.store'), ['name' => 'Agency-made type'])
            ->assertForbidden();

        $this->actingAs($this->createSuperAdmin())->get(route('otp.index'))->assertOk();
    }

    // 6
    public function test_logout_clears_impersonation_and_forged_leave_is_refused(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $super = $this->createSuperAdmin();

        $this->actingAs($super)
            ->post(route('backend.saas.accounts.login-as', $accountId))
            ->assertRedirect(route('backend.dashboard'));
        $this->assertSame($landlord->id, auth()->id());
        $this->assertSame($super->id, session('impersonator_user_id'));

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertFalse(session()->has('impersonator_user_id'));
        $this->assertFalse(session()->has('current_account_id'));
        $this->assertGuest();

        // A forged impersonator key without the matching impersonated id must not hand back the Super Admin.
        [$other, $otherAccount] = $this->createLandlord();
        $this->actingAs($other)
            ->withSession(['current_account_id' => $otherAccount, 'impersonator_user_id' => $super->id])
            ->post(route('backend.accounts.leave-login'))
            ->assertForbidden();
        $this->assertSame($other->id, auth()->id());
    }

    // 7
    public function test_duplicate_submit_token_creates_one_invoice_and_one_payment(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->createMember($accountId, 'Tenant', 'tenant');
        $tenancy = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $invoicePayload = [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => 1000,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            '_submit_token' => (string) Str::uuid(),
        ];
        $this->actingAs($landlord)->withSession($session)->post(route('admin.finance.store'), $invoicePayload)->assertRedirect();
        $this->actingAs($landlord)->withSession($session)->post(route('admin.finance.store'), $invoicePayload)->assertRedirect();
        $this->assertSame(1, RentInvoice::query()->forAccount($accountId)->count());

        $invoice = RentInvoice::query()->forAccount($accountId)->firstOrFail();
        $paymentPayload = [
            'amount' => 100,
            'paid_at' => now()->toDateString(),
            'method' => 'bank_transfer',
            '_submit_token' => (string) Str::uuid(),
        ];
        $this->actingAs($landlord)->withSession($session)->post(route('admin.finance.payments.store', $invoice), $paymentPayload)->assertRedirect();
        $this->actingAs($landlord)->withSession($session)->post(route('admin.finance.payments.store', $invoice), $paymentPayload)->assertRedirect();

        $this->assertSame(1, DB::table('rent_payments')->where('rent_invoice_id', $invoice->id)->count());
        $this->assertEquals(900.0, (float) $invoice->fresh()->balance);
    }

    // 8
    public function test_public_book_demo_form_saves_role_and_rejects_bad_input(): void
    {
        $email = 'demo-'.uniqid().'@resisquare.test';

        $response = $this->post(route('form.submit', 'book_demo'), [
            'first_name' => 'Demo',
            'email' => $email,
            'user_role' => 'landlord',
        ]);
        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('form_submissions', ['email' => $email, 'user_role' => 'landlord', 'form_type' => 'book_demo']);

        $this->post(route('form.submit', 'evil'), ['first_name' => 'X', 'email' => 'x@resisquare.test'])
            ->assertNotFound();

        $this->post(route('form.submit', 'book_demo'), ['first_name' => 'No Email'])
            ->assertSessionHasErrors('email');
    }

    // 9
    public function test_tenancy_move_out_before_move_in_is_rejected(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->createMember($accountId, 'Tenant', 'tenant');
        $tenancy = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $payload = [
            'property_id' => $tenancy->property_id,
            'status' => 'Active',
            'move_in' => now()->toDateString(),
            'move_out' => now()->subMonth()->toDateString(),
            'rent' => 1000,
            'deposit' => 1000,
            'user_id' => [$tenant->id],
            'is_main_person' => $tenant->id,
        ];

        $before = Tenancy::query()->where('account_id', $accountId)->count();
        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.tenancies.store'), $payload)
            ->assertSessionHasErrors('move_out');
        $this->assertSame($before, Tenancy::query()->where('account_id', $accountId)->count());

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.tenancies.update', $tenancy->id), $payload)
            ->assertSessionHasErrors('move_out');
    }

    // 10
    public function test_full_property_create_step_one_does_not_error(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $this->subscribeToPlan($accountId, 'landlord', 10);
        $countryId = (int) (DB::table('countries')->value('id') ?? 1);

        $response = $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.properties.store'), [
                'step' => 1,
                'prop_name' => 'Hardening House',
                'line_1' => uniqid().' Hardening Road',
                'city' => 'London',
                'country' => (string) $countryId,
                'postcode' => 'SW1A 1AA',
            ]);

        $this->assertLessThan(500, $response->status(), 'Step 1 returned '.$response->status());
        $response->assertOk();
        $response->assertSessionHasNoErrors();
        $this->assertTrue(Property::query()->where('account_id', $accountId)->where('prop_name', 'Hardening House')->exists());
    }

    // 10b: step1.blade.php renders country as a free-text input, but properties.country is unsignedBigInteger.
    public function test_full_property_create_step_one_accepts_the_typed_country_from_the_form(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $this->subscribeToPlan($accountId, 'landlord', 10);

        $response = $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.properties.store'), [
                'step' => 1,
                'prop_name' => 'Typed Country House',
                'line_1' => uniqid().' Typed Road',
                'city' => 'London',
                'country' => 'United Kingdom',
                'postcode' => 'SW1A 1AA',
            ]);

        $this->assertLessThan(500, $response->status(), 'Step 1 with a typed country returned '.$response->status());
    }

    // 11
    public function test_view_only_staff_cannot_raise_repair_but_landlord_can(): void
    {
        [$landlord, $landlordAccount] = $this->createLandlord();
        $this->actingAs($landlord)->withSession(['current_account_id' => $landlordAccount])
            ->get(route('admin.property_repairs.create'))
            ->assertOk();

        [, $agencyAccount] = $this->createLandlord('estate_agent_company');
        $viewPermission = Permission::findOrCreate('view property repair', 'web');
        $role = Role::findOrCreate('Repair Viewer '.uniqid(), 'web');
        $role->givePermissionTo($viewPermission);

        $staff = $this->createMember($agencyAccount, $role->name, 'staff');
        $this->assertFalse($staff->hasPermissionTo('create property repair'));

        $this->actingAs($staff)->withSession(['current_account_id' => $agencyAccount])
            ->get(route('admin.property_repairs.create'))
            ->assertForbidden();
    }

    // 12
    public function test_notification_retry_only_requeues_failed_deliveries(): void
    {
        Queue::fake();
        [$landlord, $accountId] = $this->createLandlord();
        $session = ['current_account_id' => $accountId];

        $sent = $this->notificationLog($accountId, $landlord, 'sent');
        $failed = $this->notificationLog($accountId, $landlord, 'failed');

        $this->actingAs($landlord)->withSession($session)
            ->post(route('backend.notifications.deliveries.retry', $sent))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('sent', $sent->fresh()->status);
        Queue::assertNothingPushed();

        $this->actingAs($landlord)->withSession($session)
            ->post(route('backend.notifications.deliveries.retry', $failed))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('pending', $failed->fresh()->status);
        Queue::assertPushed(SendNotificationJob::class);
    }

    // 13
    public function test_landlord_without_properties_sees_add_property_next_action(): void
    {
        [$landlord, $accountId] = $this->createLandlord();

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->get(route('backend.dashboard'))
            ->assertOk()
            ->assertSee('data-next-action="add-property"', false);
    }

    // ---- follow-up items (FT-205, FT-070, FT-212, FT-102, FT-117/213, FT-140) ----

    public function test_a_save_stamped_with_another_account_is_refused(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->createMember($accountId, 'Tenant', 'tenant');
        $tenancy = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];
        $payload = [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => 750,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ];

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.store'), $payload + ['_account_id' => (string) ($accountId + 999999)])
            ->assertRedirect();
        $this->assertSame(0, RentInvoice::query()->forAccount($accountId)->count());

        $this->actingAs($landlord)->withSession($session)
            ->withHeader('X-Account-Id', (string) ($accountId + 999999))
            ->postJson(route('admin.finance.store'), $payload)
            ->assertStatus(409);

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.store'), $payload + ['_account_id' => (string) $accountId])
            ->assertRedirect();
        $this->assertSame(1, RentInvoice::query()->forAccount($accountId)->count());
    }

    public function test_inviting_a_tenant_who_already_has_access_is_refused(): void
    {
        Mail::fake();
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->createMember($accountId, 'Tenant', 'tenant');
        $tenancy = $this->createLet($accountId, $landlord, $tenant);

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->post(route('admin.portal-access.invite'), [
                'name' => $tenant->name,
                'email' => $tenant->email,
                'tenancy_id' => $tenancy->id,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_tenancy_screens_flag_right_to_rent_not_checked(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->createMember($accountId, 'Tenant', 'tenant');
        $tenancy = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.tenancies.show', $tenancy->id))
            ->assertOk()
            ->assertSee('data-right-to-rent="incomplete"', false);

        TenantMember::query()->where('tenancy_id', $tenancy->id)->update(['right_to_rent_checked_at' => now()]);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.tenancies.show', $tenancy->id))
            ->assertOk()
            ->assertSee('data-right-to-rent="checked"', false)
            ->assertDontSee('data-right-to-rent="incomplete"', false);
    }

    public function test_booked_repair_visit_appears_on_the_tenant_calendar(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->createMember($accountId, 'Tenant', 'tenant');
        $tenancy = $this->createLet($accountId, $landlord, $tenant);
        $contractor = $this->createContractor();
        $visitAt = now()->addDays(3)->setTime(14, 30);

        $categoryId = DB::table('repair_categories')->value('id')
            ?? DB::table('repair_categories')->insertGetId(['name' => 'Plumbing', 'created_at' => now(), 'updated_at' => now()]);
        $repairId = DB::table('repair_issues')->insertGetId([
            'account_id' => $accountId,
            'property_id' => $tenancy->property_id,
            'repair_category_id' => $categoryId,
            'repair_navigation' => '[]',
            'description' => 'Boiler not heating',
            'tenant_id' => $tenant->id,
            'priority' => 'medium',
            'status' => 'Pending',
            'sub_status' => 'Pending',
            'reference_number' => 'QA-'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('repair_job_offers')->insert([
            'account_id' => $accountId,
            'repair_issue_id' => $repairId,
            'contractor_user_id' => $contractor->id,
            'status' => \App\Models\RepairJobOffer::JOB_ACCEPTED,
            'scheduled_for' => $visitAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($tenant)->withSession(['current_account_id' => $accountId])
            ->get(route('tenant.calendar', ['month' => $visitAt->format('Y-m')]))
            ->assertOk()
            ->assertSee('Repair visit: Boiler not heating', false)
            ->assertSee($visitAt->format('H:i'), false);
    }

    public function test_money_uses_the_account_currency_and_mrr_is_split_by_currency(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        DB::table('accounts')->where('id', $accountId)->update(['currency' => 'EUR']);

        $this->actingAs($landlord)->withSession(['current_account_id' => $accountId])
            ->get(route('backend.dashboard'))
            ->assertOk();
        $this->assertSame('€10.50', rs_money(10.5));
        $this->assertSame('£10.50', rs_money(10.5, false, 'GBP'));
        $this->assertSame('CHF 3.00', rs_money(300, true, 'CHF'));

        $planId = DB::table('plans')->insertGetId([
            'code' => 'usd-'.uniqid(), 'name' => 'USD Plan', 'target_account_type' => 'landlord',
            'monthly_price_minor' => 2000, 'annual_price_minor' => 0, 'currency' => 'USD', 'trial_days' => 0,
            'property_limit' => 1, 'branch_limit' => 0, 'staff_limit' => 0, 'property_manager_limit' => 0,
            'is_active' => 1, 'sort_order' => 99, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('account_subscriptions')->insert([
            'account_id' => $accountId, 'plan_id' => $planId, 'billing_cycle' => 'monthly', 'status' => 'active',
            'price_at_signup_minor' => 2000, 'currency_at_signup' => 'USD', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->createSuperAdmin())
            ->get(route('backend.saas.billing.index'))
            ->assertOk()
            ->assertSee('data-mrr-currency="USD"', false);
    }

    public function test_a_manual_payment_closes_any_open_card_checkout(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->createMember($accountId, 'Tenant', 'tenant');
        $tenancy = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $this->actingAs($landlord)->withSession($session)->post(route('admin.finance.store'), [
            'tenancy_id' => $tenancy->id,
            'tenant_user_id' => $tenant->id,
            'amount' => 1000,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ])->assertRedirect();
        $invoice = RentInvoice::query()->forAccount($accountId)->firstOrFail();

        $checkout = \Mockery::mock(\App\Services\Finance\RentStripeCheckoutService::class);
        $checkout->shouldReceive('closeOpenCheckoutAfterManualPayment')
            ->once()
            ->with(\Mockery::on(fn ($inv) => $inv instanceof RentInvoice && (int) $inv->id === (int) $invoice->id));
        $this->app->instance(\App\Services\Finance\RentStripeCheckoutService::class, $checkout);

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.finance.payments.store', $invoice), [
                'amount' => 400,
                'paid_at' => now()->toDateString(),
                'method' => 'bank_transfer',
            ])
            ->assertRedirect();

        $this->assertEquals(600.0, (float) $invoice->fresh()->balance);
    }

    // ---- product decisions 2026-11-20 (FT-124, FT-120, FT-074, FT-126) ----

    public function test_super_admin_inside_an_account_sees_only_that_account(): void
    {
        [$landlordA, $accountA] = $this->createLandlord();
        [$landlordB, $accountB] = $this->createLandlord();
        $propertyA = Property::create(['account_id' => $accountA, 'created_by' => $landlordA->id, 'line_1' => 'Scope A '.uniqid(), 'city' => 'London', 'postcode' => 'E1 1AA']);
        $propertyB = Property::create(['account_id' => $accountB, 'created_by' => $landlordB->id, 'line_1' => 'Scope B '.uniqid(), 'city' => 'London', 'postcode' => 'E1 1AB']);
        $admin = $this->createSuperAdmin();

        $this->assertTrue(bypasses_account_scope($admin), 'No account selected: platform view.');

        $this->actingAs($admin)->withSession(['current_account_id' => $accountA]);
        $this->assertFalse(bypasses_account_scope($admin));

        $list = $this->actingAs($admin)->withSession(['current_account_id' => $accountA])
            ->get(route('admin.properties.index'));
        if ($list->status() === 302) {
            $list = $this->actingAs($admin)->withSession(['current_account_id' => $accountA])->get($list->headers->get('Location'));
        }
        $list->assertOk()->assertDontSee($propertyB->line_1, false);

        $this->actingAs($admin)->withSession(['current_account_id' => $accountA])
            ->get(route('admin.properties.edit', $propertyB->id))
            ->assertForbidden();
    }

    public function test_super_admin_is_bound_by_named_permissions_but_not_policies(): void
    {
        $admin = $this->createSuperAdmin();
        $unassigned = Permission::findOrCreate('qa unassigned '.uniqid(), 'web');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertFalse($admin->hasEffectivePermission($unassigned->name));
        $this->assertTrue($admin->hasEffectivePermission('manage registrations'), 'Granted to the Super Admin role by migration.');

        $this->actingAs($admin)->get(route('admin.registrations.index'))->assertOk();

        $admin->roles()->first()->revokePermissionTo('manage registrations');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($admin->fresh())->get(route('admin.registrations.index'))->assertForbidden();
    }

    public function test_landlord_keeps_private_notes_on_a_tenancy_and_the_tenant_cannot(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        $tenant = $this->createMember($accountId, 'Tenant', 'tenant');
        $tenancy = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];
        $noteTypeId = DB::table('note_types')->value('id')
            ?? DB::table('note_types')->insertGetId(['name' => 'General', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($landlord)->withSession($session)
            ->postJson(route('admin.notes.save'), [
                'noteable_type' => Tenancy::class,
                'noteable_id' => $tenancy->id,
                'note_type_id' => $noteTypeId,
                'content' => 'Agreed a later rent date for December.',
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.tenancies.show', $tenancy->id))
            ->assertOk()
            ->assertSee('data-tenancy-notes="'.$tenancy->id.'"', false)
            ->assertSee('Agreed a later rent date for December.', false);

        $this->actingAs($tenant)->withSession($session)
            ->getJson(route('admin.notes.list', ['noteable_type' => Tenancy::class, 'noteable_id' => $tenancy->id]))
            ->assertForbidden();
        $this->actingAs($tenant)->withSession($session)
            ->postJson(route('admin.notes.save'), [
                'noteable_type' => Tenancy::class,
                'noteable_id' => $tenancy->id,
                'note_type_id' => $noteTypeId,
                'content' => 'Tenant should not write this.',
            ])
            ->assertForbidden();
    }

    public function test_public_site_uses_saved_site_name_and_footer_contact(): void
    {
        foreach (['website_name' => 'QA Lettings Hub', 'company_email' => 'hello@qa-lettings.test', 'meta_description' => 'QA description for the public site.'] as $type => $value) {
            DB::table('business_settings')->updateOrInsert(['type' => $type], ['value' => $value, 'updated_at' => now(), 'created_at' => now()]);
        }
        Cache::forget('business_settings');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('QA Lettings Hub', false)
            ->assertSee('mailto:hello@qa-lettings.test', false)
            ->assertSee('QA description for the public site.', false);

        Cache::forget('business_settings');
    }

    // ---- fixtures -------------------------------------------------------

    private function createLandlord(string $accountType = 'landlord'): array
    {
        $user = User::create([
            'name' => 'Hardening Landlord',
            'email' => 'hardening-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
            'can_login' => 1,
        ]);
        $user->assignRole(Role::findOrCreate($accountType === 'landlord' ? 'Landlord' : 'Estate Agent', 'web'));

        $accountId = DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => $accountType,
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

    private function subscribeToPlan(int $accountId, string $accountType, int $propertyLimit): void
    {
        $planId = DB::table('plans')->insertGetId([
            'code' => 'hardening-'.uniqid(),
            'name' => 'Hardening Plan',
            'target_account_type' => $accountType,
            'monthly_price_minor' => 0,
            'annual_price_minor' => 0,
            'currency' => 'GBP',
            'trial_days' => 7,
            'property_limit' => $propertyLimit,
            'branch_limit' => 0,
            'staff_limit' => 0,
            'property_manager_limit' => 0,
            'is_active' => 1,
            'sort_order' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('account_subscriptions')->insert([
            'account_id' => $accountId,
            'plan_id' => $planId,
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createMember(int $accountId, string $roleName, string $memberType): User
    {
        $user = User::create([
            'name' => 'Member '.uniqid(),
            'first_name' => 'Member',
            'email' => 'member-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
            'can_login' => 1,
        ]);
        $user->assignRole(Role::findOrCreate($roleName, 'web'));

        DB::table('account_users')->insert([
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

    private function createContractor(): User
    {
        $user = User::create([
            'name' => 'Hardening Contractor',
            'email' => 'hardening-contractor-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
            'can_login' => 1,
        ]);
        $user->assignRole(Role::findOrCreate('Contractor', 'web'));

        return $user;
    }

    private function createSuperAdmin(): User
    {
        $user = User::create([
            'name' => 'Hardening Super Admin',
            'email' => 'hardening-super-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
            'can_login' => 1,
        ]);
        $user->assignRole(Role::findOrCreate('Super Admin', 'web'));

        return $user;
    }

    private function createLet(int $accountId, User $landlord, User $tenant): Tenancy
    {
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => uniqid().' Hardening Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
        ]);

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1000,
            'frequency' => 'Monthly',
        ]);

        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
        ]);

        return $tenancy;
    }

    private function notificationLog(int $accountId, User $user, string $status): NotificationLog
    {
        return NotificationLog::create([
            'account_id' => $accountId,
            'identifier' => 'repair.reported',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'notification_uuid' => (string) Str::uuid(),
            'idempotency_key' => hash('sha256', uniqid($status, true)),
            'channel' => 'mail',
            'recipient' => $user->email,
            'subject' => 'Repair update',
            'message' => 'A plumber is booked.',
            'payload' => [],
            'status' => $status,
            'attempt' => $status === 'failed' ? 3 : 1,
            'sent_at' => $status === 'sent' ? now() : null,
            'error' => $status === 'failed' ? 'SMTP down' : null,
        ]);
    }
}
