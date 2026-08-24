# ResiSquare comprehensive fix and verification plan

**Prepared:** 20 August 2026  
**Inputs:** both Sabir audit reports, both HTTP smoke scripts, all three smoke-result files, the current route/view/controller structure, and the existing complete-project audit  
**Required E2E tool:** Playwright Test  
**Current release decision:** **NO-GO**

## 1. Objective and completion standard

This plan converts every supplied finding into owned remediation work and adds discovery controls for defects that the HTTP-only smoke tests could not see. The goal is not merely to make the 16 failing pages return 200. The application is ready only when its client workflows, tenant boundaries, authorization, data integrity, accessibility, browser behavior, operations, and recovery paths have objective passing evidence.

No finite audit can honestly guarantee that software contains no defects. The practical zero-escape standard for this project is therefore:

1. Every registered route, rendered control, form, modal, tab, menu item, background side effect, file path, export, email, and third-party callback is present in a traceability ledger.
2. Every ledger item has an expected role/account policy, happy path, validation path, failure path, and automated test or an explicitly approved manual test.
3. Every finding below has an owner, pull request, regression test, evidence artifact, and independent closure review.
4. Any unclassified route, control, network error, console error, accessibility violation, migration difference, or authorization result fails CI.
5. Release requires all gates in section 14; “known issue”, hidden UI, or an untested role is not a pass.

## 2. Evidence baseline

### 2.1 Current measured surface

| Surface | Current evidence |
|---|---:|
| Application routes, excluding vendor routes | 515 |
| Routes under `/admin` | 472 |
| GET routes | 292 |
| POST routes | 135 |
| PUT/PATCH routes | 47 |
| DELETE routes | 41 |
| Accounting routes | 133 |
| Blade templates | 393 |
| Controllers | 95 files / 86 routed controller classes |
| Models | 115 |
| Migrations | 147 |
| Existing automated suite | 30 tests, 410 assertions, currently passing |
| Safe parameterless GET smoke | 170 requests: 143 OK, 16 server errors, 6 forbidden, 4 not found, 1 login redirect |
| Super Admin visible-link smoke | 88 requests: 86 OK, 2 forbidden |
| Role identities proven usable | 1 of 8: Super Admin only |

The green PHP suite is valuable but covers only a small part of this surface. It does not invalidate any browser, route, authorization, security, migration, or interaction finding.

### 2.2 Environment blockers that must be fixed before E2E is credible

- The seven non-admin QA identities are absent. Create them only in a disposable QA database or approved staging environment.
- The clean schema path fails and the inherited MySQL database reports 28 pending migrations. Do not seed or run browser writes against the inherited database.
- Node is now present (`v24.19.0`), but PowerShell blocks `npm.ps1`; use `npm.cmd` in automation or set an approved process execution policy.
- The browser-control runtime still exits because `C:\Users\abc\package.json` marks the host bootstrap as ESM while that bootstrap calls CommonJS `require`. Repair the host integration before claiming a live Playwright audit.
- There is no JavaScript lockfile or installed Playwright framework in the repository.
- The worktree contains extensive inherited modifications. Remediation must use focused branches/commits and must not silently absorb unrelated changes.

### 2.3 Gaps in the supplied smoke tooling

The PowerShell smoke tests are useful baselines, but they cannot be treated as complete E2E evidence:

- `Invoke-WebRequest` does not execute JavaScript, lay out the page, operate controls, expose console errors, or test keyboard/accessibility/responsive behavior.
- The role crawler follows only anchors on the landing page, caps links, ignores fragment/JavaScript links, and deliberately excludes many mutation-like paths. It does not inventory buttons, forms, tabs, modals, AJAX-loaded content or links discovered on later pages.
- When one of the seven missing identities fails login, the role script continues crawling the guest login page. Its `redirected_to_login` entries are not role coverage.
- The route smoke tests only parameterless GET endpoints. They do not cover 223 POST/PUT/DELETE route entries, required route parameters, CSRF, validation, side effects, or object-level authorization.
- Regex safety filtering misses destructive names such as `/all-file`; conversely, it can skip safe read-only routes whose name happens to contain a blocked word.
- A 200 response can still contain a broken form, hidden permission mismatch, empty control, stale data, JavaScript failure or incorrect account data.
- `uploaded-files/file-info` is registered for every HTTP verb, so the GET failure is both a route-contract smell and a reminder that method expectations must be explicit.

Keep both scripts as low-cost HTTP smoke checks, but make the Playwright coverage ledger and lower-level authorization tests the release evidence.

## 3. Priority model

| Priority | Meaning | Target |
|---|---|---|
| P0 | Active security boundary failure, secret/config mutation, unrecoverable schema path, or client-critical workflow unavailable | Contain immediately; fix before any UAT |
| P1 | High-impact auth, upload, XSS, privacy, data-integrity, dependency, or broad functional failure | Fix before controlled client UAT |
| P2 | Interaction, accessibility, compatibility, resilience, maintainability, and operational weakness | Fix before production release unless risk acceptance is signed |
| P3 | Hygiene or low-impact consistency issue | Schedule with an explicit owner; cannot remain untracked |

## 4. Master finding register

“Confirmed” means reproduced by supplied HTTP evidence or directly present in current source. “Audit candidate” means static evidence requires a browser, data, or security test before closure.

### 4.1 P0 security and tenant-boundary findings

