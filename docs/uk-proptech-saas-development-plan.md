# UK PropTech SaaS Development Plan

Prepared on 2026-06-16 from `docs/uk-proptech-saas-audit2.md`.

This plan converts the SaaS re-audit into an implementation sequence. The goal is to build the minimum safe SaaS foundation before adding more SaaS-facing UI.

## Development Objective

Build a SaaS foundation that supports two customer types:

- Estate agent: account, company, branches, staff, designations, permissions, and branch/property scoping.
- Landlord: account and direct property portfolio management, with no company/branch/staff/designation features.

Mandatory business rules:

- Each customer has an individual property limit.
- Different customers can add the same property address.
- The same customer cannot add the same active property address twice.
- Customers cannot see each other's properties, tenants, documents, repairs, invoices, notes, uploads, or related records.

## Delivery Principles

- Do tenant isolation first. Do not build more SaaS UI until the account boundary is in place.
- Use `account_id` as the SaaS tenant key. Do not use `company_id` as the tenant key because landlords do not have companies.
- Keep `Company` for estate-agent organisation data only.
- Prefer one shared database with strict `account_id` scoping before considering schema-per-tenant.
- Add tests before refactoring every module so isolation regressions are caught immediately.
- Treat search, dropdowns, AJAX modals, uploads, documents, notes, and statements as data exposure surfaces.

## Phase 0 - Preparation And Schema Truth

### Step 0.1 Freeze SaaS Scope

Owner: product + engineering

Actions:

- Confirm customer types are only `estate_agent` and `landlord` for this SaaS milestone.
- Decide whether existing `owner`, `agent`, `freelancing_agent`, `contractor`, and `property_manager` registrations remain internal/admin-created roles or public signup types.
- Decide default property limits for landlord and estate-agent accounts.
- Decide if soft-deleted properties count toward property limit. Recommended: no, only active properties count.
- Decide if soft-deleted duplicate addresses can be re-added. Recommended: yes, with restore warning and audit trail.

Exit criteria:

- Written product decision for public customer types.
- Default property limit values are known.
- No new SaaS module work starts without `account_id` design.

### Step 0.2 Verify Current Schema

Owner: engineering

Actions:

- Run a fresh migration locally or in a disposable database.
- Identify schema drift around `users.company_id`, duplicated `users.email`, polymorphic `notes`, and `created_by` versus `added_by`.
- Create a schema correction list before adding SaaS migrations.
- Decide whether to create a schema dump after cleanup.

Files to inspect:

- `database/migrations/2024_10_24_115129_create_users_table.php`
- `database/migrations/2025_07_17_170036_merge_contacts_into_users.php`
- `database/migrations/2025_05_02_194626_create_notes_table.php`
- `app/Models/Notes.php`
- `app/Traits/TracksUser.php`
- `app/Models/Property.php`

Exit criteria:

- Fresh migration status is known.
- Any migration blockers are fixed or documented.
- Team knows whether `notes` is currently polymorphic in the live DB.

## Phase 1 - Account Foundation

### Step 1.1 Create `accounts` Table And Model

Owner: backend

Create:

- `app/Models/Account.php`
- migration for `accounts`
- `database/factories/AccountFactory.php` if tests use factories

Recommended columns:

```text
id
account_type enum/string: estate_agent | landlord
owner_user_id nullable FK users.id
display_name
property_limit integer nullable
status enum/string: active | suspended | pending
trial_ends_at nullable datetime
suspended_at nullable datetime
created_by nullable
updated_by nullable
timestamps
softDeletes optional
```

Relationships:

- `Account hasMany User`
- `Account hasOne Company`
- `Account hasMany Branch`
- `Account hasMany Property`
- `Account belongsTo owner User`

Exit criteria:

- Account model exists.
- Factory exists if testing uses factories.
- Basic model relationship tests pass.

### Step 1.2 Add `account_id` To Core Tables

Owner: backend

Add nullable `account_id` first, then backfill, then make non-null where possible.

