# Application Flow v2

Generated: 2026-06-18

This is the v2 handover and flow recheck for the Laravel application. It keeps
`docs/application-flow.md` intact and documents the current flow after reviewing
changes made in the last two months.

Recheck window:

- From: 2026-04-18
- To: 2026-06-18
- Current branch during recheck: `repair2`
- Current HEAD during recheck: `cb7f826`
- Previous flow doc was created in commit `9677056` on 2026-05-21.

Primary local sources checked:

- `docs/application-flow.md`
- `routes/web.php`
- `routes/backend.php`
- `resources/views/backend/partials/aside2.blade.php`
- Recent git history from 2026-04-18 onward
- Controllers, models, migrations, jobs, commands, and views referenced below
- `php artisan route:list --path=admin`
- `php artisan route:list --path=register`
- `php artisan route:list --path=contractor`
- `php artisan route:list --path=repair-quotes`

## 1. Two-Month Change Summary

Recent commits from 2026-05-05 through 2026-06-17 changed these major areas:

- Accounting invoice automation:
  recurring sale invoices, reminder emails, overdue reminders, penalties,
  notification logs, and notification retry.
- Tenant and tenancy flow:
  global tenancy listing, rent ledger, tenant welcome email templates, tenant
  permissions, active-tenancy property filtering, and tenant selected-property
  linking.
- Property flow:
  role-scoped property listing, property detail URL state, brochure PDF,
  property responsibility mapping, property description editing, and property
  tab fixes.
- Contacts/users:
  contact filtering, user detail flow, notes/documents/compliance fixes, profile
  views, company/branch/designation fields, and effective permission helpers.
- Public registration:
  OTP-based registration by email or phone, admin approval/rejection, welcome
  credentials email, OTP configuration, and SMS templates.
- Staff and organization:
  staff branch assignment, extra staff contacts, designation-level permissions,
  staff permission overrides, company ownership, branch scoping, and company
  owner transfer.
- Repair/work-order/contractor flow:
  tabbed repair issue view, quote-request workflow, signed quote submission,
  contractor portal, final contractor selection, scope-of-work PDF, work-order
  PDF/email, and accounting list for uncharged repair work orders.

## 2. Application Shape and Structure

### Stack

- Laravel 11 application on PHP 8.2.
- Blade server-rendered frontend and backend UI.
- jQuery-powered AJAX screens and Bootstrap-style modals in the backend.
- Spatie permissions for roles, direct permissions, and Gate checks.
- OwenIt auditing is installed and configured through `config/audit.php`.
- PDF generation uses `mpdf/mpdf` and `niklasravnsborg/laravel-pdf`.
- Stripe SDK is installed and used by the sale invoice payment flow.
- Recurring event support uses `rlanvin/php-rrule`.
- Queue-backed jobs are used for notifications, invoice emails, repair quote
  requests, and final contractor assignment emails.

Primary package/config references:

- `composer.json`
- `package.json`
- `config/permission.php`
- `config/audit.php`
- `config/notification_system.php`
- `bootstrap/app.php`

### Main Directory Map

- `routes/web.php`: public routes, public auth, registration, customer
  statements, contractor portal, signed repair quote links, forms, and uploader
  routes.
- `routes/backend.php`: admin/backend routes for nearly all business modules.
- `routes/console.php`: closure console commands. Scheduling is configured in
  `bootstrap/app.php`.
- `app/Http/Controllers/Auth`: public login, registration, and password reset.
- `app/Http/Controllers/Frontend`: public pages, forms, customer statement,
  contractor portal, and repair quote submission.
- `app/Http/Controllers/Backend`: backend controllers for properties, contacts,
  tenancies, repairs, staff, setup, documents, notes, events, website settings,
  and legacy repair invoices.
- `app/Http/Controllers/Backend/Accounting`: accounting module controllers.
- `app/Models`: Eloquent models for business records and accounting records.
- `app/Services/Accounting`: posting, statement, report, invoice lifecycle, and
  invoice penalty services.
- `app/Services/Notifications`: notification orchestration.
- `app/Services/OTP`: SMS provider adapters used by OTP registration.
- `app/Jobs`: queued notification, repair quote, and repair assignment jobs.
- `app/Console/Commands`: recurring invoice, penalty, reminder, notification
  retry, event, and smoke-test commands.
- `database/migrations`: schema changes and module additions.
- `database/seeders`: role/permission, accounting, tenant email, tenant
  permission, and lookup seeders.
- `resources/views/frontend`: public website, auth, contractor, and quote views.
- `resources/views/backend`: backend UI views and module partials.
- `resources/views/backend/layout/app.blade.php`: main backend layout.
- `resources/views/backend/partials/aside2.blade.php`: active backend sidebar.
- `resources/views/components`: shared frontend/backend Blade components.
- `public/asset/backend/js`: backend JavaScript including notes/documents and
  shared initializers.
- `docs`: local handover, audit, and development-plan documents.

### Active Backend Layout

The active backend shell is:

- `resources/views/backend/layout/app.blade.php`

Important layout behavior:

- Includes `backend.partials.navbar`.
- Includes `backend.partials.aside2`.
- Renders main page content through `@yield('content')`.
- Renders modals through `@yield('modal')`.
- Loads shared backend scripts through `backend.partials.assets.scripts`.
- Supports `@stack('styles')`, `@stack('scripts')`,
  `@yield('page.scripts')`, and `@yield('quickstepform.scripts')`.

### Active Sidebar

The active navigation file is:

- `resources/views/backend/partials/aside2.blade.php`

Current sidebar areas include:

- Dashboard
- Calendar
- Properties
- Contacts/users
- Registrations
- Tenancies
- Repair
- Contractor repair portal links for Contractor users
- Legacy invoices
- Documents
- Transactions and transaction categories
- Website setup
- Master/setup management
- Staff, roles, branches, designations, OTP, SMS templates, and email templates
- Accounting masters, sale invoices, credit notes, receipts, statements,
  uncharged repair work orders, reports, and payments

Permission gates in the sidebar use a mix of Spatie permissions and
designation-backed Gate checks. When a menu item is missing for a staff user,
check both direct Spatie permissions and the user's designation permissions.

### Shared Uploads

Uploads are handled through:

- Controller: `Backend\AizUploadController`
- Model: `Upload`
- Views: `resources/views/uploader`

Global uploader routes exist in `routes/web.php`:

- `/aiz-uploader`
- `/aiz-uploader/upload`
- `/aiz-uploader/get_uploaded_files`
- `/aiz-uploader/get_file_by_ids`
- `/aiz-uploader/download/{id}`
- `/aiz-uploader/destroy/{id}`
- `/uploaded-files`
- `/uploaded-files/file-info`
- `/uploaded-files/destroy/{id}`
- `/bulk-uploaded-files-delete`
- `/all-file`

