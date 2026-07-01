# Landlord-first SaaS development execution plan

## Document purpose

This document is the step-by-step implementation plan for converting the current Laravel application into a Landlord-first SaaS product.

It translates the product, pricing and database recommendations in the following documents into an executable development sequence:

- [02-resisquare-property-management-saas-prd.md](02-resisquare-property-management-saas-prd.md)
- [03-user-roles-and-permissions.md](03-user-roles-and-permissions.md)
- [04-pricing-plans-and-stripe-flow.md](04-pricing-plans-and-stripe-flow.md)
- [06-database-and-module-mapping.md](06-database-and-module-mapping.md)

The first release is intentionally restricted to self-managing Landlords. Estate Agent signup, branches, staff subscriptions, Property Manager add-ons and advanced billing are later phases.

## Delivery target and capacity

| Item | Commitment |
|---|---|
| Development start | 24 June 2026 |
| Phase 1 target | 31 July 2026 |
| Available working days | 28 days |
| Daily development time | 3 hours, Monday to Friday |
| Total Phase 1 capacity | 84 focused hours |
| Release type | Restricted Landlord SaaS MVP |

The 31 July target is possible only with the fixed Phase 1 scope in this document. It is not the deadline for every capability in the full Lovable prototype.

## Phase 1 outcome

At the end of Phase 1, a Landlord must be able to:

1. Open the public pricing page.
2. Select an active Landlord plan and monthly or annual billing.
3. Register an account and verify their email address.
4. Provide the Landlord portfolio/workspace details.
5. Review the order, VAT wording, trial and renewal information.
6. Complete Stripe-hosted subscription Checkout.
7. Have the account activated only after a verified Stripe webhook.
8. Log in and reach the existing Landlord dashboard.
9. Use only the workspace-scoped operational modules enabled for Phase 1.
10. Be prevented from exceeding the active-property limit configured on the plan.
11. View the current plan, subscription status, renewal date and property usage.

A Resisquare Super Admin must be able to:

1. Create and edit Landlord plans.
2. Configure monthly and annual Stripe Price IDs.
3. Configure plan features and active-property limits.
4. Publish, unpublish or deactivate a plan.
5. View each Landlord's workspace, selected plan and subscription state.

## What already exists

The following current application components should be reused rather than rebuilt:

- Laravel 11 and PHP 8.2.
- `stripe/stripe-php` is already installed.
- Public login, registration, OTP verification and password-reset screens.
- `users`, `companies`, `branches` and Spatie role/permission structures.
- Landlord role and role-aware login redirection.
- Existing backend dashboard and operational property-management modules.
- Existing Company ownership fields.
- Existing email infrastructure.

## Current gaps that must be corrected

| Current behaviour | Required SaaS behaviour |
|---|---|
| Pricing is hard-coded and buttons do not purchase anything | Pricing is loaded from published plans and starts a server-validated signup/checkout flow |
| Registration accepts several user types and ends in manual approval | Phase 1 self-service registration accepts Landlord only and activates after verification plus Stripe confirmation |
| A company/workspace is created only for Estate Agents | Every paying Landlord receives a Landlord portfolio workspace |
| Business ownership is frequently inferred from `created_by` | Access is determined by the authenticated user's current `company_id`/membership and records are workspace-scoped |
| Roles are global | Phase 1 authorisation combines the Landlord role with an active workspace and subscription |
| Existing Stripe code is for property invoices and uses one-off payment logic | SaaS subscriptions use separate controllers, services, tables and verified webhooks |
| Success redirects may be treated as payment confirmation | The success page never activates access; only verified webhook state activates access |
| No plan, subscription, entitlement or webhook tables exist | Dedicated SaaS billing and entitlement records are introduced |
| Tests are only Laravel example tests | Billing, tenant isolation, provisioning and plan-limit feature tests are required |

## Non-negotiable architecture rules

