# Laravel SaaS Architecture Audit

Date: 2026-06-29  
Scope: static analysis of the Laravel application in this repository.  
Constraint: no application code was modified. This document is documentation only.

## Executive Summary

This application is a sizeable Laravel property-management CRM with modules for public registration, backend CRM, properties, contacts, tenancies, repairs, contractors, work orders, documents, notes, calendar events, legacy finance, newer `sys_*` accounting, staff/designation permissions, branches and companies.

It is not yet safely multi-tenant. Some newer code introduces `companies`, `branches`, `users.company_id`, `users.branch_id`, staff branch assignment, and company owner transfers, but the actual business records are still mostly global. Core tables such as `properties`, `tenancies`, `repair_issues`, `work_orders`, `documents`, `notes`, `events`, legacy invoices and many accounting tables either have no tenant key or only partial `company_id` use. A user-level `company_id` is also not enough for SaaS because a person can belong to multiple workspaces.

The largest SaaS blockers are:

1. No universal tenant/workspace boundary across all customer-owned tables.
2. Global Spatie roles without teams/workspace scope.
3. Many authenticated backend routes lack controller-level authorization.
4. Many list/search/show/update queries use raw IDs, `created_by`, role checks, or unscoped `all()/find()/findOrFail()`.
5. Public/debug utility routes exist for storage linking, helper pages, SMS testing and uploads.
6. Two finance domains coexist: legacy `invoices`/`transactions` and newer `sys_*`/GL accounting.
7. Global caches use IDs without company context.
8. Authentication does not consistently enforce `status`, `can_login`, verified state, subscription state, or workspace membership.

## Analysis Inputs

Primary files inspected:

- `routes/web.php`
- `routes/backend.php`
- `config/auth.php`
- `config/permission.php`
- `app/helpers.php`
- `app/Models/*`
- `app/Http/Middleware/*`
- `app/Http/Controllers/Auth/*`
- `app/Http/Controllers/Frontend/*`
- `app/Http/Controllers/Backend/*`
- `app/Console/Commands/*`
- `database/migrations/*`
- `database/seeders/RoleSeeder.php`
- `database/seeders/RoleAndPermissionSeeder.php`
- `resources/views/backend/partials/aside2.blade.php`

There are existing untracked SaaS/role documents under `docs/`. They were not edited.

## 1. Existing Database Schema

The schema is migration-defined and broad. This inventory groups the current tables by domain and flags SaaS ownership status.

### Platform, Framework and Access Tables

| Table | Purpose | Tenant key status |
|---|---|---|
| `users` | Login/contact identity; also used as owner, landlord, tenant, contractor, staff and applicant identity | Has `company_id`, `branch_id`, `created_by`, `updated_by`, but user-level company is not sufficient for multi-workspace SaaS |
| `user_details` | Extended user/contact details | Missing `company_id`; belongs to `users` |
| `users_categories` | Contact/user categories | Global master data |
| `roles` | Legacy role table and Spatie role table name | Global; Spatie teams disabled |
| `permissions` | Spatie permissions | Global |
| `model_has_roles` | Spatie user-role pivot | Global; no `team_id`/workspace |
| `model_has_permissions` | Spatie direct permission pivot | Global; no `team_id`/workspace |
| `role_has_permissions` | Spatie role-permission pivot | Global |
| `designations` | Staff designation | Global; no `company_id` |
| `designation_has_permissions` | Designation permission pivot | Global; no `company_id` |
| `staff` | Staff profile linked to user/parent/branch | Has `branch_id`, no direct `company_id` |
| `staff_contacts` | Staff emails/phones | Missing `company_id` |
| `registrations` | Public signup approval flow | Missing `company_id`; approval may create user/company |
| `sessions` | Laravel sessions | Global framework table |
| `password_reset_tokens` | Password reset | Global framework table |
| `audits` | OwenIt audit records | Missing explicit `company_id` |
| `cache`, `cache_locks` | Laravel cache | Global keys |
| `jobs`, `job_batches`, `failed_jobs` | Queue tables | Jobs must carry tenant context during SaaS conversion |
| `uploads` | Uploaded files | Has `user_id`; missing `company_id` and private file ownership rules |
| `business_settings` | App/business settings | Global; must split platform vs workspace settings |
| `email_templates` | Email templates | Global; no `company_id` |
| `sms_templates` | SMS templates | Global; no `company_id` |
| `otp_configurations` | OTP/SMS provider config | Global platform setting |
| `notification_logs` | Outbound notification log | Missing `company_id` in current ownership model |
| `form_submissions` | Public form submissions | Missing `company_id` |

### Organisation and People Tables

| Table | Purpose | Tenant key status |
|---|---|---|
| `companies` | Newer organisation/workspace-like entity | Candidate SaaS tenant root; has owner fields |
| `company_owner_transfers` | Company owner transfer history | Has `company_id` |
| `branches` | Company branches | Has `company_id` in later migrations |
| `bank_details` | User bank details | Missing `company_id`; linked to user |
| `bank_accounts` | User bank accounts | Missing `company_id`; linked to user |
| `owner_group` | Property owner grouping | Missing `company_id` |
| `owner_group_users` | Owner group users/members | Missing `company_id` |
| `countries`, `currencies`, `nationalities` | Master/reference data | Global |
| `local_authority_groups`, `local_authorities` | UK local authority master data | Global |
| `station_names`, `school_names`, `religious_places` | Property amenity master data | Global |

### Property and Tenancy Tables

