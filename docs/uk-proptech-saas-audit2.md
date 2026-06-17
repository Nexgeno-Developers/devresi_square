# UK PropTech SaaS Re-Audit 2

Prepared on 2026-06-16 as a focused re-audit for SaaS module creation after the recent registration, company, branch, staff, designation, and permission changes.

This is a product and technical audit. It is not legal, tax, accounting, or financial advice. UK compliance requirements must be validated with qualified advisers before launch.

## Executive Verdict

The codebase has moved closer to a SaaS onboarding shape, but it is still not ready for a safe multi-customer SaaS release. The recent work adds public registration, estate-agent company creation, branch records, designation-level staff permissions, and company owner transfer. Those are useful foundations for estate-agent accounts, but the core SaaS boundary is still missing.

The biggest blocker is that the platform does not yet have a first-class `account` or `workspace` tenant boundary. `Company` exists, but landlords are explicitly not meant to have company/branch/staff features, so `company_id` alone cannot be the SaaS tenant key. Every customer still needs an isolation boundary, including a solo landlord.

| Area | Current Score | Verdict |
|---|---:|---|
| Public signup and approval | 5 / 10 | OTP and admin approval exist, with estate-agent company creation. Needs account provisioning and policy hardening. |
| Estate-agent company setup | 4 / 10 | `Company`, `Branch`, owner transfer, profile fields, and branch UI exist. Not yet a tenant-safe organisation model. |
| Staff and designation permissions | 4 / 10 | Designation permission inheritance and custom staff overrides exist. Company scoping is incomplete. |
| Landlord/estate-agent feature split | 2 / 10 | Roles exist, but controller/policy gates do not enforce the split strongly enough. |
| Property limits per customer | 0 / 10 | No plan, entitlement, usage, or per-customer property limit was found. |
| Duplicate property address rule | 0 / 10 | No account-scoped normalized address uniqueness exists. |
| Cross-account data isolation | 1.5 / 10 | Some `created_by` filters exist. There is no systemic account isolation across properties, contacts, repairs, notes, documents, tenancies, or finance. |

## Stakeholder Requirement

Required customer types:

- `estate_agent`: can have a company, branches, staff, designations, and role/permission management.
- `landlord`: must be excluded from company, branch, staff, and role-permission management features.
- Each customer can have an individual property limit.
- Two different estate agents or landlords may add the same property address.
- The same estate agent or landlord account must not be able to add the same property twice.
- Different accounts must not see each other's properties, tenants, documents, repairs, invoices, notes, or related records.

## What Changed Since The Previous Audit

Recent local evidence reviewed:

- `database/migrations/2026_05_15_000001_create_registrations_table.php`
- `database/migrations/2026_05_15_000003_add_estate_agent_to_registrations_type.php`
- `database/migrations/2026_05_15_000004_drop_unique_email_from_registrations.php`
- `database/migrations/2026_05_15_000012_create_designation_has_permissions_and_drop_staff_role_id.php`
- `database/migrations/2026_05_15_000013_add_permissions_customized_to_staff_table.php`
- `database/migrations/2026_05_18_000001_extend_companies_branches_and_staff_for_agent_ownership.php`
- `database/migrations/2026_05_18_000002_create_company_owner_transfers_table.php`
- `database/migrations/2026_05_18_000003_add_missing_company_id_to_branches_table.php`
- `database/migrations/2026_05_19_000001_add_head_office_and_social_fields_to_branches.php`
- `app/Http/Controllers/Auth/RegistrationController.php`
- `app/Http/Controllers/Backend/RegistrationController.php`
- `app/Http/Controllers/Backend/BranchController.php`
- `app/Http/Controllers/Backend/StaffController.php`
- `app/Http/Controllers/Backend/DesignationController.php`
- `app/Http/Controllers/Backend/PropertyController.php`
- `app/Http/Controllers/Backend/UserController.php`
- `app/Http/Controllers/Backend/DocumentsController.php`
- `app/Http/Controllers/Backend/NotesController.php`
- `docs/application-flow.md`

