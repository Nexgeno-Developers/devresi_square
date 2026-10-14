# Functional requirements — landlord launch

**Document:** FRD-LANDLORD-LAUNCH  
**Version:** 1.0  
**Date:** 14 October 2026  
**Product:** Resisquare landlord launch  
**Market:** England-first, GBP  
**Status:** Launch definition. A requirement here is required behavior on launch day.

This document defines the product that is ready to launch: the paying landlord workspace, the tenant portal, and the contractor marketplace. It describes how that product must behave. Where the current build differs, the requirement still stands and the difference is a launch defect.

Related documents:

- `docs/launch/SCOPE_CONTRACT.md` — pillars and change control. This FRD adds the contractor marketplace to that launch.
- `docs/launch/ACCEPTANCE_SCRIPTS.md` — sign-off scripts. Section 14 below adds the marketplace script.
- `docs/resisquare-saas/02-resisquare-property-management-saas-prd.md` — wider platform PRD. Estate-agent workspaces, agency accounting, and branches stay outside this launch.

## 1. Decisions locked for launch

These choices are part of the requirements. They close the gaps found in the 14 October 2026 audit.

| Topic | Launch rule |
|---|---|
| Who this launch is for | A self-managing landlord, the tenants of that landlord, and approved contractors instructed by that landlord. |
| Address release | The full property address, access notes, and tenant contact go to one contractor at a time, and only after the landlord accepts that contractor. A second open offer does not receive them. |
| Job instruction | `awarded_offer_id` is set only by a firm accept. An inspection accept grants a visit and exclusive access. It does not instruct the paid job. |
| Portal entry | A tenant portal session requires an active tenant membership with login allowed on the current account. A role name on its own does not open the portal. |
| Tenancy and login | Saving a tenancy does not create a login. Login is a separate invite, and the screen reports the mailer result. |
| Ending a tenancy | Archiving and deleting a tenancy both close portal access for members who have no other active tenancy on the account. |
| Co-owners | A co-owner added in onboarding is a contact. They do not receive the global Owner role, and they cannot log in until they accept an invite. |
| Rent visibility | Adults on an active tenancy can see that tenancy’s invoices. Only the named payer can start a card payment. |
| Login email | A new email becomes the login email after the new address confirms it. |
| Contractor changes | After approval, changes to trades, postcode, radius, or company number return the profile to review before the contractor can be sent new jobs. |
| Company number | One company number belongs to one active contractor profile. |
| Landlord route wall | Every non-portal member of a landlord account is limited to the landlord allowlist. A staff or contact membership cannot open routes the paying landlord cannot open. |
| Property wizard | The passport wizard stays off. Adding a property uses the onboarding overlay. |
| Onboarding result | Invite sent, failed, or skipped is decided on the server. The browser cannot supply that result. |

## 2. Actors

| Actor | Who they are | What they can do at launch |
|---|---|---|
| Landlord operator | Owner or admin membership on a landlord account | Run the workspace inside the allowlist: portfolio, tenancies, people, repairs, documents, compliance, finance, calendar, billing. |
| Tenant | Active tenant membership with login allowed | Use the tenant portal for their active tenancies only. |
| Contractor | Approved contractor profile, signed in on the contractor portal | See invited jobs, submit offers, do an awarded or inspection-accepted job, upload completion evidence. |
| Co-owner contact | Named on a property, no portal invite yet | No login. Their name, email, and phone are visible to the landlord operator. |
| Super Admin | Platform operator | Manage plans, accounts, and contractor approval. Super Admin is not a tenant and is not a landlord operator by role alone. |
| Landlord contact, staff, property manager | Other memberships that can exist on the account | Same allowlist as the landlord operator, or no workspace access. They never receive a wider set of routes. |

Estate-agent workspaces, branch staff, and agency accounting users are outside this launch. They keep their existing product surface. This FRD does not redesign it.

## 3. Scope

### 3.1 In launch