| Table | Purpose | Tenant key status |
|---|---|---|
| `properties` | Core property record | Missing `company_id`; has `added_by`, `deleted_by`; controller often uses `created_by` despite migration using `added_by` |
| `property_responsibilities` | Staff/branch/designation responsibilities per property | Has property/branch/designation/user, no `company_id` |
| `offers` | Property offers/applicants | Missing `company_id` |
| `tenancies` | Property tenancy | Missing `company_id` |
| `tenant_members` | Tenancy members/users | Missing `company_id` |
| `property_manager_tenancy` | Property-manager-to-tenancy pivot | Missing `company_id` |
| `tenancy_types` | Tenancy master data | Global or workspace-configurable later |
| `tenancy_sub_statuses` | Tenancy status master data | Global or workspace-configurable later |
| `compliance_types` | Compliance type catalogue | Global |
| `compliance_records` | Property compliance record | Missing `company_id`; linked through property |
| `compliance_details` | Compliance key/value detail | Missing `company_id`; linked through record |
| `estate_charges` | Property/owner group charges | Missing `company_id` |
| `estate_charges_items` | Estate charge line items | Missing `company_id` |

### Repair, Contractor and Work Order Tables

| Table | Purpose | Tenant key status |
|---|---|---|
| `repair_categories` | Repair category hierarchy | Global master data |
| `repair_issues` | Maintenance/repair issue | Missing `company_id`; has property/tenant/final contractor and created/updated audit fields |
| `repair_photos` | Repair photos | Missing `company_id`; linked through repair issue |
| `repair_assignments` | Generic repair assignments | Missing `company_id` |
| `repair_histories` | Repair workflow history | Missing `company_id` |
| `repair_issue_users` | Repair-to-user links | Missing `company_id` |
| `repair_issue_contractor_assignments` | Quote/final contractor assignments | Missing `company_id`; token is stored directly |
| `repair_issue_property_managers` | Repair property-manager assignments | Missing `company_id` |
| `job_types` | Work/repair job type hierarchy | Global or workspace-configurable later |
| `work_orders` | Work order header | Missing `company_id` |
| `work_order_items` | Work order lines | Missing `company_id`; linked through work order |
| `invoices`, `invoice_items`, `invoice_statuses` | Legacy repair/work-order invoice module | Missing `company_id`; overlaps newer accounting |

### Shared Records and Calendar Tables

| Table | Purpose | Tenant key status |
|---|---|---|
| `notes` | Polymorphic notes | Missing `company_id` |
| `note_types` | Note type master data | Global or workspace-configurable later |
| `documents` | Polymorphic documents | Missing `company_id`; linked to upload/document type |
| `document_types` | Document type master data | Global or workspace-configurable later |
| `events` | Calendar master events | Missing `company_id` |
| `event_instances` | Generated event instances | Missing `company_id`; linked through event |
| `event_instance_changes` | Event instance changes | Missing `company_id` |
| `event_reminders` | Event reminders | Missing `company_id` |
| `eventables` | Polymorphic event links | Missing `company_id` |

### Legacy Finance and Notes Tables

| Table | Purpose | Tenant key status |
|---|---|---|
| `account_headers` | Legacy account header | Missing `company_id` |
| `transactions` | Legacy transaction/payment ledger | Missing `company_id` |
| `transaction_categories` | Legacy transaction categories | Global/workspace-configurable later |
| `tax_rates` | Legacy tax rates | Global/workspace-configurable later |
| `payment_methods` | Payment methods | Global/workspace-configurable later |
| `purchase_invoices`, `purchase_invoice_items` | Legacy purchase invoice module | Missing `company_id` |
| `credit_notes`, `debit_notes` | Legacy notes | Missing `company_id` |
| `credit_note_refunds`, `debit_note_refunds` | Legacy note refunds | Missing `company_id` |
| `note_applications` | Note application polymorphic pivot | Missing `company_id` |
| `document_sequences` | Number sequences | Has `branch_id`; missing `company_id` |

### Newer Accounting and GL Tables

| Table | Purpose | Tenant key status |
|---|---|---|
| `sys_taxes` | Accounting taxes | Missing `company_id` |
| `sys_income_categories` | Income categories | Missing `company_id` |
| `sys_expense_categories` | Expense categories | Missing `company_id` |
| `sys_invoice_headers` | Invoice header/branding | Missing `company_id` |
| `sys_sale_invoices` | Sale invoice header | Partial `company_id` use in controllers/services; verify migration state and constraints |
| `sys_sale_invoice_items` | Sale invoice items | Missing direct `company_id` |
| `sys_purchase_invoices` | Purchase invoice header | Missing/unclear `company_id`; must be verified |
| `sys_purchase_invoice_items` | Purchase invoice items | Missing direct `company_id` |
| `sys_adjustment_notes` | Adjustments | Missing `company_id` |
| `sys_receipts` | Receipts | Has `company_id` usage in controller; verify non-null/index |
| `sys_payments` | Payments | Missing/partial `company_id`; linked to bank/accounting references |
| `sys_refunds` | Refunds | Missing/partial `company_id` |
| `sys_bank_accounts` | Bank accounts | Missing tenant ownership after owner columns were removed; should be company-owned |
| `gl_accounts` | Chart of accounts | Missing `company_id` |
| `gl_journals` | Journal header | Missing direct `company_id` |
| `gl_journal_lines` | Journal lines | Has nullable `company_id` |
| `gl_account_balances` | Balances | Missing `company_id` |
| `gl_period_closes` | Period close state | Missing `company_id` |
| `gl_audit_logs` | Accounting audit | Missing `company_id` |
| `bank_reconciliations`, `bank_reconciliation_lines` | Reconciliation | Missing `company_id` on reconciliation header |
| `fixed_assets` | Fixed assets | Missing `company_id` |

## 2. Authentication Flow

### Public Web Auth

Defined in `routes/web.php`:

- `GET /login` -> `App\Http\Controllers\Auth\AuthController@showLoginForm`
- `POST /login` -> `AuthController@login`
- `POST /logout` -> `AuthController@logout`
- password reset routes under `/password/...`
- public registration routes under `/register...`
- authenticated customer statement route: `/customer/statements`
- authenticated contractor portal routes: `/contractor/repairs`
- signed quote routes: `/repair-quotes/{assignment}/{token}`