Observed improvements:

- Public registration now supports `landlord` and `estate_agent` types.
- Registration uses OTP verification and backend approval.
- Backend approval maps `estate_agent` to the `Estate Agent` role and creates an owned `Company`.
- Company profile fields were expanded: registration number, registered address, contact arrays, logo, stamp, VAT number, website, social media, and services.
- Branches now belong to companies and can mark one branch as head office.
- Company owner transfer exists.
- Staff can be assigned a branch and designation.
- Designations have many permissions through `designation_has_permissions`.
- Staff permissions can inherit from designation or be customized directly.
- The UI now has visible surfaces for registrations, branches, designations, staff, and company profile data.

Observed non-SaaS recent work:

- Repair/work-order contractor assignment and quote email work has changed, including `SendFinalContractorAssignedEmail` and quote fields. This improves repairs but does not solve tenant isolation.

## Requirement Fit Matrix

| Requirement | Current State | Gap | Decision |
|---|---|---|---|
| Estate agent can have company | Partial. Estate-agent approval creates `Company::firstOrCreate(['owner_user_id' => $user->id])`. | The new user is not consistently linked through a tenant/account key. `company_id` on `users` is not reliably assigned in approval. | Keep `Company`, but place it under a new `accounts` tenant boundary. |
| Estate agent can have branches | Partial. `Branch` has `company_id`; `BranchController` scopes lists by owned company or `user.company_id`. | Controller lacks an explicit estate-agent-only policy. If branch routes are reachable, a non-agent user can trigger company creation through `branchCompanyFor()`. | Gate branch routes by account type and permission. |
| Estate agent can have staff | Partial. `StaffController` creates a `User` with role `Staff`, designation, branch, and optional custom permissions. | Staff users are not assigned `company_id` or `account_id`. Staff index is not company-scoped. Branch validation accepts any existing branch ID. | Staff must belong to same `account_id`; branch ID must be validated inside that account. |
| Estate agent role permission system | Partial. Spatie roles plus designation permissions and custom staff overrides exist. | Permission names remain inconsistent. Effective permissions are not scoped by account, branch, property, or ledger. | Use RBAC for actions and ABAC for data scope. |
| Landlord excluded from branches/staff/permissions | Not reliably enforced. | Roles exist, but route/controller policies are not enough. `BranchController` can create an owned company for any user if called. | Add account-type policies: landlord receives 403 for company, branch, staff, designation, and company transfer routes. |
| Individual property limit | Missing. | No property limit, subscription, plan, feature, entitlement, or usage counter found. | Add entitlements and enforce before property creation. |
| Same address allowed across different accounts | Missing but not blocked. | There is no global unique property address, which avoids the wrong global block, but there is also no account-scoped model to make this intentional. | Use account-scoped address fingerprint uniqueness only. |
| Same account cannot add same property twice | Missing. | `Property` has no `account_id`, no normalized address hash, and no duplicate validation/index. | Add `properties.account_id` and `address_fingerprint`, then enforce unique active property per account. |
| Accounts cannot see each other's data | Not safe. | Many controllers use raw `find`, `findOrFail`, global search, or user/property role queries without account scope. | Add account scopes, policies, route model binding, and isolation tests. |

## Critical Findings

### 1. There Is No First-Class SaaS Account Boundary

Current code uses a mixture of:

- `users.company_id`
- `companies.owner_user_id`
- `branches.company_id`
- `created_by`
- role checks such as `Estate Agent`, `Landlord`, `Staff`, and `Tenant`

This is not enough for SaaS isolation. Landlords do not have companies by requirement, but landlords still need an isolation boundary. Therefore `Company` cannot be the tenant root.

Recommended root model:

```text
accounts
- id
- account_type: estate_agent | landlord
- owner_user_id
- display_name
- property_limit
- status
- trial_ends_at
- suspended_at
- created_at
- updated_at
```

Then attach optional estate-agent organisation data:

