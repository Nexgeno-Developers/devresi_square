# Production-ready plan — landlord launch, end to end

**Date:** 14 October 2026
**Behavior:** `docs/launch/FRD-LANDLORD-LAUNCH.md`
**Sign-off:** `docs/launch/ACCEPTANCE_SCRIPTS.md` scripts A–E

Engineering is finished when every row in the checklist at the bottom is green on a fresh migrated database, and scripts A–E are signed on that commit on staging. Public registration then waits on `docs/launch/GA_GATE.md` (support rota, rollback rehearsal, privacy counsel, advisory waiver, independent review). Those are not code tasks. They are also not optional for opening the door.

Work one journey at a time. The next journey starts when this journey’s tests are in the launch-critical filter and green. One pull request per fix group, from current main.

## Decisions already locked

These close the forks. The FRD now says the same thing.

| Topic | Rule |
|---|---|
| Co-owners | Contact fields on the owner group only. No user, no Owner role, no membership, no login. An email that already belongs to a user is stored as a contact and that user is left unchanged. |
| Rent viewers | Every tenant member of the active tenancy sees the invoices. There is no age gate. |
| Named payer | `tenant_user_id` is copied onto the invoice at issue. Later changes to the main tenant do not rewrite it. |
| Distance | Miles must be known and ≤ `coverage_radius_miles`. Favourites sort the list. They do not skip the radius. A failed geocode omits that contractor with a reason. Find and send use this same rule. |
| Inspection cancel | Other offers stay closed. The landlord sends a new invite. A closed offer cannot be accepted. |
| Emergency count | Same eligibility as a routine send. Cap is `max_contractors_per_emergency_send`, default 15. Routine cap stays 5. |
| Agency offers | Block the route prefix `admin.offers.` only. Marketplace routes `admin.property_repairs.job_offers.*` stay on the landlord allowlist. |
| Stripe key match | `launch:assert-money-config` rejects identical operating and rent secrets when the environment is production. Staging may reuse one pair of test keys. |
| APP_KEY | Set a key only when the environment has none. Do not rotate an existing key. |
| DNS | SPF, DKIM, and DMARC are done in journey 6, before the staging rehearsal. |
| Production content | `php artisan launch:seed-content` upserts plans and email templates. It does not call `DatabaseSeeder` or `UserSeeder`. |
| Schedule | Jobs live in `bootstrap/app.php`. There is no `app/Console/Kernel.php` schedule to edit. |
| Helpers vs job page | `get_tenants_by_property` and `get_users_by_property_and_role` are landlord-workspace lookups. The awarded contractor’s address comes from the job page. |

## How a journey is finished

1. Make the code changes listed under **Fix**.
2. Add the tests listed under **Prove**. Existing tests stay. A test that encodes the old behavior is updated in the same pull request.
3. Append the new test classes to the launch-critical filter in `docs/launch/AUTOMATED_PROOF.md`.
4. Run that journey’s filter on a database created by migrations plus the seeders the test loads itself.

A screen that looks right is not a pass. A checklist row with no test name is not a pass.

---

## Journey 1 — One landlord, one portfolio

**FRD:** FR-X1, FR-X2, FR-X3, FR-X4, FR-B1, FR-B2, FR-B3, FR-O1, FR-O2, FR-O4, FR-O5, FR-P1, FR-P2

### Fix

**Lookups.** In `app/helpers.php`, `get_tenants_by_property`, `get_users_by_property_and_role`, and `get_user_address_name_by_id`:

- Load the property and return an empty result when `account_id` is not the current account.
- Return users who have an active membership on that account. A `selected_properties` JSON match is not enough.
- Cache key includes account id and property id. TTL 60 seconds. Forget the key from tenancy store, update, destroy, portal invite, portal revoke, and tenant profile save.

The HTTP routes in `routes/backend.php` return 403 when the property is on another account, with no name, email, phone, or street in the body.

**Route wall.** `RestrictLandlordRoutes` applies to every authenticated member of a landlord account who is not in the tenant portal and not in the contractor portal. Staff, landlord contacts, and property managers use the owner allowlist. Blocked prefixes win. Do not add a new deny that matches `property_repairs.job_offers`.

**Portal identity.** `EnsureTenantPortal` and `is_tenant_portal_user()` require an active tenant `account_users` row with `can_login`. Remove the Spatie `Tenant` role fallback. Super Admin does not enter the portal by role.