| ID | Status | Finding | Required correction and proof |
|---|---|---|---|
| SEC-001 | Confirmed architecture defect | Tenant-owned accounting records are queried through unscoped base queries and direct `findOrFail()` calls. Receipt, purchase invoice, banking, journals, payments, masters, exports, and PDFs are exposed to IDOR risk. | Add mandatory account ownership at model/query/route-binding/policy layers; backfill and constrain `account_id`; add Account A vs Account B read/create/update/delete/export/PDF tests for all 133 accounting routes. |
| SEC-002 | Confirmed architecture defect | Tenant ownership is opt-in across the application; `current.account` validates context but does not scope queries. | Introduce a fail-closed tenant-aware base concern/global scope, automatic account population, explicit audited platform bypass, and a static CI rule that rejects unscoped tenant-model access. |
| SEC-003 | Confirmed | Spatie teams are disabled and roles are global. `RoleController` mutation paths are not consistently authorized; effective permission logic can use global user fields rather than active membership. | Make roles/designations account-owned, reserve platform roles, authorize every mutation server-side, clear the correct caches, and test cross-account/admin escalation attempts. |
| SEC-004 | Confirmed | `/admin/env_key_update` accepts request-controlled environment keys and writes `.env`; SMTP/OTP screens can expose environment-backed secrets. | Remove HTTP `.env` mutation. Use deployment secrets plus account-scoped settings. Never echo stored secret values. Require recent auth/MFA and immutable audit logs for any retained platform configuration UI. |
| SEC-005 | Confirmed | Public or GET routes execute operational/destructive work: `/storage-link`, `/command/optimize-clear`, `/admin/clear-cache`, GET logout, upload/role/staff deletion, billing cancel, and `/all-file` mass deletion. | Remove deployment operations from web routes. Convert legitimate user mutations to POST/DELETE with CSRF, policies, re-auth/confirmation where needed, idempotency, and audit records. Test cross-site GETs are harmless. |
| SEC-006 | Confirmed | `/helper` and `/test-sms` remain production routes; the SMS path can invoke live providers and return provider details. | Remove from production routing. Put diagnostics behind non-web tooling or an environment-gated, Super Admin-only audited diagnostic service. |
| SEC-007 | Confirmed | Login, registration, reset, OTP verify, and OTP resend lack visible throttles. Login does not regenerate the session; logout does not invalidate the session/regenerate CSRF. GET logout creates logout-CSRF. | Use named rate limiters per normalized identity and IP; rotate sessions after login; fully invalidate logout; remove GET logout; test fixation, reuse, concurrent sessions, and rate-limit reset behavior. |
| SEC-008 | Confirmed | Custom password-reset lookup scans all token hashes and does not enforce token age. | Use Laravel’s password broker or an indexed, expiring one-time design; test expiry, reuse, wrong email, multiple requests, and session invalidation. |
| SEC-009 | Confirmed | OTPs/default credentials are weak: plaintext OTP lifecycle, no attempt ceiling/cooldown, `quicklyStoreUser()` known password, and guest-account email sends a plaintext password. | Hash OTPs, enforce attempts/cooldown/expiry, replace known credentials with single-use invitations, enforce password policy, and require MFA for privileged users. |
| SEC-010 | Confirmed | Uploads trust client extensions, accept SVG/XML/SWF/archives, lack strong size/content checks, use public storage, and are not scanned. | Private per-account quarantine; MIME and magic-byte verification; strict allowlist/limits; reject or rasterize SVG; malware/archive-bomb scanning; safe filenames; authorized short-lived downloads; `nosniff`; audit every upload/download/delete. |
| SEC-011 | Confirmed/audit candidate | Raw stored content is rendered in notes, emails, dynamic fragments, and repair navigation. Newsletter/support templates use raw `echo`. Stored XSS can reach privileged browsers, PDFs, and mail. | Classify rich text vs plain text, sanitize rich text server-side with an allowlist, escape everything else, use `Js::from()` for script data, add CSP, and run stored-XSS payload tests across HTML/PDF/email surfaces. |
| SEC-012 | Confirmed architecture gap | Sensitive identity, bank, tenancy, document, session, IP/header, and finance data lacks a complete encryption/retention/privacy lifecycle. | Data inventory; encrypted casts or field vaulting; secure cookies/TLS; redacted logs; DSAR/export/correction/deletion workflows; retention and backup-deletion rules; key rotation/recovery tests. |
| SEC-013 | Confirmed architecture gap | Audit coverage excludes many core property, tenancy, repair, user, document, finance, role, export, and configuration mutations. | Immutable account-scoped audit trail containing actor, account, subject, before/after, request ID, time, source and reason. Test that success, denial, export and privileged bypass events are recorded without secrets. |
| SEC-014 | Confirmed latent defect | `backendAuthenticate` hardcodes `role_id == 1`. It is currently unregistered, but applying it would deny every other backend role and reintroduce global numeric-role coupling. | Delete it or replace it with named account-aware policy/role middleware. Add a source rule against numeric role checks and tests for all role middleware aliases. |

### 4.2 P0/P1 client-functional findings

#### A. Confirmed 500 routes