- Landlord registration, email OTP, trial, and Landlord Basic billing.
- First-home onboarding overlay, then add-another-property through the same overlay.
- Properties, tenancies, people, portal invite / resend / revoke.
- Tenant portal: home, tenancy confirmation, rent, maintenance, documents, calendar, profile, notification preferences.
- Rent invoices, manual payment, bank details, tenant card pay, recurring issue by scheduler.
- Repairs raised by the landlord or the tenant, then the contractor marketplace on that repair.
- Contractor registration, document review, approval, job list, offers, exclusive access, completion.
- Documents with portal visibility, and downloads that check account plus visibility.
- Compliance capture for EPC, gas safety, and EICR, with expiry and reminders.
- Manual deposit-protection record: scheme, reference, dates, checklist, documents, reminders.
- Calendar visits and reminders.
- Email and in-app notifications for the events in section 12.
- Landlord billing and plan page.

### 3.2 Out of launch

- Agency general ledger, client-money ledgers, and sales offers.
- Branches, designations, and company profile as landlord features.
- Property passport wizard (`LANDLORD_WIZARD_ENABLED` remains false).
- TDS, DPS, and MyDeposits API integrations.
- Auto-generated tenancy agreements or How to Rent packs.
- AML / KYC for estate agents.
- A rebuilt public marketing site.
- Contractor self-mark of a rent invoice as paid.
- More than one contractor holding the full address for the same repair at the same time.

## 4. Cross-cutting rules

These rules apply to every module.

### FR-X1 Workspace isolation

Every property, tenancy, repair, invoice, document, offer, and person-link used by this launch belongs to one account. A user can read or change a record only when their current account owns it, or when a portal rule below explicitly shares that record with them.

**Acceptance:** Opening another account’s property, tenancy, invoice, repair, offer, or document by id returns 403 or 404. The response body contains no name, email, phone, or address from the other account.

### FR-X2 Property lookups

`get_tenants_by_property` and `get_users_by_property_and_role` return rows only for a property on the current account. Cache keys include the account id. Changing a membership, name, email, or tenancy link forgets the affected keys before the next read.

**Acceptance:** A landlord, tenant, contractor, and property manager each receive an empty result, or 403, for a property on another account. After a tenant is renamed, the next lookup returns the new name.

### FR-X3 Route wall

A member of a landlord account who is not in the tenant portal and not in the contractor portal can open only the routes in `config/landlord_mvp.php`. Blocked prefixes win. Agency offers, accounting, staff admin, branches, and work-order admin stay blocked for every such member, including staff and landlord contacts.

**Acceptance:** The landlord owner receives 403 on `/admin/offers`. A staff membership on the same account also receives 403. A tenant and a contractor receive 403 on `/admin/offers` and land in their own portal after login.

### FR-X4 Portal identity

The tenant portal opens only when the current account membership has member type tenant, status active, and login allowed. The Spatie role Tenant is not sufficient. A user with that role and a staff membership stays out of the portal. A user with that role and no account is signed out.

Ending the last active tenancy, or deleting it, sets that membership so the user can no longer open the portal. `TenantMember.can_login` and the account membership stay in agreement.

**Acceptance:** Role plus staff membership receives 403 on `/admin/portal/rent`. After the only tenancy is deleted, the former tenant’s next request is signed out or refused.

### FR-X5 Honest empty and error states

Lists with no rows explain what is missing and the next action. A failed payment, a failed invite, and a refused contractor stay in that failed state on screen. The interface does not report success from values the browser posted.

### FR-X6 Audit trail

Award, decline, cancel, invite, portal revoke, invoice void, manual rent payment, and contractor approval write an actor, a time, and the before/after state.

## 5. Landlord registration and billing

### FR-B1 Sign up

A new landlord registers with name, email, and password, confirms the email with a one-time code, accepts the current terms and privacy notice, and starts a trial on Landlord Basic. The workspace account type is landlord. The registering user is the owner membership and has the Landlord role.