### Backend Auth

Defined in `routes/backend.php`:

- `GET /login` -> `Backend\AuthenticateController@index`
- `POST /login` -> `Backend\AuthenticateController@login`
- `GET /logout` -> `Backend\AuthenticateController@logout`
- almost all backend CRM routes sit inside `Route::middleware('auth')->group(...)`

### Login Redirect Behaviour

Backend login logic checks roles such as `Tenant` and routes tenants to `backend.home`; other users generally go to the dashboard. Public frontend logic also uses legacy `role_id` values to redirect.

### Authentication Weaknesses for SaaS

- There are two login controllers: public `AuthController` and backend `Backend\AuthenticateController`.
- The codebase still references legacy `users.role_id` in frontend redirects.
- The active authorization model is Spatie roles, but old `App\Models\Role` and `role_id` remain.
- Login does not consistently enforce `users.status`, `users.can_login`, email/phone verification, company membership status, or subscription status.
- `User::booted()` assigns a default password of `123456` if a user is created without a password.
- Registration/approval sends generated credentials rather than secure one-time activation links.
- The password reset flow appears manually implemented and should be reviewed against Laravel broker expiry/rate-limit behaviour.

## 3. User Roles

### Roles Seeded by Spatie Seeder

`database/seeders/RoleAndPermissionSeeder.php` defines:

- `Super Admin`
- `Owner`
- `Property Manager`
- `Tenant`
- `Landlord`
- `Estate Agent`
- `Agent`
- `Contractor`
- `Maintenance`
- `Service Provider`
- `User`
- `Letting Applicant`
- `Sales Applicant`
- `Solicitor`
- `Other`
- `Staff`

### Legacy Roles

`database/seeders/RoleSeeder.php` defines lower-case legacy roles:

- `super_admin`
- `landlord`
- `estate_agent`
- `staff`
- `tenant`
- `user`

### Role Model Conflict

There are two concepts:

- `App\Models\Role` with `users.role_id`
- Spatie `Spatie\Permission\Models\Role` through `model_has_roles`

`User` uses Spatie `HasRoles` and manually defines a `roles()` relationship to Spatie roles, so the Spatie path is the active path for most current code. Legacy `role_id` still appears in migrations, frontend redirect logic and seeders.

### SaaS Role Problem

Roles are global on the user. That cannot model:

- one person as landlord admin in their own workspace,
- the same person as owner portal contact in an estate-agent workspace,
- a contractor working for multiple agencies,
- staff with branch-scoped access,
- platform support/super-admin access separate from customer workspace access.

Required SaaS direction: add workspace/company memberships and apply roles/permissions per membership, or enable Spatie teams with a company/team key and migrate all assignments.

## 4. Permission System

### Current Permission Stack

- Spatie Laravel Permission is installed and configured in `config/permission.php`.
- `teams` is set to `false`.
- `User` uses `Spatie\Permission\Traits\HasRoles`.
- Staff can inherit permissions from `Designation` via `designation_has_permissions`.
- Staff can override permissions with direct permissions when `staff.permissions_customized = true`.
- The sidebar uses many Blade `@can`, `@canany`, `@hasanyrole`, `hasRole()` checks.
- Some controllers use permission middleware:
  - `StaffController`
  - `RoleController`
  - `EmailTemplateController`
  - `DashboardController` uses `$this->authorize('view dashboard')`

### Permission Enforcement Gaps

Many backend routes are protected only by `auth`. Menu hiding is not server-side authorization. Examples of route groups without consistent explicit permission middleware/policies:

- properties
- users/contacts
- tenancies
- repairs
- work orders
- invoices
- notes/documents
- compliance
- events/calendar
- owner groups
- estate charges
- legacy and new accounting routes
- website/business/OTP/SMS settings

### Permission Naming Inconsistency

Permissions use mixed naming styles:

- `view dashboard`
- `manage_email_templates`
- `Manage Document Types`
- `manage document types`
- `view all staffs`
- `view properties`

This will complicate automated enforcement and SaaS plan entitlements.

## 5. Middleware

Application middleware files include:

- `Authenticate`
- `backendAuthenticate`
- `RedirectIfAuthenticated`
- `RoleMiddleware`
- `EnsureTokenIsValid`
- `StoreIpInSession`
- `VerifyCsrfToken`
- `ValidateSignature`
- `EncryptCookies`
- `TrustHosts`
- `TrustProxies`
- `TrimStrings`
- `PreventRequestsDuringMaintenance`

### Notable Middleware Issues

- `backendAuthenticate` checks `auth()->user()->role_id == 1`, which is legacy and conflicts with Spatie roles.
- `RoleMiddleware` checks `auth()->user()->role->name`, but `User::role()` relationship is commented out in `User.php`; this middleware is not aligned with Spatie.
- No tenant-resolution middleware exists.
- No workspace membership middleware exists.
- No subscription/entitlement middleware exists.
- No consistent verified/status/can-login middleware is visible for product routes.
- Signed URL middleware is used for repair quote links, but token storage/expiry/revocation should be improved.

## 6. Models and Relationships

### Core Identity and Organisation

- `User`
  - Spatie roles, company, ownedCompany, createdUsers, branch, designation, staff, category, details, tenancies, repairIssues, tenantMembers, bankDetails, bankAccounts, notes, documents, events, transactions.
  - Uses `TracksUser`.
  - Dangerous default password fallback.
- `Company`
  - owner, branches, users, ownerTransfers.
- `Branch`
  - company, users, staff.
- `Staff`
  - user, parent, branch, contacts, emails, phones.
- `Designation`
  - users, permissions.

### Property and Tenancy

