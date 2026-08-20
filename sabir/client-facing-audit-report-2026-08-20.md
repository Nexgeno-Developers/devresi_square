# ResiSquare client-facing QA and interaction audit

**Audit date:** 20 August 2026  
**Auditor / new developer:** Sabir  
**Branch:** `sabir/audit`  
**Base revision:** `de31c60` (`isolation`, 11 August 2026)  
**Environment tested:** local Laravel application at `http://127.0.0.1:8000`, MySQL database `resisquare_laravel_webdeveloper`  
**Decision:** **NO-GO for production or broad client UAT**

## 1. Executive result

The application has broad implemented functionality, but the current client experience is not reliable enough for release. The audit found:

- **16 registered GET pages returning HTTP 500** in a 170-route safe smoke pass.
- **2 links visibly presented to Super Admin that return HTTP 403**: Notifications and Notification Preferences.
- **4 visibly rendered sidebar entries that are inert** because their target is only `href="#"`: Documents, Users, Settings, and Reports.
- **3 major module index pages returning HTTP 500** because their views use route names that do not exist: Estate Charges, Owner Groups, and Offers.
- The Forgot Password page loads **three relative JavaScript assets from the wrong URL, all returning 404**.
- The public Login form incorrectly makes **Remember Me mandatory**.
- Contact, Bank, and Compliance tabs visibly print internal debug values such as `User ID: string(3) "157"`.
- Only the Super Admin QA identity exists in the current database. The seven documented non-admin role identities are absent, so their private workflows cannot be claimed as tested.
- The clean database migration path fails at the fourth migration, and the current local database has **28 pending migrations**.
- Composer reports **34 security advisories across 12 locked packages** and one abandoned package.
- There is no browser/E2E test framework in the repository. Node/npm are unavailable, so the frontend build could not be verified.
- Existing automated tests pass, but **30 tests for 515 application routes** do not cover the failure modes above.

This report records all issues observed by the automated and source-backed checks described below. It is not a claim that no additional bugs exist; seven roles, write workflows, third-party delivery, responsive layout, accessibility, and real browser JavaScript execution remain incompletely tested.

## 2. Branch and change ownership

The requested branch already existed and was checked out when this audit began:

```text
sabir/audit -> de31c60
SaaS-v1     -> de31c60
```

The working tree already contained **90 inherited entries** before Sabir's audit output was added: 66 modified and 24 untracked entries. They appear to be previous-developer work and were not cleaned, overwritten, staged, or attributed to Sabir.

Sabir's additions are isolated under this `sabir/` folder:

- `client-facing-audit-report-2026-08-20.md`
- `http-role-smoke.ps1`
- `http-route-smoke.ps1`
- `http-role-smoke-results.json`
- `http-role-smoke-results-super-admin.json`
- `http-route-smoke-results.json`

## 3. Tests performed

| Check | Result |
|---|---|
| `php artisan test` | **PASS:** 30 tests, 410 assertions |
| PHP syntax check excluding `vendor` | **PASS:** 1,058 files, 0 failures |
| Blade compilation (`php artisan view:cache`) | **PASS** |
| Composer metadata validation | **PASS** |
| Composer locked dependency audit | **FAIL:** 34 advisories, 12 packages, 1 abandoned package |
| Frontend build (`npm run build`) | **BLOCKED:** Node/npm are unavailable on PATH |
| Clean migration against temporary SQLite database | **FAIL:** duplicate `users.email` in migration `2024_10_24_115129_create_users_table` |
| Current MySQL migration status | **FAIL:** 28 migrations pending |
| Registered application routes | 515 excluding vendor routes; 523 total |
| Safe parameterless GET route smoke | **170 tested:** 143 OK, 16 server errors, 6 forbidden, 4 not found, 1 login redirect |
| Super Admin visible-link crawl | **88 requests:** 86 OK, 2 forbidden |
| Role login matrix | Super Admin passed; seven documented identities absent / login blocked |
| Core admin asset smoke | **PASS:** 18/18 unique local JS/CSS assets returned 200 across seven pages |
| Forgot Password asset smoke | **FAIL:** jQuery, Bootstrap bundle, and Toastr returned 404 |
| Pricing registration links | **PASS:** 3 monthly and 3 annual plan links returned the correct registration selections |
| Live browser click/responsive/accessibility run | **BLOCKED:** the browser automation runtime exits because of a host-level JavaScript module configuration outside the repository |