| ID | Endpoint | Confirmed cause | Fix and regression proof |
|---|---|---|---|
| RTE-001 | `/admin/credit-notes/create` | Missing `createCredit()` | Implement a fully authorized flow or remove the route/menu; Playwright create/validation/cancel test. |
| RTE-002 | `/admin/debit-notes/create` | Missing `createDebit()` | Same standard as RTE-001. |
| RTE-003 | `/admin/document-types/show` | Route has no identifier; controller requires one | Correct resource URI/model binding or remove invalid route; positive and unknown-ID tests. |
| RTE-004 | `/admin/email-templates/create` | Missing view | Build authorized view and validation or remove unsupported feature. |
| RTE-005 | `/admin/estate-charges` | Undefined route names in view | Normalize on `admin.estate-charges.*`; test list/create/edit/delete and empty state. |
| RTE-006 | `/admin/estate-charges/create` | Undefined store route reference | Same module test as RTE-005. |
| RTE-007 | `/admin/job-types/create` | Missing controller method | Implement or remove; test permitted and forbidden roles. |
| RTE-008 | `/admin/note-types/show` | Route has no identifier; controller requires one | Correct contract and add model-binding tests. |
| RTE-009 | `/admin/offers` | Undefined `offers.edit`/`offers.destroy` | Normalize on `admin.offers.*`; test with zero, one, and many offers. |
| RTE-010 | `/admin/offers/create` | Undefined `$property_id` | Define required route/query contract and authorized property selection; test missing/invalid/cross-account property. |
| RTE-011 | `/admin/owner-groups` | Undefined edit/destroy route names | Normalize on `admin.owner-groups.*`; test primary owner rules and concurrency. |
| RTE-012 | `/admin/purchase_invoices` | Missing database table | Resolve canonical schema, migrate/backfill, then test all lifecycle and account isolation paths. |
| RTE-013 | `/admin/purchase_invoices/create` | Missing view | Implement after schema/authorization, or remove from release scope and UI. |
| RTE-014 | `/uploaded-files` | Missing `modals.delete_modal` include | Replace legacy view dependency as part of secure upload redesign. |
| RTE-015 | `/uploaded-files/create` | Wrong/missing layout | Use the canonical authorized layout and uploader component. |
| RTE-016 | `/uploaded-files/file-info` | Route/controller contract mismatch | Restrict to the required HTTP method, require an ID, account-scope it, and test missing/invalid/cross-account IDs. |

#### B. Other route and interaction defects

| ID | Status | Finding | Required correction and proof |
|---|---|---|---|
| RTE-017 | Confirmed | Four parameterless AJAX GETs return 404 because required query data is not expressed in the route contract. | Validate inputs with 422 JSON, or move identifiers into URI/model binding. Test missing, malformed, valid and cross-account parameters. |
| RTE-018 | Confirmed static | Eleven literal route references do not exist: five Estate Charge names, two Owner Group names, two Offer names, `users.store`, and newly found `user.profile`. | Correct/remove every reference and add a CI route-reference validator for Blade/PHP. |
| RTE-019 | Confirmed | `/admin/login` is registered with authentication middleware because routing is registered/collided inconsistently. | Use one Laravel 11 route registration path and one definition per method/URI/name. Add duplicate/collision and guest-login tests. |
| UI-001 | Confirmed | Super Admin sees notification links that return 403 because an account context is required. | Define platform-admin semantics: choose/select account before navigation, provide a platform notification view, or hide with an explanation. Test visibility and direct access for every role/context. |
| UI-002 | Confirmed | Documents, Users, Settings and Reports sidebar entries are `href="#"` with no action. | Link them, make them real expandable buttons with ARIA state, or remove them. No inert visible control is allowed. |
| UI-003 | Confirmed | Numerous empty `onclick`/`onClick` controls exist in property, user, modal and helper views. | Remove fake interactivity or implement semantic buttons/links. A Playwright action inventory must fail visible unnamed/inert controls. |
| UI-004 | Confirmed | Login Sign Up points to a POST route; Remember Me is required; public title says “Backend Dashboard”; nested forgot-password assets resolve to 404; backend navbar/Clear Cache appears in the public shell. | Create a dedicated public auth layout, correct URLs/routes, make remember optional, and test login/reset/register at nested URLs with all assets 200. |
| UI-005 | Confirmed | Tenancy edit posts without PUT/PATCH override and contains duplicate `property_id` fields. | Add correct method semantics, submit one authoritative property value, reject cross-account changes, and test full edit validation/success/stale-record paths. |
| UI-006 | Confirmed | Bootstrap 4 attributes (`data-dismiss`, `data-toggle`, `data-target`) are mixed with Bootstrap 5. Modal/alert close and uploader interactions can fail. | Select one Bootstrap version, migrate all components, and test every modal/dropdown/tab/alert open-close cycle with keyboard and pointer. |
| UI-007 | Confirmed | `activeRole` is out of scope in users index; contractor scripts are pushed to an unrendered stack. | Move behavior into versioned modules, remove global/scope coupling, and add console-error-free tab/accordion tests. |
| UI-008 | Confirmed | Contractor pages use the backend administration shell. | Provide a role-appropriate portal shell and verify no admin-only links/data are present. |
| UI-009 | Confirmed | Live `var_dump` output exists in contact, bank, compliance, user index, property index, property tenancy, and property documents views; command code also emits debug output. | Remove all runtime dumps/commented diagnostics; add a source gate and rendered-response scan for dump/error signatures. |
| UI-010 | Confirmed/audit candidate | Static duplicate IDs occur in 21 template/id groups, including `photos`, `view_360`, `usersSubmenu`, `local_authority`, `school_name`, `station_name`, `invoice-tab`, and delete-modal IDs. Duplicate names include a confirmed duplicate tenancy `property_id`. | Make rendered IDs unique and names intentional; test label association, selector uniqueness and serialized FormData. Validate loop-generated DOM at runtime. |
| UI-011 | Confirmed | Approximately 20 view permission checks do not align with seeded permissions; dashboard actions and menus disappear. Staff receives no base permissions. | Create one canonical permission catalog/migration, map legacy names deliberately, remove stale names, resolve permissions from active membership, and test menu plus direct-route policy for every role. |
| UI-012 | Confirmed | Email defects: always-true contact condition, plaintext password mail, unescaped newsletter/support HTML, and fragile invoice date formatting. | Correct conditions/types, invitation-based onboarding, sanitize/escape content, use typed date formatting, and add render tests for empty/null/unicode/malicious/boundary data. |