**Acceptance:** After OTP, the user is inside the first-home overlay. They do not see agency staff, branch, or accounting navigation.

### FR-B2 Plan

Billing & Plan shows the trial or paid plan, renewal, and limits in plain language. Stripe customer and subscription ids are not the primary text. Checkout and the customer portal are the ways to pay Resisquare. Rent card payments use the separate rent Stripe account.

**Acceptance:** A trial landlord can open Checkout. A past-due or cancelled account can still open billing. Business modules follow the account-status rules already used by the app: suspended and cancelled accounts cannot run the portfolio, and the screen says why.

### FR-B3 Limits

Creating a property, inviting a portal user, or another limited action checks the plan limit first and shows the limit message. The action does not half-save.

## 6. Onboarding

### FR-O1 First home

A landlord account with no property sees the overlay until the first property is saved. The steps are address, optional co-owners, and optional first tenancy. The landlord can finish later. Finish later on an empty portfolio brings the overlay back. Finish later after a property exists does not trap them in setup.

Address search is postcode-first. Manual entry is available. The review shows the address and labels missing facts as “Not provided yet”.

### FR-O2 Co-owners

Each extra owner is stored as a contact on the property’s owner group: name, email, phone. The lead owner is the logged-in landlord, and the lead email must match that login.

If the email already belongs to a user, the system does not assign the Owner role, does not change their password, and does not attach them to this account as a manager. It stores the contact and tells the landlord that this person already has a Resisquare login and must be invited.

A brand-new co-owner email does not receive `can_login` until an invite is sent and accepted.

**Acceptance:** Posting an existing email leaves that user without a new Owner role and without a new login on this account. The landlord sees them as a contact.

### FR-O3 First tenancy

The tenancy step can record the lead tenant’s name, email, phone, rent, deposit, frequency, and move-in. It creates the tenancy and the tenant member. It grants portal login only when the landlord asks to send an invite.

The completion payload reports invite state from the server:

- not requested
- sent
- failed, with a short reason

The client cannot set `invited`, `sent`, or `invite_error`.

**Acceptance:** A completion request that posts `invited: true` while no mail was sent returns invited false. A requested invite that the mailer accepts returns sent true, creates the tenant membership with login allowed, and the tenant can set a password from that message.

### FR-O4 Add another property

“Add property” reuses the overlay in add mode. Saving it does not clear the landlord’s completed onboarding, and it does not mark onboarding incomplete. Opening the dashboard never tells the landlord that an invite succeeded unless section FR-O3 recorded that result.

### FR-O5 Wizard

`/admin/properties/wizard` redirects to the property list with the add-property overlay. Launch does not use `created_by` as ownership. Property access for the landlord operator is the current account.

## 7. Properties

### FR-P1 Portfolio

The landlord sees properties on the current account, with a human address as the title. Each property shows letting status, the active tenancy when there is one, and open repairs.

### FR-P2 Record

A property stores address lines, postcode, type, beds, baths, furnishing, rent, deposit facts used by the tenancy, and compliance summary. Identity is the account plus the normalised address. Two live properties on the same account cannot share the same identity hash.

Archive is the way to retire a property that has tenancy or money history. Archive does not leave an Active tenancy on that property.

### FR-P3 Media and notes

Photos and notes belong to the property’s account. Internal notes stay off the tenant portal and off the contractor job.

## 8. People

### FR-PE1 Directory

People lists owners, tenants, and other contacts for this account. Adding a contact does not grant a portal login. The page says that.

### FR-PE2 Tenant invite

Invite creates or reuses the user, creates an active tenant account membership with login allowed, links the active tenancy member, and sends one invite message. Resend sends a new message and does not leave two valid set-password links. Revoke clears login on that membership immediately, including sessions already open for that portal.

### FR-PE3 Welcome from the tenancy form