**Co-owners.** `LandlordOnboardingService::findOrCreateContact` stops assigning Owner and stops creating a user for a co-owner. Persist name, email, and phone on the owner group. An existing user email does not gain a role or a membership.

### Prove

Keep green: `SignupTrialFirstHomeHttpTest`, `LandlordOnboardingHttpTest`, `SaasBillingAndLimitsHttpTest`, `PropertyPlanLimitHttpTest`, `PortfolioWorkspaceHttpTest`, `PropertyArchiveLifecycleHttpTest`, `LandlordPropertyWizardHttpTest::test_wizard_redirects_to_onboarding_flow_when_disabled`, `InScopeIdorMatrixHttpTest`, `WorkspaceIsolationHttpTest`.

Add:

- Four roles against another account’s property id: landlord, tenant, contractor, property manager. Empty or 403, no contact fields. Rename the tenant, look up again, see the new name.
- Owner, staff, and landlord contact receive 403 on `/admin/offers` and 200 on properties, tenancies, repairs, finance, and billing.
- Tenant role plus staff membership receives 403 on `/admin/portal/rent`. A real tenant membership still opens tenant home.
- POST co-owner with an existing email: that user has no new Owner role and no new `account_users` row. A new email creates no user row.
- Two live properties on one account with the same normalised address: the second save fails.

**Done when:** the journey 1 filter is green.

---

## Journey 2 — A tenancy tells the truth about login

**FRD:** FR-O3, FR-PE1, FR-PE2, FR-PE3, FR-T1, FR-T2, FR-T3, FR-T4, FR-T5, FR-T6, FR-TN1, FR-TN2, FR-TN3

### Fix

**Create.** `TenancyController::store` replaces `foreach ([] as $userId)`. Default save: `can_login` 0, no new tenant `account_users` row, no “invite sent” flash. When the form asks to invite, call `PortalAccessService` after the user and the token exist, and send `tenant_welcome` through `TenantInviteMailer`. If the template is missing or the mailer throws, the tenancy remains, login stays off, and the flash says the invite failed.

Creating an Active tenancy that archives another Active tenancy on the same property calls `closePortalForEndedTenancy` on the archived tenancy.

**Onboarding result.** `LandlordOnboardingController::complete` stops copying `invited`, `sent`, and `invite_error` from the request. The JSON is whatever `LandlordOnboardingService` recorded from the mailer.

**Invite lifecycle.** Resend invalidates the previous set-password token. Revoke clears `can_login` and ends that user’s other sessions in the same request.

**Archive and delete.** `destroy` calls `closePortalForEndedTenancy` while the members are still readable, then deletes. A tenant with another Active tenancy on the account keeps login.

**Confirmation.** `TenancyDetailsConfirmationService::membershipNeedingAction` requires tenancy status Active.

**Template.** `tenant_welcome` is upserted by `launch:seed-content`, built in journey 6. Until that command exists, journey 2’s test loads `TenantWelcomeEmailTemplateSeeder` itself.

### Prove

Keep green: `PeopleInviteHttpTest`, `TenancyDay2HttpTest`, `TenancyDetailsConfirmationHttpTest`, `DepositProtectionHttpTest`, `TenantPortalHttpTest`.

Add:

- Store without invite: `can_login` 0, account-user count unchanged.
- Store with invite and mail faked: one mailable, login on.
- Complete JSON ignores a posted `invite_error`.
- Second Active tenancy on the property archives the first and that tenant loses login when it was their only home.
- Delete the only tenancy: row gone, membership not active-with-login. A second active tenancy: login remains.
- Resend: the first token no longer sets a password. Revoke: the open session’s next request is refused.
- Archived tenancy does not render the confirmation form. One tenant cannot confirm another household’s tenancy.
- People index states that adding a contact does not grant a login.

**Done when:** the journey 2 filter is green.

---

## Journey 3 — Rent is paid once, by the named person

**FRD:** FR-R1, FR-R2, FR-R3, FR-R4, FR-R5, FR-NF2

### Fix

**Who pays.** The rent list shows the active tenancy’s invoices to every member. The Pay control renders only when `rent_invoices.tenant_user_id` is the logged-in user. `TenantPortalController::pay` returns 403 before any Stripe call otherwise.

**One settlement.** A webhook applies a payment only when the invoice is still issued or partial and the balance can take the amount. A manual payment that already zeroed the balance makes a late webhook a no-op. A webhook that already zeroed the balance makes a further manual payment fail. A partial manual payment leaves the remainder payable by card.

