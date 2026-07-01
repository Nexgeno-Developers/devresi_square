# Existing Resisquare CRM code analysis

**Review date:** 20 June 2026  
**Purpose:** establish what the current CRM already provides, what can be reused, and what must change before it can safely become a multi-tenant SaaS product.

## Executive assessment

The repository is a substantial Laravel property-management CRM. It already models companies, branches, staff, contacts, properties, tenancies, maintenance, contractor quotes, work orders, documents, calendar events, invoices, payments, statements, and general-ledger accounting. This is useful domain code and is a strong reference for a Resisquare prototype.

It is **not yet a production SaaS platform**. The main blockers are:

1. Tenant separation is based mainly on `created_by`, role checks, and partial `company_id` use. Core records such as properties, tenancies, repairs, and documents are not consistently keyed to a tenant company.
2. There are no plan, subscription, entitlement, add-on, webhook, or SaaS billing tables.
3. The existing Stripe flow is a test-mode, one-off sale-invoice payment flow. It does not create or manage subscriptions.
4. Permission checks are extensive in the sidebar but sparse at the route/controller boundary. Hiding a menu item is not access control.
5. The migration ledger is not clean: several migrations are pending in the reviewed environment even though related code is active. A fresh-schema rehearsal is required before further product development.

The correct reuse strategy is to retain the Laravel domain concepts and selected services, introduce a real tenant boundary, consolidate access control and accounting, and then add SaaS subscriptions.

## Review basis

The review covered:

- `composer.json`, `package.json`, Laravel bootstrap and configuration
- all route files and the runtime route list
- authentication, registration, password reset, middleware, Gates and policies
- controllers, models, services, jobs and commands
- migrations, seeders and the current migration status
- backend, frontend, portal and shared component views
- property, tenancy, repair, contractor, document and accounting flows
- Stripe references and pricing screens
- the test suite and existing project documentation

Runtime inspection reported **486 registered routes**: 133 accounting routes, 20 property routes, 20 repair routes, 8 global tenancy routes, 18 user/contact routes, 237 other admin routes, and 50 public/portal routes. There is no `routes/api.php`.

## Technology stack

| Layer | Current implementation | Reuse assessment |
|---|---|---|
| Backend | PHP 8.2+, Laravel 11 | Retain |
| UI | Server-rendered Blade, Bootstrap-style components/modals, jQuery/AJAX | Reuse domain behaviour; a Lovable prototype can use a modern component UI |
| Build tooling | Vite 5, Tailwind CSS 3, PostCSS, Axios | Retain where useful |
| ORM/database | Eloquent and Laravel migrations; configured environment is database-backed | Retain after schema baseline and tenant-key work |
| Authentication | Laravel session guard and `User` provider | Retain, then consolidate login and add verification/status enforcement |
| Authorisation | Spatie Laravel Permission 6, Laravel Gates, three policies, designation permissions | Retain package; redesign enforcement and tenant-scoped memberships |
| Audit | OwenIt Laravel Auditing and accounting audit records | Reuse and expand |
| PDFs | mPDF and `laravel-pdf` | Reuse for invoices, work orders and brochures |
| Recurrence | `rlanvin/php-rrule` | Reuse for calendar events |
| Payments | Stripe PHP package plus direct cURL Checkout call | Reuse SDK/config only; replace flow for subscriptions |
| Messaging | Laravel Mail, queued jobs, Twilio SDK and multiple SMS adapters | Reuse after configuration/security hardening |
| Queues/schedule | Database queues, jobs, Laravel scheduler | Reuse with production workers and monitoring |
| Tests | PHPUnit 11 | Framework retained, but current tests are only starter examples |

## Project structure

| Path | Responsibility |
|---|---|
| `routes/web.php` | Public pages, login, OTP registration, password reset, customer statements, contractor portal, signed quote links, forms and upload utilities |
| `routes/backend.php` | Nearly all `/admin` CRM and accounting routes |
| `bootstrap/app.php` | Middleware aliases and invoice/notification schedules |
| `app/Http/Controllers/Auth` | Public login, OTP registration and password reset |
| `app/Http/Controllers/Backend` | CRM, setup, organisation, repairs, legacy invoices and accounting |
| `app/Http/Controllers/Frontend` | Public website, customer statements, contractor portal and quote submission |
| `app/Models` | CRM, people, property, maintenance, accounting and configuration entities |
| `app/Services/Accounting` | Posting, invoice lifecycle, penalties, reports and statements |
| `app/Services/Notifications` | Template-driven queued notification flow |
| `app/Jobs` | Notifications, repair quote email and final contractor email |
| `app/Console/Commands` | Recurring invoices, reminders, penalties, retries, events and smoke tests |
| `resources/views/backend` | Main CRM/admin UI and module partials |
| `resources/views/frontend` | Website, registration, contractor and quote screens |
| `database/migrations` | Historical schema and newer organisation/accounting additions |
| `database/seeders` | Roles, permissions, master data, templates and accounting defaults |