```text
companies
- id
- account_id
- owner_user_id
- name
- registration_number
- registered_address
- communication_address
- emails
- phones
- logo_path
- stamp_path
- vat_number
- website
- social_media
- services
```

Decision: every customer gets an `account`; only estate-agent accounts get company/branch/staff features.

### 2. `Property` Is Not Tenant-Owned

`app/Models/Property.php` does not include `account_id`, `company_id`, or `branch_id` in the model fillable fields. The base migration for `properties` also does not create an account/company tenant key.

`PropertyController` currently scopes some list views by role:

- Landlord and staff: `created_by = auth()->id()`
- Estate agent: `created_by` in the agent's created users plus the agent
- Tenant: active tenancy property IDs
- Property manager and super admin: all properties

This does not satisfy account isolation. Staff created by an estate agent should see account-owned data according to branch/permission scope, not only records they personally created. A malicious or mistaken direct ID request also has to be blocked consistently across all property-related modules.

Required property columns:

```text
properties
- account_id
- company_id nullable
- branch_id nullable
- address_fingerprint
- normalized_line_1
- normalized_line_2
- normalized_city
- normalized_postcode
- normalized_country
- created_by
- updated_by
- deleted_at
```

For current MySQL-style soft deletes, use one of these uniqueness strategies:

- Application validation inside a transaction plus a supporting index on `(account_id, address_fingerprint)`.
- A generated `active_unique_key` column that is `1` for active rows and `NULL` for deleted rows, then unique index `(account_id, address_fingerprint, active_unique_key)`.

For PostgreSQL, use a partial unique index:

```sql
CREATE UNIQUE INDEX properties_account_address_active_unique
ON properties (account_id, address_fingerprint)
WHERE deleted_at IS NULL;
```

Do not create a global unique address index. The account ID must be part of the unique key so different customers can add the same address.

### 3. Property Limit Is Completely Missing

No implementation was found for:

- plan limits
- account-level property limit
- subscriptions
- feature entitlements
- usage counters
- property creation blocking at limit

Minimum viable design:

```text
accounts.property_limit
account_usage_snapshots.account_id
account_usage_snapshots.properties_active_count
usage_events.account_id
usage_events.event_type
usage_events.quantity
```

Create rule:

1. Resolve current `account_id`.
2. Count active properties for that account.
3. If count >= `property_limit`, block create with a clear validation error.
4. Allow edit of existing properties even when at limit.
5. If a property is soft-deleted, decide whether it frees capacity. Recommended: active properties only count.

### 4. Branch And Staff Features Are Not Safely Estate-Agent-Only

`BranchController::branchCompanyFor()` can create an owned company for any authenticated user when `$createIfMissing` is true. That is not compatible with the stakeholder rule that landlords are excluded from company/branch/staff features.

`StaffController` creates staff users with:

- `user_type = staff`
- `designation_id`
- `branch_id`
- role `Staff`

But it does not assign the staff user's `company_id` or any future `account_id`. Its index query is global:

```text
Staff::with('user.designation')->with('branch')->whereHas('user', user_type = staff)->paginate(...)
```

Required hardening:

- Add `account_id` to users and staff.
- On staff creation, set `user.account_id`, `user.company_id`, `user.branch_id`, `staff.account_id`, and `staff.branch_id`.
- Validate `branch_id` with `Rule::exists('branches', 'id')->where('account_id', current_account_id)`.
- Scope `StaffController@index`, edit, update, destroy by `account_id`.
- Deny staff/designation/branch routes for landlord accounts at policy and route middleware level.

### 5. Contact/User Searches Are Global

`UserController@index`, `UserController::ajaxList`, `UserController::staffAjaxList`, and property search helpers use global user/property queries. This can leak people, tenants, contractors, and property references across accounts.

Examples of risky patterns:

- `User::query()` in AJAX user search.
- `User::role('Tenant')->get()` in tenancy flows.
- `Property::where(...)` in user property search.
- `Property::query()` in sale invoice property search.