### What the green test suite does cover

The current tests give useful confidence in selected notification behavior, address providers, property identity/isolation, property statements, user account isolation, linked-property rendering, and a few model rules.

### What it does not cover

There is no end-to-end suite for buttons, modals, form validation, JavaScript console errors, mobile layout, accessibility, registration/OTP, role navigation, accounting CRUD, uploads, settings, or the 16 failing pages found by the route smoke test.

## 4. Role and account-type results

### Guest / prospective client

**Observed failures**

1. **Login cannot be submitted after unchecking Remember Me.** The checkbox has `required` in `resources/views/frontend/login.blade.php`.
2. **Forgot Password loads broken JavaScript URLs.** The layout uses relative paths such as `asset/js/jquery.min.js`; from `/password/forgot` these resolve to `/password/asset/js/...` and return 404.
3. **Public authentication uses the backend shell.** The Login page title is `Backend Dashboard`, includes the backend navbar, shows a side-menu toggle without a meaningful public sidebar, and renders a Clear Cache button. A guest clicking Clear Cache is redirected back to Login, making it appear non-responsive.
4. **Debug tooling is injected into public responses** in the tested local configuration because debug mode is enabled.
5. **Operational endpoints are registered on the public web surface:** `/storage-link`, `/command/optimize-clear`, and `/helper`. They should not be client-accessible web routes.
6. Login, registration, password reset, OTP verification, and OTP resend routes have no visible throttle middleware.
7. Authentication does not regenerate the session after successful login, and logout does not invalidate the session and regenerate the CSRF token.

**Observed passes**

- `/pricing` rendered three active plan cards.
- All three monthly and all three annual registration URLs returned 200 and preserved the correct plan, billing cycle, and account type.
- The basic invalid-login error tests pass.

### Super Admin

**Coverage:** highest-confidence role in this audit. The documented Super Admin identity exists and authenticated successfully.

**Observed passes**

- 86 of 88 login/visible-link requests returned usable responses.
- Dashboard, Calendar, Properties, Add Property, Contacts, Registrations, Plans, Addons, Accounts, Subscriptions, Tenancies, Repairs, Invoices, and Transactions returned 200 in the visible-link crawl.
- The seven representative core pages referenced 18 unique local JS/CSS assets; all 18 returned 200.

**Observed failures**

1. Navbar **View all notifications** returns 403.
2. Navbar **Notification Preferences** returns 403.
3. Estate Charges index returns 500.
4. Owner Groups index returns 500.
5. Offers index returns 500.
6. Contacts' Contact, Bank, and Compliance tabs visibly print internal `var_dump` output.
7. The live sidebar renders Documents, Users, Settings, and Reports links with `href="#"` and no action; clicking them only changes the fragment and appears to do nothing.
8. Clear Cache is a normal navbar link using GET. It changes server state without confirmation and is available far more broadly than a deployment operation should be.
9. A broader route pass found 16 HTTP 500 pages, listed in section 5.
10. Six parameterless routes returned 403. Billing and Portal Access may be intentionally account-only for a platform admin, but the three notification pages conflict with notification UI rendered for Super Admin.

### Landlord Owner (`landlord` account)

**Coverage status:** **BLOCKED.** `landlord.owner@resisquare.test` is documented but absent from the current database, so login and private flows could not be tested without mutating the inherited database.

**Source-backed risks affecting this role**

- Accounting paths still use unscoped `query()` / `findOrFail()` lookups, so changing record IDs can cross account boundaries.
- The required Remember Me checkbox affects login.
- Property media contains duplicated DOM IDs, including two live `view_360` controls sharing the same ID/name, which can make label/JavaScript targeting unpredictable.
- Uploads accept client extensions and store files on the public disk without strong content/size validation.
- Plan limits and contact/portal controls have focused tests, but full landlord CRUD, billing, finance, document, repair, and tenancy interactions do not.

### Landlord Contact

**Coverage status:** **BLOCKED.** `landlord.contact@resisquare.test` is absent.