**Catch-up.** `RentFinanceService::generateRecurringForTenancy` already issues due periods up to 24. When the 24 cap stops the loop and further periods are due, the command writes that tenancy id and the remaining `rent_next_period_start` to the log. It does not move the cursor past unissued periods.

**Subscription failure.** Keep `invoice.payment_failed` on the operating webhook (`StripeWebhookService`) as the event that sets account `past_due`. The rent webhook stays on `checkout.session.completed` and `checkout.session.async_payment_succeeded`. An abandoned rent Checkout leaves the invoice open, which `test_cancelled_checkout_leaves_the_invoice_unpaid` already covers.

### Prove

Keep green: `TenantRentPaymentHttpTest`, `LandlordFinanceHttpTest`.

Add:

- Housemate GET shows the invoice number and no pay form. Housemate POST pay is 403. Named payer POST pay redirects to Checkout. The invoice row is unchanged until the webhook fixture.
- Webhook twice: one payment, balance zero once.
- Manual pay to zero, then the same Checkout webhook: balance stays zero, one payment row.
- Webhook pays in full, then manual pay is rejected.
- Partial manual pay, then Checkout for the remainder: balance zero, two payment rows, sum equals the invoice.
- Void blocks pay.
- Recurring catch-up of three missed months creates three invoices. A 25th period is not dropped: the next run creates it.
- Changing `is_main_person` after issue leaves `tenant_user_id` on the open invoice.
- Suspended account: rent Checkout is refused, bank details still show. `past_due` still opens the portfolio and the billing page.

**Done when:** the journey 3 filter is green.

---

## Journey 4 — One contractor, one address

**FRD:** FR-M1 through FR-M9, FR-TN5, FR-C1 through FR-C6

### Fix

**Find and send share one eligibility function.** Approved profile, trade, `ContractorCompliance::blockReason` (Gas Safe and electrical stay), and miles ≤ radius. `RepairMarketplacePanel` stops treating `mine` as inside the area, and stops treating a matching outward postcode as inside the radius when miles are null. `RepairJobOfferService::sendRequest` calls that same function before insert. A skip leaves no offer row. The landlord sees the skip reason.

Emergency send uses that function, then `max_contractors_per_emergency_send` (default 15 in `config/contractor_marketplace.php`). Sixteen ids create no rows.

**Exclusive access.** In `RepairJobOfferService::accept`, an inspection accept sets `inspection_accepted` and `inspection_accepted_at` on that offer and, in the same transaction, closes every other open offer. It does not set `awarded_offer_id`. A firm accept sets `awarded_offer_id` and closes the others. `RepairJobOffer::unlocksAccess` is true only for that single offer. Cancel clears access. Closed offers stay closed. Send may invite that contractor again as a new invite with no street.

**Decline.** `declineOffer` locks the repair and the offer, re-reads status, and accepts only `offer_sent` or `offer_expired`. Inspection-accepted and job-accepted are cancelled, not declined. After a decline to `not_selected`, `awarded_offer_id` does not point at that offer.

**Revision.** Accept already requires `expectedRevision`. Keep the rejection when the stored revision moved. The landlord sees the new price.

**Tenant.** Marketplace mutations use `RepairIssuePolicy::update`, which is landlord-workspace only, and also refuse `is_tenant_portal_user()`. Find-contractor GET returns 403 for a portal tenant and for a contractor. The tenant maintenance screen stays the repair surface.

**Contractor account.**

- Claim: replace the 30-day `temporarySignedRoute` in `ContractorDirectory` with a random secret, hash stored, 72-hour expiry, deleted on use. Second use is 404 or 410. Update `ContractorMarketplaceTest`, which builds a signed URL today.
- `updateBusiness` may save phone and trading name while approved. A change to trades, postcode, `coverage_radius_miles`, or `company_number` sets `approval_status` back to the review state the admin queue already uses. Awarded jobs stay with that contractor. `sendRequest` skips anyone not approved.
- Unique index on `contractor_profiles.company_number`. Removal nulls `company_number` inside the removal transaction so MySQL can hold many nulls and still reject two live rows with the same number. The form check stays.
- The business form asks “Do you employ staff?” and saves `employs_staff`. When true, employers’ liability is required at `ContractorSettings::elMinimum()` before submit-for-approval and before send. It is not an optional extra while the flag is true.

**Pre-award copy.** Job page, job list, visit rows, and contractor mail show town and outward postcode only. Street, access notes, tenant phone, and tenant email appear on the job page and in mail only for the one offer that `unlocksAccess` allows.

### Prove

