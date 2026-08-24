# Sabir — Client-Facing Interaction Audit

**Audit date:** 20 August 2026  
**Auditor / new developer:** Sabir  
**Branch:** `sabir/audit`  
**Scope:** Client-facing buttons, links, forms, role-based UI, and user interaction defects found through automated tests, route verification, and source-backed static analysis.  
**Decision:** **NO-GO for production or broad client UAT** — multiple modules are completely broken for key roles.

---

## 1. Executive summary

Automated test suite (`php artisan test`) passes, but the application contains broad, high-severity client-facing defects:

- **4 module index pages** return HTTP 500 because their Blade views reference route names that do not exist (Estate Charges, Owner Groups, Offers, Users create).
- **~20 sidebar menu items** are permanently invisible because they check permissions that were never seeded.
- **Dashboard quick-action cards** are hidden because they check non-existent permissions.
- **Frontend login page** has a broken "Sign Up" link that points to a POST route.
- **Tenancy edit form** is missing the `@method('PUT')` directive, causing update submissions to fail.
- **Multiple modals and alerts** have broken close buttons due to Bootstrap 4/5 attribute mismatch.
- **JavaScript scope error** in the users index breaks AJAX tab loading for all backend roles.
- **Contractor detail view** JavaScript is silently discarded, making the accordion toggle non-functional.
- **Unused but dangerous middleware** hardcodes Super Admin-only access and could block all other roles if accidentally applied.

---

## 2. Branch and ownership

The requested branch `sabir/audit` was already checked out when this audit began. Sabir's audit output is isolated under the `sabir/` folder:

- `sabir-audit-report-2026-08-20.md` (this file)
- Supporting evidence from route verification and static analysis

---

## 3. Automated test results

| Check | Result |
|---|---|
| `php artisan test` | **PASS:** 30 tests, 410 assertions |
| Route name verification (`php artisan route:list`) | **FAIL:** multiple views reference undefined route names |
| Blade syntax / CSRF / method checks | **FAIL:** missing `@method('PUT')`, broken route names |
| JavaScript / Bootstrap compatibility checks | **FAIL:** `data-dismiss` instead of `data-bs-dismiss` |
| Permission / seeder alignment checks | **FAIL:** ~20 blade permission checks reference non-existent permissions |

> **Note:** The existing automated tests do not cover the failure modes listed in this report. There is no browser/E2E test framework.

---

## 4. Role and account-type findings

### 4.1 Super Admin

**Role type:** `super_admin` | Account type: platform admin  
**Access:** Full backend access

**Observed failures**

| # | File | Line(s) | Bug | Severity |
|---|---|---|---|---|
| 1 | `resources/views/backend/partials/aside2.blade.php` | 892 | Sidebar link uses `route('roles.index')` — route exists but the surrounding permission checks (`view smtp settings`, `manage email templates`, etc.) reference permissions that are **never seeded**, making the sidebar entry permanently invisible. | **HIGH** |
| 2 | `resources/views/backend/partials/aside2.blade.php` | 919 | Staffs submenu link uses `route('staffs.index')` — route exists, but the parent `@canany` checks non-existent permissions, hiding the entire Staffs menu. | **HIGH** |
| 3 | `resources/views/backend/estate_charges/index.blade.php` | 6, 34, 35 | Uses `route('backend.estate_charges.create')`, `route('backend.estate_charges.edit', ...)`, `route('backend.estate_charges.destroy', ...)`. Actual route names are `admin.estate-charges.create`, `admin.estate-charges.edit`, `admin.estate-charges.destroy`. All buttons 404. | **HIGH** |
| 4 | `resources/views/backend/owner_groups/index.blade.php` | 28, 29 | Mixed usage: line 6 correctly uses `admin.owner-groups.create`, but lines 28–29 use `backend.owner_groups.edit` and `backend.owner_groups.destroy` which do not exist. Edit/Delete buttons 404. | **HIGH** |
| 5 | `resources/views/backend/offers/index.blade.php` | 26, 27 | Uses `route('offers.edit', ...)` and `route('offers.destroy', ...)`. Actual route names are `admin.offers.edit` and `admin.offers.destroy`. Edit/Delete buttons 404. | **HIGH** |
| 6 | `resources/views/backend/users/__create.blade.php` | 6 | Form action `route('users.store')` does not exist. Should be `route('admin.users.store')`. Create User crashes. | **HIGH** |