## Existing modules

| Module | Existing capability | Recommended reuse |
|---|---|---|
| Public website | Home, pricing, registration and contact/demo forms | Rework visuals and connect pricing to real plan records |
| Authentication | Login/logout, remember me, forgot/reset password | Consolidate duplicate public/backend controllers; enforce account status and verification |
| Registration | Landlord, owner, estate-agent and contractor application; email/phone OTP; admin approval | Reuse OTP and approval concepts; insert plan selection and Stripe Checkout before activation |
| Companies | Company profile, estate-agent owner, ownership transfer history | Make `companies` the universal SaaS workspace for agencies and landlord portfolios |
| Branches | Company-scoped branches, head office, addresses and contact/social fields | Reuse for Estate Agent plans only; enforce plan limits |
| Staff/designations | Staff user, branch, designation permissions and direct overrides | Reuse; replace global roles with company-membership scope |
| Contacts/users | Central user/contact record, categories, profile, bank details and shared notes/documents | Reuse concept; separate contact identity, login invitation and workspace membership states |
| Properties | Detailed UK property attributes, media, local authorities, brochures, owners, compliance, offers, teams, notes and statements | High-value reuse after adding tenant key and consistent relationship tables |
| Owner groups | Owner groups, members and main owner | Reuse or simplify into property-party ownership records |
| Tenancies | Property tenancy, members, main tenant, rent/deposit, status, property managers and rent ledger | Reuse after England 2026 tenancy-state review and tenant scoping |
| Compliance | EPC, EICR, gas and other compliance records with issue/expiry dates | Reuse and add reminder/requirement configuration by jurisdiction |
| Maintenance | Repair issues, categories, photos, history, property managers and status tracking | Reuse as `maintenance_requests` product module |
| Contractor workflow | Quote requests, signed submission link, availability, final contractor, contractor portal | Strong reuse; add assigned-job updates, quote/invoice/document permissions |
| Work orders | Work-order header/lines, PDF and email | Reuse |
| Documents/notes | Polymorphic documents and notes with shared AJAX components | Reuse after tenant key, visibility and secure-download controls |
| Calendar/events | Recurrence, instances, changes, reminders and linked entities | Reuse; schedule event commands and tenant-scope records |
| Communications | Email/SMS templates, notification logs and queued retries | Reuse; add conversations/messages and tenant-visible history |
| Legacy finance | Repair invoices, purchase invoices, transactions, categories, credits/debits | Do not expand until consolidated with `sys_*` accounting |
| New accounting | Sale/purchase invoices, receipts, payments, tax, bank accounts, GL, reports, statements, reconciliation, fixed assets | Reuse selectively; add mandatory tenant keys and permissions |
| Stripe invoice payment | One-off test Checkout and automatic GL payment posting | Reference only; do not use as subscription implementation |
| Settings | Business, SMTP, website, OTP, SMS/email templates and master data | Split platform-level settings from workspace settings |

## Authentication and registration findings

### What exists

- One `web` session guard using `App\Models\User`.
- Public login in `AuthController` and a second backend login in `Backend\AuthenticateController`.
- Role-based redirects for Super Admin, Owner, Property Manager, Landlord, Estate Agent/Agent, Staff, Tenant and Contractor.
- OTP registration by email or phone with a two-minute expiry.
- Registration types: landlord, owner, estate agent and contractor.
- Admin approval creates a user, assigns a role and sends generated credentials. Estate-agent approval also creates a company.
- Forgot/reset-password pages and database reset tokens.

### Gaps and risks

- Login uses email/password only; it does not include `status = 1`, `can_login = 1`, subscription status, or workspace membership status in the authentication decision.
- `email_verified_at` exists, but no verified middleware protects product access.
- Tenant and contractor landings are minimal rather than complete role dashboards.
- Registration approval is global-admin driven and not connected to a paid plan.
- Registration creates estate-agent companies but not an equivalent landlord workspace.
- The manual reset implementation scans token hashes and does not enforce the configured token expiry. Use Laravel's password broker directly.
- `User::booted()` can create a default password of `123456` when none is supplied. Production invitation flows must use a one-time activation link instead.

## Roles and permissions findings

### Roles found

The primary seeder includes Super Admin, Owner, Property Manager, Tenant, Landlord, Estate Agent, Agent, Contractor, Maintenance, Service Provider, User, applicant roles, Solicitor, Other and Staff. A second legacy role seeder uses lower-case names, so seeders must be consolidated.

### Permission model