Every selectable list must be account-scoped. For a SaaS product, dropdown leakage is still data leakage.

### 6. Notes And Documents Can Bypass Parent Access Rules

`DocumentsController` and `NotesController` accept a morph class and ID from the request, then load the parent model by raw ID:

```text
$documentable = $documentableType::findOrFail($documentableId)
$noteable = $noteableType::findOrFail($noteableId)
```

They do not call the parent module's property/user access policy. A user who cannot open another account's property page must also be blocked from:

- listing notes
- creating notes
- editing notes
- deleting notes
- listing documents
- uploading documents
- viewing documents
- deleting documents

Fix: add a central `AccountOwned` contract or policy resolver for morph targets, then authorize every notes/documents operation through the target model.

### 7. Repair, Tenancy, Accounting, And Statement Flows Are Not Fully Account-Scoped

Several downstream modules link to property/user IDs but do not carry their own tenant key. This makes isolation depend on joins and caller discipline.

High-risk areas:

- `tenancies`
- `tenant_members`
- `repair_issues`
- `work_orders`
- legacy `invoices`
- `sys_sale_invoices`
- `sys_receipts`
- `sys_payments`
- GL journal lines
- documents
- notes
- uploads
- events
- notification logs

Recommendation: add `account_id` to all tenant-owned rows, even if the row also links to `property_id` or `user_id`. It makes authorization, indexes, reporting, exports, deletion, and incident response much safer.

### 8. Migration History Has Schema Drift Risk

There are signs that migration history and model expectations are not aligned:

- The older `create_users_table` migration shows duplicated `email` definitions and non-null `company_id`, while later user creation paths create users without company assignment.
- `Notes` model expects polymorphic `noteable_id` and `noteable_type`, but the visible notes migration creates `property_id`, `user_id`, and `type`.
- `PropertyController` writes `added_by` while `TracksUser` writes `created_by`; both concepts exist in different places.

This matters for SaaS because clean tenant migration depends on a reliable schema. Before adding the SaaS module, run a fresh migration build or schema dump validation and align models/migrations.

## Recommended SaaS Data Model

Minimum model ownership:

| Table / Domain | Required Ownership |
|---|---|
| accounts | Root SaaS tenant. |
| companies | `account_id`, estate-agent accounts only. |
| branches | `account_id`, `company_id`, estate-agent accounts only. |
| users | `account_id`, optional `company_id`, optional `branch_id`; super admins may be platform-scoped. |
| staff | `account_id`, `user_id`, optional `branch_id`, designation. |
| designations | `account_id`, not global, unless explicitly platform templates. |
| properties | `account_id`, optional `company_id`, optional `branch_id`, address fingerprint. |
| property_responsibilities | `account_id`, `property_id`, `user_id`, responsibility type. |
| owner_groups | `account_id`, `property_id`. |
| tenancies | `account_id`, `property_id`. |
| tenant_members | `account_id`, `tenancy_id`, `user_id`. |
| repairs/work orders | `account_id`, `property_id`, `tenant_id`, contractor IDs. |
| documents/notes/uploads | `account_id` plus morph target. |
| sale/purchase invoices, receipts, payments | `account_id`, optional `company_id`, optional `branch_id`, party ID. |
| GL lines/journals | `account_id`, ledger type, company/branch when relevant. |
| events/notifications | `account_id` plus target. |

## Required Policies

Add route or middleware guards:

```text
account.type:estate_agent
account.type:landlord
account.permission:manage branches
account.permission:manage staff
account.permission:manage designations
```

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
- `InvoicePolicy`
- `ReceiptPolicy`
- `UploadPolicy`

Policy rules:

- Super admin can cross accounts only in explicit platform-admin routes.
- Estate-agent owner can manage own company, branches, staff, designations, and account properties.
- Estate-agent staff can only access account data allowed by designation/custom permissions and branch/property scope.
- Landlord owner can manage own landlord account, own properties, tenants, repairs, documents, notes, and invoices, but cannot access company/branch/staff/designation routes.
- Tenants can access only linked active tenancy data and tenant-facing repair/document surfaces.
- Contractors can access only assigned repair/work-order data.