The tenancy form’s invite choice uses the same invite service as People. There is no second, empty email loop. If the template `tenant_welcome` is missing, the invite fails visibly and login is not marked sent.

## 9. Tenancies

### FR-T1 Create

A tenancy belongs to one property on the current account. Required facts: property, status, move-in, rent, deposit, at least one tenant, and one main tenant. Frequency is Monthly or Weekly. Creating an Active tenancy archives any other Active tenancy on that property.

The tenant member row records the user and whether they are the main person. `can_login` stays off unless FR-PE2 or FR-PE3 ran.

**Acceptance:** After save, the member’s `can_login` is 0 and no new tenant account membership exists, unless the landlord chose invite and the mailer accepted it.

### FR-T2 Edit and archive

Day-to-day edit can change rent, dates, deposit, status, and members. Moving status to Archived closes portal access for members who have no other Active tenancy on the account.

### FR-T3 Delete

Delete removes the tenancy and then runs the same portal close as archive. Members whose tenancy link is cleared cannot keep an active tenant login for that home.

**Acceptance:** After delete, the tenancy row is gone and the former tenant’s account membership is not both active and login-allowed for that tenancy.

### FR-T4 Confirmation

The tenant is asked to confirm details only for an Active tenancy, and only while their membership is pending, empty, or waiting on a correction. An Archived tenancy does not open the overlay.

The tenant can confirm or request a correction. The landlord approves or rejects a correction. Reject leaves the stored tenancy unchanged and tells the tenant. One tenant cannot confirm another household’s tenancy.

The overlay shows the active address, rent, dates, and deposit that are stored, and it labels them as the facts to confirm.

### FR-T5 Deposit record

The tenancy can store scheme, reference, who holds the deposit, date received, date protected, prescribed information sent, and written terms sent. Reminders use those dates. Launch does not call a deposit-scheme API.

### FR-T6 Household

More than one tenant member can sit on one tenancy. The main person is the named rent payer for card payments. Other members are household viewers as defined in FR-R2.

## 10. Tenant portal

The portal is a separate shell. It does not show staff navigation, agency offers, or accounting. On a phone-width screen the main areas are bottom tabs: Home, Rent, Maintenance, Documents, Calendar.

### FR-TN1 Entry

After login, a tenant lands on tenant home. A landlord operator who opens a portal URL receives 403. A contractor who opens a portal URL receives 403.

### FR-TN2 Home

Home shows each Active tenancy: address, rent, dates, household, and the confirmation overlay when FR-T4 says it is due. Ended tenancies are absent from home.

### FR-TN3 My tenancy

The tenant sees the active tenancy facts and the confirmation state. Historical tenancies, if shown, are read-only and do not ask for confirmation.

### FR-TN4 Profile

The tenant can change first name, last name, and phone immediately. Changing email stores the new address as pending and sends a confirmation link. The current login email and `email_verified_at` stay as they are until the link is used. Password change asks for the current password, requires a confirmation field, and ends other sessions.

**Acceptance:** After a profile save with a new email, sign-in with the old email still works, and sign-in with the new email waits until confirmation.

### FR-TN5 Maintenance

The tenant can raise a repair on an Active tenancy: category, description, availability, and an optional photo. The repair is stored on that property and account, with the tenant as reporter. They can see status suitable for a tenant. They cannot open find-contractor, send invites, accept or decline offers, cancel a job, rate a contractor, or mark a contractor invoice paid.

**Acceptance:** The tenant receives 200 on the maintenance form and 403 on `/admin/property-repairs/{id}/find-contractor` and on offer accept.

## 11. Rent

### FR-R1 Invoice

The landlord can issue a rent invoice for an Active tenancy: invoice number, issue date, due date, period, amount, and the named payer (`tenant_user_id` is the main tenant unless the landlord picks another member of that tenancy). Status starts as issued. Balance starts equal to amount.

States: issued, partial, paid, void. Void blocks pay. An issued or partial invoice with balance above zero is payable.