### 4.3 P1/P2 architecture, data, and operations findings

| ID | Status | Finding | Required correction and proof |
|---|---|---|---|
| ARC-001 | Confirmed | Fresh migration fails, migration ordering/duplicate tables conflict, 28 migrations are pending, and the purchase-invoice table is absent. | Establish canonical clean-install and representative upgrade paths on MySQL. Back up first, backfill deterministically, verify constraints, and run clean/upgrade/rollback/restore tests in CI. |
| ARC-002 | Confirmed | Application boot queries the permissions table; diagnostics and route listing can depend on database availability. | Remove database work from provider boot, register authorization lazily/statically, and test build/cache/route discovery with database unavailable. |
| ARC-003 | Confirmed | Custom permissions are cached forever and mutation invalidation is incomplete. | Use the framework/Spatie cache contract or bounded tagged cache; test add/edit/delete permission visibility without manual cache clearing. |
| ARC-004 | Confirmed | Stripe signatures are checked, but durable event-ID inbox/idempotency, ordering and replay/reconciliation are absent. | Persist unique event IDs and processing status, transact side effects, handle duplicate/out-of-order events, add retry/reconciliation tooling, and test all Stripe lifecycle events in test mode. |
| ARC-005 | Confirmed | Queue is synchronous in the reviewed environment; worker/scheduler locks, liveness, dead-letter alerts and deployment documentation are incomplete. | Managed async queue, `after_commit`, retries/backoff, idempotent/unique jobs, `withoutOverlapping`, `onOneServer`, worker/scheduler health and failure alerts. |
| ARC-006 | Confirmed | Runtime code calls `env()` directly even though production is expected to use `config:cache`. | Move all reads into `config/*`, access via `config()`, and test cached vs uncached configuration parity. |
| ARC-007 | Confirmed | Nullable ownership columns, missing foreign keys, global `exists`/unique rules, and conditional migrations leave data invariants optional. | Backfill; add NOT NULL/FK/composite unique/index constraints; use account-scoped validation; test orphan, duplicate and cross-account relationship attempts. |
| ARC-008 | Confirmed | Dependency lock contains reported advisories; frontend has no lockfile, engine declaration, provenance, or CI audit. | Upgrade on a dedicated branch, commit lockfiles, pin supported Node/PHP, run Composer/npm audits and license/SBOM generation in CI, and regression-test affected integrations. |
| ARC-009 | Confirmed | Very large controllers/views, duplicated old templates, business logic in controllers/helpers, and no Form Requests create high regression risk. | Refactor by capability into policies, requests, actions/services, query objects and components. Add complexity/size and dead-code checks without mixing refactor and behavior changes in one PR. |
| ARC-010 | Confirmed | Global `EnsureTokenIsValid` middleware is a no-op and its alias is misleading. | Remove it or implement one clearly defined control; test middleware ordering and fail-closed behavior. |
| ARC-011 | Confirmed | README, CI, environment setup, queue/scheduler/storage/recovery documentation and asset provenance are incomplete. | Replace default documentation with reproducible setup/runbook/architecture/test/recovery guidance and CI status. |
| OPS-001 | Confirmed gap | Security headers are not centrally enforced. | Add environment-correct CSP, HSTS after HTTPS verification, `nosniff`, frame, referrer and permissions policies; test headers and CSP violation-free core flows. |
| OPS-002 | Confirmed gap | Production observability, redaction, queue/scheduler health, security alerts, backup/restore drills and client-safe error pages are not proven. | Structured redacted logs, error/APM metrics, trace IDs, SLOs, alert tests, queue/scheduler dashboards, encrypted backups and timed restore drills. |

## 5. Remediation sequence and dependencies

### Phase 0 — Freeze, baseline, and make QA reproducible

1. Freeze feature work touching auth, roles, accounting, uploads, settings, routing and migrations.
2. Snapshot the exact release candidate and inherited working-tree changes. Assign ownership; do not combine unknown edits into remediation PRs.
3. Repair/squash the clean migration path and construct a scrubbed representative upgrade snapshot.
4. Provision two independent landlord/company accounts plus all eight documented roles, with cross-account twin records for every tenant-owned model.
5. Create deterministic factories/seed commands that are idempotent and forbidden outside test/staging.
6. Fix the host browser bootstrap, pin a supported Node version, use `npm.cmd` or an approved execution policy, and commit `package-lock.json`.
7. Add CI services for MySQL, queue and mail capture. Never point automated writes at inherited or production-like shared data.