1. A Landlord portfolio is represented by a `companies` row with `workspace_type = landlord`.
2. `created_by` remains audit information and is not the tenant ownership boundary.
3. Every operational module exposed to a Landlord must be scoped to the current workspace.
4. A request must never trust a browser-supplied amount, Stripe Price ID, `company_id` or entitlement limit.
5. The browser submits a plan code and billing interval; the server resolves the published local price.
6. Property invoices and Resisquare SaaS subscription invoices remain separate.
7. Stripe keys exist only in environment configuration and are never displayed in Super Admin.
8. Stripe webhook signatures are verified using the raw request body.
9. Stripe event IDs are stored with a unique constraint so duplicate delivery is safe.
10. A Checkout success URL never activates a subscription or user.
11. Limits are enforced in backend services/controllers as well as reflected in the UI.
12. A module that has not passed cross-workspace isolation tests must not be available to paying users.

## Target request flow

```text
Published Landlord plan
    -> pricing selection
    -> registration and email OTP
    -> pending User + Landlord workspace + membership
    -> order summary
    -> server-created Stripe Checkout Session
    -> Stripe-hosted Checkout
    -> success page displays "confirming subscription"
    -> verified webhook synchronises subscription
    -> user/workspace becomes active
    -> login
    -> Landlord dashboard with plan entitlements
```

## Phase 1 implementation modules

### Module 0: Baseline, scope freeze and release safety

**Estimate:** 3 hours

**Purpose:** Establish a reproducible starting point and prevent unrelated application work from expanding the release.

#### Tasks

- Confirm that current migrations run successfully on a clean test database.
- Record the current login, registration, dashboard and property-create behaviour.
- Confirm that the `Landlord` and `Super Admin` roles exist in all target environments.
- Confirm email OTP delivery in the development environment.
- Add the required Stripe test configuration placeholders to `.env.example`:
  - publishable key
  - secret key
  - webhook signing secret
- Define the Phase 1 route allowlist for a Landlord.
- Hide and block any operational module that is not workspace-safe by release time.
- Freeze Phase 1 exclusions in the project tracker.

#### Acceptance criteria

- A clean local test environment can run the application.
- Required environment values are documented without committing secrets.
- The Phase 1 feature and route allowlists are agreed.

---

### Module 1: SaaS database foundation

**Estimate:** 8 hours

**Purpose:** Add the minimum stable schema needed for Landlord workspaces, plans, Checkout and subscriptions without reusing property-accounting tables.

#### Schema changes

Alter `companies`:

- `workspace_type`: `landlord` or `estate_agent`; Phase 1 creates only `landlord`.
- `status`: `pending_checkout`, `active`, `restricted`, `cancelled` or `suspended`.
- `stripe_customer_id`: nullable and unique.
- `billing_owner_user_id`: nullable foreign key to `users`.
- `timezone`: default `Europe/London`.
- `currency`: default `gbp`.

Add `company_memberships`:

- `company_id`
- `user_id`
- `membership_type`
- `status`
- `is_billing_owner`
- invitation/activation timestamps
- unique `(company_id, user_id)`

Add `plans`:

- stable `code`
- name and description
- `workspace_type`
- active/published flags
- display order

Add `plan_prices`:

- `plan_id`
- `interval`: `month` or `year`
- currency and amount in minor units
- VAT/tax wording
- Stripe Product ID
- Stripe Price ID, unique
- active dates/status

Add `plan_features`:

- `plan_id`
- stable feature code
- included boolean or numeric limit
- unique `(plan_id, feature_code)`

Phase 1 feature codes:

- `properties`
- `administrators`
- `storage_gb`
- `portal_users`

Add `subscriptions`:

- `company_id`
- `plan_id`
- Stripe Customer and Subscription IDs
- local status
- billing interval
- trial start/end
- current-period start/end
- cancellation fields
- timestamps from the last Stripe update

Add `checkout_attempts`:

- user and company
- selected plan/price
- server-calculated order snapshot
- Stripe Checkout Session ID
- `pending`, `completed`, `expired` or `cancelled` state
- expiry and completion timestamps