- `Property`
  - Rich property fields; relationships include responsibilities, owner groups, tenancies, offers, compliance, repairs, notes/documents/events in surrounding code.
- `PropertyResponsibility`
  - property, user, branch, designation.
- `OwnerGroup`
  - property and owner group/member relationships.
- `OwnerGroupUser`
  - owner group members.
- `Tenancy`
  - property, offer, tenantMembers, tenancySubStatus, tenancyType, propertyManagers.
- `TenantMember`
  - tenancy, user.
- `PropertyManagerTenancy`
  - tenancy, propertyManager, property.

### Repair and Work Orders

- `RepairIssue`
  - property, repairCategory, repairPhotos, repairAssignments, repairIssuePropertyManagers, repairIssueContractorAssignments, repairHistories, repairIssueUsers, finalContractor, tenant, workOrder, invoice, events.
- `RepairIssueContractorAssignment`
  - repairIssue, contractor, assignedBy.
  - Contractor relation filters by role/category.
- `RepairIssuePropertyManager`
  - repairIssue, propertyManager.
- `RepairPhoto`
  - repairIssue.
- `RepairAssignment`
  - repairIssue, assignedTo.
- `RepairHistory`
  - repairIssue.
- `WorkOrder`
  - repairIssue, items, contractor/final contractor through repair issue.
- `WorkOrderItem`
  - workOrder.

### Documents, Notes, Events

- `Document`
  - upload, documentType, polymorphic `documentable`.
- `Notes`
  - polymorphic `noteable`.
- `Event`
  - type, children, parent, instances, changes, reminders, properties, repairIssues, users, diaryOwner, onBehalfOf.
- `EventInstance`
  - event, changes, reminders.
- `EventReminder`
  - instance/event.
- `EventType`
  - events, subTypes.
- `EventSubType`
  - type.

### Finance and Accounting

- Legacy:
  - `Invoice` -> workOrder, items, payments, status, user.
  - `InvoiceItems` -> invoice.
  - `Transaction` -> user/category/invoice style links.
  - `PurchaseInvoice` -> items, supplier, note applications.
  - `CreditNote`, `DebitNote` -> party, applications, refunds.
- Newer:
  - `SysSaleInvoice` -> receipts, payments, user, invoiceHeader, linkTo, chargeTo, bankAccount, journals, items.
  - `SysReceipt` -> receiptable, user, journal, customer credit scope.
  - `SysPayment` -> reference, user, bankAccount, paymentMethod, journal, sourceReceipt.
  - `SysPurchaseInvoice` -> receipts, payments, user.
  - `GlJournal` -> lines, reversal, reversedFrom.
  - `GlJournalLine` -> journal, account, company, user.
  - `GlAccount` -> parent, children, balances.
  - `SysBankAccount` -> glAccount.
  - `BankReconciliation` -> bankAccount, reconciledByUser, lines.

## 7. Controllers

### Public/Auth Controllers

- `Auth\AuthController`: public login/logout.
- `Auth\RegistrationController`: public OTP registration.
- `Auth\PasswordResetController`: password reset.
- `Frontend\FrontendController`: home/pricing and auth redirect logic.
- `Frontend\FormController`: public form submission.
- `Frontend\CustomerStatementController`: logged-in customer statement.
- `Frontend\ContractorPortalController`: contractor repairs list/detail scoped by `final_contractor_id`.
- `Frontend\RepairQuoteController`: signed quote link show/submit.

### Backend CRM Controllers

- `DashboardController`
- `AuthenticateController`
- `UserController`
- `RegistrationController`
- `CompanyController`
- `BranchController`
- `StaffController`
- `RoleController`
- `DesignationController`
- `PropertyController`
- `PropertyResponsibilityController`
- `OwnerGroupController`
- `TenancyController`
- `OfferController`
- `ComplianceController`
- `PropertyRepairController`
- `WorkOrderController`
- `InvoiceController`
- `DocumentsController`
- `NotesController`
- `EventController`
- `EventTypeController`
- `EventSubTypeController`
- setup/master controllers such as `NoteTypeController`, `DocumentTypeController`, `TenancyTypeController`, `TenancySubStatusController`, `JobTypeController`, `UserCategoryController`, `EstateChargeController`, `EstateChargeItemController`, `TransactionCategoryController`.

### Backend Accounting Controllers

- `Accounting\BaseCrudController`
- `Accounting\Masters\BankController`
- `Accounting\Masters\PaymentMethodController`
- `Accounting\Masters\IncomeCategoryController`
- `Accounting\Masters\ExpenseCategoryController`
- `Accounting\Masters\SysInvoiceHeaderController`
- `Accounting\Masters\TaxController`
- `Accounting\Sale\SaleInvoiceController`
- `Accounting\Sale\CreditNoteController`
- `Accounting\Purchase\PurchaseInvoiceController`
- `Accounting\Purchase\DebitNoteController`
- `Accounting\Receipts\ReceiptController`
- `Accounting\Payments\PaymentController`
- `Accounting\GlAccountController`
- `Accounting\GlJournalController`
- `Accounting\GlJournalLineController`
- `Accounting\GlAccountBalanceController`
- `Accounting\ReportController`
- `Accounting\StatementController`
- `Accounting\BankReconciliationController`
- `Accounting\FixedAssetController`
- `Accounting\UnchargedRepairWorkOrderController`

### Controller-Level SaaS Risks

- Many methods accept numeric IDs and call `find()`, `findOrFail()`, route model binding or `all()` without a company predicate.
- `PropertyController` uses custom role logic and `created_by` assumptions.
- `DashboardController` counts records by `created_by`, not tenant ownership.
- `UserController` lists/searches users globally except for a tenant self-only branch.
- `SaleInvoiceController` uses many global lookups for users, properties, tenancies, bank accounts, payment methods, invoice headers and GL accounts.
- `ReceiptController` partially scopes by `company_id` but defaults to `auth()->user()->company_id ?? 1` in places.
- `AizUploadController` has super-admin/seller style logic and global upload operations such as `Upload::all()` and `truncate()`.
- `helpers.php` contains global property/user lookup helpers and cache keys without company context.