**Exit:** clean install and upgrade pass; seeded QA resets in one command; Playwright can authenticate all roles; baseline artifacts are stored.

### Phase 1 — Immediate containment

1. Remove/disable web-accessible Artisan, `.env`, helper, SMS-test, mass-file-delete and GET mutation routes.
2. Turn debug off outside local development and remove all rendered dumps.
3. Temporarily reject dangerous upload types and enforce a conservative size/MIME allowlist while the private pipeline is built.
4. Fix session rotation/invalidation, remove GET logout, add auth/reset/OTP throttles, remove known/default credentials, and expire reset/OTP tokens.
5. Add a restrictive initial CSP/report-only rollout, secure cookie settings and log redaction.

**Exit:** no unauthenticated operational action; no GET mutation; no runtime dump; auth abuse controls pass; known active upload/XSS vectors are contained.

### Phase 2 — Tenant isolation and authorization foundation

1. Inventory every tenant-owned table/model and assign an ownership path, including child records and polymorphic models.
2. Implement mandatory tenant scoping, account-aware route binding, account-scoped validation and database constraints.
3. Redesign roles/designations around active account membership; separate platform privileges.
4. Introduce policies/Form Requests for every resource and custom mutation. UI permissions are display logic only.
5. Add immutable audit events and an explicit, logged platform bypass.

**Exit:** automated two-account tests prove read/write/delete/export/PDF/download denial across every module; static scoping/authorization gates pass.

### Phase 3 — Restore client functionality

1. Fix or deliberately remove all 16 failing route pages and the four ambiguous AJAX routes.
2. Remove all 11 undefined literal route names and add route-reference CI validation.
3. Resolve route registration and `/admin/login` behavior.
4. Fix authentication/public layouts, nested assets, notifications, permission menus and role shells.
5. Fix tenancy edit, user tabs, contractor scripts, Bootstrap components, sidebar controls, duplicate IDs/names and email templates.
6. For every repaired CRUD module, implement empty/loading/success/validation/forbidden/not-found/conflict/error states—not only the happy path.

**Exit:** all 515 routes have an expected result by role/context; safe route smoke has zero unexpected 4xx/5xx; every visible control has a verified action.

### Phase 4 — Data, integration, and operational hardening

1. Complete the private upload/download pipeline and migrate existing files safely.
2. Add Stripe event inbox/replay/reconciliation, mail/SMS provider abstractions, async queues and scheduler locks.
3. Upgrade dependencies, establish a reproducible frontend build and generate an SBOM/license record.
4. Apply encryption, retention/privacy workflows, security headers, observability and backup/restore controls.
5. Refactor high-risk large files behind passing characterization tests.

**Exit:** integration failure/replay tests, dependency audits, config-cache parity, queue/scheduler health, and restore drill pass.

### Phase 5 — Full browser, accessibility, resilience, and UAT verification

Execute the Playwright program in sections 6–11, remediate every failure, rerun the HTTP smoke tests, and conduct independent security/accounting review.

**Exit:** all release gates pass twice from clean environments and the exact candidate commit is signed off.

## 6. Playwright test architecture

### 6.1 Repository setup

- Add `@playwright/test` and `@axe-core/playwright` as pinned development dependencies; commit the lockfile.
- Add `playwright.config.ts` with a test-only `baseURL`, deterministic locale/timezone, trace on first retry, screenshot on failure, video on retry/failure, and HTML/JUnit/blob reports.
- Test Chromium, Firefox and WebKit. Add desktop, 1366×768 laptop, tablet, Mobile Chrome and Mobile Safari projects. Include high-DPI and reduced-motion coverage.
- Start Laravel through `webServer` only against the disposable QA database. Run queue/mail capture as explicit CI services.
- Store secrets in CI secret storage. Never commit passwords or reusable storage-state files.
- Create role fixtures through approved seed/API helpers, then generate isolated `storageState` per worker, role, account and selected-account context.
- Use `getByRole`, `getByLabel`, `getByText` and explicit `data-testid` only where semantic locators cannot be stable. Never bind tests to presentation classes or database IDs.

### 6.2 Mandatory global fixtures

Every test page must automatically:

1. Fail on uncaught `pageerror`.
2. Fail on unexpected console error/warning, with a narrow reviewed allowlist.
3. Record failed requests and fail unexpected 4xx/5xx responses.
4. Detect Laravel exception/debug pages and raw dump signatures in the DOM.
5. Assert no mixed content, broken local script/style/image/font request, or unsafe cross-origin navigation.
6. Attach route, role, account, console, network, trace, screenshot and relevant response body to failures with secrets redacted.
7. Run an accessibility scan after stable page load and after each modal/tab/dynamic state.
8. Assert visible actionable elements have an accessible name, unique actionable identity, enabled/disabled explanation, and observable result.

### 6.3 Coverage ledger

Generate a machine-readable ledger from `php artisan route:list --json`, rendered navigation and runtime action inventory. Each entry must include:

- method, URI/name/controller and mutation classification;
- guest/auth/current-account middleware and policy/permission;
- allowed and denied roles/account types;
- positive, validation, forbidden, unauthenticated, not-found and cross-account test IDs;
- UI entry point and deep-link behavior;
- created/changed/deleted records and audit event;
- external side effects, queue job, email/SMS/webhook/file/export/PDF;
- responsive/a11y/visual assertions;
- implementation PR, owner and closure evidence.