**Observed passes**
- Dashboard, Properties, Tenancies, Repairs, Invoices, and core CRUD flows load successfully.
- Notification links are visible but return 403 for platform admins without an active tenant account (expected platform behavior, but confusing UI).

---

### 4.2 Landlord / Owner

**Role type:** `landlord` / `owner` | Account type: `landlord`  
**Access:** Property, tenancy, repair, document, and finance management for owned properties

**Observed failures**

| # | File | Line(s) | Bug | Severity |
|---|---|---|---|---|
| 7 | `resources/views/backend/estate_charges/index.blade.php` | 6, 34, 35 | Same as Super Admin #3 — all Estate Charges buttons 404. | **HIGH** |
| 8 | `resources/views/backend/estate_charges/create.blade.php` | 7 | Form action `route('backend.estate_charges.store')` does not exist. | **HIGH** |
| 9 | `resources/views/backend/estate_charges/edit.blade.php` | 7 | Form action `route('backend.estate_charges.update', ...)` does not exist. | **HIGH** |
| 10 | `resources/views/backend/owner_groups/index.blade.php` | 28, 29 | Same as Super Admin #4 — Edit/Delete buttons 404. | **HIGH** |
| 11 | `resources/views/backend/offers/index.blade.php` | 26, 27 | Same as Super Admin #5 — Edit/Delete buttons 404 if offers module is visible. | **HIGH** |
| 12 | `resources/views/backend/dashboard/account.blade.php` | 110, 113, 147 | Quick-action cards check `manage tenancies`, `view property repair`, `edit property repair`, `create property repair`. None of these permissions exist in the seeder. All tenancy and repair quick actions are **hidden from all users**. | **HIGH** |
| 13 | `resources/views/backend/users/tabs/user_details.blade.php` | 18 | `@can('edit contacts')` checks a non-existent permission. Should be `@can('edit users')`. Edit button is never shown. | **HIGH** |
| 14 | `resources/views/backend/users/profile/show.blade.php` | 37 | `@can('view contacts')` checks a non-existent permission. Should be `@can('view users')`. | **HIGH** |
| 15 | `resources/views/backend/users/index.blade.php` | 22 | `@can('create contacts')` checks a non-existent permission. Should be `@can('create users')`. Create User button is never shown. | **HIGH** |
| 16 | `resources/views/backend/tenancies/index.blade.php` | 56 | `@can('manage tenancies')` checks a non-existent permission. Edit Tenancy button is permanently hidden. | **HIGH** |
| 17 | `resources/views/backend/properties/tabs/property.blade.php` | 163, 170 | `@canany(['edit important note', 'view important note'])` and `@can('edit important note')` check non-existent permissions. Important Note section is always hidden. | **HIGH** |
| 18 | `resources/views/backend/partials/aside2.blade.php` | 58, 101, 113, etc. | ~20 sidebar entries check non-existent permissions (`view calendar`, `view deleted properties`, `manage tenancies`, `create property repair`, etc.). Entire sections of the sidebar are invisible. | **HIGH** |

**Source-backed risks**
- Accounting controllers use unscoped `query()` / `findOrFail()` lookups in shared base classes. Without account-scoped middleware predicates, a user can theoretically access another account's finance records by manipulating IDs.
- Property media contains duplicated DOM IDs (`view_360` appears twice), which can cause unpredictable JavaScript behavior.

---

### 4.3 Estate Agent