Documents, profile pictures, quote attachments, work-order PDFs, sale invoice
PDF attachments, and property media all rely on this upload/storage foundation
or Laravel storage paths.

### Shared Notes and Documents

Notes and documents are reusable polymorphic features.

Models:

- `Notes`
- `Document`
- `NoteType`
- `DocumentType`

Relationships:

- `Property::notes()` -> `morphMany(Notes::class, 'noteable')`
- `Property::documents()` -> `morphMany(Document::class, 'documentable')`
- `User::notes()` -> `morphMany(Notes::class, 'noteable')`
- `User::documents()` -> `morphMany(Document::class, 'documentable')`

Backend routes:

- `/admin/notes/list`
- `/admin/notes/create`
- `/admin/notes/{note}/edit`
- `/admin/notes/show/{id}`
- `/admin/notes/save`
- `/admin/notes/delete/{id}`
- `/admin/documents/list`
- `/admin/documents/create`
- `/admin/documents/{document}/edit`
- `/admin/documents/show/{id}`
- `/admin/documents/save`
- `/admin/documents/delete/{id}`

Controllers:

- `Backend\NotesController`
- `Backend\DocumentsController`

JavaScript:

- `public/asset/backend/js/common-notes.js`
- `public/asset/backend/js/common-documents.js`

Shared behavior:

- Initial list HTML is server-rendered.
- Filter, reset, save, delete, pagination, and refresh actions reload list HTML
  over AJAX.
- Add/edit/view actions load modal content over AJAX.
- Delete actions use the shared `#deleteConfirmModal`.
- Saves submit `#notesForm` or `#documentsForm` via AJAX.
- Notes reinitialize the AIZ text editor if present.
- Documents reinitialize the AIZ uploader preview.

## 3. Top-Level Routing

### Public routes

`routes/web.php` owns public and customer-facing routes:

- `GET /` -> `FrontendController@index`
- `GET /pricing` -> `FrontendController@pricing`
- `GET|POST /login` -> `AuthController`
- `POST /logout` and fallback `GET /logout`
- `GET /password/forgot`
- `POST /password/email`
- `GET /password/reset/form/{token}`
- `POST /password/reset/`
- `GET|POST /register`
- `GET|POST /register/verify-otp`
- `POST /register/resend-otp`
- `GET /customer/statements` behind `auth`
- `GET /contractor/repairs` behind `auth`
- `GET /contractor/repairs/{id}` behind `auth`
- `GET /repair-quotes/{assignment}/{token}` signed route
- `POST /repair-quotes/{assignment}/{token}` signed route
- `GET|POST /form/{type}`
- AIZ uploader and uploaded-file utility routes
- Debug/helper routes: `/helper` and `/test-sms`

### Backend routes

`routes/backend.php` owns `/admin` routes:

- Backend login/logout/dashboard.
- Most business routes behind `auth`.
- Route names are a mix of `admin.*`, `backend.*`, and a few unprefixed names
  such as `roles.*`, `staffs.*`, `otp.*`, and `sms-templates.*`.
- The active sidebar is `resources/views/backend/partials/aside2.blade.php`.

Route list verification succeeded with `php artisan route:list --path=admin`.
The invalid option `--compact` was not available in this install.

## 4. Auth, Roles, Permissions, and Staff Access

Authentication is still split:

- Public auth: `App\Http\Controllers\Auth\AuthController`
- Public OTP registration: `App\Http\Controllers\Auth\RegistrationController`
- Backend auth: `App\Http\Controllers\Backend\AuthenticateController`
- Password reset: `App\Http\Controllers\Auth\PasswordResetController`

Permissions now flow through `User::hasEffectivePermission()`:

- `AppServiceProvider` defines a `Gate::before` override for `Super Admin`.
- Every cached permission is registered as a Laravel Gate.
- Staff accounts use designation permissions unless their staff record has
  `permissions_customized = true`.
- Customized staff permissions are stored as direct Spatie permissions on the
  user.
- Non-staff users continue to use Spatie roles and direct permissions.

Important models and tables:

- `User`
- `Staff`
- `Designation`
- `designation_has_permissions`
- `permissions`
- `model_has_roles`
- `model_has_permissions`

Staff creation/edit flow:

- `StaffController@create` loads designations, permissions, and branch options.
- `StaffController@store` creates a `User` with `user_type = staff`, assigns the
  `Staff` role, creates the `Staff` row, saves extra staff contacts, and syncs a
  permission override only if selected permissions differ from the designation.
- `StaffController@update` keeps user, branch, staff contacts, role, and
  permission override in sync.

Designation flow:

- `DesignationController` now creates/updates designations with permission
  mappings.
- Staff can inherit permission sets from designations.

## 5. Registration and OTP Flow

Public registration supports:

- Types: `landlord`, `owner`, `estate_agent`, `contractor`
- Verification channel: `email` or `phone`
- OTP expiry: 2 minutes

Frontend flow:

1. `GET /register` renders `resources/views/frontend/register.blade.php`.
2. `POST /register` validates the applicant, creates a `Registration` row with
   status `pending`, stores a 6-digit OTP, and sends it by email or SMS.
3. The registration id is stored in session as `reg_id`.
4. `GET /register/verify-otp` shows the OTP page.
5. `POST /register/verify-otp` validates the OTP, stamps the verified channel,
   changes status to `verified`, clears `reg_id`, and shows pending approval.
6. `POST /register/resend-otp` generates a fresh OTP and resends it.

Backend approval flow:

- Routes live under `/admin/registrations`.
- `RegistrationController@index` lists only `verified`, `approved`, and
  `rejected` records.
- `RegistrationController@show` loads permissions and roles for approval.
- `RegistrationController@approve` creates a `User`, assigns the chosen role,
  creates an owned `Company` for `estate_agent`, links the user to the
  registration, and emails credentials.
- `RegistrationController@reject` marks the registration rejected and emails the
  applicant.
- `RegistrationController@updatePermissions` syncs direct permissions on the
  approved user.

OTP and SMS setup:

- `/admin/otp-configuration` -> `OTPController`
- `/admin/sms-templates` -> `SmsTemplateController`
- SMS providers live under `app/Services/OTP`.
- `SmsUtility::phone_number_verification()` selects the active SMS provider.

Recheck notes:

- The sidebar badge counts every registration whose status is not `approved` or
  `rejected`, but the registration index hides raw `pending` rows that have not
  completed OTP verification. The badge can therefore be higher than the visible
  list.
- `/test-sms` is a public debug route in `routes/web.php`. It should be removed
  or protected before production use.

## 6. Frontend, Dashboard, Customer, and Public Forms

### Frontend Pages

Public frontend pages are simple Blade-rendered routes:

- `GET /` -> `FrontendController@index`
- `GET /pricing` -> `FrontendController@pricing`

Main views:

- `resources/views/frontend/index.blade.php`
- `resources/views/frontend/pricing.blade.php`

### Public Auth

Public login and logout use:

- `Auth\AuthController`
- `resources/views/frontend/login.blade.php`

Public registration no longer uses the older direct-register controller path.
It is handled by the OTP registration flow documented above.

### Password Reset

Password reset uses:

- `Auth\PasswordResetController`
- `resources/views/auth/passwords/email.blade.php`
- `resources/views/frontend` reset-related views where present

Routes:

- `GET /password/forgot`
- `POST /password/email`
- `GET /password/reset/form/{token}`
- `POST /password/reset/`

`User::createResetLink()` creates a reset URL through
`password.reset.form`.

### Dashboard

Backend dashboard route:

- `GET /admin/dashboard` -> `DashboardController@dashboard`
- Route name: `backend.dashboard`

Main view:

- `resources/views/backend/dashboard.blade.php`

Tenant home route:

- `GET /admin/home`
- View: `resources/views/backend/tenant/home.blade.php`

### Customer Statements

Customer self-service statement route:

- `GET /customer/statements`
- Controller: `Frontend\CustomerStatementController`
- Service: `Accounting\StatementService`
- View: `resources/views/frontend/customer/statement.blade.php`

This route is behind `auth` and builds customer/contact statements through the
newer accounting statement service.

### Public Forms

Dynamic public forms use:

- `GET /form/{type}` -> `FormController@show`
- `POST /form/{type}` -> `FormController@submit`
- Model: `FormSubmission`
- Shared component: `resources/views/components/frontend/form.blade.php`

Fields now include:

- first name
- last name
- email
- phone
- user role/type
- demo date
- demo time
- source/hear-about text
- subscribe checkbox
- attachment
- IP and referrer metadata

Recheck notes:

- `FormController` validates `user_role` as
  `landlord,owner,freelancing_agent,contractor`.
- The shared form posts `estate_agent`, not `freelancing_agent`.
- `FormController` assigns `$validated['ip_date']`, but the model fillable uses
  `ip_data`. If the table does not have `ip_date`, that metadata will not save.

## 7. Organization, Company, and Branch Flow

Company and branch data is now part of user/staff/property ownership:

- `Company` belongs to an owner user through `owner_user_id`.
- `Company` has many `Branch` records.
- `User` belongs to `Company`, `Branch`, and `Designation`.
- `User` can own one company through `ownedCompany()`.
- `Staff` belongs to `Branch`.

Branch flow:

- `/admin/branches` routes use `BranchController`.
- `BranchController@branchCompanyFor()` scopes branches to the current user's
  owned company or assigned company.
- If a user has no company and a branch create flow needs one, an owned company
  is created automatically.
- One branch per company can be marked `is_main_head_office`.

Company owner transfer:

- Route: `POST /admin/companies/{company}/transfer-owner`
- Controller: `CompanyController@transferOwner`
- New owner must be an Agent or Estate Agent account.
- Transfer history is stored in `CompanyOwnerTransfer`.

## 8. Property Flow

Property routes remain under `/admin/properties`.

Key changes from the last two months:

- The property index is role-scoped.
- Selected property/detail state is carried in query parameters:
  `property_id` and `tabname`.
- Tenants can see a clean list state without auto-selecting a property.
- Property managers and super admins see all properties.
- Tenants see properties for active tenancies only.
- Landlords and staff are mostly scoped to their created properties in the list.
- Estate agents see properties created by themselves and sub-users.
- Brochure PDF route added:
  `GET /admin/properties/{property}/brochure`.
- Property description has its own popup form.
- Responsibility mapping tab was added.

Current property tabs include:

- Property
- Owners
- Compliance
- Media
- Offers
- Tenancy
- APS
- Teams
- Responsibility
- Documents
- Notes
- Appointments
- Statement

Responsibility mapping:

- Tab view: `resources/views/backend/properties/tabs/responsibility.blade.php`
- Popup form:
  `resources/views/backend/properties/popup_forms/responsibility.blade.php`
- Model: `PropertyResponsibility`
- Responsibility types:
  `property_manager`, `sales_consultant`, `lettings_consultant`,
  `sales_manager`, `lettings_manager`
- Save path: `PropertyController@saveForm` with `form_type = responsibility`

Brochure flow:

- `PropertyController@brochure` checks access with `canAccessProperty()`.
- It loads creator company/branches, local authority, station names, school
  names, and up to four property photos.
- PDF view: `resources/views/backend/properties/brochure.blade.php`

Recheck note:

- In `PropertyController@index`, the detail authorization condition contains
  `($user->hasRole('Staff') || $user->hasRole('Test') && ...)`. Because `&&`
  binds tighter than `||`, any `Staff` user passes that part of the condition.
  The list query scopes staff by creator, and `canAccessProperty()` for
  brochures is grouped correctly, but the detail-page authorization should be
  reviewed.

## 9. Contacts and Users

The UI still labels this module as Contacts, while routes remain under
`/admin/users`.

Important current behavior:

- `UserController@index` supports contact filtering.
- `UserController@show` now has a detail page route:
  `GET /admin/users/show/{id}`.
- `UserController@searchProperties` backs user form property search.
- `UserController@ajaxList` and `staffAjaxList` support select-style lookups.
- User profile routes remain under `/admin/users/profile*`.
- Users can now carry `company_id`, `branch_id`, `designation_id`, and
  `profile_picture`.
- `User::access_label` returns a designation label for staff accounts and role
  labels for non-staff accounts.
- `User::hasEffectivePermission()` is used by Gates.

Tenant account emails:

- `TenantEmailTemplatesSeeder` creates or updates:
  `tenant_account_created` and `tenant_welcome`.
- Tenancy creation sends `tenant_welcome`.

## 10. Tenancy Flow

Tenancy routes now support both property-scoped and global listing:

- `GET /admin/properties/{propertyId}/tenancies`
- `GET /admin/tenancies`
- `GET /admin/tenancies/create`
- `POST /admin/tenancies/store`
- `GET /admin/tenancies/{id}`
- `GET /admin/tenancies/{id}/edit`
- `POST /admin/tenancies/{id}/update`
- `POST /admin/tenancies/{id}/delete`
- `GET /admin/tenancies/{id}/rent-ledger`

Current create flow:

1. User selects property, tenants, main person, property managers, tenancy type,
   sub-status, dates, rent, deposit, and flags.
2. If the new tenancy status is `Active`, existing active tenancies for the same
   property are set to `Archived`.
3. `Tenancy` is created.
4. Property manager pivots are created in `property_manager_tenancy`.
5. Tenant members are created with group id `GROUP_{tenancy_id}`.
6. The property id is added to each tenant user's `selected_properties`.
7. A tenant welcome email is sent for each tenant.

Rent ledger:

- Controller: `TenancyController@rentLedger`
- View: `resources/views/backend/tenancies/rent-ledger.blade.php`
- Pulls sale invoices linked directly to the tenancy.
- Also pulls property-linked sale invoices charged to the tenancy's tenants.
- Shows invoice count, total invoiced, paid, balance, latest payment, status,
  invoice rows, and payment rows.

Recheck note:

- `TenancyController@store` and `update` validate status as `Active,Archive`,
  but the global list filter and auto-archive code use `Archived`. This should
  be normalized.

## 11. Repair, Quote, Contractor, and Work-Order Flow

The repair module now has two list/detail experiences:

- Classic list: `GET /admin/property-repairs/issue-list`
- Tabbed list: `GET /admin/property-repairs/issue-list-tabbed`

The sidebar exposes both All and Issue List (Tabbed), plus status filters:

- Pending
- Reported
- Under Process
- Work Completed
- Invoice Received
- Invoice Paid
- Closed

Tabbed repair detail:

- Controller: `PropertyRepairController@indexTabbed`
- View: `resources/views/backend/repair/index_tabbed.blade.php`
- List partial: `resources/views/backend/repair/list/tabbed-cards.blade.php`
- Tabs:
  Issue, Property Manager, Request a quote, Work Order
- Tab partials:
  `resources/views/backend/repair/tabs/issue.blade.php`,
  `property-manager.blade.php`, `contractors.blade.php`,
  `work-order.blade.php`

Quote request flow:

1. Admin opens the Request a quote tab.
2. Admin selects existing contractors or adds a contractor inline.
3. `POST /admin/property-repairs/quote-contractors` can create/update a
   contractor user and assign the Contractor role.
4. `POST /admin/property-repairs/{repairIssue}/quote-requests` creates or
   updates `RepairIssueContractorAssignment` rows.
5. Assignment status becomes `Quote Requested`.
6. A random `quote_token` is stored.
7. `SendRepairQuoteRequestEmail` is queued on database connection and queue
   `repair-quotes`.
8. The email includes a scope-of-work PDF and a temporary signed quote URL.

Signed contractor quote flow:

- Public signed route:
  `GET /repair-quotes/{assignment}/{token}`
- Submit route:
  `POST /repair-quotes/{assignment}/{token}`
- Controller: `Frontend\RepairQuoteController`
- View: `resources/views/frontend/repair_quotes/show.blade.php`
- Token must match `RepairIssueContractorAssignment::quote_token`.
- Signed URL is generated with a 14-day expiry.
- Contractor submission stores:
  estimated price, availability options, preferred availability,
  consultant name/phone, tentative dates, quote notes, quote attachment,
  `quote_submitted_at`, and status `Quoted`.

Final contractor flow:

- Route:
  `POST /admin/property-repairs/{repairIssue}/contractor-assignments/{assignment}/finalize`
- Controller: `PropertyRepairController@finalizeContractor`
- Sets `repair_issues.final_contractor_id`.
- Sets assignment status to `Finalized`.
- Queues `SendFinalContractorAssignedEmail` on database connection and queue
  `repair-assignments`.

Work order flow:

- Route: `POST /admin/work-orders/store`
- Controller: `WorkOrderController@store`
- Requires `final_contractor_assignment_id`.
- Creates or updates a `WorkOrder`.
- Replaces work-order items on update.
- Calculates each line total from quantity, unit price, and tax rate.
- Ensures the selected assignment is the repair's final contractor.
- PDF route: `GET /admin/work-orders/generate-pdf/{id}`
- Send route: `POST /admin/work-orders/send/{id}`
- Work-order email goes to the repair's final contractor and attaches the PDF.

Contractor portal:

- Routes:
  `GET /contractor/repairs`,
  `GET /contractor/repairs/{id}`
- Controller: `Frontend\ContractorPortalController`
- Shows only repairs where `final_contractor_id = auth()->id()`.
- Supports search and status filters.
- AJAX detail excludes admin-only contractor assignment/invoice actions.
- Backend sidebar shows a contractor-only Repair Issues menu when the logged-in
  user has the Contractor role.

Uncharged repair work orders:

- Route:
  `GET /admin/accounting/uncharged-repair-work-orders`
- Controller:
  `Backend\Accounting\UnchargedRepairWorkOrderController@index`
- Lists work orders with a final contractor and no linked legacy invoice.
- Sidebar item lives under Accounting.

Queue requirements:

- Quote request emails need a worker listening on the `repair-quotes` queue.
- Final contractor emails need a worker listening on the `repair-assignments`
  queue.
- General notification emails need normal queue workers for `SendNotificationJob`.

## 12. Other Business Modules and Shared Features

### Offers

Offers live under `/admin/offers`.

Controller:

- `Backend\OfferController`

Routes:

- `GET /admin/offers`
- `GET /admin/offers/create`
- `POST /admin/offers/store`
- `GET /admin/offers/{offer}/edit`
- `PUT /admin/offers/{offer}/update`
- `DELETE /admin/offers/{offer}/delete`
- `POST /admin/offers/{id}/set-main-person`
- `POST /admin/offers/{id}/update-status`

Views:

- `resources/views/backend/offers/*`

Model:

- `Offer`

Property detail pages render offers in the Offers tab.

### Owner Groups

Owner groups live under `/admin/owner-groups`.

Controller:

- `Backend\OwnerGroupController`

Views:

- `resources/views/backend/owner_groups/*`

Models:

- `OwnerGroup`
- `OwnerGroupUser`
- `User`

Supported behavior:

- Main owner group CRUD.
- Subgroup create, update, and delete.
- Main group update.
- Property Owners tab integration.

### Estate Charges

Estate charge routes live under:

- `/admin/estate-charges`
- `/admin/estate-charges-items`

Controllers:

- `EstateChargeController`
- `EstateChargeItemController`

Views:

- `resources/views/backend/estate_charges/*`

Models:

- `EstateCharge`
- `EstateChargeItem`

`Property::estateCharge()` links a property to an estate charge through
`estate_charges_id`.

### Compliance

Compliance routes live under `/admin/compliance`.

Controller:

- `Backend\ComplianceController`

Views:

- `resources/views/backend/compliance/*`

Models:

- `ComplianceType`
- `ComplianceRecord`
- `ComplianceDetail`

Form partials exist for:

- EICR
- EPC
- Gas
- Landlord registration
- Common compliance fields

Compliance surfaces exist on both property and user/contact detail pages.

### Calendar and Events

Calendar routes live under `/admin/calendar`.

Controller:

- `Backend\EventController`

Views:

- `resources/views/backend/events/calendar.blade.php`
- `resources/views/backend/events/modal.blade.php`
- `resources/views/backend/partials/calendar.blade.php`
- `resources/views/backend/partials/_calendar_modals.blade.php`

Routes:

- `GET /admin/calendar`
- `GET /admin/calendar/instances`
- `POST /admin/calendar/instances/store`
- `POST /admin/calendar/instances/update/{instance}`
- `PUT /admin/calendar/master/update/{event}`
- `POST /admin/calendar/instances/cancel/{id}`
- `POST /admin/calendar/instances/delete/{id}`
- `POST /admin/calendar/instances/change-status/{id}`

Models:

- `Event`
- `EventInstance`
- `EventInstanceChange`
- `EventReminder`
- `EventType`
- `EventSubType`

Event setup routes:

- `Route::resource('event-types', EventTypeController::class)`
- `Route::resource('event-sub-types', EventSubTypeController::class)`
- `GET /admin/api/event-sub-types/{typeId}`

Background commands:

- `events:generate-future {days=30}`
- `app:generate-recurring-events`
- `events:send-reminders`

Recheck note:

- Event commands exist but are not scheduled in `bootstrap/app.php` during this
  recheck.

### Transactions and Transaction Categories

Transactions:

- `Route::resource('transactions', TransactionController::class)`
- Route names: `backend.transactions.*`
- Views: `resources/views/backend/transactions/*`
- Model: `Transaction`

Transaction categories:

- `Route::resource('transaction-categories', TransactionCategoryController::class)`
- Route names: `backend.transaction_categories.*`
- Views: `resources/views/backend/transaction_categories/*`
- Model: `TransactionCategory`

### Master Data and Setup Configuration

Master/setup modules include:

- User categories: `Route::resource('user-categories', UserCategoryController::class)`
- Branches: `/admin/branches`
- Designations: `/admin/designations`
- Note types: `/admin/note-types`
- Document types: `/admin/document-types`
- Tenancy types: `/admin/tenancy-types`
- Tenancy sub statuses: `/admin/tenancy-sub-statuses`
- Event types and event subtypes
- Job types: `/admin/job-types`
- Transaction categories
- OTP configuration: `/admin/otp-configuration`
- SMS templates: `/admin/sms-templates`
- Email templates: `/admin/email-templates`

### Website Setup

Website setup routes are grouped under `/admin/website`.

Controller:

- `Backend\WebsiteController`

Routes:

- `GET /admin/website/footer`
- `GET /admin/website/header`
- `GET /admin/website/appearance`

Views:

- `resources/views/backend/website_settings/*`

### Business and Mail Setup

Business settings routes:

- `POST /admin/business-settings/update`
- `GET /admin/smtp-settings`
- `POST /admin/env_key_update`
- `POST /admin/test/smtp`

Controller:

- `Backend\BusinessSettingsController`

SMTP view:

- `resources/views/backend/setup_configurations/smtp_settings.blade.php`

### Staff Roles

Role routes use `Backend\RoleController`:

- `Route::resource('roles', RoleController::class)`
- `GET /admin/roles/edit/{id}`
- `GET /admin/roles/destroy/{id}`
- `POST /admin/roles/add_permission`

Views:

- `resources/views/backend/staff/staff_roles/*`

Recheck note:

- Staff account permission behavior now depends on designations and direct
  overrides, while non-staff users still use Spatie role permissions.

### Legacy Purchase Invoices and Account Notes

The backend still has older purchase invoice/note routes outside the newer
`/admin/accounting` purchase invoice area:

- `Route::resource('purchase_invoices', PurchaseInvoiceController::class)`
- `AccountsNoteApplicationController` routes for credit notes, debit notes,
  note applications, and refunds.

These should be treated separately from the newer accounting module routes
unless the business intentionally consolidates them.

## 13. Accounting, Notifications, and Scheduled Jobs

The accounting module lives under `/admin/accounting` and route names under
`backend.accounting.*`.

### Accounting Navigation

The active sidebar exposes:

- Masters
- Sale invoices
- Credit notes
- Receipts
- Customer statements
- Account ledger
- Uncharged repair work orders
- Reports
- Payments

Routes also exist for purchase invoices, debit notes, bank reconciliation, fixed
assets, GL accounts, balances, journals, and journal lines. Some of these are
not all visible in the current sidebar.

### Accounting Masters

Master routes:

- `/admin/accounting/masters/banks`
- `/admin/accounting/masters/payment-methods`
- `/admin/accounting/masters/income-categories`
- `/admin/accounting/masters/expense-categories`
- `/admin/accounting/masters/invoice-headers`
- `/admin/accounting/masters/taxes`

Controllers:

- `Accounting\Masters\BankController`
- `Accounting\Masters\PaymentMethodController`
- `Accounting\Masters\IncomeCategoryController`
- `Accounting\Masters\ExpenseCategoryController`
- `Accounting\Masters\SysInvoiceHeaderController`
- `Accounting\Masters\TaxController`

Models:

- `SysBankAccount`
- `PaymentMethod`
- `SysIncomeCategory`
- `SysExpenseCategory`
- `SysInvoiceHeader`
- `SysTax`

### General Ledger

Routes:

- `/admin/accounting/gl-accounts`
- `/admin/accounting/gl-account-balances`
- `/admin/accounting/gl-journals`
- `/admin/accounting/gl-journal-lines`

Controllers:

- `GlAccountController`
- `GlAccountBalanceController`
- `GlJournalController`
- `GlJournalLineController`

Models:

- `GlAccount`
- `GlAccountBalance`
- `GlJournal`
- `GlJournalLine`
- `GlPeriodClose`
- `GlAuditLog`

Important behavior:

- `GlJournal` has active/reversal relationships.
- Posting services create/update/delete journals and maintain balances.
- Default GL account mappings are read from `BusinessSetting` values and GL
  account codes.

### Sale Invoices

Main routes:

- `GET /admin/accounting/sale/invoices`
- `GET /admin/accounting/sale/invoices/create`
- `POST /admin/accounting/sale/invoices`
- `GET /admin/accounting/sale/invoices/{invoice}`
- `GET /admin/accounting/sale/invoices/{invoice}/edit`
- `PUT/PATCH /admin/accounting/sale/invoices/{invoice}`
- `DELETE /admin/accounting/sale/invoices/{invoice}`
- `GET /admin/accounting/sale/invoices/search`
- `GET /admin/accounting/sale/invoices/link-to-search`
- `GET /admin/accounting/sale/invoices/property-context/{property}`
- `GET /admin/accounting/sale/invoices/tenancy-context/{property}/{tenant}`
- `GET /admin/accounting/sale/invoices/{invoice}/json`
- `POST /admin/accounting/sale/invoices/{invoice}/pay`
- `GET /admin/accounting/sale/invoices/{invoice}/paid`
- `POST /admin/accounting/sale/invoices/{invoice}/apply-credit`
- `POST /admin/accounting/sale/invoices/advance`
- `POST /admin/accounting/sale/invoices/{invoice}/undo-credit/{payment}`
- `GET /admin/accounting/sale/invoices/{invoice}/pdf`