## 8. Current Business Flow

### Public Signup and Approval

1. Visitor opens `/register`.
2. Registration accepts type such as landlord, owner, freelancing agent, contractor and estate agent.
3. OTP verification occurs by email or phone.
4. Backend registration approval creates/links a `User`, assigns Spatie role, and may create a `Company` for estate agents.
5. Credentials/templates are sent by email/SMS.

SaaS gap: no plan selection, Stripe subscription, workspace membership creation for all customer types, or entitlement activation.

### Backend Login and Dashboard

1. User logs in through public or backend login.
2. Tenant role redirects to tenant home.
3. Other roles go to dashboard.
4. Sidebar visibility is controlled by role/permission checks.
5. Most backend module access is still under generic `auth`.

SaaS gap: no current workspace selector/context and no membership-scoped authorization.

### Property Management

1. Staff/admin creates contacts/users.
2. Property is created with rich UK property details, media, charges, features and assignments.
3. Owners/owner groups, responsibilities, compliance, offers, notes/documents and tenancy tabs attach to the property.
4. Property access is partly role-based and partly based on creator/tenant membership.

SaaS gap: property ownership should be company/workspace, not creator or selected properties JSON.

### Tenancy Flow

1. Tenancy is created under a property.
2. Tenant members are linked to users.
3. Property managers may be linked through `property_manager_tenancy`.
4. Tenant portal/home can show limited access.
5. Rent ledger/statement uses accounting services.

SaaS gap: tenancy and tenant-member tables need company scope and portal access must derive from active membership plus tenancy relationship.

### Repair and Contractor Flow

1. Repair issue is raised against a property, category and tenant/contact.
2. Property managers and contractors can be assigned.
3. Quote requests can be emailed with signed links.
4. Contractor quote details are submitted.
5. A final contractor can be selected.
6. Work order can be generated and emailed/PDFed.
7. Invoices can be generated from work orders.

SaaS gap: repair/work-order/quote records need company scope, assignment visibility, token hashing/expiry, and contractor membership/portal scoping.

### Accounting Flow

1. Legacy work-order invoices exist.
2. Newer `sys_*` sale invoices support lifecycle, PDF, recurring invoices, reminders, penalties, payments/receipts and GL posting.
3. Payments and receipts interact with bank accounts and journals.
4. Reports include statements, trial balance, P&L, balance sheet, AR/AP aging and reconciliation.

SaaS gap: choose one canonical accounting path, then tenant-scope every master/header/line/report query.

## 9. Modules

Current modules:

- Public website and pricing page
- Authentication and password reset
- OTP public registration and admin approval
- Dashboard
- Companies and ownership transfer
- Branches
- Staff
- Designations and staff permissions
- Roles and permissions
- Users/contacts/categories
- Properties
- Owner groups
- Offers
- Tenancies and tenant members
- Compliance
- Notes
- Documents and uploads
- Repairs/maintenance
- Contractor quote workflow
- Work orders
- Legacy invoices
- Legacy transactions/purchase invoices/notes/refunds
- New accounting masters
- New sale/purchase invoices
- Receipts/payments/refunds
- GL accounts/journals/balances/period close/audit
- Reports/statements/reconciliation/fixed assets
- Calendar/events/reminders
- Email/SMS/OTP templates and settings
- Business/website settings
- Public/customer/contractor portals

## 10. Hardcoded Assumptions Preventing Multi-Tenancy

| Assumption | Evidence | SaaS impact |
|---|---|---|
| One user has one company | `users.company_id`, `users.branch_id`; controllers use `auth()->user()->company_id` | Cannot support multi-workspace identity |
| Roles are global | Spatie `teams=false`; `model_has_roles` has no workspace key | Same person cannot safely have different roles per company |
| Creator is owner | `DashboardController` counts by `created_by`; `PropertyController` filters by `created_by` | Creator is audit metadata, not tenant ownership |
| Super Admin/Property Manager can see broad data | `PropertyController` role logic allows broad lists | Needs explicit platform vs workspace boundary |
| Tenant access can be special-cased | Tenant-only branches in `UserController`, `PropertyController`, `PropertyRepairController` | Does not generalize to owner/contractor/multi-workspace access |
| Property selection JSON controls access | `selected_properties` and `JSON_CONTAINS` helpers | JSON relationship is hard to constrain, audit and tenant-scope |
| Global master data is safe | settings/templates/types/categories globally queried | Some should be platform-level, others workspace-level |
| Global cache keys are safe | `business_settings`, `uploaded_asset_{id}`, `users_{id}`, `tenants_{propertyId}` | Cross-tenant cache leakage after SaaS conversion |
| Default company fallback is safe | `company_id ?? 1` in accounting receipt/sale invoice logic | Data can attach to wrong tenant |
| Numeric IDs are safe in URLs | Many routes use `{id}` and `findOrFail()` | Cross-tenant IDOR risk |
| File uploader ownership is user/super-admin based | `AizUploadController` uses `user_type` checks and global operations | File visibility must be company/resource-scoped |
| Public debug utilities can remain | `/storage-link`, `/helper`, `/test-sms`, `/clear-cache` | Operational/security risk in SaaS |

## 11. Tables Missing `account_id`

No first-class SaaS `accounts` table or `account_id` tenant key exists. The code appears to be moving toward `companies` as the tenant/workspace root, not `account_id`.

If the intended SaaS tenant key is literally `account_id`, then all customer-owned tables are currently missing it except none. If the intended tenant key is `company_id`, the tables below need company ownership.