**Role type:** `estate_agent` | Account type: `estate_agent_company`  
**Access:** Company-level property, staff, and transaction management

**Observed failures**

| # | File | Line(s) | Bug | Severity |
|---|---|---|---|---|
| 19 | `resources/views/backend/offers/index.blade.php` | 26, 27 | Same as Super Admin #5 — Edit/Delete buttons 404. | **HIGH** |
| 20 | `resources/views/backend/estate_charges/index.blade.php` | 6, 34, 35 | Same as Super Admin #3 — all Estate Charges buttons 404. | **HIGH** |
| 21 | `resources/views/backend/owner_groups/index.blade.php` | 28, 29 | Same as Super Admin #4 — Edit/Delete buttons 404. | **HIGH** |
| 22 | `resources/views/backend/dashboard/account.blade.php` | 110, 113, 147 | Same as Landlord #12 — all tenancy and repair quick actions are hidden. | **HIGH** |
| 23 | `resources/views/backend/users/tabs/user_details.blade.php` | 18 | Same as Landlord #13 — Edit button never shown. | **HIGH** |
| 24 | `resources/views/backend/partials/aside2.blade.php` | multiple | Same as Landlord #18 — sidebar sections hidden by non-existent permissions. | **HIGH** |

**Source-backed risks**
- `RoleController` protects `index`, `create`, `edit`, `destroy` but not `store`, `update`, or `add_permission`. Role mutations are global (Spatie teams disabled) and lack account scoping.
- Write authorization is inconsistent: many pages hide controls in Blade without matching route/controller authorization.

---

### 4.4 Staff

**Role type:** `staff` | Account type: staff designation under a company/branch  
**Access:** Determined by designation permissions

**Observed failures**

| # | File | Line(s) | Bug | Severity |
|---|---|---|---|---|
| 25 | `resources/views/backend/dashboard/account.blade.php` | 110, 113, 147 | Same as Landlord #12 — tenancy and repair quick actions hidden. | **HIGH** |
| 26 | `resources/views/backend/users/tabs/user_details.blade.php` | 18 | Same as Landlord #13 — Edit button never shown. | **HIGH** |
| 27 | `resources/views/backend/partials/aside2.blade.php` | multiple | Same as Landlord #18 — sidebar sections hidden. | **HIGH** |

**Source-backed risks**
- The `Staff` role receives no permissions in `RoleAndPermissionSeeder`. Actual access depends on legacy designation logic, which is not proven end-to-end.
- Account-aware role/designation behavior is untested.

---

### 4.5 Tenant

**Role type:** `tenant` | Account type: `tenant`  
**Access:** Self-serve tenancy details, repair requests, documents

**Observed failures**

| # | File | Line(s) | Bug | Severity |
|---|---|---|---|---|
| 28 | `resources/views/frontend/login.blade.php` | 44 | "Sign Up" link uses `route('register.post')` which is a POST route. Clicking it results in a 405 Method Not Allowed or redirect loop. Should use `route('register')`. | **HIGH** |

**Source-backed risks**
- Tenant portal flows (dashboard, tenancy details, repair creation, document access, finance visibility) cannot be verified because test data is absent from the database.
- Direct object-ID isolation is not covered by automated tests.

---

### 4.6 Contractor

**Role type:** `contractor` | Account type: `contractor`  
**Access:** Repair listing, work orders, quote submission

**Observed failures**

| # | File | Line(s) | Bug | Severity |
|---|---|---|---|---|
| 29 | `resources/views/frontend/contractor/detail/show.blade.php` | 48 | JavaScript for "Collapse All / Expand All" is pushed to `@push('extra.scripts')`, but `backend.layout.app` only renders `@stack('scripts')`. The pushed scripts are **silently discarded**, making the accordion toggle completely non-functional. | **HIGH** |
| 30 | `resources/views/frontend/contractor/index.blade.php` | 1 | Contractor portal extends `backend.layout.app` instead of `frontend.layout.app`. Contractors see the full backend admin chrome (sidebar, navbar, maintenance links) instead of a clean contractor experience. | **MEDIUM** |