Add `stripe_webhook_events`:

- unique Stripe event ID
- event type and object ID
- received, processed and failed timestamps
- attempt count and safe error text
- payload hash or minimised payload; no card data or secrets

#### Application classes

Create backed enums or constants for:

- workspace type
- company status
- subscription status
- billing interval
- entitlement feature code

Create model relationships among Company, User, Membership, Plan, PlanPrice and Subscription.

#### Acceptance criteria

- Migrations run up and down in a test database.
- Stripe IDs and plan codes have database uniqueness constraints.
- One user can have a pending or active membership in a Landlord workspace.
- SaaS billing records cannot be confused with property/rent invoices.

---

### Module 2: Workspace context and tenant isolation

**Estimate:** 8 hours for the restricted Phase 1 route set

**Purpose:** Prevent one Landlord from viewing or changing another Landlord's information.

This is a release gate, not an optional enhancement.

#### Tasks

- Add a `TenantContext` or `CurrentWorkspace` service resolved after authentication.
- Resolve workspace from an active `company_membership`; retain `users.company_id` only as a transitional compatibility field.
- Add a company-owned model contract/trait with an explicit `forCompany()` scope.
- Set `company_id` from the server-side context on all Phase 1 writes.
- Add tenant-aware policies and route model binding for enabled Landlord routes.
- Update the Phase 1 property queries to use `company_id`, not only `created_by`.
- Scope dashboard counts to the current workspace.
- Check relationships before save: property, tenancy, contact and related records must belong to the same company.
- Prefix new cache keys and file/storage paths with the workspace public identifier where applicable.
- Deny Landlord access to Super Admin and Estate Agent-only routes.

#### Phase 1 exposure rule

Only enable a module when its list, show, create, update, archive, search, download and export paths have been checked for workspace scope. Hide and return `403` for unfinished modules.

#### Required isolation test pattern

For each enabled module:

1. Create Workspace A and Workspace B.
2. Create equivalent records in both.
3. Authenticate as Landlord A.
4. Confirm A cannot list, show, update, archive, search, download or infer B's records.

#### Acceptance criteria

- Directly changing a record ID in a URL cannot access another workspace.
- Form manipulation cannot assign a record to an arbitrary `company_id`.
- Dashboard totals include only the current workspace.
- Every Phase 1-enabled operational route has an isolation test.

---

### Module 3: Super Admin Landlord plan catalogue

**Estimate:** 7 hours

**Purpose:** Allow Resisquare to control which Landlord plans appear on the public site and which limits they provide.

#### Routes and screens

- Super Admin plan list.
- Create Landlord plan.
- Edit plan metadata and features.
- Configure monthly and annual price records.
- Publish/unpublish and activate/deactivate actions.
- Read-only subscribed-customer count.

#### Required fields

- Plan code, name and description.
- Monthly and annual amounts in GBP minor units.
- VAT display setting.
- Stripe Product ID.
- Monthly Stripe Price ID.
- Annual Stripe Price ID.
- Active property limit.
- Administrator, storage and portal-user display limits.
- Display order and published state.

#### Phase 1 Stripe decision

Products and Prices are created in the Stripe Dashboard and their IDs are mapped in Resisquare. The application does not create or mutate Stripe Products/Prices in Phase 1.

Stripe Prices are treated as immutable. Changing a commercial amount means creating a new Stripe Price and deactivating the old local price for new purchases. Existing subscriptions continue to reference their original price unless deliberately migrated later.

#### Acceptance criteria

- Only Super Admin can manage the catalogue.
- Invalid or duplicate Stripe Price IDs are rejected.
- An unpublished/inactive plan cannot be purchased.
- A plan with subscribers cannot be deleted.
- Historic subscribed price records remain available.

---

### Module 4: Public pricing and plan selection

**Estimate:** 5 hours

**Purpose:** Replace the current hard-coded pricing cards with a dynamic Landlord-only acquisition page.

#### Tasks