First wave tables:

- `users`
- `companies`
- `branches`
- `staff`
- `designations`
- `properties`
- `property_responsibilities`
- `owner_groups`
- `owner_group_users`
- `tenancies`
- `tenant_members`
- `repair_issues`
- `work_orders`
- `documents`
- `notes`
- `uploads`

Second wave finance/accounting tables:

- legacy `invoices`
- `transactions`
- `sys_sale_invoices`
- `sys_purchase_invoices`
- `sys_receipts`
- `sys_payments`
- `gl_journals`
- `gl_journal_lines`
- `bank_reconciliations`
- `sys_bank_accounts`

Second wave communication/calendar tables:

- `events`
- `event_instances`
- `event_reminders`
- `notification_logs`

Exit criteria:

- All tenant-owned tables have `account_id` or a documented reason why they are platform-global lookup tables.
- Indexes exist on `account_id` and common query combinations such as `(account_id, created_at)` and `(account_id, status)`.

### Step 1.3 Backfill Existing Data

Owner: backend + DBA

Backfill rules:

- For every company with `owner_user_id`, create an `estate_agent` account.
- For approved landlord users without a company, create a `landlord` account.
- Link company to its account.
- Link owner user to account.
- Link branches through company account.
- Link staff through creator, branch, company, or manual mapping.
- Link properties through `created_by` for now, then validate manually for estate-agent staff records.
- Link property children from property account.
- Link tenancy, repairs, documents, notes, invoices, and statements from parent property/user/account.

Create an audit report:

- rows without account
- rows where parent and child account disagree
- duplicate active property addresses within same account
- users with multiple possible account owners

Exit criteria:

- No tenant-owned row is left unassigned without explicit platform-global classification.
- Parent-child account mismatches are resolved.
- Duplicate active properties within one account are reviewed.

### Step 1.4 Create Current Account Resolver

Owner: backend

Create:

- `app/Services/Accounts/CurrentAccountResolver.php`
- optional middleware: `SetCurrentAccount`

Resolver behavior:

- Super admin can be platform-scoped unless choosing an account context.
- Normal users must resolve exactly one current account.
- Staff resolves account from `users.account_id`.
- Tenant resolves account from `users.account_id` or active tenancy context, based on final product model.
- Contractor resolves account only inside assigned job context unless they are account-owned contacts.

Exit criteria:

- Controllers can call a single resolver instead of duplicating tenant logic.
- Invalid account context fails closed.

## Phase 2 - Registration And Account Provisioning

### Step 2.1 Update Public Registration Types

Owner: backend + frontend

Files:

- `app/Http/Controllers/Auth/RegistrationController.php`
- `resources/views/frontend/register.blade.php`
- `database/migrations/2026_05_15_000001_create_registrations_table.php`

Actions:

- Limit public signup to `landlord` and `estate_agent` if product confirms only these customer types.
- Keep other roles admin-created unless there is a product reason for public signup.
- Validate `type` consistently in frontend and backend.
- Preserve OTP verification.

Exit criteria:

- Frontend type options match backend validation.
- Old registration types are either migrated, hidden, or explicitly retained.

### Step 2.2 Update Backend Approval

Owner: backend

Files:

- `app/Http/Controllers/Backend/RegistrationController.php`
- `app/Models/Registration.php`
- `app/Models/User.php`
- `app/Models/Company.php`

Approval behavior:

- Create `Account` inside the same transaction as user creation.
- For `estate_agent`, create `Company` with `account_id`.
- For `landlord`, do not create `Company`.
- Set `users.account_id`.
- Set `users.company_id` only for estate-agent owner if the column remains.
- Set default `accounts.property_limit`.
- Assign role from registration type.
- Send password setup/reset link. Avoid long-term reliance on email-delivered plain password.

Exit criteria:

- Approved estate-agent user has account and company.
- Approved landlord user has account and no company.
- Failed approval rolls back account, company, user, and role assignment.