### Customer-Owned Tables Missing `company_id`

High-priority:

- `properties`
- `users` should not be the only owner; add memberships rather than relying on `users.company_id`
- `user_details`
- `staff` direct company or company via branch/membership
- `property_responsibilities`
- `owner_group`
- `owner_group_users`
- `offers`
- `tenancies`
- `tenant_members`
- `property_manager_tenancy`
- `compliance_records`
- `compliance_details`
- `estate_charges`
- `estate_charges_items`
- `repair_issues`
- `repair_photos`
- `repair_assignments`
- `repair_histories`
- `repair_issue_users`
- `repair_issue_contractor_assignments`
- `repair_issue_property_managers`
- `work_orders`
- `work_order_items`
- `documents`
- `notes`
- `uploads`
- `events`
- `event_instances`
- `event_instance_changes`
- `event_reminders`
- `eventables`
- `notification_logs`
- `form_submissions`
- `invoices`
- `invoice_items`
- `transactions`
- `purchase_invoices`
- `purchase_invoice_items`
- `credit_notes`
- `debit_notes`
- `credit_note_refunds`
- `debit_note_refunds`
- `note_applications`
- `bank_details`
- `bank_accounts`

Accounting priority:

- `sys_taxes`
- `sys_income_categories`
- `sys_expense_categories`
- `sys_invoice_headers`
- `sys_sale_invoice_items`
- `sys_purchase_invoices`
- `sys_purchase_invoice_items`
- `sys_adjustment_notes`
- `sys_payments`
- `sys_refunds`
- `sys_bank_accounts`
- `gl_accounts`
- `gl_journals`
- `gl_account_balances`
- `gl_period_closes`
- `gl_audit_logs`
- `bank_reconciliations`
- `bank_reconciliation_lines`
- `fixed_assets`
- `document_sequences`

Tables that can remain global:

- `countries`
- `currencies`
- `nationalities`
- `local_authority_groups`
- `local_authorities`
- `station_names`
- `school_names`
- `religious_places`
- framework tables such as `jobs`, `cache`, `sessions`, with tenant-aware payload/key rules
- platform-wide permission templates, if membership assignment is workspace-scoped

## 12. Places Where Authenticated User Is Assumed to Own All Data

Examples:

- `DashboardController`: counts users, properties, invoices, work orders and repair issues by `created_by = auth user`.
- `PropertyController@index`: Property Manager/Super Admin broad access; other roles use `created_by` and users created by current user.
- `PropertyController@view/brochure`: custom role access rather than tenant-bound route model binding.
- `UserController@index`: tenants see only themselves; other roles can list contacts broadly with role filters.
- `UserController@searchProperties` and helper `searchProperties()`: property search is global.
- `PropertyRepairController`: tenant filtering exists, but non-tenant access is broad.
- `TenancyController`: property/tenancy access uses raw property/tenancy IDs.
- `NotesController` and `DocumentsController`: polymorphic records are loaded by ID without visible tenant checks.
- `BankDetailController`: bank details are operated on by `user_id` or raw ID without company/resource authorization.
- `AizUploadController`: upload listing is based on `user_type`/user ID and has global file operations.
- `SaleInvoiceController`: many global lists/searches for users, properties, tenancies, bank accounts and invoices.
- `ReceiptController`: partial company filter but defaults to current user's company or `1`.
- `helpers.php`: helpers fetch properties/users/tenants by ID and cache them globally.

## 13. Queries That Must Be Tenant Scoped

All list/show/create/update/delete/search/export/PDF/download/report queries over customer-owned data must be scoped by current workspace.

### Immediate Query Families to Scope

- `Property::query()`, `Property::where(...)`, `Property::find*()`, `Property::all()`
- `User::query()` for contacts/staff/portal users within a workspace
- `Branch::query()` except platform admin
- `Staff::query()`
- `Tenancy::query()`
- `TenantMember::query()`
- `OwnerGroup::query()`
- `Offer::query()`
- `PropertyResponsibility::query()`
- `ComplianceRecord::query()`
- `RepairIssue::query()`
- `RepairIssueContractorAssignment::query()`
- `WorkOrder::query()`
- `Invoice::query()` legacy
- `SysSaleInvoice::query()`
- `SysReceipt::query()`
- `SysPayment::query()`
- `SysPurchaseInvoice::query()`
- `GlAccount::query()`
- `GlJournal::query()`
- `GlJournalLine::query()`
- `Document::query()`
- `Notes::query()`
- `Upload::query()`
- `Event::query()`
- `EventInstance::query()`
- `NotificationLog::query()`
- all raw `DB::table(...)` operations against customer-owned tables

### Controllers With High Tenant-Scoping Priority

- `PropertyController`
- `UserController`
- `TenancyController`
- `PropertyRepairController`
- `WorkOrderController`
- `InvoiceController`
- `DocumentsController`
- `NotesController`
- `AizUploadController`
- `EventController`
- `ComplianceController`
- `OfferController`
- `OwnerGroupController`
- `BankDetailController`
- all `Backend\Accounting\*` controllers
- console commands for recurring invoices, reminders, penalties, future event instances and notification retries
- jobs sending notifications/quotes/final contractor emails

## 14. Duplicate Logic