- Update `FrontendController::pricing()` to load only active, published Landlord plans and active prices.
- Replace hard-coded `resources/views/frontend/pricing.blade.php` values.
- Add monthly/annual toggle.
- Display GBP amounts consistently.
- Clearly show `+ VAT` or the configured tax wording.
- Display plan features and capacity limits.
- Connect each CTA to registration with a signed/session-backed plan code and interval.
- Reject missing, inactive or manipulated plan selection on the server.
- Provide an accessible responsive layout and empty state when no plan is published.

#### Acceptance criteria

- Super Admin plan changes appear on pricing without code changes.
- The browser cannot submit a custom price or raw Stripe Price ID.
- Refresh/back navigation retains a valid selected plan.
- Monthly and annual selections produce the correct local price.

---

### Module 5: Landlord registration, verification and pending workspace

**Estimate:** 8 hours

**Purpose:** Convert the existing manual-approval registration into a self-service Landlord signup while preserving OTP verification.

#### Tasks

- Restrict the new SaaS signup flow to `landlord`.
- Keep legacy registration types/manual approval separate until their later migration.
- Collect:
  - first and last name
  - email and optional phone
  - password and confirmation
  - portfolio/workspace name
  - billing contact/address fields needed for order/tax display
  - selected plan and interval from server-validated state
  - acceptance of current terms/privacy versions
- Reuse email OTP generation, expiry and resend behaviour.
- Rate-limit registration, OTP verification and resend endpoints.
- Never store a plain-text password; store a hash if the registration remains pending.
- After verification, transactionally create:
  - a disabled/pending User
  - a `landlord` Company workspace in `pending_checkout`
  - a pending billing-owner membership
  - a pending Checkout attempt
- Set `can_login = false` until subscription activation.
- Replace the manual "pending admin approval" result for this Landlord SaaS path with the order-summary step.
- Allow a verified pending user to resume Checkout without making duplicate users/workspaces.

#### Acceptance criteria

- Unverified users cannot proceed to Checkout.
- A successful email verification does not yet grant dashboard access.
- Repeated registration/resume attempts do not create duplicate workspaces.
- Passwords and OTPs are never logged or stored in plain text.
- Existing approved legacy accounts continue to log in.

---

### Module 6: Order summary and Stripe Checkout

**Estimate:** 8 hours

**Purpose:** Create a secure Stripe-hosted subscription checkout from the verified local catalogue selection.

#### Application components

Create a dedicated SaaS billing namespace, for example:

- `App\Services\Billing\StripeCheckoutService`
- `App\Http\Controllers\Billing\CheckoutController`
- `App\Http\Requests\Billing\StartCheckoutRequest`

Do not place SaaS subscription behaviour in property invoice controllers.

#### Tasks

- Build an order-summary page with:
  - plan name
  - interval
  - subtotal
  - explicit VAT wording/placeholder according to the commercial decision
  - 14-day trial and exact first-charge date when enabled
  - renewal interval/date
- Revalidate the selected plan/price on every server request.
- Create or reuse one Stripe Customer for the workspace.
- Create a Stripe Checkout Session with:
  - `mode=subscription`
  - the server-resolved Stripe Price ID
  - verified customer email
  - workspace and Checkout-attempt metadata
  - success and cancel routes
  - trial configuration when enabled
- Save the Session ID and expiry to the Checkout attempt.
- Add success, cancel, failure and retry screens.
- The success page polls local Checkout/subscription state and shows "Confirming your subscription" until the webhook is processed.

#### Acceptance criteria

- Checkout always uses the current active local catalogue price.
- A customer cannot change the amount using browser tools.
- Refreshing/retrying does not create uncontrolled duplicate sessions or workspaces.
- Opening the success URL manually does not activate the user.
- Test-mode Checkout supports both monthly and annual plans.

---

### Module 7: Stripe webhook and subscription synchronisation

**Estimate:** 10 hours

**Purpose:** Make verified asynchronous Stripe state the source of truth for subscription access.