Recurring rent is created by the scheduler from the tenancy frequency and rent. A missed run is visible to the operator. It does not silently skip a period.

### FR-R2 Who can see and pay

Every tenant member of the Active tenancy can see its invoices and the balance. The Pay action is shown only to the named payer. Other members see who can pay and, when the landlord has set them, the bank details. They do not get a Pay button that then fails.

**Acceptance:** The housemate’s pay request returns 403 or 404. The named payer’s pay request redirects to Stripe Checkout. Until Stripe reports success, status stays issued or partial and the balance is unchanged.

### FR-R3 Card pay

Card pay uses the rent Stripe account, Checkout, the webhook, and reconcile. The paid amount applies once. The webhook checks the invoice, the account, and the tenant user. A failed or abandoned Checkout leaves the invoice open and the tenant sees a clear message.

The portal has no control that marks an invoice paid.

### FR-R4 Manual pay

The landlord can record a bank payment against an open invoice, including a partial amount. Balance and status follow the amount recorded. The tenant sees the updated balance on the next load.

### FR-R5 Landlord finance

The finance area lists invoices for the current account, supports void, and shows an honest empty state when there are no invoices. It does not offer agency ledger or sales-invoice tools.

## 12. Repairs and the contractor marketplace

### FR-M1 Raise

A landlord or a tenant can raise a repair with category, description, priority, and optional photos. The repair stores the property, the account, and the reporting tenant when a tenant raised it. Tenant-visible status uses plain language. Internal notes stay off the tenant portal and off the pre-award contractor view.

### FR-M2 Find

Find-a-contractor lists approved contractors who cover the repair’s trade and whose coverage radius includes the property. Filters for trade, distance, Gas Safe, and DBS match the list the landlord can send to.

Gas boiler work and a smell-of-gas report require a Gas Safe registered contractor. Electrical work requires a registered electrical contractor. A contractor approved only for general or plumbing is excluded, with the reason visible to the landlord.

### FR-M3 Send

Send accepts only contractor ids that FR-M2 would list for this repair at this moment. Distance, radius, trade, and compliance are checked again on the write. A contractor who fails is skipped with a reason, and no offer row is created for them. The landlord sees who was sent and who was skipped.

An emergency send-all uses the same eligibility rules, then the emergency cap.

**Acceptance:** A contractor outside the radius is skipped. A plumber sent onto a gas repair is skipped with the Gas Safe reason. Neither gets an offer.

### FR-M4 What the contractor sees before acceptance

The job list and the job page show the town and the outward postcode. They do not show the full street, access notes, or tenant phone and email. The page says those appear after the landlord accepts this contractor.

### FR-M5 Offers

A contractor can decline the invite or submit an offer: a firm price, or an inspection visit with an inspection fee. The offer stores gross and net amounts, what is included, and an expiry. Submitting again raises the revision. The landlord’s accept must send the revision they saw. A stale revision is rejected and the landlord is shown the new price.

### FR-M6 Exclusive access

When the landlord accepts one inspection offer:

- That offer becomes inspection accepted and records the time.
- That contractor’s job page then shows the full address, access notes, and tenant contact.
- Every other open offer on that repair is closed so it cannot also be accepted for access.
- `awarded_offer_id` stays empty.
- The repair is not described as a firm instruction.

Cancelling that inspection removes the address from the contractor view and allows the landlord to accept a different contractor.

When the landlord accepts one firm offer:

- That offer becomes the awarded offer and `awarded_offer_id` points at it.
- Other open offers close as not selected or withdrawn.
- The awarded contractor sees the full address and access notes.
- The job can move through scheduled, in progress, completed, and confirmed.

**Acceptance:** Two inspection offers cannot both be inspection accepted. After one acceptance, the other contractor’s job page does not contain the street. After a firm accept, exactly one offer is awarded.

### FR-M7 Decline and cancel