## Phase 3 - Feature Split And Route Gates

### Step 3.1 Add Account Type Middleware

Owner: backend

Create:

- `app/Http/Middleware/EnsureAccountType.php`

Usage:

```text
account.type:estate_agent
account.type:landlord
```

Apply estate-agent-only middleware to:

- company owner transfer
- branch routes
- staff routes
- designation routes
- company profile edit sections if exposed separately

Files:

- `routes/backend.php`
- `app/Http/Controllers/Backend/BranchController.php`
- `app/Http/Controllers/Backend/StaffController.php`
- `app/Http/Controllers/Backend/DesignationController.php`
- `app/Http/Controllers/Backend/CompanyController.php`

Exit criteria:

- Landlord receives 403 on branch, staff, designation, and company transfer routes.
- Estate-agent owner can access those routes if permissions allow it.

### Step 3.2 Update Sidebar And Profile UI

Owner: frontend/backend

Files:

- `resources/views/backend/partials/aside2.blade.php`
- `resources/views/backend/users/profile/show.blade.php`
- `resources/views/backend/users/profile/edit.blade.php`

Actions:

- Hide branch/staff/designation/company admin UI for landlords.
- Do not rely on hidden UI for security; backend middleware and policies must enforce it.
- Show estate-agent company and branch sections only for estate-agent account type.

Exit criteria:

- Landlord UI has no branch/staff/designation/company admin links.
- Direct route tests still enforce 403.

## Phase 4 - Property Ownership, Limits, And Duplicate Rules

### Step 4.1 Add Property Account Columns

Owner: backend

Files:

- `app/Models/Property.php`
- property migration

Add columns:

```text
account_id
company_id nullable
branch_id nullable
normalized_line_1 nullable
normalized_line_2 nullable
normalized_city nullable
normalized_postcode nullable
normalized_country nullable
address_fingerprint nullable
```

Model changes:

- Add `account_id`, `company_id`, `branch_id`, normalized fields, and `address_fingerprint` to `$fillable`.
- Add relationships to `Account`, `Company`, and `Branch`.
- Consider replacing `Property::optionsForSelect()` with an account-scoped method.

Exit criteria:

- New and backfilled properties have `account_id`.
- Model relationships work.

### Step 4.2 Add Address Fingerprint Service

Owner: backend

Create:

- `app/Services/Properties/AddressFingerprintService.php`

Rules:

- Trim whitespace.
- Lowercase address lines and city.
- Uppercase postcode.
- Remove postcode spaces.
- Include country/country ID.
- Hash the normalized source string.

Exit criteria:

- `Flat 1, 10 High Street, SW1A 1AA` and same address with casing/spacing differences produce the same fingerprint.
- Different countries or postcodes produce different fingerprints.

### Step 4.3 Add Property Limit Service

Owner: backend

Create:

- `app/Services/Properties/PropertyLimitService.php`

Behavior:

- Count active properties by account.
- Compare to `accounts.property_limit`.
- Throw validation exception if limit is reached.
- Ignore other accounts.
- Permit updates to existing property.

Exit criteria:

- Property create fails at limit.
- Property create succeeds after limit increase.
- Soft-deleted property behavior matches product decision.

### Step 4.4 Update Property Create And Update Flows

Owner: backend

Files:

- `app/Http/Controllers/Backend/PropertyController.php`
- property full form views
- property quick form views

Actions:

- Resolve current account on create.
- Enforce property limit before insert.
- Compute address fingerprint during create and when address changes.
- Validate duplicate address inside account.
- Set `account_id`, optional `company_id`, optional `branch_id`, `created_by`, `updated_by`.
- Replace role/creator-only scoping with account-aware query scopes.

Exit criteria:

- Different accounts can create the same address.
- Same account cannot create duplicate active address.
- Staff-created property belongs to estate-agent account, not only staff user.

### Step 4.5 Add Database Indexes

Owner: backend/DBA

Add:

- `properties(account_id, address_fingerprint)`
- `properties(account_id, branch_id)`
- `properties(account_id, created_at)`
- `properties(account_id, deleted_at)`

For duplicate active protection:

- MySQL: use generated column or transaction-level application validation plus index.
- PostgreSQL: use partial unique index on active rows.

Exit criteria:

- Duplicate rule is enforced by application and supported by DB index.
- Import/backfill scripts detect duplicates before unique constraint is enabled.

## Phase 5 - Estate-Agent Company, Branch, Staff, Designation Hardening

### Step 5.1 Company Hardening

Owner: backend

Files:

- `app/Models/Company.php`
- `app/Http/Controllers/Backend/CompanyController.php`
- `app/Http/Controllers/Backend/UserController.php`

Actions:

- Add `account_id` relationship.
- Ensure company owner transfer is same account or creates a deliberate audited transfer.
- When owner transfers, update account owner too or block until design is confirmed.
- Keep company features estate-agent-only.

Exit criteria:

- Company belongs to one account.
- Landlord cannot create company through profile or branch controller.

### Step 5.2 Branch Hardening

Owner: backend

Files:

- `app/Models/Branch.php`
- `app/Http/Controllers/Backend/BranchController.php`

Actions:

- Add `account_id`.
- Remove "create company if missing" behavior for non-estate-agent users.
- Scope index/edit/update/delete by account.
- Validate head office uniqueness inside account/company.

Exit criteria:

- Estate Agent A cannot view, edit, update, or delete Estate Agent B branch.
- Landlord receives 403.

### Step 5.3 Designation Hardening

Owner: backend

Files:

- `app/Models/Designation.php`
- `app/Http/Controllers/Backend/DesignationController.php`

Actions:

- Add `account_id` to designations.
- Treat existing global designations as templates or migrate them per account.
- Scope designation CRUD by current account.
- Keep permission definitions global, but designation-to-permission assignments account-scoped.

Exit criteria:

- Estate Agent A cannot see or edit Estate Agent B designations.
- Staff permission inheritance works inside account.

### Step 5.4 Staff Hardening

Owner: backend

Files:

- `app/Models/Staff.php`
- `app/Models/User.php`
- `app/Http/Controllers/Backend/StaffController.php`

Actions:

- Add `account_id` to `staff`.
- On staff create, set `users.account_id`, `users.company_id`, `users.branch_id`, `staff.account_id`, and `staff.branch_id`.
- Scope staff index/edit/update/delete by account.
- Validate branch and designation within account.
- Preserve custom permission override logic.

Exit criteria:

- Staff cannot be created into another account's branch.
- Staff list only shows current account staff.
- Custom permission override still works.

## Phase 6 - Contact/User Isolation

### Step 6.1 Scope Contact Lists

Owner: backend

Files:

- `app/Http/Controllers/Backend/UserController.php`

Actions:

- Scope `index` by `users.account_id`.
- Scope selected user detail tabs by account policy.
- Decide whether contractors can be global shared contacts or account-owned contacts. Recommended for P0: account-owned contacts only.
- Remove global user dropdowns.

Exit criteria:

- Account A cannot see Account B contacts in list or detail by direct URL.

### Step 6.2 Scope User Search Endpoints

Owner: backend

Files:

- `UserController::ajaxList`
- `UserController::staffAjaxList`
- `UserController::searchProperties`

Actions:

- Add account scope to all user/contact/property searches.
- Validate `ids` input belongs to current account.
- Return empty results for cross-account IDs.

Exit criteria:

- Tampered IDs do not reveal other account users/properties.

## Phase 7 - Notes, Documents, Uploads

### Step 7.1 Add Account Ownership To Notes/Documents

Owner: backend

Files:

- `app/Models/Notes.php`
- `app/Models/Document.php`
- `app/Http/Controllers/Backend/NotesController.php`
- `app/Http/Controllers/Backend/DocumentsController.php`

Actions:

- Add `account_id` to notes and documents.
- On create, derive `account_id` from parent model.
- On list/show/edit/delete, authorize through parent model and note/document account.
- Reject arbitrary morph classes not in an allowlist.

Allowed morph targets for P0:

- `App\Models\Property`
- `App\Models\User`
- add others only after policy exists

Exit criteria:

- Account A cannot list, show, edit, or delete Account B note/document by direct ID.
- Notes/documents cannot be attached to unauthorized parent records.

### Step 7.2 Scope Uploads

Owner: backend

Files:

- `app/Http/Controllers/Backend/AizUploadController.php`
- `app/Models/Upload.php`

Actions:

- Add `account_id` to uploads.
- Assign account on upload.
- Scope file list, preview, download, and delete by account.
- Consider tenant-specific storage paths for future S3 migration.

Exit criteria:

- Account A cannot access Account B uploaded file by ID.

## Phase 8 - Tenancy, Repair, Work Order, Finance Scoping

### Step 8.1 Tenancy Scoping

Owner: backend

Files:

- `app/Http/Controllers/Backend/TenancyController.php`
- `app/Models/Tenancy.php`
- `app/Models/TenantMember.php`

Actions:

- Add `account_id`.
- Scope tenant and property-manager selectors.
- Ensure selected property belongs to current account.
- Ensure selected tenants belong to current account or are created inside current account.

Exit criteria:

- Account A cannot create tenancy on Account B property.
- Account A cannot add Account B tenant.

### Step 8.2 Repair And Work Order Scoping

Owner: backend

Files:

- `app/Http/Controllers/Backend/PropertyRepairController.php`
- `app/Http/Controllers/Backend/WorkOrderController.php`
- repair and work-order models

Actions:

- Add `account_id` to repair issues and work orders.
- Derive account from selected property.
- Scope repair lists and direct show/edit/update/delete by account.
- Scope tenant, contractor, property manager, and property dropdowns.
- Decide contractor model: account-owned contractor contacts for P0, optional shared contractor network later.

Exit criteria:

- Account A cannot access Account B repair/work order by direct URL.
- Tenant can only see active tenancy property repairs.
- Contractor can only see assigned repairs.

### Step 8.3 Finance And Statement Scoping

Owner: backend/accounting

Files:

- `app/Http/Controllers/Backend/Accounting/*`
- `app/Services/Accounting/*`
- finance models

Actions:

- Add or enforce `account_id` on all finance rows.
- Ignore request-provided `company_id` or `account_id` unless user is authorized.
- Scope invoice, receipt, payment, GL, bank, and statement queries.
- Prevent cross-account party/property selection.

Exit criteria:

- Tampered `company_id` cannot expose another account statement.
- Invoices and receipts cannot be created for another account's user or property.

## Phase 9 - Policy And Test Gate

### Step 9.1 Add Policies

Owner: backend

Minimum policies:

- `AccountPolicy`
- `CompanyPolicy`
- `BranchPolicy`
- `StaffPolicy`
- `DesignationPolicy`
- `PropertyPolicy`
- `UserContactPolicy`
- `TenancyPolicy`
- `RepairIssuePolicy`
- `DocumentPolicy`
- `NotePolicy`
- `UploadPolicy`
- `InvoicePolicy`
- `ReceiptPolicy`

Exit criteria:

- Direct ID access is consistently blocked.
- Controllers stop duplicating raw authorization logic where possible.

### Step 9.2 Add Feature Tests

Owner: backend QA

Create test files:

- `tests/Feature/Saas/AccountProvisioningTest.php`
- `tests/Feature/Saas/PropertyIsolationTest.php`
- `tests/Feature/Saas/PropertyLimitTest.php`
- `tests/Feature/Saas/BranchStaffAccessTest.php`
- `tests/Feature/Saas/DocumentNoteIsolationTest.php`
- `tests/Feature/Saas/TenancyRepairIsolationTest.php`
- `tests/Feature/Saas/FinanceIsolationTest.php`