#### Application components

Create, for example:

- `App\Http\Controllers\Billing\StripeWebhookController`
- `App\Services\Billing\SubscriptionSyncService`
- `App\Services\Billing\WorkspaceProvisioner`

Register a dedicated webhook route outside normal authenticated pages. Exclude only this exact route from CSRF and verify its Stripe signature.

#### Phase 1 event handling

| Stripe event | Required local action |
|---|---|
| `checkout.session.completed` | Link the Session, Customer and Subscription to the pending Checkout attempt/workspace |
| `customer.subscription.created` | Upsert plan, status, trial and current-period data |
| `customer.subscription.updated` | Synchronise status, period and cancellation fields |
| `customer.subscription.deleted` | Mark the local subscription ended/cancelled and prevent normal paid access |
| `invoice.paid` | Record successful billing state and clear a basic payment issue |
| `invoice.payment_failed` | Mark payment issue and notify the billing owner |

#### Processing rules

- Verify `Stripe-Signature` against the raw body and configured webhook secret.
- Insert the Stripe event ID before side effects; duplicate IDs return success without repeating work.
- Return a quick 2xx response after safe processing/dispatch.
- Use database transactions and locks for workspace/subscription updates.
- Handle events arriving out of order without overwriting newer subscription data.
- Store safe processing failures for Super Admin diagnosis.
- Never store or log card data, API keys or webhook secrets.

#### Activation rule

Activate the user/workspace only when the local subscription has been synchronised to an allowed Stripe state such as `trialing` or `active`.

#### Acceptance criteria

- Invalid signatures are rejected.
- The same event delivered twice has one business effect.
- A verified active/trialing subscription activates the pending workspace once.
- Failed or cancelled Checkout does not activate access.
- Webhook processing failures are visible without exposing secrets.

---

### Module 8: Workspace provisioning and Landlord dashboard access

**Estimate:** 6 hours

**Purpose:** Complete account activation and connect the new SaaS customer to the existing application.

#### Tasks

- On verified subscription activation, transactionally:
  - mark the Company active
  - mark the membership active and billing-owner
  - assign the Landlord role
  - enable login
  - set transitional `users.company_id` for existing module compatibility
  - record activation timestamps
- Send the activation/welcome email after the transaction commits.
- Redirect an authenticated Landlord to the existing dashboard.
- Update dashboard queries to current-workspace scope.
- Show current plan, subscription state and property usage summary.
- Build permission-aware navigation for the Phase 1 route allowlist.
- Add middleware that requires both an active membership and an allowed subscription state for paid business routes.
- Keep pricing, billing, support, logout and password-management routes reachable when business access is restricted.

#### Acceptance criteria

- Provisioning is safe to run more than once.
- A pending Checkout user cannot log in to the dashboard.
- An activated Landlord sees only their workspace information.
- Super Admin remains able to use platform administration.

---

### Module 9: Entitlements and active-property limit

**Estimate:** 6 hours

**Purpose:** Ensure a Landlord can manage the application only within the purchased plan.

#### Application component

Create a central service such as `App\Services\Billing\EntitlementService`.

It should expose operations such as:

- `limit(company, featureCode)`
- `usage(company, featureCode)`
- `remaining(company, featureCode)`
- `allows(company, featureCode, requestedQuantity = 1)`

#### Phase 1 enforcement

- Count active, non-archived properties in the current workspace.
- Check the limit before property creation.
- Check the limit before restoring/reactivating an archived property.
- Do not consume capacity for archived properties.
- Display current usage and remaining capacity.
- Return a friendly plan-limit response with an upgrade/contact-support action.
- Enforce the rule server-side even if the button is hidden.
- Display administrator, storage and portal-user allowances, but only claim enforcement where the relevant usage calculation is implemented and tested.

#### Acceptance criteria

- A five-property plan blocks creation/reactivation of a sixth active property.
- Archiving an active property releases one unit of capacity.
- Direct POST requests cannot bypass the limit.
- Limits come from `plan_features`, not hard-coded controller values.