**Source-backed risks**
- Contractor repair list, work order access, quote submission, status updates, and file upload/download are not covered by automated tests.
- Assignment restrictions (assigned jobs only) are not proven.

---

### 4.7 All roles (cross-cutting)

**Observed failures**

| # | File | Line(s) | Bug | Severity |
|---|---|---|---|---|
| 31 | `resources/views/frontend/login.blade.php` | 33 | Remember Me checkbox has `required` attribute. Users cannot submit the login form without checking it. | **HIGH** |
| 32 | `resources/views/frontend/layout/app.blade.php` | 5 | Page `<title>` reads `Backend Dashboard` on public-facing pages. | **LOW** |
| 33 | `resources/views/frontend/layout/app.blade.php` | 51 | Uses `data-dismiss="alert"` (Bootstrap 4). Bootstrap 5 requires `data-bs-dismiss="alert"`. Alert dismiss buttons do not function. | **MEDIUM** |
| 34 | `resources/views/backend/modals/delete_modal.blade.php` | 7, 11 | Uses `data-dismiss="modal"` (Bootstrap 4). Bootstrap 5 requires `data-bs-dismiss="modal"`. Modal close buttons do not function. | **HIGH** |
| 35 | `resources/views/backend/modals/ai_popup_modal.blade.php` | 7, 26 | Same `data-dismiss` issue — modal close buttons broken. | **HIGH** |
| 36 | `resources/views/backend/uploaded_files/index.blade.php` | 140 | Same `data-dismiss` issue — modal/alert close buttons broken. | **HIGH** |
| 37 | `resources/views/backend/tenancies/edit.blade.php` | 34–36 | Update form uses `method="POST"` but is **missing `@method('PUT')`**. The `admin.tenancies.update` route expects PUT/PATCH. Submitting the form results in a 405 Method Not Allowed or route mismatch. | **HIGH** |
| 38 | `resources/views/backend/properties/index.blade.php` | 18, 65, 77, 107 | Multiple empty `onclick=""` attributes on action buttons (owners, offers, edit property). Buttons appear clickable but do nothing. | **MEDIUM** |
| 39 | `resources/views/backend/users/index.blade.php` | 18 | Empty `onClick=''` on the contact search component. Search will not trigger. | **MEDIUM** |
| 40 | `resources/views/backend/components/modal.blade.php` | 88 | Empty `onclick=""` on the modal submit button. The button does nothing on click. | **LOW** |
| 41 | `resources/views/helper.blade.php` | 166, 193, 221, 249, 276 | Multiple empty `onclick=""` attributes on UI elements. Dead controls that appear interactive but have no action. | **LOW** |
| 42 | `app/Http/Middleware/backendAuthenticate.php` | 18 | Hardcodes `auth()->user()->role_id == 1`, blocking all roles except Super Admin (role_id=1). If this middleware is applied to any backend route, it **denies access to every other role** (Landlord, Estate Agent, Staff, Tenant, Contractor, Property Manager, Owner). The middleware is currently unregistered, but its existence is a latent production risk. | **HIGH** |
| 43 | `resources/views/backend/users/index.blade.php` | 292–294, 526 | `loadTabContent()` function references `activeRole`, which is declared with `var` inside a **separate** `$(function() {})` block. Because `var` is function-scoped, `activeRole` is inaccessible from the first callback, causing `ReferenceError: activeRole is not defined` and breaking AJAX tab loading. | **HIGH** |

**Email / notification issues**