Required scenarios:

- Estate Agent A and Estate Agent B can add same normalized address.
- Estate Agent A cannot add same active address twice.
- Landlord A and Landlord B can add same normalized address.
- Landlord A cannot add same active address twice.
- Property limit blocks create at cap.
- Property limit allows create after super-admin limit increase.
- Landlord receives 403 on branch routes.
- Landlord receives 403 on staff routes.
- Landlord receives 403 on designation routes.
- Staff cannot be assigned to another account's branch.
- Account A cannot view Account B property by direct URL.
- Account A cannot fetch Account B property through AJAX.
- Account A cannot fetch Account B users through AJAX.
- Account A cannot fetch Account B notes/documents/uploads.
- Tenant can only see active tenancy-linked property.
- Contractor can only see assigned repair.
- Statement endpoints ignore tampered account/company parameters.

Exit criteria:

- These tests are passing before broader module refactoring continues.

## Phase 10 - Release Plan

### Step 10.1 Internal Migration Dry Run

Owner: backend + DBA

Actions:

- Run all migrations on a copy of current data.
- Generate backfill report.
- Resolve duplicate active properties per account before enabling unique rule.
- Confirm no tenant-owned orphan rows remain.

Exit criteria:

- Dry run can be repeated without manual guesswork.
- Backfill report is clean or has documented manual fixes.

### Step 10.2 Staging Verification

Owner: QA + product

Manual test accounts:

- Estate Agent A with two branches and two staff.
- Estate Agent B with one branch and one staff.
- Landlord A.
- Landlord B.
- Tenant linked to one property.
- Contractor assigned to one repair.

Manual checks:

- Same address can exist in Account A and Account B.
- Duplicate same address fails inside same account.
- Landlord cannot see branch/staff/designation links.
- Landlord direct route access returns 403.
- Staff sees only allowed account/branch/property data.
- Notes, documents, uploads, repairs, tenancies, invoices, and statements do not leak.

Exit criteria:

- QA signoff on all P0 isolation scenarios.

### Step 10.3 Production Rollout

Owner: engineering lead

Actions:

- Backup database.
- Run migrations.
- Run backfill.
- Run post-migration consistency checks.
- Enable route middleware and policies.
- Enable duplicate-address enforcement.
- Monitor logs for authorization failures and account resolver errors.

Rollback plan:

- Migrations must be reversible where possible.
- Keep pre-migration backup.
- Keep feature flag for new route middleware if practical, but do not disable isolation silently after launch.

Exit criteria:

- Production accounts are created.
- Existing users can log in and resolve account.
- No cross-account data is visible in smoke tests.

## Recommended Work Order

Build in this exact order:

1. Schema truth and migration cleanup.
2. `accounts` model and account backfill.
3. Current account resolver.
4. Registration approval provisioning.
5. Route middleware for landlord versus estate-agent feature split.
6. Property `account_id`, property limit, and duplicate address fingerprint.
7. Property list/search/detail scoping.
8. Branch, staff, and designation scoping.
9. User/contact search scoping.
10. Notes, documents, and uploads scoping.
11. Tenancy scoping.
12. Repair/work-order scoping.
13. Finance/statement scoping.
14. Full isolation test suite.
15. Staging dry run and production rollout.

## Definition Of Done

The SaaS foundation is done only when:

- Every normal user resolves to one current account.
- Estate-agent account has company/branch/staff/designation features.
- Landlord account cannot access company/branch/staff/designation features.
- Every tenant-owned record is account-scoped.
- Same address is allowed across accounts.
- Same active address is blocked inside one account.
- Property limits are enforced on create.
- Direct ID tampering returns 403 or 404.
- Search/dropdown/AJAX endpoints are account-scoped.
- Notes/documents/uploads cannot bypass parent model access.
- Tenant, contractor, staff, landlord, estate-agent owner, and super-admin access paths are covered by tests.
- Fresh migration and backfill path is documented and repeatable.