---

### Module 10: Basic Plan & Billing and Super Admin subscription visibility

**Estimate:** 3 hours

**Purpose:** Provide the minimum billing visibility required to support the first customers.

#### Landlord billing page

- Current plan and billing interval.
- Trialing, active, payment issue or cancelled status.
- First-charge or current-period end date.
- Active-property usage and limit.
- Billing support contact/action.

#### Super Admin subscription view

- Landlord/workspace name.
- Owner name/email.
- Plan and interval.
- Local subscription status.
- Stripe Customer and Subscription IDs as non-secret references.
- Trial and current-period dates.
- Property usage.
- Link to safe webhook event processing history.

#### Acceptance criteria

- Landlords can see only their own billing information.
- Super Admin can find the customer and identify the current subscription state.
- No Stripe secret or full payment information is displayed.

---

### Module 11: Automated tests, UAT and deployment

**Estimate:** 12 hours

**Purpose:** Release a safe acquisition and billing path rather than only a clickable demonstration.

#### Required automated feature tests

Plan catalogue:

- public pricing lists only active/published Landlord plans
- inactive plans cannot be purchased
- non-Super Admin cannot change plans
- submitted raw amounts/Price IDs are ignored or rejected

Registration:

- OTP is required and expires
- duplicate registration is handled safely
- pending users cannot log in
- verified registration creates one pending workspace

Checkout:

- monthly and annual prices resolve correctly
- Checkout uses the local Stripe Price ID
- success route does not activate access
- retry does not duplicate the workspace

Webhook:

- invalid signature is rejected
- supported events update the local subscription
- duplicate event is idempotent
- active/trialing event provisions once
- failed/cancelled states do not activate access

Tenant isolation:

- Landlord A cannot list/show/update/archive/search/download Landlord B's exposed records
- dashboard counts are workspace-scoped
- form `company_id` manipulation is ignored/rejected

Entitlements:

- property capacity is enforced on create and restore
- archive releases capacity
- controller/API requests cannot bypass limits

#### Manual/UAT checks

- Mobile and desktop pricing, registration, OTP, Checkout and dashboard.
- Stripe test success, cancellation and failed-payment scenarios.
- Email delivery and links.
- Back/refresh/resume behaviour.
- Accessibility basics: labels, keyboard focus, contrast and error messages.
- No secret values in pages, logs or repository changes.

#### Production release checklist

- Back up the production database.
- Apply migrations in a staging copy first.
- Create live Stripe Product/Prices and map them in Super Admin.
- Register the production webhook URL and secret.
- Configure live Stripe keys outside the repository.
- Confirm production mail configuration.
- Run queue worker/scheduler requirements.
- Run automated tests and a Stripe test-mode smoke test.
- Deploy code, migrate, clear/cache Laravel configuration as appropriate.
- Complete one controlled live-mode purchase and refund/cancellation support check using an authorised business test process.
- Monitor logs and webhook failures closely after launch.

#### Acceptance criteria

- Required automated tests pass.
- Client UAT signs off the agreed Phase 1 flow.
- Production webhook delivery succeeds.
- A controlled end-to-end subscription activates exactly one workspace.

## Phase 1 effort summary

| Module | Hours |
|---|---:|
| 0. Baseline, scope freeze and release safety | 3 |
| 1. SaaS database foundation | 8 |
| 2. Workspace context and tenant isolation | 8 |
| 3. Super Admin Landlord plan catalogue | 7 |
| 4. Public pricing and plan selection | 5 |
| 5. Landlord registration and verification | 8 |
| 6. Order summary and Stripe Checkout | 8 |
| 7. Stripe webhook and subscription synchronisation | 10 |
| 8. Provisioning and dashboard access | 6 |
| 9. Entitlements and property limit | 6 |
| 10. Basic billing and Super Admin visibility | 3 |
| 11. Tests, UAT and deployment | 12 |
| **Total** | **84 hours** |

## Calendar execution plan