| Duplicate area | Current duplication |
|---|---|
| Auth controllers | Public `AuthController` and backend `AuthenticateController` both handle login/redirect concerns |
| Roles | Legacy `users.role_id`/`App\Models\Role` and Spatie roles coexist |
| Role seeders | `RoleSeeder` and `RoleAndPermissionSeeder` define different role names/cases |
| Finance | Legacy `Invoice`/`Transaction`/`PurchaseInvoice` and newer `SysSaleInvoice`/`SysPayment`/GL modules overlap |
| Purchase invoices | `Backend\PurchaseInvoiceController` and `Backend\Accounting\Purchase\PurchaseInvoiceController` |
| Credit/debit notes | `AccountsNoteController`, `AccountsNoteApplicationController`, `Accounting\Sale\CreditNoteController`, `Accounting\Purchase\DebitNoteController` |
| Documents/notes AJAX components | Similar polymorphic store/show/list flows in notes/documents |
| Property search | `helpers.php`, `PropertyController`, `UserController`, `SaleInvoiceController` all implement property searching |
| Tenant/property lookup | `get_tenants_by_property`, controller methods and accounting context methods duplicate lookup behaviour |
| Permission UI vs enforcement | Sidebar Blade checks duplicated separately from route/controller enforcement |
| Work-order invoice generation | legacy invoice generation and newer accounting invoice context overlap |
| Email/template rendering | `EmailTemplate::replace`, `render_template`, `replaceVariables`, helper `get_email_template_data` |

## 15. Security Issues

Critical/high issues:

- Potential IDOR across most modules due to raw IDs and missing tenant-scoped route binding.
- Global roles can grant access across all customers.
- Missing server-side permission checks on many authenticated routes.
- Public `/storage-link` can run `Artisan::call('storage:link')`.
- Public `/test-sms` sends test SMS using configured providers.
- Public `/helper` view exists.
- Authenticated `/clear-cache` is available to any authenticated backend user unless further web-server controls exist.
- AIZ upload routes are not consistently wrapped in backend auth/authorization and include global operations.
- `AizUploadController@all_file` uses `Upload::all()`.
- `AizUploadController` includes `Upload::query()->truncate()` in a controller method path.
- `User::booted()` default password `123456`.
- Welcome emails include generated/plain credentials.
- Quote token is stored directly in `repair_issue_contractor_assignments`; should be hashed with expiry and revocation.
- Signed quote URLs should verify both signature and stored token/assignment state.
- `company_id ?? 1` fallback can attach accounting records to the wrong company.
- Cache keys omit tenant context.
- Business settings/templates are global and mutable through backend.
- File download/preview helpers rely on storage paths and upload IDs without tenant/resource policy.
- Password reset flow should be verified for expiry, throttling and token broker correctness.
- Stripe invoice payment flow must verify Checkout Session/server-side payment state and webhook idempotency before production use.
- Super Admin bypass/policy behaviour must be separated from ordinary workspace context and audited.

Medium issues:

- Permission names are inconsistent and may fail silently.
- Master data edit permissions are not consistently enforced.
- Console commands process global records without tenant context.
- Jobs load records by ID without company verification.
- Financial records can be edited/deleted through CRUD-style controllers; posted accounting should be voided/reversed.
- Logging helpers may log sensitive property/user details.
- Public IDs are numeric and guessable throughout admin routes.

## 16. Files That Will Require Modification for SaaS Conversion

### Routing and Middleware

- `routes/web.php`
- `routes/backend.php`
- `bootstrap/app.php`
- `app/Http/Middleware/Authenticate.php`
- `app/Http/Middleware/backendAuthenticate.php`
- `app/Http/Middleware/RoleMiddleware.php`
- new tenant/workspace middleware
- new subscription/entitlement middleware
- new tenant-aware route binding provider or policy layer

### Auth and Registration

- `app/Http/Controllers/Auth/AuthController.php`
- `app/Http/Controllers/Auth/RegistrationController.php`
- `app/Http/Controllers/Auth/PasswordResetController.php`
- `app/Http/Controllers/Backend/AuthenticateController.php`
- `app/Http/Controllers/Backend/RegistrationController.php`
- `app/Models/User.php`
- registration and login Blade views under `resources/views/frontend` and `resources/views/backend/login.blade.php`

### Authorization and Roles

- `config/permission.php`
- `app/Providers/AppServiceProvider.php` or equivalent gate registration file if present
- `app/Models/User.php`
- `app/Models/Role.php`
- `app/Models/Permission.php`
- `app/Models/Designation.php`
- `app/Models/Staff.php`
- `database/seeders/RoleSeeder.php`
- `database/seeders/RoleAndPermissionSeeder.php`
- `database/seeders/TenantPermissionsSeeder.php`
- sidebar and permission views under `resources/views/backend/partials/aside2.blade.php`, registration permission screens and role/staff/designation views

### Tenant Foundation

Modify/add migrations and models for:

- `companies`
- `branches`
- `users`
- `company_memberships`
- `membership_roles` or Spatie team pivots
- `membership_branches`
- `portal_invitations`
- SaaS plan/price/subscription/entitlement tables

Existing files:

- `app/Models/Company.php`
- `app/Models/Branch.php`
- `app/Models/CompanyOwnerTransfer.php`
- `app/Http/Controllers/Backend/CompanyController.php`
- `app/Http/Controllers/Backend/BranchController.php`
- `app/Http/Controllers/Backend/StaffController.php`

### Property and Tenancy

- `app/Models/Property.php`
- `app/Models/PropertyResponsibility.php`
- `app/Models/OwnerGroup.php`
- `app/Models/OwnerGroupUser.php`
- `app/Models/Offer.php`
- `app/Models/Tenancy.php`
- `app/Models/TenantMember.php`
- `app/Models/PropertyManagerTenancy.php`
- `app/Http/Controllers/Backend/PropertyController.php`
- `app/Http/Controllers/Backend/PropertyResponsibilityController.php`
- `app/Http/Controllers/Backend/OwnerGroupController.php`
- `app/Http/Controllers/Backend/OfferController.php`
- `app/Http/Controllers/Backend/TenancyController.php`
- property/tenancy views under `resources/views/backend/properties`, `resources/views/backend/users`, `resources/views/backend/tenancies`

### Repairs, Contractors and Work Orders