Decline and cancel lock the offer and the repair, re-read status, and then change state. A decline cannot run against an offer that has just been firm-accepted or inspection-accepted. The awarded id and the offer status cannot disagree: if the offer is not selected, it is not the awarded offer; if it is awarded, it is not not-selected.

**Acceptance:** An accept that commits, followed by a decline that started from the older status, leaves the award and the offer status in agreement. The decline is rejected.

### FR-M8 Completion

The contractor can mark the job complete with completion photos when the job is awarded and in a completable state. The landlord confirms completion and can rate. A variation or reschedule is an explicit decision on the awarded job. The tenant sees the tenant-safe status, not the quote comparison.

### FR-M9 Contractor invoice

The contractor invoice on a job is separate from the rent invoice. The landlord records it as paid. The tenant cannot mark it paid.

## 13. Contractor account

### FR-C1 Registration

Public registration creates a draft profile, verifies email with OTP, and collects business facts and documents. The register post is throttled. The contractor cannot be sent a job until approval status is approved, required documents are approved, and terms are accepted.

### FR-C2 Compliance pack

Required documents include photo id and public liability. Gas, electrical, and other regulated trades require the documents configured for that trade. If the contractor says they employ staff, employers’ liability is required and the cover meets the configured minimum. The question “Do you employ staff?” is on the business step, and the document is required when the answer is yes. It is not filed only under optional extras while the answer can stay no by default.

Admin approval is manual. Launch does not call Gas Safe or Companies House.

### FR-C3 Company number

For a limited company, the company number is required and unique among profiles that are not removed. The form and the database both enforce that.

### FR-C4 Changes after approval

The contractor can edit phone and trading name while approved. Saving a new trade list, postcode, radius, or company number sets the profile back to review and blocks new invites until a reviewer approves again. Jobs already awarded stay with that contractor.

**Acceptance:** An approved profile that posts a new radius does not stay approved.

### FR-C5 Legacy claim

A claim link is a single-use token stored on the server, valid for 72 hours, and bound to that user. Using it sets the password, records terms, and signs the user in. A second use fails. Forwarding the email after use does not keep the link alive. There is no 30-day signed URL that can set the password on its own.

### FR-C6 Contractor navigation

A contractor login lands on contractor jobs. Workspace admin, tenant portal, finance, and offers return 403.

## 14. Documents

### FR-D1 Upload

A landlord upload has a name, a type, a linked property or tenancy, and a visibility. Visibility for the tenant portal is shared or portal. Private documents stay off the portal.

### FR-D2 Download

Download is allowed when the document’s account is the current account and, for a tenant, the document is shared and linked to an Active tenancy, property, or the tenant themselves. Anyone else receives 404. The file is not served from a public disk path.

**Acceptance:** A tenant request for a shared document on another account returns 404 and does not start a file response.

## 15. Compliance

### FR-CP1 Certificates

Each property can store EPC, gas safety, and EICR with issue date, expiry, file, and whether it was served to the tenant. Expired and soon-to-expire items show on the landlord home and the compliance list. Reminders go by email and in-app notification to the landlord operator.

### FR-CP2 Evidence

A certificate without a file is “Not provided yet”. The screen does not show it as valid.

## 16. Calendar and notifications

### FR-N1 Visits

The landlord can put a visit on a property. Tenants of an Active tenancy on that property see it on the portal calendar. Contractors see visits only when the visit is attached to a job they are allowed to see under FR-M4 and FR-M6.

### FR-N2 Events that notify

The launch sends email and an in-app notification for:

- tenant portal invite, resend, and revoke
- tenancy details confirmed, correction requested, approved, and rejected
- rent invoice issued, payment received, and payment failed
- repair raised, contractor invited, offer received, inspection accepted, firm award, not selected, completion
- compliance expiry reminder
- visit reminder

Each message names the home in tenant-safe language. Pre-award contractor messages use town and outward postcode only.