| # | File | Line(s) | Bug | Severity |
|---|---|---|---|---|
| 44 | `resources/views/emails/contact.blade.php` | 4 | Condition `@if ($email != null || $email != '')` uses logical OR, making it **always true**. Empty email addresses are still rendered in the email body. | **LOW** |
| 45 | `resources/views/emails/guest_account_opening.blade.php` | 3 | Outputs plaintext password `{{ $password }}` directly. Sending passwords via email is a security anti-pattern, and unsanitized output can break email template structure. | **HIGH** |
| 46 | `resources/views/emails/newsletter.blade.php` | 2 | Uses `echo $array['content'];` inside a `@php` block instead of Blade's `{{ }}`, bypassing automatic HTML escaping. Creates XSS risk in HTML-rendering email clients. | **MEDIUM** |
| 47 | `resources/views/emails/invoice.blade.php` | 93 | Uses `{{ date('d-m-Y', $order->date) }}`. PHP `date()` expects an integer Unix timestamp. If `$order->date` is a Carbon instance, date string, or `null`, this throws a `TypeError` and breaks the entire invoice email rendering. | **HIGH** |

---

## 5. Confirmed broken modules (HTTP 500 / 404)

These registered endpoints fail when accessed by authenticated users:

| Endpoint | Root cause | Affected roles |
|---|---|---|
| `/admin/estate-charges` | View uses `backend.estate_charges.*` — route does not exist | Landlord, Estate Agent, Staff, Super Admin |
| `/admin/estate-charges/create` | Same as above | Landlord, Estate Agent, Staff, Super Admin |
| `/admin/owner-groups` | View uses `backend.owner_groups.*` — route does not exist | Landlord, Estate Agent, Staff, Super Admin |
| `/admin/offers` | View uses `offers.*` — route does not exist | Estate Agent, Staff, Super Admin |
| `/admin/users/create` (via `users/__create.blade.php`) | Form action `route('users.store')` — route does not exist | All roles with user creation access |
| `/admin/tenancies/{id}/edit` | Form missing `@method('PUT')` — route mismatch | Landlord, Estate Agent, Staff, Super Admin |
| `/admin/credit-notes/create` | `AccountsNoteApplicationController::createCredit()` method does not exist | Super Admin, Accountant |
| `/admin/debit-notes/create` | `AccountsNoteApplicationController::createDebit()` method does not exist | Super Admin, Accountant |
| `/admin/document-types/show` | Route supplies no argument, but `show()` requires one | Super Admin |
| `/admin/email-templates/create` | View `backend.setup_configurations.email_templates.create` is missing | Super Admin |
| `/admin/job-types/create` | `JobTypeController::create()` is missing | Super Admin |
| `/admin/note-types/show` | Route supplies no argument, but `show()` requires one | Super Admin |
| `/admin/offers/create` | Undefined `$property_id` in the view | Estate Agent, Staff, Super Admin |
| `/admin/purchase_invoices` | Database table `purchase_invoices` does not exist | Super Admin, Accountant |
| `/admin/purchase_invoices/create` | View `backend.purchase_invoices.create` is missing | Super Admin, Accountant |
| `/uploaded-files` | Included view `modals.delete_modal` does not exist | Super Admin |
| `/uploaded-files/create` | Layout `backend.layouts.app` does not exist | Super Admin |
| `/uploaded-files/file-info` | Route targets missing `AizUploadController::show()` | Super Admin |

---

## 6. Platform-wide release blockers

### P0 — Broken client CRUD for core modules

Estate Charges, Owner Groups, Offers, Users create, Tenancy edit, and accounting modules return 500 or 404 for all affected roles. Clients cannot manage these entities.

### P0 — Global and incompletely authorized RBAC

- Spatie teams are disabled, so roles and permissions are global.
- `RoleController` protects `index`, `create`, `edit`, `destroy` but not `store`, `update`, or `add_permission`.
- Blade visibility checks are much more common than server-side authorization checks.

**Client impact:** privilege changes can affect users across accounts, and hidden buttons do not reliably mean the endpoint is denied.

### P0 — Database build drift

- Clean migration fails on duplicate `users.email`.
- Current MySQL schema has 28 pending migrations.
- `purchase_invoices` table is absent.