CI fails if the route list or rendered action inventory changes without a ledger update.

## 7. Role and account E2E matrix

| Identity/context | Positive workflows | Mandatory negative assertions |
|---|---|---|
| Guest/prospective client | Home, pricing monthly/annual selection, register, OTP, login, forgot/reset password, signed repair quote where applicable | No backend chrome/maintenance links; no auth data; throttles work; invalid/expired/reused tokens fail without account enumeration |
| Super Admin without selected account | Platform dashboard, plans/add-ons/accounts/subscriptions/registrations, explicit account selection | Tenant-only links are hidden/explained; no accidental 403 from visible controls; bypass is explicit and audited |
| Super Admin with selected account | Approved support/administration actions | Cannot silently mutate another account; account switch is visible; stale tabs/context cannot cross tenants |
| Landlord Owner | Properties, owners, tenancies, repairs, documents, contacts, statements, accounting allowed by plan | Other-account IDs/files/exports denied; limits enforced server-side; unavailable plan features cannot be called directly |
| Landlord Contact | Assigned properties and enabled finance/document portal flags | Unassigned properties, disabled flags, account settings and staff/role mutation denied |
| Estate Agent Owner | Company/branch/staff, property, tenancy, offer, repair, document, accounting workflows | Another company and platform roles denied; branch/staff limits and account-role ownership enforced |
| Estate Agent Staff | Only designation-granted workflows | Hidden and direct unauthorized mutations denied; changing designation/account invalidates stale access immediately |
| Tenant | Own tenancy, permitted documents/finance, repair creation/history, profile/logout | Other tenants/properties/repairs/documents denied; admin chrome/routes absent |
| Contractor | Assigned jobs, work order, quote, status/completion and allowed attachments | Unassigned jobs, customer finance/identity and admin modules denied; signed quote replay/expiry enforced |
| Property Manager | Assigned portfolio operations and explicitly granted reports/users/settings | Unassigned portfolio, global settings, platform roles and cross-account finance denied |

Each role runs both with an active account and with missing/expired/disabled membership where meaningful. Shared users belonging to two accounts must be tested for account switching, stale pages, back button, concurrent tabs and revoked membership.

## 8. Module and interaction coverage

For every module below, test index, search/filter/sort/pagination, empty state, create, view, edit, delete/archive/restore, bulk actions, validation, cancel/back, deep link, unknown ID, unauthorized ID, concurrent update, double submission, refresh/back, export/PDF/download, audit record and responsive keyboard behavior as applicable.

1. Public home, pricing, registration, OTP, login, password reset and logout.
2. Dashboard, quick actions, calendar/events/reminders and account switching.
3. Accounts, memberships, subscriptions, plans, add-ons, billing and portal access.
4. Companies, branches, staff, roles, designations, permissions and user categories.
5. Users/contacts: details, bank, compliance, notes, documents and portal flags.
6. Properties: full/quick forms, owners/groups, media, accessibility, availability/pricing, features, services, appointments, offers, compliance, notes, documents, soft delete/restore.
7. Tenancies: members, deposits, dates, notices, renewals, move-in/out and tenant portal.
8. Repairs: issue creation, navigation, assignment, contractor view, work orders, quotes, invoices, status/history and attachments.
9. Accounting: GL accounts/journals/lines/balances, banks, reconciliation, tax/payment/income/expense masters, sale/purchase invoices, credit/debit notes, receipts, payments, fixed assets, transactions and statements.
10. Documents, document/note/job/event/tenancy/transaction types, uploader, preview, download and deletion.
11. Notifications: list/read/unread, preferences, deliveries, retries, account isolation, email/SMS rendering and failures.
12. Website/business/SMTP/OTP/email/SMS settings, with platform-only permissions and masked secrets.
13. Registrations approval/rejection/invitation and onboarding.
14. Stripe checkout/webhooks/subscription changes/cancellation and reconciliation.
15. Operational error pages, maintenance behavior, queue/scheduler jobs, logs, backups and restore verification.

## 9. Edge-case catalogue

### 9.1 Forms and data

- Missing, null, empty, whitespace-only, zero, negative, maximum, maximum+1, very long, Unicode, emoji, RTL, HTML/script and malformed values.
- Duplicate records within one account vs valid duplicates across accounts.
- Invalid foreign key, cross-account foreign key, deleted/disabled parent, stale version and concurrent update.
- Multi-step form back/forward, refresh, partial completion, browser restore, double click, Enter submission and network retry.
- Server validation must match UI requirements; errors must focus the first invalid control and preserve safe input without passwords/secrets.

### 9.2 Money and dates

- Zero/negative/large amounts; integer and decimal precision; tax inclusive/exclusive; rounding per line vs total; credits/refunds/overpayments; multiple currencies if supported.
- Month/year/leap-day boundaries, DST transitions in the configured UK/business timezone, due dates, recurrence, notice periods, move-in/out overlap and expired compliance.
- Ledger transactions must be atomic, balanced, immutable after posting or reversed through explicit entries; concurrent payment/webhook retries must not duplicate value.

### 9.3 Files

