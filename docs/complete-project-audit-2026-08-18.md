# ResiSquare Complete Project Audit

**Audit date:** 18 August 2026  
**Repository:** `devresi_square`  
**Branch / revision:** `SaaS-v1` / `de31c60` (`isolation`, 11 August 2026)  
**Working tree reviewed:** 66 modified and 23 untracked entries were present. This report therefore covers the current local working tree, including uncommitted CRM-notification work, and is not a report on the commit alone.  
**Review type:** source, architecture, security, data, dependency, test, operations, and product-readiness audit. This was not a live penetration test or a legal compliance certification.

## 1. Executive verdict

ResiSquare is a substantial Laravel property-management application with real implementation depth in properties, contacts, tenancies, repairs, work orders, accounting, SaaS billing, plans/add-ons, notifications, and portals. The July/August SaaS work has added a credible account model, account memberships, plan enforcement, Stripe subscription handling, selected account-scoped queries, and useful isolation tests.

It is **not ready for production as a multi-tenant SaaS handling financial, identity, tenancy, or compliance data**. The recommended release decision is **NO-GO** until the P0 findings are resolved and independently retested.

The four release blockers are:

1. Accounting and several shared modules do not enforce the active account boundary. An authenticated user can potentially read or mutate another account's records by changing an ID.
2. Roles and permissions are global, while some write endpoints omit permission middleware. One tenant can potentially modify shared roles that affect other tenants.
3. authenticated non-portal users can reach configuration endpoints that write arbitrary keys to `.env`; the SMTP form also renders the configured mail password back into HTML.
4. A fresh database cannot be built from the migrations. The first `users` migration defines `email` twice, references tables created later, and the migration history attempts to create `roles` twice.

### Overall scorecard

| Area | Score | Verdict |
|---|---:|---|
| Tenant isolation | 2/10 | Critical gaps remain outside the recently hardened paths |
| Authentication and authorization | 3/10 | Authentication exists; throttling, session lifecycle, and RBAC boundaries are unsafe |
| Application security | 2/10 | Critical configuration access plus upload and stored-XSS risks |
| Database integrity and recoverability | 2/10 | Existing-upgrade migrations exist, but clean installation is broken |
| Dependency security | 2/10 | Current lockfile has 34 advisory records across 12 packages |
| Test quality | 4/10 | Useful focused tests, but 3 of 30 fail and core accounting/RBAC paths are untested |
| Maintainability | 3/10 | Very large controllers/views and duplicated legacy paths raise change risk |
| Operations and deployment | 2/10 | No CI, non-reproducible frontend, DB-coupled boot, unsafe web maintenance routes |
| Product implementation depth | 6/10 | Broad operational feature set, but critical control layers are incomplete |
| Regulated-data readiness | 2/10 | No evidence of adequate retention, privacy operations, MFA, secure file pipeline, or complete audit trail |

**Indicative overall readiness: 3/10.** The core application is valuable, but its control plane is not yet strong enough for safe tenant or financial separation.

## 2. Scope and evidence

### Repository inventory

| Item | Count / observation |
|---|---:|
| Files excluding dependencies | about 1,194 |
| PHP and Blade lines | about 102,922 |
| Models | 116 |
| Controllers | 95 |
| Services | 36 |
| Console commands | 15 |
| Policies | 3 |
| Migrations | 147 |
| Seeders | 44 |
| Blade views | 394 |
| Registered routes | 523 |
| Explicit tests | 30 in 15 test files |

### Technology profile

- PHP 8.2 and Laravel 11.
- MySQL is the configured local application database; PHPUnit has no isolated database configuration.
- Server-rendered Blade application with Bootstrap/jQuery-era assets plus a small Vite/Tailwind toolchain.
- Spatie Permission for roles/permissions, OwenIt Auditing, Stripe, Twilio, mPDF/FPDI, Intervention Image, and Laravel queues/scheduling.
- SaaS is implemented as a shared database with `accounts`, `account_users`, `account_subscriptions`, add-ons, and account IDs added to selected legacy tables.
- There is no API surface, mobile client, public integration API, or modern SPA boundary in the reviewed code.

### Verification performed