A missing template fails the action that depended on it, or completes the data change and shows a visible mail failure. It does not report sent.

### FR-N3 Preferences

The tenant can turn notification categories on or off in profile, except messages required for security: invite, password, and email confirmation.

## 17. Non-functional requirements

### FR-NF1 Environments

Production uses live Stripe keys for subscriptions and for rent, a real mail transport, a queue worker, and the scheduler. Debugbar and staging copy are absent. Secrets are not in the repository.

### FR-NF2 Money

Rent Checkout and subscription Checkout stay on their own Stripe accounts. A webhook applies a rent payment once. Ledger balance, invoice balance, and the Stripe payment agree. Manual pay and card pay cannot both settle the same balance.

### FR-NF3 Sessions

Revoke, password change, and email confirmation end sessions that should no longer be valid. Portal login checks the membership on every request.

### FR-NF4 Files

Contractor compliance files and private documents are encrypted or stored on a private disk. Admin download of contractor documents is limited to Super Admin reviewers.

### FR-NF5 Time and locale

Dates and money are shown in UK format. Currency is GBP. Times used for offer expiry and reminders use the application timezone consistently.

### FR-NF6 Operability

Launch follows `docs/launch/GA_GATE.md` and `docs/launch/OPS_RUNBOOK.md`: health check, queue, mail, webhook, and a rehearsed rollback. A money fault turns off rent Checkout and leaves bank transfer available.

## 18. Launch acceptance

Scripts A to D in `docs/launch/ACCEPTANCE_SCRIPTS.md` still apply. Add script E.

### E. Contractor marketplace

1. Approve a contractor whose radius covers the property and whose trade matches a non-gas repair.
2. Landlord finds them and sends the job. The contractor sees town and outward postcode only.
3. A second contractor outside the radius is skipped and has no offer.
4. A plumbing-only contractor is skipped on a gas repair.
5. Two inspection offers exist. Accepting one closes the other. Only the accepted contractor sees the street. `awarded_offer_id` is empty.
6. Firm-accept one price. The award id matches that offer. The other offer is not open. Decline cannot clear the award while leaving the id in place.
7. Tenant cannot open find-contractor or accept an offer.
8. Claim link works once within 72 hours and fails on the second use.
9. Approved contractor changes radius and can no longer be sent a new job until review.
10. Two profiles cannot save the same company number.

**Pass:** Steps 1–10 on the release candidate, with probe data removed afterwards.

### Stop-ship

Launch waits if any requirement in sections 4, 9, 10, 11, or 12 fails, if one account can read another account’s tenant contact details, if two contractors can see the same full address before a single acceptance, or if a rent payment can mark an invoice paid without Stripe or a landlord manual entry.

## 19. Trace from the 14 October 2026 audit

| Audit finding | Requirement that replaces it |
|---|---|
| Property lookup returned another account’s tenants | FR-X2 |
| Offers stayed open to tenants, contractors, and staff | FR-X3 |
| Two inspection accepts both revealed the street | FR-M6 |
| Tenant could accept an offer | FR-TN5, FR-M6 |
| Stale decline cleared an awarded offer | FR-M7 |
| Send ignored distance | FR-M3 |
| 30-day claim link set the password | FR-C5 |
| Tenant role plus staff membership opened the portal | FR-X4 |
| Tenancy create did not open the portal or send mail | FR-T1, FR-PE3, FR-O3 |
| Delete left portal login in place | FR-T3 |
| Co-owner email received the Owner role | FR-O2 |
| Archived tenancy opened confirmation | FR-T4 |
| Housemate saw an invoice they could not pay | FR-R2 |
| Email change was immediate | FR-TN4 |
| Approved contractor widened radius and stayed approved | FR-C4 |
| Company number could be duplicated | FR-C3 |
| Onboarding echoed the browser’s invite result | FR-O3 |
| Gas Safe gate, and other-account document download | Keep, as FR-M2, FR-M3, and FR-D2 |