Keep green: `ContractorMarketplaceTest` cases for gas block, version check, completion, invoice paid by the landlord, registration, and approval. Update the claim-link case and any case that expected a favourite outside the radius to be listed.

Add the script E cases: distance skip, favourite skip, electrical skip, exclusive inspection, stale decline, stale revision, cancel-then-new-send, tenant 403, claim single use, radius returns to review, duplicate company number, emergency 16 rejected and emergency 6 accepted.

Contractor login receives 403 on workspace admin, tenant portal, finance, and `/admin/offers`, and lands on contractor jobs.

**Done when:** the journey 4 filter is green, which is script E under test.

---

## Journey 5 — Documents, safety, diary, and the tenant’s own account

**FRD:** FR-X5, FR-X6, FR-P3, FR-D1, FR-D2, FR-CP1, FR-CP2, FR-N1, FR-N2, FR-N3, FR-TN4, FR-NF3, FR-NF4, FR-NF5

### Fix

**Email change.** Add `users.pending_email` and `email_change_tokens` (hashed secret, 72 hours, single use). `TenantPortalController::updateProfile` saves name and phone immediately. A new email writes `pending_email` and sends the confirmation. `users.email` and `email_verified_at` change when the link is opened, and other sessions for that user end. Password change already requires the current password and also ends other sessions. The template is in `launch:seed-content`.

**Visits.** A contractor sees a visit only when it is attached to a job their offer can already see under FR-M4 and FR-M6. The visit payload uses town and outward postcode until `unlocksAccess`.

**Audit rows.** Award, decline, cancel, invite, portal revoke, invoice void, manual rent payment, and contractor approval each write actor, time, and before/after. Offer logging in `RepairJobOfferService` stays. Add the missing actions in the same shape.

**Files.** Contractor documents and private landlord documents stay on the private disk. A tenant download of another account’s document returns 404 and does not start a file response. Admin download of contractor documents is limited to Super Admin reviewers.

**Empty states and locale.** Lists with no rows keep the designed empty state already covered by `DesignedEmptyStatesHttpTest`. Dates and money on the rent page and the offer page render UK format and £.

**Preferences.** The tenant can switch notification categories off, except invite, password, and email confirmation. Those three still send when the category is off.

### Prove

Keep green: `DocumentsHttpTest`, `ComplianceHttpTest`, `CalendarIntegrationHttpTest`, `DesignedEmptyStatesHttpTest`, `SecureUploadHttpTest`, `StoredXssHttpTest`, `TenantProfileHttpTest`.

Add:

- Profile POST with a new email: old email authenticates, new email does not, until the confirmation request. Other sessions end at confirmation.
- Internal property notes are absent from the tenant portal and from the pre-award contractor job.
- A certificate with no file renders “Not provided yet”.
- Contractor visit before acceptance has no street. After inspection accept, the accepted contractor’s visit has the street and the other contractor’s does not.
- Each FR-N2 event creates an email and an in-app row, or the action shows a visible mail failure when the template is missing. Pre-award contractor mail has town and outward postcode only.
- Security notifications still send when preferences are off.
- Audit row exists for invite, revoke, void, manual pay, and contractor approval.

**Done when:** the journey 5 filter is green.

---

## Journey 6 — The release can run without the developer’s machine

**FRD:** FR-NF1, FR-NF6, and the templates named in FR-N2

### Fix

**Content command.** Add `launch:seed-content`. It upserts Landlord Basic and the templates: signup OTP, `tenant_welcome`, email-change confirmation, contractor claim, repair invited, inspection accepted, awarded, not selected, rent invoice issued, payment received, compliance expiry. Running it twice changes nothing. It refuses to call `UserSeeder`.

**Environment.** `.env.example` lists every production key as an empty value with a one-line comment: `APP_URL`, `APP_ENV`, `APP_DEBUG`, `QUEUE_CONNECTION=database`, `MAIL_*`, both Stripe accounts, `RENT_PAYMENT_FEE_PERCENT`, `RENT_PAYMENT_FEE_FIXED`.

`launch:assert-money-config` and `launch:assert-ops` fail when mail is `log`, queue is `sync`, or debug is true. Identical Stripe secrets fail for `--env=production` only. Production also requires live-mode secrets (`sk_live`, and `whsec_` from the live endpoint). Document that in `docs/launch/PRODUCTION_MAIL_AND_STRIPE.md` without changing the staging allowance already written there.

**Processes.**

- `docs/launch/deploy/scheduler.cron` — `schedule:run` every minute
- `docs/launch/deploy/queue-worker.conf` — tries 3, timeout 120, restart after deploy