| Check | Result |
|---|---|
| `composer validate --strict --no-check-publish` | Passed |
| `composer audit --locked --format=json` | Failed: 34 advisory records across 12 packages |
| PHP syntax check across `app`, `routes`, `config`, `database`, and `tests` | 563/563 files passed |
| Default `php artisan test` | Could not complete because application boot tries the unavailable MySQL database |
| Tests with isolated SQLite/array cache/session | 27 passed, 3 failed, 403 assertions |
| `php artisan migrate:fresh --force` with SQLite memory DB | Failed in the fourth migration: duplicate `users.email` |
| `php artisan route:list` with isolated DB | 523 routes; route collision and middleware observations confirmed |
| Frontend build | Not executable in the environment because Node/npm is absent; the repository also has no frontend lockfile |

## 3. Architecture and module map

The system is a modular-in-name but operationally monolithic Laravel application:

```text
Public web/auth
    -> registration + OTP -> account provisioning -> Stripe Checkout/webhook
    -> customer statement / contractor portal / signed quote links

Authenticated admin
    -> active account in session
    -> account/status/portal middleware
    -> property, contact, tenancy, repair, work order, accounting,
       documents, notes, calendar, notifications, settings, SaaS administration

Shared database
    -> SaaS account and membership tables
    -> legacy operational tables with partially backfilled account_id
    -> global role/permission tables
    -> mixed legacy and newer accounting tables
```