**Unverified client-critical flows**

- Only assigned property visibility.
- Finance/document flag enforcement.
- Direct URL denial for another account's property, documents, statements, and repairs.
- Contact portal navigation and logout.

The current property isolation tests are useful but do not substitute for this portal flow.

### Estate Agent Owner (`estate_agent_company` account)

**Coverage status:** **BLOCKED.** `estate.owner@resisquare.test` is absent.

**Source-backed risks affecting this role**

- Global Spatie roles are not account-owned (`teams` is disabled), while `RoleController::store`, `update`, and `add_permission` are not covered by the constructor's permission middleware.
- Estate Charges, Offers, Owner Groups, purchase invoices, credit/debit note creation, and uploads contain confirmed HTTP 500 pages.
- Contact Contact/Bank/Compliance tabs render debug output.
- Account data isolation is manually applied and is incomplete in accounting.
- Branch/staff limits and tenant-safe role management have no end-to-end coverage.

### Estate Agent Staff

**Coverage status:** **BLOCKED.** `estate.staff@resisquare.test` is absent.

**Source-backed risks affecting this role**

- `Staff` receives no permissions in `RoleAndPermissionSeeder`; actual access depends on other legacy/global designation logic.
- Write authorization is inconsistent; many pages hide controls in Blade without matching route/controller authorization.
- Account-aware role/designation behavior is not proven.
- The same broken contacts, module pages, assets, upload, and accounting paths can affect staff depending on assigned permissions.

### Tenant

**Coverage status:** **BLOCKED.** `tenant@resisquare.test` is absent.

**Unverified client-critical flows**

- Tenant dashboard and tenancy details.
- Assigned property restriction.
- Repair creation/history.
- Document access/upload.
- Finance visibility flags.
- Direct object-ID isolation.
- Mobile/responsive tenant experience.

### Contractor

**Coverage status:** **BLOCKED.** `contractor@resisquare.test` is absent.

**Unverified client-critical flows**

- Contractor repair list and detail.
- Work order access.
- Quote view/submission.
- Status updates and completion.
- Restriction to assigned jobs only.
- File upload and download authorization.

### Property Manager

**Coverage status:** **BLOCKED.** `property.manager@resisquare.test` is absent.

**Source-backed risks affecting this role**

- The live sidebar explicitly renders the inert Users, Settings, and Reports links for Property Manager.
- Broad permissions expose this role to many of the confirmed 500 pages.
- Assigned-property boundaries are manually enforced and not covered end-to-end.
- Accounting, uploads, contacts, documents, roles, repairs, and notifications need direct negative/positive tests for this role.

## 5. Confirmed HTTP 500 pages

These are registered, parameterless GET endpoints exercised with an authenticated Super Admin. Each failure was corroborated by the Laravel log.

| Endpoint | Confirmed cause | Likely client impact |
|---|---|---|
| `/admin/credit-notes/create` | `AccountsNoteApplicationController::createCredit()` does not exist | Create Credit Note crashes |
| `/admin/debit-notes/create` | `AccountsNoteApplicationController::createDebit()` does not exist | Create Debit Note crashes |
| `/admin/document-types/show` | Route supplies no argument, but `DocumentTypeController::show()` requires one | Document Types show page crashes |
| `/admin/email-templates/create` | View `backend.setup_configurations.email_templates.create` is missing | Create Email Template crashes |
| `/admin/estate-charges` | View references undefined route `backend.estate_charges.create` | Estate Charges list crashes |
| `/admin/estate-charges/create` | View references undefined route `backend.estate_charges.store` | Add Estate Charge crashes |
| `/admin/job-types/create` | `JobTypeController::create()` is missing | Add Job Type crashes |
| `/admin/note-types/show` | Route supplies no argument, but `NoteTypeController::show()` requires one | Note Types show page crashes |
| `/admin/offers` | View references undefined route `offers.edit` | Offers list crashes when data exists |
| `/admin/offers/create` | Undefined `$property_id` in the view | Create Offer crashes |
| `/admin/owner-groups` | View references undefined route `backend.owner_groups.edit` | Owner Groups list crashes |
| `/admin/purchase_invoices` | Database table `purchase_invoices` does not exist | Purchase Invoices list crashes |
| `/admin/purchase_invoices/create` | View `backend.purchase_invoices.create` is missing | Create Purchase Invoice crashes |
| `/uploaded-files` | Included view `modals.delete_modal` does not exist | Uploaded Files list crashes |
| `/uploaded-files/create` | Layout `backend.layouts.app` does not exist | Upload create page crashes |
| `/uploaded-files/file-info` | Route targets missing `AizUploadController::show()` | File details action crashes |