`bootstrap/app.php` already schedules `rent-invoices:generate-recurring`, `rent:reconcile-checkouts`, `crm-notifications:send-due` (compliance expiry), `events:send-reminders`, and `contractors:sweep` (offer expiry). Leave those lines in place. Add a test that the schedule contains those five command names.

**Webhooks.** `/stripe/webhook` and `/stripe/rent/webhook` stay CSRF-exempt and signature-checked. Tests use Stripe fixture payloads, not the network: subscription checkout activates once, rent checkout pays once, the same event id does not move the balance twice, `invoice.payment_failed` sets `past_due`.

**Errors.** With `APP_DEBUG=false`, a forced exception returns the generic error page and no stack trace.

**Mail proof on staging, before rehearsal.** DNS for the sending domain is published. Three messages arrive: signup OTP, tenant invite, rent notice. Record the provider message id.

### Prove

Keep green: `LaunchMoneyConfigHttpTest`, `LaunchOpsConfigHttpTest`, `LaunchKillSwitchesHttpTest`, `RegistrationOtpMailTest`.

**Done when:** both assert commands pass with test keys, fail when a secret is missing, and fail on identical secrets only for production. The five scheduled commands are present. The three staging messages have ids.

---

## Journey 7 — Rehearsal, then production secrets

### Staging

1. Migrate a fresh staging database. Run `launch:seed-content`. Install the cron line and the queue worker. `launch:assert-ops` passes. `APP_DEBUG=false`.
2. Run scripts A–E. Record pass or fail with the commit hash in the sign-off table. A failed step is fixed on a new commit and the whole script is run again. No step is waived.
3. One subscription Checkout and one rent Checkout on test cards, including a webhook replay.
4. Failure script D, plus the stop-ship list in the FRD: other-account ids, void invoice, suspended account, claim link reused, two contractors and one street.
5. Delete every probe user, property, invoice, and offer. Counts match the content seed.

### Production

1. Deploy that commit. Migrate. Run `launch:seed-content`. Install cron and the worker. `php artisan queue:restart`.
2. Write secrets into the environment file only: database password, `MAIL_*`, operating Stripe key, secret, and webhook secret, rent Stripe key, secret, and webhook secret. Write `APP_KEY` only if it is absent. The two Stripe accounts are different accounts. Webhook endpoints are the routes from journey 6, with the events already listed in `docs/launch/PRODUCTION_MAIL_AND_STRIPE.md`.
3. `launch:assert-money-config --env=production` and `launch:assert-ops --env=production` both pass.
4. Smoke one new landlord: OTP, first home, tenant invite, invoice. Charge a live card only when the rent account is allowed to take a real payment. Otherwise the signed script C from staging is the card proof, and production shows bank details. Watch 5xx, webhook logs, queue depth, and bounces for two hours.
5. Rollback stays `docs/launch/GA_GATE.md`: a money fault disables rent Checkout and leaves bank transfer; a bad deploy rolls the previous artifact and restarts workers.

**Done when:** the checklist is green, scripts A–E are signed on the release commit, the assert commands pass on production, the smoke landlord can sign in, and the queue is draining.

Public registration stays closed until the GA gate’s non-code rows are signed: support rota, one rollback rehearsal, privacy counsel, the Composer advisory waiver, and the independent review.

---

## Checklist

Every FRD id has a journey. **Fix** means this plan changes code. **Prove** means an existing test already matches the locked rule and must stay green. A row is done only when its test is in the launch-critical filter.