- Zero-byte, exact limit, limit+1, corrupt file, mismatched extension/MIME/magic bytes, double extension, Unicode/control-character name, executable content, SVG script, XML entity, archive bomb and password-protected archive.
- Multiple/parallel upload, interrupted upload, duplicate, preview failure, malware-positive quarantine, deleted object and missing storage object.
- Owner vs other account download/preview/delete, guessed ID, copied URL, expired signed URL and response header/content-disposition checks.

### 9.4 Authentication and authorization

- Valid/invalid/case-variant email, wrong password, unchecked remember, disabled user/account, expired/revoked membership, no selected account, simultaneous accounts and stale sessions.
- Session ID rotation, CSRF token rotation, fixation attempt, logout in another tab, password change, role revocation and remember-cookie invalidation.
- Direct URI, altered ID, altered hidden field, crafted JSON/form request, bulk IDs containing another account, relationship attachment and export/PDF/download bypass.

### 9.5 UI, responsive and accessibility

- 320, 375, 768, 1024, 1366, 1440 and wide layouts; portrait/landscape; 200% zoom; text resizing; reduced motion; light/dark if supported.
- Full keyboard navigation, visible focus, skip link, logical heading/landmark order, label/error association, live validation announcements, modal focus trap/return, escape close and table/card equivalents.
- No duplicate IDs, unnamed buttons/links, empty hrefs, keyboard-inaccessible clickable rows, clipped text, horizontal overflow, obscured controls or hover-only information.
- Axe violations are zero unless a time-limited, reviewed exception has a linked issue. Critical journeys receive manual screen-reader verification.

### 9.6 Failure and recovery states

- 401/403/404/405/409/419/422/429/500/503, expired signed URLs, expired session, offline/slow network, aborted request, duplicate response, provider timeout and queue delay.
- User sees a safe, actionable message and retry path; no stack trace, secret, SQL, provider credential or cross-account data is returned.
- Retry is idempotent; optimistic UI rolls back correctly; loaders terminate; buttons re-enable; partial writes are rolled back.

## 10. Security verification beyond ordinary E2E

Playwright provides browser evidence but does not replace lower-level security tests. Add PHPUnit/integration tests and approved security tooling for:

- IDOR/BOLA across every tenant-owned identifier and nested relationship.
- CSRF on every mutation and proof that GET/HEAD/OPTIONS are side-effect free.
- Stored/reflected/DOM XSS in HTML, attributes, URLs, JavaScript, email and PDF contexts.
- SQL/query manipulation, mass assignment, parameter pollution, open redirect, path traversal, malicious filenames and unsafe external links.
- Authentication enumeration, brute force, reset/OTP abuse, session fixation, privilege escalation and role cache staleness.
- Stripe signature, replay, ordering and amount/account binding.
- CSP/header/cookie/TLS checks and secret scanning.
- Dependency/lockfile audit, static analysis, architecture rules and malware-upload test corpus.

Any penetration test uses the disposable environment and agreed rules of engagement. P0 boundary fixes require independent review by someone other than the author.

## 11. CI pipeline and quality gates

Run these stages on every pull request, then on the exact release artifact:

1. Formatting, PHP syntax, Pint, Blade/JS lint, type/static analysis, secret scan and forbidden-pattern scan (`var_dump`, debug routes, raw unsafe output, runtime `env`).
2. Route-name/method collision, undefined Blade route, permission-catalog and tenant-scope architecture checks.
3. Composer/npm validation, locked install, audit, license/SBOM and production asset build.
4. PHPUnit unit/feature/security tests on isolated MySQL.
5. Fresh migration + seed and representative upgrade migration + data assertions.
6. Playwright Chromium smoke shard for every role on every PR.
7. Full Chromium/Firefox/WebKit, mobile/tablet, accessibility and visual suites on merge/nightly/release.
8. Stripe/mail/SMS fake or sandbox integration; queue/scheduler and idempotency tests.
9. Backup creation, restore to a fresh service, schema/data checksum and smoke suite before release.

Use fail-fast only to shorten feedback; CI must still provide a scheduled full run that reports all failures. Flaky tests are defects: quarantine requires an owner, expiry date and linked issue, and cannot cover a release-critical path.

## 12. Work-item definition of done

A finding is closed only when all are true:

- root cause is corrected at the right architectural layer, not hidden in Blade;
- authorization and tenant ownership are enforced server-side;
- database constraints and rollback/backfill implications are addressed;
- positive, validation, failure, unauthorized and cross-account regression tests pass;
- Playwright evidence shows no console/network/a11y regression at applicable viewports/browsers;
- audit/logging/observability and user-facing error behavior are verified;
- docs/runbook/ledger are updated;
- security-sensitive changes receive independent review;
- fix is verified from a clean environment against the exact commit.

## 13. Ownership and execution model

Create one tracked work item per register ID. Each item records severity, affected roles/accounts/modules/routes, data migration, security reviewer, implementation owner, test owner, dependency, estimate, PR, deployment/rollback plan and evidence link.

Recommended workstreams, executed in dependency order:

1. Schema/QA environment and migration recovery.
2. Tenant model/data constraints and policies.
3. Account-aware RBAC and authentication.
4. Routes/client CRUD and design-system interactions.
5. Secure files/content/email.
6. Accounting integrity and Stripe.
7. Queue/notifications/operations/observability.
8. Playwright platform, coverage ledger and independent verification.

Do not run all streams as unrelated fixes against unstable foundations. Tenant ownership and canonical schema decisions must precede broad CRUD repair.

## 14. Release gates