Controller:

- `Accounting\Sale\SaleInvoiceController`

Views:

- `resources/views/backend/accounting/sale/invoices/index.blade.php`
- `resources/views/backend/accounting/sale/invoices/create.blade.php`
- `resources/views/backend/accounting/sale/invoices/edit.blade.php`
- `resources/views/backend/accounting/sale/invoices/show.blade.php`
- `resources/views/backend/accounting/sale/invoices/pdf.blade.php`
- `resources/views/backend/accounting/sale/invoices/_form.blade.php`

Models:

- `SysSaleInvoice`
- `SysSaleInvoiceItem`
- `SysReceipt`
- `SysPayment`
- `SysInvoiceHeader`
- `GlJournal`

Services:

- `SaleInvoiceLifecycleService`
- `SaleInvoicePenaltyService`
- `PostingService`

Current sale invoice behavior:

- `SaleInvoiceLifecycleService::nextInvoiceNo()` uses the
  `sale_invoice_prefix` business setting and current max invoice ID.
- `SaleInvoiceLifecycleService::persistItems()` recalculates subtotal, tax,
  total, and balance from submitted line items.
- `SaleInvoiceLifecycleService::postInvoiceIfNeeded()` creates or removes sale
  invoice issue journals depending on invoice status.
- The controller handles recurring settings, notification settings, PDF
  generation, Stripe checkout, mark-paid, advances, apply-credit, undo-credit,
  and AJAX selection helpers.
- `SysSaleInvoice` has item, receipt, payment, customer, invoice header,
  GL journal, `linkTo`, and `chargeTo` relationships.

Sale invoice fields added or changed in the recheck window:

- Recurring invoice settings:
  month interval, custom interval/unit, sequence, cycles, unlimited cycles.
- Reminder setting:
  `reminder_days_before_due`.
- Penalty settings:
  enable flag, type, fixed/percentage rate, grace days, max amount, GL account,
  applied timestamp, and applied amount.
- Invoice PDF attachments for notification emails.
- Property and tenancy context helpers for sale invoice creation.

### Credit Notes, Purchase Invoices, Debit Notes, Receipts, and Payments

Credit notes:

- Routes under `/admin/accounting/sale/credit-notes`
- Controller: `Accounting\Sale\CreditNoteController`
- Views: `resources/views/backend/accounting/sale/credit_notes/*`
- Model: `CreditNote`

Purchase invoices:

- Routes under `/admin/accounting/purchase/invoices`
- Controller: `Accounting\Purchase\PurchaseInvoiceController`
- Views: `resources/views/backend/accounting/purchase/invoices/*`
- Models: `SysPurchaseInvoice`, `SysPurchaseInvoiceItem`

Debit notes:

- Routes under `/admin/accounting/purchase/debit-notes`
- Controller: `Accounting\Purchase\DebitNoteController`
- Views: `resources/views/backend/accounting/purchase/debit_notes/*`
- Model: `DebitNote`

Receipts:

- Routes under `/admin/accounting/receipts`
- PDF route: `/admin/accounting/receipts/{receipt}/pdf`
- Controller: `Accounting\Receipts\ReceiptController`
- Views: `resources/views/backend/accounting/receipts/*`
- Model: `SysReceipt`

Payments:

- Routes under `/admin/accounting/payments`
- Extra list modes: `all-transactions`, `incomes`, `expenses`,
  `general-entry`
- Controller: `Accounting\Payments\PaymentController`
- Views: `resources/views/backend/accounting/payments/*`
- Model: `SysPayment`

### Statements and Reports

Statement routes:

- `/admin/accounting/statements/customers`
- `/admin/accounting/statements/accounts`

Controller:

- `Accounting\StatementController`

Service:

- `Accounting\StatementService`

Views:

- `resources/views/backend/accounting/statements/customer.blade.php`
- `resources/views/backend/accounting/statements/account.blade.php`

Statement behavior:

- Builds customer/contact statements from GL journal lines.
- Builds account ledgers with opening, running, and closing balances.
- Supports property statements by resolving tenant users and property-linked
  sale invoices.

Report routes:

- `/admin/accounting/reports/trial-balance`
- `/admin/accounting/reports/profit-loss`
- `/admin/accounting/reports/balance-sheet`
- `/admin/accounting/reports/ar-aging`
- `/admin/accounting/reports/ap-aging`

Controller:

- `Accounting\ReportController`

Service:

- `Accounting\ReportService`

Views:

- `resources/views/backend/accounting/reports/trial_balance.blade.php`
- `resources/views/backend/accounting/reports/profit_loss.blade.php`
- `resources/views/backend/accounting/reports/balance_sheet.blade.php`
- `resources/views/backend/accounting/reports/ar_aging.blade.php`
- `resources/views/backend/accounting/reports/ap_aging.blade.php`

### Bank Reconciliation and Fixed Assets

Bank reconciliation:

- `GET /admin/accounting/bank-reconciliation`
- `POST /admin/accounting/bank-reconciliation/reconcile`
- Controller: `Accounting\BankReconciliationController`
- View: `resources/views/backend/accounting/bank_reconciliation/index.blade.php`
- Models: `BankReconciliation`, `BankReconciliationLine`

Fixed assets:

- `Route::resource('fixed-assets', FixedAssetController::class)`
- Route names: `backend.accounting.fixed_assets.*`
- Controller: `Accounting\FixedAssetController`
- Model: `FixedAsset`

### Accounting Posting Rules

`App\Services\Accounting\PostingService` is the core GL posting service.

Current posting flows include:

- Sale invoice issue: debit AR and credit revenue or penalty income.
- Purchase invoice issue: debit expense and credit AP.
- Advance receipt: debit bank/cash and credit advances.
- Applying credit to a sale invoice: debit advances and credit AR.
- Stripe payment: debit bank/cash and credit AR.
- Purchase payment: debit AP and credit bank/cash.
- Undo credit: reverse the apply-credit posting.
- Sale and purchase issue journal updates after invoice edits.
- Journal deletion and balance reversal.

Default accounts are resolved through `BusinessSetting` keys and GL account
codes.

### Notifications and Invoice Automation

Notification flow:

- `NotificationService::trigger()` loads active email templates by identifier.
- If `sms_templates` exists, active SMS templates are also loaded.
- Optional mock WhatsApp/system channels are controlled by
  `config/notification_system.php`.
- A `NotificationLog` row is created per template/channel.
- `SendNotificationJob` sends the notification and updates attempts/status.
- Email notifications can attach a sale invoice PDF when payload contains
  `attach_invoice_pdf` and `invoice_id`.

Notification identifiers currently used by sale invoice commands:

- `sale_invoice_send`
- `sale_invoice_due_reminder`
- `sale_invoice_overdue_reminder`

Scheduled commands are registered in `bootstrap/app.php`:

- `sale-invoices:generate-recurring` daily at 00:05
- `sale-invoices:apply-penalties` daily at 00:10
- `sale-invoices:send-reminders` daily at 09:00
- `sale-invoices:send-overdue-reminders` daily at 09:05
- `notifications:retry` every five minutes

Other available commands:

- `sale-invoices:send-unsent-emails`
- `sale-invoices:recurring-smoke-test`
- `sale-invoices:penalty-smoke-test`
- `events:generate-future`
- `app:generate-recurring-events`
- `events:send-reminders`

Recheck note:

- Event reminder commands exist, but event commands are not scheduled in
  `bootstrap/app.php` during this recheck.

## 14. Data Model Highlights

This is not a complete schema reference. Use `database/migrations` for
column-level detail.

Core user and access entities:

- `User`: central person/account record. Represents owners, tenants, property
  managers, contractors, staff, estate agents, applicants, and other contacts
  through roles, category data, and local fields.
- `UserCategory`: contact categorization used across older forms and filters.
- `Role`, `Permission`: Spatie role/permission records.
- `Designation`: staff designation with a permission set.
- `Staff`: staff-specific record linked to a user and branch.
- `StaffContact`: multiple emails/phones for staff.
- `Company`: organization owned by an agent/estate-agent user.
- `Branch`: company branch/head-office data.
- `CompanyOwnerTransfer`: company owner transfer history.

Property and tenancy entities:

- `Property`: central property record. Links to compliance records, estate
  charges, notes, documents, events, owner groups, repairs, tenancies, local
  authority, and country data.
- `PropertyResponsibility`: maps a property to staff users by responsibility
  type.
- `OwnerGroup` and `OwnerGroupUser`: property owner group and subgroup data.
- `Offer`: offer records attached to properties.
- `Tenancy`: links property, offer, tenancy type, sub-status, dates, rent,
  deposit, tenants, and property managers.
- `TenantMember`: tenancy member records with main-person flag and group id.
- `PropertyManagerTenancy`: property manager tenancy pivot.

Repair entities:

- `RepairIssue`: repair case linked to property, tenant, category, photos,
  assignments, histories, managers, contractor quote assignments, final
  contractor, work order, and legacy invoice.
- `RepairCategory`: repair category/subcategory hierarchy.
- `RepairPhoto`: repair issue media.
- `RepairAssignment`, `RepairHistory`, `RepairIssueUser`,
  `RepairIssuePropertyManager`, `RepairIssueContractorAssignment`: assignment,
  history, user, manager, and contractor quote tracking.
- `WorkOrder`, `WorkOrderItem`: repair work order header and lines.
- `Invoice`, `InvoiceItems`, `InvoiceStatuses`: legacy repair/work-order
  invoice records.

Shared entities:

- `Notes`, `NoteType`: polymorphic notes.
- `Document`, `DocumentType`: polymorphic documents.
- `Upload`: stored uploaded file metadata.
- `Event`, `EventInstance`, `EventInstanceChange`, `EventReminder`,
  `EventType`, `EventSubType`: calendar and recurrence records.
- `FormSubmission`: public dynamic form submissions.
- `Registration`: public OTP registration and approval workflow.
- `OtpConfiguration`, `SmsTemplate`: OTP and SMS configuration/template data.

Accounting entities:

- `SysSaleInvoice`, `SysSaleInvoiceItem`: sale invoice header and lines.
- `SysPurchaseInvoice`, `SysPurchaseInvoiceItem`: purchase invoice header and
  lines.
- `SysReceipt`: receipts and advance credits.
- `SysPayment`: payments and accounting payment entries.
- `PaymentMethod`, `SysBankAccount`: payment/bank masters.
- `SysIncomeCategory`, `SysExpenseCategory`, `SysInvoiceHeader`, `SysTax`:
  accounting masters.
- `CreditNote`, `DebitNote`: accounting notes.
- `GlAccount`, `GlAccountBalance`, `GlJournal`, `GlJournalLine`,
  `GlPeriodClose`, `GlAuditLog`: general ledger.
- `BankReconciliation`, `BankReconciliationLine`: reconciliation data.
- `FixedAsset`: fixed asset data.
- `NotificationLog`: queued notification tracking.

Seeder highlights:

- `DatabaseSeeder` coordinates baseline seeders.
- Role/permission seeders define access.
- Tenant seeders add tenant permissions and email templates.
- Accounting seeders define GL and accounting defaults.
- Lookup seeders define tenancy types, repair/event lookups, and other master
  records.

## 15. Background and Operational Flows

Scheduling is configured in `bootstrap/app.php`, not `routes/console.php`.

Scheduled commands:

- `sale-invoices:generate-recurring` daily at 00:05
- `sale-invoices:apply-penalties` daily at 00:10
- `sale-invoices:send-reminders` daily at 09:00
- `sale-invoices:send-overdue-reminders` daily at 09:05
- `notifications:retry` every five minutes

Operational commands to know:

- `sale-invoices:send-unsent-emails`
- `sale-invoices:recurring-smoke-test`
- `sale-invoices:penalty-smoke-test`
- `events:generate-future {days=30}`
- `app:generate-recurring-events`
- `events:send-reminders`

Queue-backed jobs:

- `SendNotificationJob`
- `SendRepairQuoteRequestEmail`
- `SendFinalContractorAssignedEmail`

Queue notes:

- General notifications use `SendNotificationJob`.
- Repair quote requests are dispatched to database queue `repair-quotes`.
- Final contractor assignment emails are dispatched to database queue
  `repair-assignments`.
- `composer.json` has a dev script that starts `php artisan queue:listen
  --tries=1`, but production should run queue workers explicitly and include
  the named repair queues.

## 16. Where to Change Things

### Add or Change a Backend Menu Item

Change:

- `resources/views/backend/partials/aside2.blade.php`

Also check:

- Route name in `routes/backend.php` or `routes/web.php`.
- Permission name in migrations/seeders.
- `User::hasEffectivePermission()` if staff/designation access is involved.
- Active-route checks using `request()->routeIs()` or local helper functions.

### Add a Backend Page

Usually change:

- `routes/backend.php`
- A controller under `app/Http/Controllers/Backend`
- Views under `resources/views/backend`
- Sidebar only if the page should be navigable.
- Permission seeders/migrations if access should be gated.

### Add a Property Form Step or Popup Section

Usually change:

- `PropertyController@getStepView`
- `PropertyController@getQuickStepView`
- `PropertyController@getValidationRules`
- `PropertyController@getValidationRulesQuick`
- `PropertyController@loadForm`
- `PropertyController@saveForm`
- Views under `resources/views/backend/properties/form_components`
- Views under `resources/views/backend/properties/quick_form_components`
- Views under `resources/views/backend/properties/popup_forms`
- Tab views under `resources/views/backend/properties/tabs`