- `app/Models/RepairIssue.php`
- `app/Models/RepairPhoto.php`
- `app/Models/RepairAssignment.php`
- `app/Models/RepairHistory.php`
- `app/Models/RepairIssueUser.php`
- `app/Models/RepairIssueContractorAssignment.php`
- `app/Models/RepairIssuePropertyManager.php`
- `app/Models/WorkOrder.php`
- `app/Models/WorkOrderItem.php`
- `app/Http/Controllers/Backend/PropertyRepairController.php`
- `app/Http/Controllers/Backend/WorkOrderController.php`
- `app/Http/Controllers/Frontend/ContractorPortalController.php`
- `app/Http/Controllers/Frontend/RepairQuoteController.php`
- repair/work-order views under `resources/views/backend/repair`, `resources/views/backend/work_orders`, `resources/views/frontend/contractor`, `resources/views/frontend/repair_quotes`
- jobs `SendRepairQuoteRequestEmail`, `SendFinalContractorAssignedEmail`

### Documents, Notes, Uploads and Files

- `app/Models/Upload.php`
- `app/Models/Document.php`
- `app/Models/DocumentType.php`
- `app/Models/Notes.php`
- `app/Models/NoteType.php`
- `app/Http/Controllers/Backend/AizUploadController.php`
- `app/Http/Controllers/Backend/DocumentsController.php`
- `app/Http/Controllers/Backend/NotesController.php`
- `public/asset/backend/js/common-documents.js`
- `public/asset/backend/js/common-notes.js`
- document/note/upload views and components

### Calendar and Notifications

- `app/Models/Event.php`
- `app/Models/EventInstance.php`
- `app/Models/EventInstanceChange.php`
- `app/Models/EventReminder.php`
- `app/Models/EventType.php`
- `app/Models/EventSubType.php`
- `app/Models/NotificationLog.php`
- `app/Http/Controllers/Backend/EventController.php`
- `app/Console/Commands/GenerateRecurringEvents.php`
- `app/Console/Commands/GenerateFutureInstances.php`
- `app/Console/Commands/SendEventReminders.php`
- `app/Console/Commands/RetryNotificationsCommand.php`
- `app/Jobs/SendNotificationJob.php`

### Accounting and Finance

Legacy module:

- `app/Models/Invoice.php`
- `app/Models/InvoiceItems.php`
- `app/Models/InvoiceStatuses.php`
- `app/Models/Transaction.php`
- `app/Models/TransactionCategory.php`
- `app/Models/PurchaseInvoice.php`
- `app/Models/PurchaseInvoiceItem.php`
- `app/Models/CreditNote.php`
- `app/Models/DebitNote.php`
- `app/Models/NoteApplication.php`
- `app/Http/Controllers/Backend/InvoiceController.php`
- `app/Http/Controllers/Backend/TransactionController.php`
- `app/Http/Controllers/Backend/PurchaseInvoiceController.php`
- `app/Http/Controllers/Backend/AccountsNoteController.php`
- `app/Http/Controllers/Backend/AccountsNoteApplicationController.php`

New accounting module:

- `app/Models/SysSaleInvoice.php`
- `app/Models/SysSaleInvoiceItem.php`
- `app/Models/SysPurchaseInvoice.php`
- `app/Models/SysPurchaseInvoiceItem.php`
- `app/Models/SysReceipt.php`
- `app/Models/SysPayment.php`
- `app/Models/SysRefund.php`
- `app/Models/SysBankAccount.php`
- `app/Models/SysInvoiceHeader.php`
- `app/Models/SysTax.php`
- `app/Models/SysIncomeCategory.php`
- `app/Models/SysExpenseCategory.php`
- `app/Models/GlAccount.php`
- `app/Models/GlJournal.php`
- `app/Models/GlJournalLine.php`
- `app/Models/GlAccountBalance.php`
- `app/Models/GlPeriodClose.php`
- `app/Models/GlAuditLog.php`
- all controllers under `app/Http/Controllers/Backend/Accounting`
- all services under `app/Services/Accounting` if present
- recurring invoice/reminder/penalty console commands

### Helpers, Settings and Shared Views

- `app/helpers.php`
- `app/Mail/*`
- `app/Contracts/SendSms.php`
- `app/Http/Controllers/Backend/BusinessSettingsController.php`
- `app/Http/Controllers/Backend/EmailTemplateController.php`
- `app/Http/Controllers/Backend/SmsTemplateController.php`
- `app/Http/Controllers/Backend/OTPController.php`
- `resources/views/backend/partials/aside2.blade.php`
- all module list/search partials that expose unscoped data

### Tests Required

The current tests are starter examples. SaaS conversion needs:

- tenant isolation tests for every module
- route authorization tests for every backend action
- membership-scoped role tests
- file download/upload authorization tests
- signed quote link expiry/revocation tests
- accounting company-scope and report tests
- job/command tenant-context tests
- registration/onboarding/subscription tests
- Stripe webhook idempotency/signature tests

## Recommended SaaS Conversion Order

1. Freeze current schema and make a clean migration baseline pass.
2. Decide tenant key: `company_id` is the natural candidate; do not introduce separate `account_id` unless product vocabulary requires it.
3. Add universal workspace model: every landlord/agency gets a `companies` row.
4. Add memberships and workspace-scoped roles.
5. Add tenant middleware/context and tenant-aware route binding.
6. Add nullable `company_id` to customer-owned tables.
7. Backfill properties first, then descendants from verified parent records.
8. Switch writes to set `company_id` from tenant context.
9. Add scoped policies and controller authorization.
10. Scope all list/search/show/update/delete/download/report queries.
11. Make `company_id` non-null where customer-owned.
12. Consolidate accounting to one canonical path.
13. Add SaaS plans, subscriptions, Stripe webhooks and entitlements.
14. Remove legacy role authority, selected-properties JSON access, public debug routes and default password behaviour.
15. Add isolation tests before onboarding a second customer.