- [ ] Clean install, representative upgrade, rollback strategy and restore drill pass on production-equivalent MySQL.
- [ ] No unexplained pending migration or environment-specific schema drift remains.
- [ ] All eight documented identities plus two customer accounts are provisioned reproducibly in QA.
- [ ] Every one of the 515 routes is classified and has the expected guest/role/account result.
- [ ] Zero unexpected 4xx/5xx, Laravel exception pages, console errors or failed local assets in route and Playwright suites.
- [ ] No visible control is inert, misleading, inaccessible or denied after being offered to the user.
- [ ] Every tenant-owned read/write/delete/relationship/export/PDF/download is account-scoped and negative-tested.
- [ ] Tenant administrators cannot create/assign/mutate platform roles or another account’s permissions.
- [ ] No HTTP route mutates `.env`, runs deployment commands, exposes debug tools, or changes state through GET.
- [ ] Login/reset/OTP throttling, expiry, session rotation/invalidation, secure cookies and privileged MFA pass.
- [ ] Uploads are private, validated, scanned, account-authorized and safely served.
- [ ] Stored content is escaped/sanitized and CSP/header tests pass with no critical exception.
- [ ] Financial operations are atomic, balanced, idempotent and auditable; independent accounting-control review passes.
- [ ] Stripe duplicate/out-of-order replay and reconciliation tests pass.
- [ ] Queue/scheduler workers are managed, observable and tested for retry/dead-letter behavior.
- [ ] Composer/npm audits have no unaccepted applicable advisory; lockfiles and SBOM match the release artifact.
- [ ] Chromium, Firefox, WebKit, mobile/tablet, keyboard, axe and critical screen-reader suites pass.
- [ ] Privacy/retention/export/deletion, log redaction, backup encryption and recovery controls are approved.
- [ ] All P0/P1 findings are closed; any P2/P3 exception has named owner, rationale, expiry and signed acceptance.
- [ ] The full release suite passes twice from clean environments against the exact candidate commit.

## 15. Immediate next sprint backlog

1. **QA-001:** repair clean/upgrade schema and provision disposable two-account/eight-role data.
2. **E2E-001:** fix host browser bootstrap, install/pin Playwright, commit lockfile/config/reporting/global error fixtures.
3. **SEC-005:** remove all web operational/debug/GET mutation paths, including `/all-file`.
4. **SEC-004:** remove `.env` read/write UI and rotate any credentials exposed to client environments.
5. **SEC-007/008/009:** harden login/logout/reset/OTP/invitation lifecycle.
6. **SEC-001/002:** implement tenant ownership foundation, then cover accounting/uploads/documents with two-account tests.
7. **SEC-003/UI-011:** make RBAC account-aware and reconcile the canonical permission catalog.
8. **RTE-001–019:** fix/remove every broken/ambiguous/colliding route and add route-contract regression tests.
9. **UI-001–012:** repair the client interaction defects, dumps, route links, method mismatch, Bootstrap behavior, script scope/stacks, IDs and emails.
10. **SEC-010/011:** deliver private file pipeline and stored-content sanitization.
11. **E2E-002:** implement role/module/edge-case shards and the generated coverage ledger.
12. **REL-001:** run full cross-browser/security/integration/recovery suite and independent release review.

## 16. Supplied-report traceability

This mapping ensures that none of the numbered Sabir findings disappears during consolidation:

| Supplied finding | Plan IDs |
|---|---|
| Sabir #1-2: hidden Roles/Staff menus | UI-011 |
| Sabir #3-11 and #19-21: Estate Charges, Owner Groups and Offers routes | RTE-005, RTE-006, RTE-009, RTE-011, RTE-018 |
| Sabir #6: Users create route | RTE-018 |
| Sabir #12-18, #22-27: nonexistent permissions and hidden actions | UI-011, SEC-003 |
| Sabir #28 and #31-32: Sign Up, Remember Me and public title | UI-004 |
| Sabir #29: discarded contractor script stack | UI-007 |
| Sabir #30: contractor backend shell | UI-008 |
| Sabir #33-36: Bootstrap dismiss failures | UI-006 |
| Sabir #37: Tenancy update method | UI-005 |
| Sabir #38-41: empty interaction handlers | UI-003 |
| Sabir #42: hardcoded Super Admin middleware | SEC-014 |
| Sabir #43: `activeRole` JavaScript scope | UI-007 |
| Sabir #44-47: contact/password/newsletter/invoice email defects | UI-012, SEC-009, SEC-011 |
| Client report: 16 HTTP 500 pages | RTE-001 through RTE-016 |
| Client report: four ambiguous 404 AJAX routes | RTE-017 |
| Client report: visible notification 403s | UI-001 |
| Client report: inert sidebar | UI-002 |
| Client report: forgot-password assets and auth shell | UI-004 |
| Client report: debug output and duplicate DOM IDs | UI-009, UI-010 |
| Client report: accounting isolation, RBAC, `.env`, schema, auth, uploads and GET mutation blockers | SEC-001 through SEC-014, ARC-001 |
| Complete-project audit: password reset, XSS, privacy, auditing, Stripe, queues, configuration caching, constraints, maintainability, frontend supply chain and operations | SEC-008, SEC-011-013, ARC-004-011, OPS-001-002 |

Until these items and the release gates are complete, the correct external status remains **not ready for production or broad client UAT**.