### Add a User/Contact Form Step or Popup Section

Usually change:

- `UserController@getQuickStepView`
- `UserController@getValidationRulesQuick`
- `UserController@loadForm`
- `UserController@saveForm`
- Views under `resources/views/backend/users/user_form`
- Views under `resources/views/backend/users/popup_forms`
- Views under `resources/views/backend/users/tabs`

### Add a Shared Notes or Documents Surface

Usually change:

- Add `backend-notes-component` or `backend-documents-component` markup.
- Pass the correct polymorphic type and id.
- Ensure `common-notes.js` or `common-documents.js` is loaded.
- Ensure shared modals and `#deleteConfirmModal` exist.
- Confirm the target model has the `morphMany` relationship.

### Change Repair Quote or Work Order Flow

Usually change:

- `PropertyRepairController`
- `WorkOrderController`
- `RepairIssueContractorAssignment`
- Repair tab views under `resources/views/backend/repair/tabs`
- Repair popup forms under `resources/views/backend/repair/popup_forms`
- Email views under `resources/views/emails`
- Jobs under `app/Jobs`
- Signed public quote views under `resources/views/frontend/repair_quotes`

### Change Contractor Portal Flow

Usually change:

- `Frontend\ContractorPortalController`
- `resources/views/frontend/contractor/index.blade.php`
- `resources/views/frontend/contractor/list/cards.blade.php`
- `resources/views/frontend/contractor/detail/show.blade.php`
- Contractor-only sidebar block in `aside2.blade.php`

### Change Tenancy or Rent Ledger Flow

Usually change:

- `TenancyController`
- `resources/views/backend/tenancies/*`
- Property tenancy tab views
- Tenant email templates and seeders
- `StatementService` or sale invoice link fields if accounting linkage changes.

### Change Sale Invoice Posting

Usually change:

- `SaleInvoiceController`
- `SaleInvoiceLifecycleService`
- `SaleInvoicePenaltyService`
- `PostingService`
- `SysSaleInvoice`
- `SysSaleInvoiceItem`
- Views under `resources/views/backend/accounting/sale/invoices`
- Relevant migrations for changed invoice fields.

### Change Reports or Statements

Usually change:

- `ReportController`
- `ReportService`
- `StatementController`
- `StatementService`
- Views under `resources/views/backend/accounting/reports`
- Views under `resources/views/backend/accounting/statements`

### Change Notifications

Usually change:

- `NotificationService`
- `SendNotificationJob`
- Email template seeders/data
- `EmailTemplateController`
- SMS templates and OTP/SMS setup if SMS is involved.
- `config/notification_system.php`
- Notification commands under `app/Console/Commands`

### Change Roles, Staff, or Permissions

Usually change:

- `RoleAndPermissionSeeder`
- Permission migrations/additional seeders.
- `DesignationController`
- `StaffController`
- `User::hasEffectivePermission()`
- `AppServiceProvider` Gate definitions.
- Sidebar `@can`/`@canany` checks.

### Change Upload Behavior

Usually change:

- `AizUploadController`
- `Upload`
- Uploader Blade views under `resources/views/uploader`
- Shared upload scripts/assets.

## 17. Updated Navigation Notes

Current sidebar additions/changes:

- Registrations menu for non-contractor users.
- Tenancies global menu gated by `manage tenancies`.
- Repair menu includes classic issue list and tabbed issue list.
- Contractor users get a contractor-only repair menu.
- Accounting includes Uncharged repair work order.
- OTP configuration and SMS templates exist under backend setup areas.

When adding a menu item, check:

- `resources/views/backend/partials/aside2.blade.php`
- Route name in `routes/backend.php` or `routes/web.php`
- Permission name seeded in migrations/seeders
- `User::hasEffectivePermission()` behavior for staff/designation accounts

## 18. Verification Checklist for Future Changes

Use this checklist when changing flows described in this document:

- Run `php artisan route:list` and confirm route names used by Blade views
  still exist.
- Check `resources/views/backend/partials/aside2.blade.php` for permission
  gates and active-route checks.
- Confirm changed model relationships are used consistently by controllers,
  views, jobs, and services.
- For staff access changes, test designation-inherited permissions and direct
  permission overrides.
- For AJAX modals, confirm the modal id, form id, component wrapper, data
  attributes, CSRF token, response shape, and refresh event.
- For property/contact notes and documents, test list, filter, create, edit,
  view, delete, and pagination flows.
- For repair quote changes, test contractor creation, quote request email queue,
  signed quote submission, final contractor selection, work-order save, PDF, and
  send email.
- For contractor portal changes, test as a Contractor user and confirm only
  assigned repairs are visible.
- For tenancy changes, test property-scoped list, global list, create/update,
  tenant members, main person, property managers, tenant welcome email, and rent
  ledger.
- For accounting changes, verify GL posting creates balanced journal lines and
  updates balances.
- For recurring invoice or penalty changes, run the smoke commands when database
  state allows it.
- For notification changes, verify template identifiers, `notification_logs`,
  queue worker behavior, and retry behavior.
- For PDF changes, test browser/download routes and queued email attachments.
- For public registration changes, test email OTP, phone OTP, resend, expiry,
  admin approval, rejection, and generated credentials email.

## 19. Recheck Findings to Fix or Confirm

These are not documentation guesses; they were found while checking current
code paths.

1. Public form role mismatch:
   `FormController` accepts `freelancing_agent`, but the frontend form posts
   `estate_agent`.

2. Public form metadata mismatch:
   `FormController` writes `ip_date`, while `FormSubmission` fillable contains
   `ip_data`.

3. Tenancy status mismatch:
   create/update validation accepts `Archive`, while lists and auto-archive use
   `Archived`.

4. Property detail authorization grouping:
   Staff access in `PropertyController@index` should be reviewed because of
   `||` and `&&` precedence.

5. Registration badge/list mismatch:
   the sidebar badge can count pending OTP records that the index intentionally
   hides.

6. Public debug route:
   `/test-sms` is exposed in `routes/web.php`.

7. Queue dependency:
   repair quote and final-assignment emails are queued on named queues. If only
   the default queue is running, those emails may not send.

## 20. Verification Performed

Commands run during this v2 recheck:

- `git log --since="2026-04-18" --stat --oneline`
- `git log --follow --date=short --pretty=format:"%h %ad %s" -- docs/application-flow.md`
- `php artisan route:list --path=admin`
- `php artisan route:list --path=register`
- `php artisan route:list --path=contractor`
- `php artisan route:list --path=repair-quotes`

Verification result:

- Route list booted successfully.
- Public registration routes are registered.
- Contractor portal routes are registered.
- Signed repair quote routes are registered.
- Admin repair quote/work-order/accounting routes are registered.
- `php artisan route:list --compact` failed because this Laravel install does
  not support the `--compact` option.