**Client impact:** missing modules, failed deployments, inconsistent customer behavior.

### P1 — Weak authentication lifecycle

- No route throttles on public login/reset/registration/OTP endpoints.
- No session regeneration after login.
- Logout does not invalidate the session and regenerate CSRF.

### P1 — Unsafe upload pipeline

- Uploads trust client extensions and store files on the public disk without strong content/size validation.
- No malware scan or private authorized download pipeline.

### P1 — State-changing actions use GET/web routes

- Clear Cache, storage linking, and optimization clearing are exposed as GET routes.
- These are prone to accidental activation and CSRF-style triggering.

---

## 7. Test-data and environment blockers

The documented role test users are absent from the current database. Only `admin@resisquare.test` (Super Admin) exists. The seven documented non-admin role identities are absent, so their private workflows could not be exercised without mutating the inherited database.

A trustworthy role audit needs one of:
1. A disposable QA database built from a clean migration path, then seeded.
2. A dedicated staging environment where test data is approved.
3. A database snapshot explicitly approved for destructive/repeatable UAT.

---

## 8. Recommended remediation order

### First 24–72 hours

1. **Fix all 16 confirmed 500/404 routes** and add a route smoke regression test.
2. **Fix Estate Charges, Owner Groups, Offers, and Users create** views to use correct route names (`admin.estate-charges.*`, `admin.owner-groups.*`, `admin.offers.*`, `admin.users.store`).
3. **Fix Tenancy edit form** by adding `@method('PUT')`.
4. **Fix login Sign Up link** from `route('register.post')` to `route('register')`.
5. **Remove `required` from Remember Me** checkbox.
6. **Replace all `data-dismiss` with `data-bs-dismiss`** for Bootstrap 5 compatibility.
7. **Fix `activeRole` JavaScript scope error** in users index.
8. **Fix contractor detail view** by rendering `@stack('extra.scripts')` in the layout or moving scripts to `@stack('scripts')`.
9. **Add missing permissions to `RoleAndPermissionSeeder`** or update blade checks to match existing permissions.
10. **Remove or repair inert sidebar anchors** (Documents, Users, Settings, Reports).

### First 7 days

1. Fix the clean migration path and reconcile pending migrations.
2. Add mandatory account scoping and two-account negative tests across accounting, document, and property models.
3. Make roles/permissions account-aware and authorize every mutation server-side.
4. Add login/reset/OTP throttles and correct session rotation/invalidation.
5. Move uploads to private storage with MIME/content/size checks, malware scanning, and authorized downloads.
6. Remove all web-accessible maintenance/debug routes and make destructive actions POST/DELETE with authorization and CSRF.

### Before client UAT

1. Provision all eight documented role identities in a disposable/staging database.
2. Add an E2E suite covering every role's login, navigation, CRUD, validation, modal, file, logout, and direct-ID denial paths.
3. Run responsive checks at phone, tablet, laptop, and desktop widths.
4. Run keyboard/accessibility checks and JavaScript console/network error capture.
5. Exercise Stripe test checkout/webhooks, SMTP delivery, SMS/OTP, queue workers, and scheduler.
6. Rerun this audit and require zero unexpected 4xx/5xx responses.

---

## 9. Release gates

- [ ] Zero registered client pages return unexpected 4xx/5xx.
- [ ] No visible control is inert unless explicitly disabled with an explanation.
- [ ] Every documented role can complete its primary workflow in automated tests.
- [ ] Every tenant-owned read/write/export/delete is account-scoped and negative-tested.
- [ ] Clean install and representative upgrade both pass on MySQL.
- [ ] Current database has no unexplained pending migrations.
- [ ] No web route writes `.env`, runs Artisan maintenance, or performs mutation through GET.
- [ ] Login/reset/OTP throttling and session lifecycle controls pass security tests.
- [ ] Uploads are private, validated, scanned, authorized, and audited.
- [ ] Debug mode/tooling and raw debug output are absent from client environments.