Four additional smoke routes returned 404 because their controllers expect query data even though the route URI has no required parameter (`properties/load-form`, `property-repairs/load-form`, `property-repairs/selected-property/tenants`, and `users/load-form`). These were not counted as confirmed bugs without the expected AJAX parameters, but the route contracts should be made explicit and covered.

## 6. Confirmed dead, misleading, or fragile interactions

### Dead sidebar entries

The active backend layout includes `backend.partials.aside2`. Its rendered Super Admin sidebar contains four anchors with `href="#"` and no action:

- Documents
- Users
- Settings
- Reports

Documents is guarded by the Document Types permission. Users, Settings, and Reports are explicitly shown to Super Admin and Property Manager. These are genuine non-responsive UI entries, not merely hidden legacy markup.

### Notification controls deny the user who sees them

`backend.partials.navbar` always renders View all and Preferences to authenticated users. The corresponding routes require `current.account` and `account.status`. A platform Super Admin without a selected tenant account receives 403 from both links.

### Login and password-reset interaction defects

- Remember Me is incorrectly required.
- The public layout has a backend page title and backend navbar.
- Clear Cache is rendered before the `@auth` block.
- Relative script paths work on `/login` but fail on nested `/password/forgot`.

### Debug output visible inside normal tabs

The Contact, Bank, and Compliance user tabs execute `var_dump($userId)`. HTTP verification confirmed the debug text is present in all three rendered responses.

### Fragile duplicate DOM IDs

`resources/views/backend/properties/popup_forms/property_media.blade.php` contains:

- `id="photos"` twice on one input.
- Two live controls with `id="view_360"` and `name="view_360"` (URL input and upload hidden input).

Browser APIs and labels select the first matching ID; form submission can send two values for the same name. This is likely to produce inconsistent media form behavior.

## 7. Platform-wide release blockers

### P0 — Cross-account accounting access

The shared accounting base controller starts with `$modelClass::query()` and then uses `findOrFail()` for edit, update, and delete. Receipt show/PDF/delete and purchase invoice update also use direct unscoped lookups. Account-selection middleware does not add an Eloquent account predicate.

**Client impact:** a user in Account A can potentially view, edit, export, or delete Account B finance records by changing an ID.

### P0 — Global and incompletely authorized RBAC

- Spatie teams are disabled, so roles and permissions are global.
- `RoleController` protects `index`, `create`, `edit`, and `destroy`, but not `store`, `update`, or `add_permission`.
- Role queries and mutations are global rather than account-owned.
- Blade visibility checks are much more common than server-side authorization checks.

**Client impact:** privilege changes can affect users across accounts, and hidden buttons do not reliably mean the endpoint is denied.

### P0 — Web-based environment mutation

`POST /admin/env_key_update` accepts request-provided environment key names and writes them into `.env`. SMTP settings render environment-backed credential fields. The route is inside general authenticated/account middleware, not a narrowly protected deployment control.

**Client impact:** configuration corruption, credential exposure, mail takeover, or application outage.

### P0 — Database build and upgrade drift

- Clean migration fails on duplicate `users.email`.
- Current MySQL schema has 28 pending migrations.
- One confirmed page expects `purchase_invoices`, but that table is absent.

**Client impact:** missing modules, failed deployments, inconsistent customer behavior, and unreliable disaster recovery.

### P1 — Vulnerable and non-reproducible dependencies

- Composer audit: 34 advisories across 12 packages; one abandoned package.
- No JavaScript lockfile.
- Node/npm unavailable, so assets cannot be rebuilt or audited.

### P1 — Weak authentication lifecycle

- No route throttles on public login/reset/registration/OTP endpoints.
- No session regeneration after login.
- Logout does not invalidate the session and regenerate CSRF.
- OTP attempts/resends are not protected by a visible throttle route policy.