The intended tenant boundary is selected by `CurrentAccountService`, then manually applied through `forAccount()` or `ensureModelBelongsToCurrentAccount()`. The `BelongsToAccount` concern only defines an opt-in query scope; it does not add a global scope or automatically populate/validate `account_id` ([BelongsToAccount.php](../app/Models/Concerns/BelongsToAccount.php#L16)). This makes security depend on every controller author remembering to apply the scope on every query and relation.

### Module maturity

| Module | Current state | Main concern |
|---|---|---|
| Public marketing/auth | Implemented | No rate limits; duplicated auth routes; session lifecycle gaps |
| Registration/OTP | Implemented with plan selection and provisioning | Plaintext OTP, unlimited attempts/resends, synchronous external calls |
| SaaS plans/billing | Substantial Stripe checkout/webhook implementation | Webhook event IDs are not persisted for replay deduplication |
| Accounts/memberships | Good foundation | Not consistently consumed by RBAC and legacy modules |
| Properties | Broad CRUD and UI; several isolation checks | Extremely large controller/view; security remains manual |
| Contacts/users | Broad contact and portal functionality | Global role assignment, default password path, large controller |
| Tenancies | CRUD, ledger, notices, tenant members | Limited policy enforcement and workflow/state validation |
| Repairs/work orders | Mature operational flow, quotes, PDFs, contractors | Large coupled controllers/views; file and authorization risks |
| Accounting/GL | Broadest route area (133 routes) | Critical cross-account and integrity weaknesses |
| Documents/uploads/notes | Reusable polymorphic features | Public file pipeline, no malware scan, stored HTML/XSS risk |
| Calendar/events | Recurrence and reminders present | Large client/server implementation and limited authorization evidence |
| CRM notifications | Well-structured new service layer and idempotency key | Current work is uncommitted; queue/default environment is not production-safe |
| Compliance | Generic records and selected UK fields | No complete rule/evidence/retention control layer |
| Reporting | GL and statements present | Correctness depends on unresolved accounting isolation |

## 4. Critical findings — P0

### C-01 — Cross-account access is possible in accounting and other legacy paths

**Impact:** confidentiality and integrity breach across SaaS customers; incorrect ledgers, statements, payments, and reports.  
**Likelihood:** high once multiple customer accounts contain records.  
**Evidence:**

- The shared accounting base controller builds `$modelClass::query()` without account scoping, and its index/edit/update/delete operations all use that query ([BaseCrudController.php](../app/Http/Controllers/Backend/Accounting/BaseCrudController.php#L41), [query method](../app/Http/Controllers/Backend/Accounting/BaseCrudController.php#L112)).
- Receipt show/PDF/delete paths use direct `SysReceipt::findOrFail($id)` calls. The model has a `forAccount` scope, but these paths do not use it ([ReceiptController.php](../app/Http/Controllers/Backend/Accounting/Receipts/ReceiptController.php#L69)).
- Purchase invoice update directly uses `SysPurchaseInvoice::findOrFail($id)`, and that model does not use the account concern ([PurchaseInvoiceController.php](../app/Http/Controllers/Backend/Accounting/Purchase/PurchaseInvoiceController.php#L86), [SysPurchaseInvoice.php](../app/Models/SysPurchaseInvoice.php#L12)).
- `GlAccount`, `SysBankAccount`, and several accounting/master models expose account-owned data but have no enforced account scope. `FixedAsset` does not even have `account_id` in its model/migration.
- The route's `current.account` middleware only confirms that the user selected a valid account. It does not modify Eloquent queries.
- Only 19 controller files use `forAccount()`, 13 controller files call the explicit account assertion helper, and 95 controllers exist.

**Representative attack path:** an authenticated user in Account A requests the show/PDF/update/delete endpoint for an accounting record ID owned by Account B. A direct `findOrFail` returns the record because there is no account predicate or policy.

**Required remediation:**

1. Introduce one mandatory tenant-aware base model/trait that automatically populates `account_id`, adds a fail-closed global scope for tenant requests, and offers an explicit, audited super-admin bypass.
2. Add `account_id NOT NULL` and foreign keys to every tenant-owned table after a verified backfill. Add composite unique constraints with `account_id` where business uniqueness is tenant-local.
3. Scope route-model binding and all relationship/`exists` validation to the active account.
4. Add policies for all top-level resources and call `authorizeResource` or route `can` middleware.
5. Add two-account read/write/delete/export/PDF tests for every module, especially all 133 accounting routes.

**Release gate:** no unscoped access to tenant-owned models is permitted by automated static checks and integration tests.

### C-02 — Tenant-unsafe global RBAC and missing write authorization

**Impact:** privilege escalation and cross-tenant permission changes.  
**Evidence:**

- Spatie teams are disabled (`'teams' => false`), so roles, user-role assignments, and direct permissions are global rather than account-specific ([permission.php](../config/permission.php#L134)).
- `RoleController` protects only `index`, `create`, `edit`, and `destroy` in its constructor. It does **not** protect `store`, `update`, or `add_permission` ([RoleController.php](../app/Http/Controllers/Backend/RoleController.php#L15), [store](../app/Http/Controllers/Backend/RoleController.php#L51), [update](../app/Http/Controllers/Backend/RoleController.php#L112), [add_permission](../app/Http/Controllers/Backend/RoleController.php#L155)).
- Role queries are global and role mutations synchronize global permissions. A tenant with the plan feature enabled can therefore affect roles used elsewhere.
- `User::hasEffectivePermission()` reads the user's global `designation_id`/Spatie permissions, not the `designation_id` stored on the active `account_users` membership ([User.php](../app/Models/User.php#L318)).
- The server has only 2 route permission/can middleware declarations and about 6 explicit controller authorization checks, while views contain about 66 permission checks. Hiding UI is not authorization.
- Only `UserPolicy`, `PropertyPolicy`, and `TenancyPolicy` exist, and controller use of those policies is sparse.

**Required remediation:**

1. Make roles/designations account-owned. Either enable Spatie teams with `account_id` and migrate all pivots, or implement an account-role model with an explicit platform-role namespace.
2. Resolve effective permissions from the active `AccountUser` membership, not global fields on `users`.
3. Protect every mutation, including `store`, `update`, bulk actions, AJAX actions, exports, and custom endpoints.
4. Reserve platform roles and permissions so tenant administrators cannot view or mutate them.
5. Add negative tests proving a tenant admin cannot change another account's role, assign Super Admin, or call a hidden endpoint directly.

### C-03 — Application secrets/configuration can be read or mutated through tenant UI

**Impact:** application compromise, denial of service, credential disclosure, mail takeover, or loss of encrypted data.  
**Evidence:**

- `/admin/env_key_update` is available to authenticated, active, non-portal users but has no role/permission requirement ([backend.php](../routes/backend.php#L742)).
- `env_key_update()` accepts request-controlled key names and values without an allowlist, then writes them to `.env` ([BusinessSettingsController.php](../app/Http/Controllers/Backend/BusinessSettingsController.php#L97)). Newlines/quotes are not safely encoded.
- The SMTP settings form renders `MAIL_USERNAME`, `MAIL_PASSWORD`, and other environment values into HTML controls ([smtp_settings.blade.php](../resources/views/backend/setup_configurations/smtp_settings.blade.php#L43)).
- OTP configuration also reads environment values into a browser form, and multiple runtime services call `env()` directly.

**Required remediation:** remove `.env` mutation from HTTP entirely. Store tenant settings in account-scoped database records; keep infrastructure secrets in a deployment secret manager. If a platform settings screen is retained, require Super Admin plus recent password/MFA confirmation, use a fixed server-side allowlist, never return existing secret values, and audit every change.

### C-04 — Fresh install and disaster recovery are not reproducible

**Impact:** deployments, ephemeral test environments, onboarding, rollback, and disaster recovery can fail.  
**Verified result:** `php artisan migrate:fresh --force` failed in migration `2024_10_24_115129_create_users_table` with `duplicate column name: email`.

Additional defects:

- `users.email` is declared twice ([migration](../database/migrations/2024_10_24_115129_create_users_table.php#L17), [second declaration](../database/migrations/2024_10_24_115129_create_users_table.php#L28)).
- That migration references `users_categories`, `companies`, `branches`, and `designations` before their migrations run ([migration](../database/migrations/2024_10_24_115129_create_users_table.php#L22)).
- `roles` is created in October 2024, then the Spatie migration attempts to create `roles` again in July 2025.
- Several later SaaS migrations are conditional and have intentionally non-destructive `down()` methods. That may be appropriate for a live upgrade but is not a complete rebuild strategy.
- The staging checklist contains unchecked deployment/database verification items.

**Required remediation:** create and verify a canonical schema path. Prefer repairing/squashing migrations for new installations while retaining a separately tested upgrade path for existing production databases. In CI, build both a clean database and a representative upgraded snapshot on the actual production database engine.

## 5. High findings — P0/P1

### H-01 — Locked dependencies contain current security advisories

`composer audit` returned **34 advisory records across 12 packages**, including at least 8 explicitly high-severity records. Important locked versions include:

| Package | Locked | Required direction from advisory ranges |
|---|---:|---|
| `laravel/framework` | 11.51.0 | Current 11.x is affected; plan an upgrade to a patched supported major (12.61.1+ based on current advisories) |
| `guzzlehttp/guzzle` | 7.10.0 | 7.15.2+ |
| `guzzlehttp/psr7` | 2.9.0 | 2.12.3+ |
| `league/commonmark` | 2.8.2 | 2.9.0+ |
| `setasign/fpdi` | 2.6.6 | 2.6.7+ |
| Symfony components | 7.4.8 | Update to advisory-fixed 7.4 patch levels, generally 7.4.13+ |
| `symfony/polyfill-intl-idn` | 1.33.0 | 1.38.1+ |

Examples include Laravel email validation/header injection, Guzzle host/cookie handling, Symfony MIME header injection and routing issues, CommonMark denial-of-service cases, and FPDI resource exhaustion. Treat the Composer output generated on the audit date as the source of truth; advisory status changes over time.

The frontend dependency risk could not be assessed because there is no `package-lock.json`, `yarn.lock`, or `pnpm-lock.yaml`, and npm was not installed in the review environment.

### H-02 — File uploads are public, weakly validated, and not scanned

- AIZ upload trusts the client-supplied extension, has no request validation or per-file size limit, and writes accepted files to the public disk ([AizUploadController.php](../app/Http/Controllers/Backend/AizUploadController.php#L69)).
- SVG, XML, archives, media, and legacy formats are accepted. SVG is served as `image/svg+xml`; a malicious SVG can become stored script content when opened under the application origin.
- `Upload::storeFile()` also accepts files without validating MIME/content and defaults unknown extensions to `document`.
- There is no malware scanning, quarantine, content-disposition enforcement, per-account storage prefix, or signed/private download pipeline.
- Some delete code concatenates a stored filename into `public_path()` and uses `unlink`; current generated names reduce traversal likelihood, but the design is fragile.

Move all user uploads to private object storage, validate MIME and magic bytes, enforce size/page/dimension limits, rasterize or reject SVG, scan malware, and serve authorized downloads using short-lived signed responses with safe `Content-Disposition` and `nosniff` headers.

### H-03 — Authentication, OTP, and credential lifecycle are weak

- Login, registration, password reset, OTP verify, and OTP resend routes have no throttle middleware.
- Both login controllers call `Auth::attempt()` without regenerating the session ID. Logout does not invalidate the session and regenerate the CSRF token ([AuthController.php](../app/Http/Controllers/Auth/AuthController.php#L33), [logout](../app/Http/Controllers/Auth/AuthController.php#L217)).
- Registration OTP is stored in plaintext, compared directly, has no attempt counter/lockout, and can be resent without a cooldown ([RegistrationController.php](../app/Http/Controllers/Auth/RegistrationController.php#L163), [verification](../app/Http/Controllers/Auth/RegistrationController.php#L245), [resend](../app/Http/Controllers/Auth/RegistrationController.php#L332)).
- `quicklyStoreUser()` creates a login-capable user with the known password `password` ([UserController.php](../app/Http/Controllers/Backend/UserController.php#L886)).
- Staff password validation only requires a value on create and allows six characters on update.
- No MFA/TOTP/WebAuthn implementation was found.

Use Laravel's rate limiter per IP and normalized identity, regenerate sessions after authentication, fully invalidate logout, hash OTPs, cap attempts/resends, require strong passwords or passwordless invitations, remove all shared/default credentials, and require MFA for platform/account administrators.

### H-04 — Password-reset tokens do not expire and lookup is inefficient

The custom reset implementation stores a secure hash but retrieves every row and runs `Hash::check()` until one matches ([PasswordResetController.php](../app/Http/Controllers/Auth/PasswordResetController.php#L61), [reset](../app/Http/Controllers/Auth/PasswordResetController.php#L106)). It never evaluates `created_at`, so the configured expiry is not enforced. The full-table hash loop also creates an avoidable CPU/availability risk as the table grows.

Replace this code with Laravel's password broker. Enforce a short documented expiry, throttle issuance, bind the token to the requested account/email, invalidate other sessions when appropriate, and test expired/reused tokens.

### H-05 — Operational and destructive actions use public or GET routes

- Public unauthenticated `GET /storage-link` creates the public storage link.
- Public unauthenticated `GET /command/optimize-clear` executes Artisan cache clearing ([web.php](../routes/web.php#L26), [web.php](../routes/web.php#L45)).
- Authenticated `GET` routes perform logout, cache clearing, upload deletion, role deletion, and staff deletion ([web.php](../routes/web.php#L73), [backend.php](../routes/backend.php#L111), [backend.php](../routes/backend.php#L755)). GET actions are not CSRF protected and can be triggered by navigation or embedded content.
- `/helper` and `/test-sms` are debugging surfaces in the production route file. The SMS route sends a fixed OTP-like message through live providers and exposes provider response details.

Remove all web-accessible Artisan/debug routes. Use authenticated POST/DELETE routes with CSRF, policy checks, audit logs, confirmation/re-authentication, and non-web deployment tooling.

### H-06 — Stored HTML can execute in privileged browsers

Notes accept arbitrary strings and persist them without sanitization, then render them using raw Blade output in list/detail views ([NotesController.php](../app/Http/Controllers/Backend/NotesController.php#L92), [_notes_show.blade.php](../resources/views/components/backend/notes/_notes_show.blade.php#L6), [_notes_list.blade.php](../resources/views/components/backend/notes/_notes_list.blade.php#L14)). Similar raw-output patterns exist for email-template content and several dynamic view fragments. No Content Security Policy was found.

Use a well-maintained server-side HTML sanitizer with a narrowly defined rich-text allowlist, escape all plain text, serialize JavaScript data with `Js::from()`, and deploy a restrictive CSP. Add stored-XSS tests for notes, templates, repair descriptions, names, and generated PDFs/emails.

### H-07 — Sensitive data protection and privacy operations are incomplete

The system stores passport numbers, contact details, bank/sort-code data, tenancy details, identity documents, IP/header data, and financial records. No application-level encrypted casts were found for this data. Sessions are stored in the database with encryption defaulting to false, and the secure-cookie environment value is absent. A helper logs whole `Property` models and address details at info level ([helpers.php](../app/helpers.php#L490)).

No implemented MFA, DSAR/export workflow, consent/legal-basis registry, retention engine, anonymization workflow, breach log, or secure deletion process was found. Existing documentation mentions these needs but documentation is not an implemented control.

Define a data inventory/classification, retention schedule, account closure/export process, subject-rights workflows, backup deletion policy, field/file encryption strategy, log redaction policy, and key rotation/recovery process. Confirm TLS and secure cookies in every deployed environment.

### H-08 — Audit logging does not cover core financial and property changes

OwenIt Auditing is configured, but only a small group of models implements `Auditable` (primarily SaaS accounts/subscriptions, plans/add-ons, events, and property participants). Core properties, tenancies, users, repairs, work orders, notes/documents, invoices, receipts, payments, bank details, role changes, and configuration changes are not consistently covered.

For a property/finance product, create an immutable, account-scoped audit trail for authentication, impersonation, access changes, exports/downloads, record lifecycle changes, ledger postings/reversals, document serving, and configuration/secret operations. Audit records should include actor, account, subject, before/after values, request/correlation ID, time, source, and reason where required.

## 6. Medium findings — P1/P2

### M-01 — Route registration is duplicated and collision-prone

Laravel 11 bootstrap registers `routes/web.php`, while `RouteServiceProvider` registers it again and separately registers `backend.php` ([bootstrap/app.php](../bootstrap/app.php#L10), [RouteServiceProvider.php](../app/Providers/RouteServiceProvider.php#L34)). `web.php` also wraps routes in `web` again and defines `/admin/login` and `/admin/dashboard`, colliding with backend routes. The generated route list showed `GET /admin/login` carrying auth middleware, meaning the later collision changed the intended public backend-login behavior.

Use one Laravel 11 routing configuration, one definition per URI/name, route-level rate limits, and automated route-name/method collision tests.

### M-02 — Application boot depends on a live database

`AppServiceProvider` calls `Schema::hasTable('permissions')` during every boot and rethrows outside a narrow test exception case ([AppServiceProvider.php](../app/Providers/AppServiceProvider.php#L73)). Normal `route:list` failed when MySQL was unavailable. This makes build, diagnostics, cache generation, and recovery unnecessarily dependent on database availability.

Do not query application tables during provider boot. Register authorization behavior statically or lazily, and let Spatie's permission registrar handle its own cache.

### M-03 — Permission cache can become stale

All permissions are cached forever under the custom `all_permissions` key. Role/permission mutations clear Spatie's cache, not necessarily this separate key. Newly added permissions may not become Gates until the application cache is manually cleared. Prefer static policy definitions or a bounded/invalidation-aware cache.

### M-04 — Stripe processing lacks durable event replay protection

Webhook signatures are correctly verified, which is a strong control. However, Stripe event IDs are only logged; there is no `stripe_events`/webhook inbox table with a unique event ID. Several handlers are update-oriented and likely tolerate duplicates, but durable idempotency, ordering, retry status, and reconciliation cannot be proven.

Persist each event ID and payload hash/status before processing, handle it transactionally/idempotently, and add a reconciliation command against Stripe.

### M-05 — Queue and scheduler deployment is not hardened

The reviewed `.env` uses the synchronous queue, so queued email/notification work can execute inside web requests. The example uses a database queue but no process-manager configuration or health check exists. Several schedules run every minute; not all use `withoutOverlapping()`, none uses `onOneServer()`, and no scheduler-liveness alert was found.

Use Redis/SQS or a deliberately managed database queue, `after_commit`, supervised workers, retry/dead-letter alerting, unique jobs, scheduler locks, and delivery metrics. Document queue/scheduler requirements as release gates rather than unchecked checklist items.

### M-06 — Test and CI safety net is insufficient

- 27 tests passed and 3 failed. `Feature/ExampleTest` and both login-error tests fail because views call `get_setting()` and the test schema lacks `business_settings`.
- The default test command inherits MySQL and can hang/fail instead of using an isolated database.
- No CI configuration was found.
- The existing tests provide useful coverage for property/user/notification isolation, address providers, and cleanup policy, but do not cover the broad accounting, RBAC, settings, upload, authentication abuse, Stripe replay, or migration paths.

Add a committed testing environment, a canonical schema/factory setup, CI on every change, MySQL integration tests, static analysis (PHPStan/Larastan), Pint, Blade/JS linting, dependency audit, and security regression tests.

### M-07 — Configuration caching is incompatible with runtime `env()` usage

Routes, controllers, services, mail classes, helpers, and views call `env()` directly. The production checklist recommends `config:cache`; after configuration is cached, Laravel does not load `.env` for normal runtime calls, so many of these values may become null or stale. This affects mail, SMS providers, storage behavior, URLs, and templates.

Move all environment reads into `config/*.php`; application code should only call `config()`.

### M-08 — Data model constraints are incomplete

Many SaaS account columns were appended as nullable indexed integers without foreign keys. Several `exists:` validation rules validate only global record existence, not ownership by the active account. Some uniqueness rules are global even when business semantics are account-local. Conditional migrations can silently skip indexes when duplicates exist, leaving the intended invariant unenforced.

After backfill and duplicate remediation, make ownership columns non-null, add foreign keys/composite constraints, and make validation account-aware.

### M-09 — Maintainability and change-risk are high

Largest files include:

- `PropertyController`: 2,546 lines / 49 methods.
- `SaleInvoiceController`: 1,839 lines / 44 methods.
- `PropertyRepairController`: 1,796 lines / 41 methods.
- `UserController`: 1,758 lines / 39 methods.
- `properties/index.blade.php`: 2,145 lines.
- `sale/invoices/_form.blade.php`: 1,757 lines.
- `app/helpers.php`: 1,058 lines.

Four active `*-old.blade.php` files remain, debug `var_dump()` calls exist in rendered views, and validation/business/data-access logic is concentrated in controllers. There are no Form Request classes.

Refactor by business capability into actions/services, policies, Form Requests, query objects, view components, and domain events. Delete or archive old views outside the runtime tree. Enforce file complexity/size thresholds in CI.

### M-10 — Frontend supply chain and asset strategy are not reproducible

There is no JS lockfile, no Node engine declaration, and no CI build. Large minified vendor assets are committed directly under `public/`, which obscures provenance and vulnerability tracking. Define a single asset pipeline, lock dependencies, record licenses, apply Subresource Integrity for external assets where appropriate, and build immutable versioned artifacts in CI.

## 7. Low and hygiene findings

- The root README is still the default Laravel README and does not document ResiSquare setup, architecture, environment, queues, scheduler, storage, testing, or recovery.
- `EnsureTokenIsValid` is globally appended but is a no-op, and its `subscribed` alias is misleading.
- Debug routes, commented implementations, console logging, and active `var_dump()` output remain in runtime code.
- HTTP security headers are not set centrally. Add CSP, HSTS after HTTPS verification, `X-Content-Type-Options`, frame protection, referrer policy, and permissions policy.
- Global settings/cache keys are not consistently namespaced by environment or account. Some are legitimately platform-global, but ownership needs to be explicit.
- Generic public forms have no anti-spam/rate limit and accept attachments without MIME constraints.
- `GET /logout` enables logout CSRF and conflicts with the POST logout design.
- Debugbar routes are registered in the current debug environment. Production must enforce `APP_ENV=production`, `APP_DEBUG=false`, and no dev dependencies.

## 8. Positive controls worth preserving

The project has several good foundations:

- `.env` is ignored and is not tracked by Git.
- Stripe webhook signature verification is correctly implemented.
- Public repair quote routes use signed URLs and a separate token.
- `CurrentAccountService` verifies active account membership before switching.
- Several important property, user, statement, and notification paths now use account-scoped queries.
- Focused isolation tests exist for properties, statements, users, and notifications.
- The new CRM notification service uses an idempotency key and a database unique constraint.
- Notification delivery jobs have retry/backoff behavior and failure notifications.
- The database cleanup policy fails closed on unknown tables and is unit tested.
- Composer metadata is valid, and all reviewed PHP source files pass syntax validation.
- Plan/add-on enforcement and Stripe service code are separated better than much of the legacy controller logic.

These controls should be expanded into consistent platform-wide patterns rather than replaced piecemeal.

## 9. Product and regulated-operation readiness

This section assesses implemented controls, not legal compliance.

### Property operations

The application supports a useful core workflow: property/contact creation, tenancies, repairs, contractor quotes, work orders, invoices, statements, documents, notes, calendar events, and notifications. This is a credible agency-operations base.

The remaining workflow weaknesses are the lack of explicit domain state machines and invariant enforcement. Controllers often assign status strings directly, and business transitions are not centralized. Introduce tested state machines for tenancy, repair/work order, invoice/payment, subscription, compliance, and notice lifecycles.

### Finance/client money

The GL, receipts, payments, invoices, reconciliation, statements, and reporting code show meaningful implementation effort. However, cross-account isolation, incomplete account ownership, mixed legacy/new finance models, missing comprehensive audit coverage, and migration drift mean figures cannot yet be treated as reliably segregated or audit-ready.

Do not market the product as client-money, statutory accounting, or audit-ready until opening balances, posting invariants, reversal-only corrections, period locks, client/office segregation, reconciliation, owner allocations, rounding, tax, and immutable audit evidence have been independently validated against a documented accounting specification.

### Privacy and identity documents

Passport/right-to-rent fields and document uploads exist, but privacy operations and secure document lifecycle controls are not implemented deeply enough for sensitive identity evidence. Add purpose/legal-basis metadata, access/download auditing, retention/expiry, encryption, malware scan, revocation, export, and deletion/anonymization workflows.

### UK compliance features

There are generic compliance records and fields/search references for EPC, EICR, gas safety, PEP, deposit protection, and DSAR-related concepts. The review did not find a complete rules engine, evidence-serving log, blocking rules, immutable deadlines, or end-to-end automated tests for these areas. Treat them as partial product features, not compliance guarantees. Obtain specialist legal/accounting review before making regulated claims.

## 10. Remediation roadmap

### First 24–72 hours

1. Remove `/storage-link`, `/command/*`, `/helper`, `/test-sms`, all destructive GET routes, and HTTP-accessible cache/Artisan actions.
2. Disable `.env` mutation and stop returning secret values in settings forms.
3. Add permission middleware to every RoleController mutation; temporarily restrict all role/permission/settings routes to Super Admin until tenant-safe RBAC exists.
4. Disable or restrict vulnerable public uploads, especially SVG/XML/archive formats; enforce conservative size/MIME limits immediately.
5. Upgrade advisory-affected Composer packages on a security branch and retest.
6. Set production environment controls: debug off, secure cookies on, non-sync queue, least-privilege DB credentials, private storage.

### First 7 days

1. Implement mandatory account scoping in all accounting models/controllers and add two-account regression tests.
2. Replace the password reset code with Laravel's broker; add login/OTP/reset throttles and correct session regeneration/invalidation.
3. Remove default passwords and replace them with expiring set-password invitations.
4. Fix clean migrations and create an automated MySQL clean-build test.
5. Add CI for Composer validation/audit, migrations, tests, PHPStan/Larastan, Pint, and frontend build.
6. Add a frontend lockfile and documented Node version.

### First 30 days

1. Complete account ownership mapping for every table; backfill, validate, make keys non-null, and add foreign/composite constraints.
2. Implement account-aware RBAC, route policies, and authorization tests for every module/action.
3. Replace public uploads with a private scanned/signed-download pipeline.
4. Sanitize rich text and deploy security headers/CSP.
5. Add durable Stripe webhook inbox/idempotency and reconciliation.
6. Expand immutable audit coverage across access, finance, files, properties, tenancies, repairs, and settings.
7. Split the largest controllers and views around domain actions and Form Requests.

### 60–90 days

1. Formalize ledger and client/office money invariants with external accounting review.
2. Implement privacy/retention/export/deletion workflows and incident-response evidence.
3. Build end-to-end UAT automation for signup-to-billing, property-to-tenancy, repair-to-payment, and account cancellation/export.
4. Add production observability: structured/redacted logs, error tracking, queue/scheduler health, security alerts, audit export, backups, and restore drills.
5. Run an independent penetration test and tenant-isolation review after fixes, followed by a controlled staging pilot.

## 11. Release gates

Production should require all of the following:

- [ ] Every tenant-owned query is fail-closed and covered by two-account tests.
- [ ] Roles, permissions, designations, and memberships are account-aware; platform privileges are reserved.
- [ ] No HTTP endpoint writes `.env`, invokes Artisan, or reveals stored secrets.
- [ ] Clean install and representative upgrade both pass on the production database engine.
- [ ] Composer and frontend audits have no unaccepted high/critical advisories.
- [ ] All tests pass in CI; default local test setup is isolated and reproducible.
- [ ] Private upload, malware scanning, authorized download, and file-retention controls are active.
- [ ] Login/reset/OTP throttling, strong credential flow, session rotation, and administrator MFA are active.
- [ ] Core financial/property changes and sensitive downloads are immutably audited.
- [ ] Production uses HTTPS, secure cookies, debug off, security headers, managed secrets, asynchronous queues, and monitored scheduler/workers.
- [ ] Backup restoration and tenant export/deletion have been exercised.
- [ ] Independent security and accounting/control reviews approve the release scope.

## 12. Limitations

- The configured MySQL service was unavailable, so current live schema contents, backfill completeness, query plans, real data quality, and production configuration were not verified.
- No deployed infrastructure, TLS/proxy/WAF/CDN, cloud permissions, backups, worker manager, or scheduler host was available for review.
- No browser-based UX/accessibility/responsive testing or active exploitation was performed.
- npm was unavailable, and the missing lockfile prevented a reliable frontend advisory audit.
- The working tree contained 89 modified/untracked entries; findings should be revalidated against the exact release commit.
- This report identifies technical readiness and risk. It is not legal, tax, or accounting advice.

## 13. Final recommendation

Freeze production expansion and treat the next release as a control-hardening release. The highest-value implementation work is not another feature module; it is making account ownership, authorization, schema reproducibility, secret handling, file security, and testing non-optional platform behavior.

Once the four critical findings and high-risk authentication/upload/dependency issues are closed, ResiSquare will have a credible path from a feature-rich property-management monolith to a defensible SaaS platform. Until then, additional customers increase the impact of the existing shared-boundary defects.