| Dates | Capacity | Primary output |
|---|---:|---|
| 24-30 June | 15 hours | Baseline, SaaS schema and start workspace isolation |
| 1-7 July | 15 hours | Finish isolation, Super Admin plan catalogue and seed launch plan |
| 8-14 July | 15 hours | Dynamic pricing, Landlord registration, OTP and pending workspace |
| 15-21 July | 15 hours | Order summary, Stripe Checkout and initial webhook processing |
| 22-28 July | 15 hours | Subscription sync, provisioning, dashboard, limits and billing visibility |
| 29-31 July | 9 hours | Regression testing, UAT fixes, production configuration and deployment |
| **Total** | **84 hours** | **Restricted Landlord SaaS MVP** |

Testing must be written alongside each module. Leaving all testing until 29 July makes the deadline unsafe.

## Phase 1 route outline

Route names can follow current project conventions, but responsibilities should remain separate.

### Public/customer routes

```text
GET  /pricing
GET  /register
POST /register
GET  /register/verify-otp
POST /register/verify-otp
POST /register/resend-otp
GET  /checkout/summary
POST /checkout/session
GET  /checkout/success
GET  /checkout/cancel
GET  /checkout/status
POST /stripe/webhook
GET  /login
POST /login
GET  /password/forgot
POST /password/email
GET  /password/reset/form/{token}
POST /password/reset
```

### Authenticated Landlord routes

```text
GET /admin/dashboard
GET /admin/billing
```

Existing property and operational routes are enabled only after their workspace isolation and entitlement checks pass.

### Super Admin routes

```text
GET    /admin/saas/plans
GET    /admin/saas/plans/create
POST   /admin/saas/plans
GET    /admin/saas/plans/{plan}/edit
PUT    /admin/saas/plans/{plan}
PATCH  /admin/saas/plans/{plan}/publication
GET    /admin/saas/subscriptions
GET    /admin/saas/subscriptions/{subscription}
```

## Suggested code organisation

```text
app/
  Enums/
    BillingInterval.php
    CompanyStatus.php
    FeatureCode.php
    SubscriptionStatus.php
    WorkspaceType.php
  Http/
    Controllers/
      Billing/
        CheckoutController.php
        StripeWebhookController.php
      Backend/Saas/
        PlanController.php
        SubscriptionController.php
    Middleware/
      EnsureActiveMembership.php
      EnsureSubscriptionAccess.php
    Requests/
      Billing/
      Saas/
  Models/
    CompanyMembership.php
    Plan.php
    PlanPrice.php
    PlanFeature.php
    Subscription.php
    CheckoutAttempt.php
    StripeWebhookEvent.php
  Services/
    Billing/
      EntitlementService.php
      StripeCheckoutService.php
      SubscriptionSyncService.php
      WorkspaceProvisioner.php
    Tenancy/
      TenantContext.php
```

Keep Stripe SDK calls inside billing services. Controllers should validate requests, call a service and return a response; they should not contain the subscription state machine.

## Seed data for the first release

Seed one initial Landlord plan after the Super Admin catalogue exists:

| Field | Initial value |
|---|---|
| Code | `landlord` |
| Name | Landlord |
| Monthly price | GBP 19 + VAT |
| Annual price | GBP 190 + VAT |
| Active properties | 5 |
| Administrators | 1 |
| Portal users | Unlimited |
| Storage display allowance | 5 GB |
| Trial | 14 days, card required |

Stripe Product and Price IDs must come from environment-specific Super Admin configuration or environment-specific seed/configuration. Do not commit live Stripe IDs as general application defaults.

## Explicit Phase 1 exclusions

The following are not part of the 84-hour deadline:

- Estate Agent self-service registration.
- Agency branches, staff-seat billing and branch limits.
- Property Manager add-on.
- Property and storage add-on purchases.
- Automated upgrade/downgrade and proration.
- Stripe Customer Portal.
- Self-service cancellation/reactivation.
- Full SaaS invoice history.
- Seven-day past-due grace and read-only business mode.
- Automated trial-expiry reminder sequence.
- Advanced Super Admin MRR/ARR/churn analytics.
- Full Stripe reconciliation/replay tooling.
- Complete redesign of the existing dashboard.
- Rebuilding property, tenancy, invoice, maintenance or document modules.
- Storage-byte enforcement if current uploads cannot yet be measured reliably.
- Estate Agent, Tenant, Contractor and Owner portal redesigns.

## Post-launch modules required for the complete SaaS platform

Phase 1 creates the acquisition and subscription foundation. The following modules complete the broader SaaS conversion.

### Phase 2A: Billing lifecycle hardening

- Stripe Customer Portal.
- Payment method management.
- SaaS invoice projection/history.
- Cancel at period end and reactivate.
- Upgrade/downgrade previews and proration.
- Add-on subscription items and quantities.
- Trial reminders.
- Past-due grace, restricted read-only state and recovery.
- Subscription reconciliation/replay command.
- Webhook failure retry and monitoring.

### Phase 2B: Complete workspace isolation

- Add mandatory `company_id` to every tenant-owned operational table.
- Backfill existing records and quarantine ambiguous ownership.
- Replace all `created_by` ownership queries.
- Add tenant-aware policies and binding to every module.
- Scope files, caches, jobs, exports, searches and notifications.
- Add cross-workspace tests for all routes and reports.
- Remove transitional `users.company_id` as the canonical membership authority.

### Phase 2C: Usage and entitlement expansion

- Reliable file-byte usage accounting and storage enforcement.
- Administrator/member counting.
- Portal invitation counting where commercially required.
- Property and storage add-ons.
- Central entitlement cache/versioning.
- Over-limit behaviour for downgrades.
- Usage recalculation and repair commands.

### Phase 2D: Estate Agent SaaS

- Estate Agent plan and registration selection.
- Agency workspace onboarding.
- Company profile.
- Branch creation and included branch limit.
- Staff invitations and included seat limit.
- Workspace-scoped role templates.
- Extra branch, staff, property and storage add-ons.
- Agency billing owner and ownership-transfer workflow.

### Phase 2E: Platform administration and operations

- Customer workspace search/detail.
- Subscription and payment issue queues.
- MRR, ARR, churn and plan-mix reporting.
- Audited suspend/reactivate and promotional trial operations.
- Support-access auditing.
- Stripe/local mismatch detection.
- Billing operations runbooks and alerts.

## Go/no-go release gates

Do not onboard unrelated paying Landlords until all of the following are true:

- Stripe webhook signature verification passes in production.
- Checkout success alone cannot activate a user.
- Duplicate webhook events are idempotent.
- Landlord A cannot access Landlord B's enabled data.
- Property limits are enforced server-side.
- Pending/failed/cancelled accounts cannot access paid business routes.
- Super Admin catalogue cannot expose inactive or invalid prices.
- Secrets are absent from views, logs and version control.
- Production backup and rollback procedures are ready.
- Client UAT has approved the exact Phase 1 journey.

If tenant isolation fails for an operational module, disable that module for launch rather than releasing a cross-customer data risk.

## Definition of done for 31 July 2026

Phase 1 is complete only when:

1. Super Admin can publish a valid Landlord plan with monthly and annual Stripe Price mappings.
2. The public pricing page reads the published plan from the database.
3. A Landlord can register, verify email and resume an interrupted Checkout.
4. Stripe test Checkout creates a real test subscription.
5. A verified webhook creates/synchronises the local subscription and activates the workspace exactly once.
6. The Landlord can log in and reach a workspace-scoped dashboard.
7. The configured active-property limit is enforced on create and restore.
8. The Landlord can view basic plan/status/usage information.
9. Super Admin can view the Landlord and subscription state.
10. All exposed business routes pass two-workspace isolation checks.
11. Required automated tests pass.
12. Production configuration, webhook and controlled end-to-end smoke test succeed.