| ID | Journey | Work | Test |
|---|---|---|---|
| FR-X1 | 1 | Fix | Other-account property, tenancy, invoice, repair, document |
| FR-X2 | 1 | Fix | Four-role lookup, rename, no forever cache |
| FR-X3 | 1 | Fix | `/admin/offers` 403 for owner, staff, tenant, contractor; marketplace routes still open |
| FR-X4 | 1 | Fix | Role plus staff membership refused; real tenant allowed |
| FR-X5 | 5 | Prove | `DesignedEmptyStatesHttpTest` |
| FR-X6 | 5 | Fix | Audit rows for invite, revoke, void, manual pay, approval |
| FR-B1 | 1 | Prove | `SignupTrialFirstHomeHttpTest` |
| FR-B2 | 1, 3 | Prove | Billing page and `past_due` portfolio |
| FR-B3 | 1 | Prove | `PropertyPlanLimitHttpTest` |
| FR-O1 | 1 | Prove | `LandlordOnboardingHttpTest` |
| FR-O2 | 1 | Fix | Co-owner creates no user and no Owner role |
| FR-O3 | 2 | Fix | Complete JSON ignores client invite flags |
| FR-O4 | 1 | Prove | Add-property overlay test |
| FR-O5 | 1 | Prove | Wizard redirect when disabled |
| FR-P1 | 1 | Prove | `PortfolioWorkspaceHttpTest` |
| FR-P2 | 1 | Fix | Duplicate address rejected; archive test stays |
| FR-P3 | 5 | Fix | Internal notes absent from portal and pre-award job |
| FR-PE1 | 2 | Prove | People page says a contact is not a login |
| FR-PE2 | 2 | Fix | Resend invalidates the token; revoke ends sessions |
| FR-PE3 | 2 | Fix | Tenancy form uses `PortalAccessService` |
| FR-T1 | 2 | Fix | Store without invite; displace closes portal |
| FR-T2 | 2 | Prove | Archive closes portal when it is the last home |
| FR-T3 | 2 | Fix | Destroy closes portal, then deletes |
| FR-T4 | 2 | Fix | Archived confirmation hidden; household isolation |
| FR-T5 | 2 | Prove | `DepositProtectionHttpTest` |
| FR-T6 | 3 | Fix | Payer frozen on the invoice |
| FR-TN1 | 1 | Fix | Landlord and contractor receive 403 on portal URLs |
| FR-TN2 | 2 | Prove | `TenantPortalHttpTest` home shows active tenancies |
| FR-TN3 | 2 | Prove | Confirmation state on my tenancy |
| FR-TN4 | 5 | Fix | Pending email, then confirm |
| FR-TN5 | 4 | Fix | Tenant 403 on find-contractor and accept |
| FR-R1 | 3 | Fix | Catch-up cap is logged and resumed |
| FR-R2 | 3 | Fix | Housemate sees the invoice and cannot pay |
| FR-R3 | 3 | Prove | Checkout, webhook once, abandoned Checkout |
| FR-R4 | 3 | Fix | Manual pay versus webhook race |
| FR-R5 | 3 | Prove | `LandlordFinanceHttpTest` |
| FR-M1 | 4 | Prove | `RepairHappyPathHttpTest` |
| FR-M2 | 4 | Fix | Radius required; electrical and Gas Safe |
| FR-M3 | 4 | Fix | Send rechecks eligibility; emergency cap 15 |
| FR-M4 | 4 | Fix | Town and outward postcode only |
| FR-M5 | 4 | Prove | Stale revision rejected |
| FR-M6 | 4 | Fix | One inspection accept; cancel then new send |
| FR-M7 | 4 | Fix | Stale decline leaves the award |
| FR-M8 | 4 | Prove | Completion and rating test |
| FR-M9 | 4 | Prove | Landlord marks the contractor invoice paid; tenant cannot |
| FR-C1 | 4 | Prove | Registration and approval tests |
| FR-C2 | 4 | Fix | Employs-staff requires employers’ liability |
| FR-C3 | 4 | Fix | Unique company number |
| FR-C4 | 4 | Fix | Radius returns the profile to review |
| FR-C5 | 4 | Fix | Claim token, 72 hours, single use |
| FR-C6 | 4 | Fix | Contractor 403 outside contractor jobs |
| FR-D1 | 5 | Prove | `DocumentsHttpTest` |
| FR-D2 | 5 | Prove | Cross-account download 404 |
| FR-CP1 | 5 | Prove | `ComplianceHttpTest` |
| FR-CP2 | 5 | Fix | File-less certificate is “Not provided yet” |
| FR-N1 | 5 | Fix | Contractor visit follows the job’s address rule |
| FR-N2 | 5, 6 | Fix | Email and in-app, or a visible mail failure |
| FR-N3 | 5 | Fix | Security messages ignore the off switch |
| FR-NF1 | 6 | Fix | Assert commands, worker files, content seed |
| FR-NF2 | 3 | Fix | Card and manual pay cannot double-settle |
| FR-NF3 | 2, 5 | Fix | Revoke, password, and email confirmation end other sessions |
| FR-NF4 | 5 | Fix | Private disk; Super Admin downloads contractor files |
| FR-NF5 | 5 | Prove | £ and UK dates on rent and offer |
| FR-NF6 | 6, 7 | Fix | Staging rehearsal, then production secrets |

64 rows. Journey 7 signs scripts A–E against this list. A row that is still open blocks the rehearsal.