### P1 — Unsafe upload pipeline

Uploads trust client extensions, allow formats such as SVG/XML/archives, lack a clear file-size/content validation block, and store files on the public disk. There is no malware scan or private authorized download pipeline.

### P1 — State-changing and maintenance actions use GET/web routes

The application exposes storage linking, cache clearing, optimization clearing, logout, and some destroy actions as GET routes. These actions are prone to accidental activation and CSRF-style triggering.

## 8. Test-data and environment blockers

The documented role users are intended to be created by `php artisan saas:create-staging-test-data`. In the current database, querying all eight documented email addresses returned only:

```text
admin@resisquare.test — Super Admin
```

The audit did not run the staging seeder because it mutates the inherited local database by creating/updating users, accounts, memberships, subscriptions, plans, properties, tenancies, repairs, and work orders. A trustworthy role audit needs one of:

1. A disposable QA database built from a fixed migration path, then seeded.
2. A dedicated staging environment where this test data is approved.
3. A database snapshot explicitly approved for destructive/repeatable UAT.

The browser automation connection was also blocked by a host-level Node module configuration outside this repository. The repository has no Laravel Dusk, Playwright, Cypress, or equivalent E2E configuration to fall back on.

## 9. Recommended remediation order

### First 24–72 hours

1. Fix all 16 confirmed 500 routes and add a route smoke regression test.
2. Replace the four inert sidebar anchors with real routes/collapsible controls or remove them.
3. Hide or adapt notification links when Super Admin has no current account.
4. Remove `required` from Remember Me; use the proper public layout and absolute `asset()` URLs.
5. Remove active `var_dump` output from user tabs.
6. Remove all web-accessible maintenance/debug routes and make destructive actions POST/DELETE with authorization and CSRF.
7. Disable `.env` mutation from HTTP and stop rendering existing secrets.

### First 7 days

1. Fix the clean migration path and reconcile the 28 pending migrations against a backed-up database.
2. Add mandatory account scoping and two-account tests across all accounting and document models.
3. Make roles/permissions account-aware and authorize every mutation server-side.
4. Upgrade advisory-affected Composer dependencies and add a frontend lockfile/Node version.
5. Add login/reset/OTP throttles and correct session rotation/invalidation.
6. Move uploads to private storage with MIME/content/size checks, malware scanning, and authorized downloads.

### Before client UAT

1. Provision all eight documented role identities in a disposable/staging database.
2. Add an E2E suite covering every role's login, navigation, CRUD, validation, modal, file, logout, and direct-ID denial paths.
3. Run responsive checks at phone, tablet, laptop, and desktop widths.
4. Run keyboard/accessibility checks and JavaScript console/network error capture.
5. Exercise Stripe test checkout/webhooks, SMTP delivery, SMS/OTP, queue workers, and scheduler.
6. Rerun this audit and require zero unexpected 4xx/5xx responses.

## 10. Release gates

- [ ] Zero registered client pages return unexpected 4xx/5xx.
- [ ] No visible control is inert unless explicitly disabled with an explanation.
- [ ] Every documented role can complete its primary workflow in automated E2E tests.
- [ ] Every tenant-owned read/write/export/delete is account-scoped and negative-tested.
- [ ] Clean install and representative upgrade both pass on MySQL.
- [ ] Current database has no unexplained pending migrations.
- [ ] Composer/frontend audits have no unaccepted high/critical advisories.
- [ ] No web route writes `.env`, runs Artisan maintenance, or performs mutation through GET.
- [ ] Login/reset/OTP throttling and session lifecycle controls pass security tests.
- [ ] Uploads are private, validated, scanned, authorized, and audited.
- [ ] Debug mode/tooling and raw debug output are absent from client environments.

## 11. Evidence files

- `http-role-smoke.ps1` — reproducible role login and visible-link crawler.
- `http-route-smoke.ps1` — reproducible safe parameterless GET route smoke test.
- `http-role-smoke-results.json` — eight documented role attempts.
- `http-role-smoke-results-super-admin.json` — expanded Super Admin visible-link results.
- `http-route-smoke-results.json` — all 170 breadth-pass route results.