- Spatie roles and permissions are attached to `User`.
- Super Admin bypasses Gates through `Gate::before`.
- Gates are generated dynamically for cached permissions.
- Staff normally inherit permissions from their `Designation`.
- A staff user with `permissions_customized = true` uses direct permissions.
- The sidebar contains many `@can` and `@canany` checks.
- Only a small set of controllers use permission middleware. Dashboard and a few endpoints use controller checks; many authenticated admin routes have no server-side permission check.
- Property, tenancy and user policies exist, but controllers mainly use custom role/ownership checks. Policy rules and live behaviour are not consistently aligned.

### SaaS implication

Roles must be scoped to a workspace membership, not globally to a user. The same person can be a contractor for multiple agencies, an owner in one workspace and a landlord subscriber in another. A global `model_has_roles` assignment cannot represent this safely without Spatie teams or an explicit membership-role model.

## Database inventory

This catalogue is based on migrations and Eloquent usage. The reviewed database has a mixed migration state, so it should not be interpreted as proof that every listed table exists consistently in every environment.

### Platform and access

`users`, `user_details`, `users_categories`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`, `designations`, `designation_has_permissions`, `staff`, `staff_contacts`, `registrations`, `sessions`, `password_reset_tokens`, `audits`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `uploads`, `business_settings`, `email_templates`, `sms_templates`, `otp_configurations`, `notification_logs`, `form_submissions`.

### Organisation and people

`companies`, `company_owner_transfers`, `branches`, `bank_details`, `bank_accounts`, `owner_group`, `owner_group_users`, `countries`, `currencies`, `nationalities`.

Owner, landlord, tenant, contractor and property-manager records are primarily users with roles; `Owner`, `Tenant` and `Contractor` models are wrappers over `users`, not separate tables.

### Property and tenancy

`properties`, `property_responsibilities`, `offers`, `tenancies`, `tenant_members`, `property_manager_tenancy`, `tenancy_types`, `tenancy_sub_statuses`, `compliance_types`, `compliance_records`, `compliance_details`, `estate_charges`, `estate_charges_items`, `local_authority_groups`, `local_authorities`, `station_names`, `school_names`, `religious_places`.

### Repair and contractor operations

`repair_categories`, `repair_issues`, `repair_photos`, `repair_assignments`, `repair_histories`, `repair_issue_users`, `repair_issue_contractor_assignments`, `repair_issue_property_managers`, `job_types`, `work_orders`, `work_order_items`, `invoices`, `invoice_items`, `invoice_statuses`.

### Shared records and calendar

`notes`, `note_types`, `documents`, `document_types`, `events`, `event_instances`, `event_instance_changes`, `event_reminders`, `eventables`.

### Finance and accounting

Legacy/general tables include `account_headers`, `transactions`, `transaction_categories`, `tax_rates`, `payment_methods`, `purchase_invoices`, `purchase_invoice_items`, `credit_notes`, `debit_notes`, refund tables, `note_applications` and `document_sequences`.

The newer accounting tables include `sys_taxes`, `sys_income_categories`, `sys_expense_categories`, `sys_invoice_headers`, `sys_sale_invoices`, `sys_sale_invoice_items`, `sys_purchase_invoices`, `sys_purchase_invoice_items`, `sys_adjustment_notes`, `sys_receipts`, `sys_payments`, `sys_refunds`, `sys_bank_accounts`, `gl_accounts`, `gl_journals`, `gl_journal_lines`, `gl_account_balances`, `gl_period_closes`, `gl_audit_logs`, `bank_reconciliations`, `bank_reconciliation_lines` and `fixed_assets`.

### Schema observations

- `companies` and `branches` have meaningful organisation fields, but `properties`, `tenancies`, `repair_issues`, `work_orders`, `documents` and most accounting headers do not consistently have `company_id`.
- `users.company_id` supports only one primary company and cannot safely model cross-workspace participation.
- `created_by` records authorship, not data ownership. It must not be used as the SaaS tenant boundary.
- Both legacy and newer invoice/payment structures remain active. Duplicate financial domains increase reporting and migration risk.
- Several identifiers such as invoice numbers are globally unique. In SaaS they should usually be unique per company, with composite indexes.
- The current environment reports multiple pending migrations, including parts of legacy invoices, documents, event instances, staff, notification logs and newer invoice scheduling. Establish a schema baseline before adding SaaS migrations.
- Early migration history contains ordering/duplication hazards. A clean database build must be rehearsed and repaired rather than assuming the historical files can be replayed unchanged.

## Existing invoice, payment and Stripe capability

The newer accounting module supports:

- draft, issued, partial, paid and cancelled sale invoices
- invoice items, taxes, discounts and PDF generation
- recurring invoice generation, reminders, overdue reminders and penalties
- purchase invoices, receipts, payments, credit/debit notes and advances
- customer, account, property and tenancy statements
- trial balance, profit and loss, balance sheet, AR ageing and AP ageing
- double-entry posting, reversals, period close, reconciliation and fixed assets

Stripe currently supports only a test one-off invoice payment:

1. An authenticated user posts an amount for a `SysSaleInvoice`.
2. The controller creates a Checkout Session in `payment` mode using cURL.
3. Stripe returns the browser to a signed success URL.
4. The success route records a payment and marks/partially marks the invoice paid.

There is no subscription mode, Stripe Customer mapping, Product/Price mapping, webhook endpoint, event-signature verification, idempotent event store, Customer Portal, proration handling, dunning or entitlement service. The success redirect also does not retrieve and verify the Checkout Session before recording the invoice payment, so it must not be copied into production SaaS billing.

## Code worth reusing

Prioritise these assets:

1. Property, tenancy and compliance models/forms as the domain reference.
2. Company, branch, staff and designation UI as the organisation reference.
3. Repair issue, quote, contractor assignment and work-order workflow.
4. Polymorphic notes/documents, after visibility and tenant security are added.
5. PDF generation for brochures, work orders and invoices.
6. Accounting posting, invoice lifecycle, statements and reports after tenant scoping.
7. Queued template notifications and retry logging.
8. OTP registration concepts, but not the generated/default password behaviour.
9. Audit infrastructure and created/updated tracking.

## Missing SaaS capabilities

| Area | Required addition |
|---|---|
| Tenant boundary | A mandatory workspace/company key, membership model, global/query scopes, route binding checks and tenant-aware jobs |
| Billing | Plans, prices, features, add-ons, subscriptions, subscription items, Stripe customers and webhook event store |
| Entitlements | Central feature/limit service for branches, staff, properties, storage and Property Manager add-on |
| Onboarding | Plan choice, company/portfolio creation, checkout, webhook activation and guided first-run checklist |
| Portals | Complete tenant, contractor and owner dashboards; landlord-contact view-only mode |
| Access | Server-side permission middleware/policies on every action and assigned-resource scoping |
| Invitations | Secure expiring invitations, membership acceptance and one user across multiple workspaces |
| Platform admin | SaaS customer, MRR/subscription, failed payment, plan/add-on and usage dashboards |
| Communications | Workspace-scoped conversations, messages, participants, delivery history and preferences |
| Files | Private object storage, signed downloads, visibility rules, quotas, malware scanning and retention |
| Product analytics | Onboarding, activation, feature use, churn and limit usage events |
| API | Versioned API only if needed by future mobile/integrations; none exists now |
| Quality | Domain tests, authorisation tests, webhook tests, tenancy-isolation tests and fresh-migration CI |

## Risk register

| Priority | Risk | Required response |
|---|---|---|
| Critical | Cross-customer data exposure from partial tenant scoping | Add tenant keys and isolation tests before onboarding a second paying customer |
| Critical | Authenticated routes often lack server-side permission enforcement | Define policy/middleware coverage and deny by default |
| Critical | Subscription state does not exist | Build webhook-driven billing and entitlement state; never trust redirect success alone |
| High | Some upload/file utility routes and debug/helper routes are public or weakly protected | Remove debug routes; require authentication, authorisation and ownership checks |
| High | `status` and `can_login` are not enforced by login | Block disabled/unapproved accounts in authentication middleware/provider |
| High | Migration drift and pending migrations | Create a tested baseline migration and reconcile all environments |
| High | Legacy and new accounting overlap | Select the canonical accounting path and migrate/deprecate the other |
| High | Global roles cannot express multi-workspace identities | Introduce membership-scoped roles/permissions |
| High | Property detail has a known Staff boolean-precedence access issue | Replace ad hoc checks with policies and regression tests |
| Medium | Tenancy status vocabulary differs (`Archive`/`Archived`) | Use constants/enums and data migration |
| Medium | Registration badge/list and public form field mismatches exist | Fix during onboarding redesign |
| Medium | Named repair queues require explicit workers | Configure supervised queue workers, retries and alerts |
| Medium | Event reminder commands are not scheduled | Schedule only after tenant-aware review |
| Medium | Only starter tests exist | Add focused coverage before refactoring |

## Recommended technical sequence

1. Freeze and document the canonical schema; make a fresh database build pass.
2. Add the workspace membership and tenant-key migration, then backfill existing records.
3. Enforce tenant resolution and server-side authorisation everywhere.
4. Consolidate roles, authentication and invitation/verification flows.
5. Choose the canonical accounting module and tenant-scope it.
6. Add plans, Stripe subscriptions, webhooks and entitlements.
7. Build the role dashboards and view-only portals.
8. Add isolation, billing, authorisation and workflow tests before launch.