## Duplicate Address Rule

Use a normalized address fingerprint instead of raw string comparison.

Recommended normalization:

```text
fingerprint_source = lower(trim(line_1)) + "|" +
                     lower(trim(line_2)) + "|" +
                     lower(trim(city)) + "|" +
                     upper(remove_spaces(postcode)) + "|" +
                     country_id
address_fingerprint = sha256(fingerprint_source)
```

Acceptance rules:

- Account A can add `10 High Street, SW1A 1AA`.
- Account B can add `10 High Street, SW1A 1AA`.
- Account A cannot add another active `10 High Street, SW1A 1AA`.
- Staff under Account A also cannot duplicate it.
- If Account A soft-deletes the property, product must decide whether re-add is allowed. Recommended: allow re-add after soft delete, but keep an audit trail and optional restore prompt.

## Property Limit Rule

Create flow must call a single service before inserting:

```text
PropertyLimitService::assertCanCreateProperty($account)
```

The service must:

- Count active properties by `account_id`.
- Compare against `accounts.property_limit`.
- Ignore properties from other accounts.
- Run inside the same transaction as property creation.
- Produce a validation error, not a server error.
- Log rejected attempts for sales/support visibility.

Admin controls:

- Super admin can set account property limit.
- Limit can be overridden per customer.
- Import/migration tools can temporarily bypass limit only with a platform-admin audit event.

## P0 Implementation Plan

### P0.1 Create Account Boundary

1. Add `accounts` table.
2. Add `account_id` to `users`, `companies`, `branches`, `staff`, `designations`, `properties`, and all tenant-owned operational tables.
3. Create `CurrentAccountResolver`.
4. On login, resolve exactly one current account for normal users.
5. Add account-scoped global scopes or explicit query builders for tenant-owned models.

### P0.2 Provision Accounts From Registration

On approval:

- If registration type is `estate_agent`, create account with `account_type = estate_agent`, create company, link owner user to account/company.
- If registration type is `landlord`, create account with `account_type = landlord`, no company, no branches, no staff.
- Assign default property limit from plan/default config.
- Send password setup link instead of relying on email-delivered long-term password.

### P0.3 Enforce Feature Split

Estate-agent-only:

- company profile
- branches
- staff
- designations
- company owner transfer
- branch-level reporting
- staff permissions

Landlord-allowed:

- profile
- properties
- tenants/tenancies if used for own portfolio
- repairs
- documents
- notes
- invoices/statements relevant to own account

### P0.4 Property Create Hardening

Add to both full and quick property create:

- resolve account
- enforce property limit
- compute normalized address fingerprint
- validate account-scoped uniqueness
- assign `account_id`, optional `company_id`, optional `branch_id`, `created_by`

### P0.5 Scope Every Search And AJAX Endpoint

High-priority endpoints:

- `PropertyController::search`
- `PropertyController::searchAjax`
- `PropertyController::ajaxList`
- `UserController::searchProperties`
- `UserController::ajaxList`
- `UserController::staffAjaxList`
- accounting invoice property/customer searches
- tenancy tenant/property-manager selects
- repair property/tenant/contractor selects
- notes/documents list/create/edit/show/delete
- uploader file listing and download

### P0.6 Add Isolation Tests

Create automated tests before expanding features. The test suite must prove isolation, not only happy-path CRUD.

Required tests:

- Estate Agent A and Estate Agent B can add the same normalized address.
- Estate Agent A cannot add the same normalized address twice.
- Landlord A and Landlord B can add the same normalized address.
- Landlord A cannot add the same normalized address twice.
- Estate Agent A cannot view Estate Agent B property by direct URL.
- Estate Agent A cannot fetch Estate Agent B property through AJAX search.
- Estate Agent A cannot fetch Estate Agent B documents or notes by direct document/note endpoint.
- Staff in Estate Agent A cannot be assigned to Estate Agent B branch by tampered `branch_id`.
- Landlord receives 403 on branch, staff, designation, and company transfer routes.
- Property limit blocks create at cap and allows create after limit increase.
- Accounting statements ignore tampered `company_id` or `account_id` parameters.
- Tenant can only see active tenancy-linked property and repairs.
- Contractor can only see assigned repair/work-order records.

## Suggested Migration Sequence

1. Create `accounts`.
2. Backfill account rows:
   - one estate-agent account for each `companies.owner_user_id`
   - one landlord account for each approved landlord user without company
   - platform account or nullable account only for super admin/system users
3. Add nullable `account_id` columns to tenant-owned tables.
4. Backfill `account_id` from company, creator, property, tenancy, or linked parent.
5. Add indexes on `account_id`.
6. Add property address fingerprint columns and backfill.
7. Detect duplicate active properties within the same account and resolve manually.
8. Make `account_id` non-null on tenant-owned tables after cleanup.
9. Add account-scoped unique property address rule.
10. Add policies and tests.
11. Remove or quarantine old unscoped helper methods such as `Property::optionsForSelect()` and `User::optionsForSelect()`.

## SaaS Module Acceptance Criteria

The SaaS module should not be considered complete until all of these are true:

- Every customer has exactly one account boundary unless explicitly supporting multi-account users.
- Estate-agent accounts can manage company, branches, staff, and designations.
- Landlord accounts cannot access company, branch, staff, or designation features through UI or direct routes.
- Every tenant-owned row has `account_id` or derives from a parent with enforced policy and tests.
- Properties are unique by active normalized address within account, not globally.
- Property creation enforces per-account limit.
- All list, search, select, modal, document, note, upload, repair, tenancy, invoice, and statement endpoints are account-scoped.
- Direct ID tampering returns 403 or 404 without revealing whether another account's record exists.
- Super-admin cross-account access is available only through explicit platform-admin routes and is audit logged.
- Isolation tests are mandatory in CI.

## UK Compliance Context To Preserve During SaaS Design

The SaaS boundary must also protect compliance and finance evidence. Public GOV.UK guidance reviewed for this re-audit confirms these remain product-critical areas:

- Tenancy deposits in England and Wales must be handled through approved tenancy deposit protection workflows, including time-bound protection after receipt.
- Property agents in England who hold client money need client money protection controls and certificate handling.
- Right to Rent checks in England apply before tenancy start for adult occupiers, with anti-discrimination and follow-up requirements.
- Landlord safety workflows need evidence for gas, electrical, fire, and hazard responsibilities.
- Domestic MEES/EPC rules still require EPC-driven letting controls and exemption tracking.
- Estate agency businesses may need AML supervision workflows where applicable.

Source URLs:

- https://www.gov.uk/tenancy-deposit-protection
- https://www.gov.uk/client-money-protection-scheme-property-agents
- https://www.gov.uk/check-tenant-right-to-rent-documents
- https://www.gov.uk/private-renting/your-landlords-safety-responsibilities
- https://www.gov.uk/guidance/domestic-private-rented-property-minimum-energy-efficiency-standard-landlord-guidance
- https://www.gov.uk/guidance/registration-guide-for-estate-agency-businesses

## Final Decision

Do not build more SaaS-facing UI until the tenant boundary is implemented. The current company/branch/staff work should be retained, but it must sit under a new `accounts` model. That model is the only clean way to support both stakeholder customer types:

- Estate agent account: account plus company, branches, staff, designations, permissions, and branch/property scoping.
- Landlord account: account plus direct property portfolio management, with no branch/staff/designation surface.

The next engineering milestone should be a thin but strict SaaS foundation: account provisioning, account-scoped property ownership, duplicate-address prevention per account, property limit enforcement, route policies, and cross-account isolation tests. Without that, the same-address requirement and "do not see each other's data" requirement cannot be guaranteed.
