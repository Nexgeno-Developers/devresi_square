# Current Application Flow

Developer handover document for the current Laravel application. This document describes the codebase as it exists now and points maintainers to the main routes, controllers, models, views, services, assets, commands, and data flows.

## 1. Application Shape

### Stack

- Laravel 11 application on PHP 8.2.
- Blade server-rendered frontend and backend UI.
- jQuery and Bootstrap-style modals for many backend interactions.
- Spatie permissions for roles and permission checks.
- OwenIt auditing is installed and `config/audit.php` exists.
- PDF generation uses `mpdf/mpdf` and `niklasravnsborg/laravel-pdf`.
- Stripe SDK is installed and used in sale invoice payment flow.
- Recurrence support uses `rlanvin/php-rrule`.

Primary package references:

- `composer.json`
- `package.json`
- `config/permission.php`
- `config/audit.php`
- `config/notification_system.php`

### Main Directory Map

- `routes/web.php`: public web routes, frontend auth, customer statement, public dynamic forms, AIZ uploader routes.
- `routes/backend.php`: admin/backend routes. Most application functionality sits behind the `auth` middleware here.
- `app/Http/Controllers/Frontend`: public and customer-facing controllers.
- `app/Http/Controllers/Backend`: admin controllers for properties, contacts, repairs, tenancies, documents, notes, setup, staff, and legacy accounting/invoices.
- `app/Http/Controllers/Backend/Accounting`: newer accounting module controllers.
- `app/Models`: Eloquent models for business entities and accounting entities.
- `app/Services/Accounting`: posting, statement, report, invoice lifecycle, and invoice penalty services.
- `app/Services/Notifications`: notification orchestration.
- `app/Console/Commands`: commands for recurring events, recurring sale invoices, invoice reminders, penalties, notification retry, and smoke tests.
- `resources/views/frontend`: public website views.
- `resources/views/backend`: backend UI views.
- `resources/views/backend/layout/app.blade.php`: main backend layout.
- `resources/views/backend/partials/aside2.blade.php`: active backend sidebar/navigation.
- `public/asset/backend/js`: backend JavaScript, including shared note/document components.

### Routing Layout

`routes/web.php` owns public and global web routes:

- `/login`, `/register`, `/logout` through `Auth\AuthController`.
- `/`, `/pricing` through `Frontend\FrontendController`.
- `/password/reset/form/{token}` and `/password/reset/` through `Auth\PasswordResetController`.
- `/customer/statements` through `Frontend\CustomerStatementController`, behind `auth`.
- `/form/{type}` GET/POST through `Frontend\FormController`.
- `/storage-link` utility route.
- `/aiz-uploader*` and `/uploaded-files*` routes through `Backend\AizUploadController`.
- `/admin` redirects to backend login.

`routes/backend.php` owns admin routes:

- `/admin/login`, `/admin/logout`, `/admin/dashboard`.
- All business modules inside `Route::middleware('auth')`.
- Most admin business route names use either `admin.*` or `backend.*`.
- The active sidebar uses `resources/views/backend/partials/aside2.blade.php`.

## 2. Authentication, Layout, Permissions, and Shared Infrastructure

### Auth Flows

Public auth and backend auth are separated:

- Public login/register: `App\Http\Controllers\Auth\AuthController`.
- Backend login/logout: `App\Http\Controllers\Backend\AuthenticateController`.
- Backend dashboard: `App\Http\Controllers\Backend\DashboardController`.
- Password reset: `App\Http\Controllers\Auth\PasswordResetController`.

User profile actions are under the backend users route group:

- `admin.users.profile.show`
- `admin.users.profile.edit`
- `admin.users.profile.update`
- `admin.users.profile.password`

These are handled by `Backend\UserController`.

### Backend Layout and Navigation

The active backend shell is `resources/views/backend/layout/app.blade.php`.

Important layout points:

- Includes `backend.partials.navbar`.
- Includes `backend.partials.aside2`.
- Renders page content through `@yield('content')`.
- Renders modals through `@yield('modal')`.
- Loads shared backend scripts through `backend.partials.assets.scripts`.
- Supports `@stack('styles')`, `@stack('scripts')`, `@yield('page.scripts')`, and `@yield('quickstepform.scripts')`.

The active sidebar, `resources/views/backend/partials/aside2.blade.php`, shows:

- Dashboard.
- Calendar, when the user can `view calendar`.
- Properties, gated by property permissions.
- Contacts, gated by contact permissions.
- Tenancies placeholder link when the user can `manage tenancies`.
- Repair module, gated by property repair permissions.
- Legacy invoices, gated by `view invoices`.
- Documents, transactions, website setup, master management, staff/setup configuration, accounting, and email templates.

Some sidebar links are placeholders (`href="#"`) even though related routes or modules may exist elsewhere.

### Roles and Permissions

Roles and baseline permissions are seeded in `database/seeders/RoleAndPermissionSeeder.php`.

Seeded roles:

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

Seeded permission groups include dashboard/system, properties, tenancies, maintenance, users, documents, finance, communication, staff, and staff roles.

Important implementation detail:

- The seeder defines many lowercase permission names, for example `view properties`.
- The sidebar also checks some title-case or later-added permission names, for example `View Contacts`, `Manage Document Types`, `view property repair`, `manage email templates`, and `view calendar`.
- When debugging missing menu items, compare the permission seeder, any later migrations/seeders, and `@can`/`@canany` checks in `aside2.blade.php`.

### Uploads and AIZ Uploader

Uploads are handled by `Backend\AizUploadController` and the `Upload` model.

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

The backend document modal calls `AIZ.uploader.previewGenerate()` after loading forms and view content.

### Notes and Documents Shared Components

Notes and documents are polymorphic features used by properties and users.

Models:

- `App\Models\Notes`
- `App\Models\Document`
- `App\Models\NoteType`
- `App\Models\DocumentType`

Polymorphic relationships:

- `Property::notes()` uses `morphMany(Notes::class, 'noteable')`.
- `Property::documents()` uses `morphMany(Document::class, 'documentable')`.
- `User::notes()` uses `morphMany(Notes::class, 'noteable')`.
- `User::documents()` uses `morphMany(Document::class, 'documentable')`.

Routes:

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

Both JS files follow the same flow:

- The initial list is server-rendered.
- Filter, reset, save, delete, pagination, and custom refresh events reload list HTML over AJAX.
- Add/edit/view actions load modal content over AJAX.
- Delete actions use the shared `#deleteConfirmModal`.
- Saves submit `#notesForm` or `#documentsForm` via AJAX and refresh the current component.
- Notes reinitialize the AIZ text editor if present.
- Documents reinitialize the AIZ uploader preview.

## 3. Feature Flows

### Frontend, Public Auth, Customer Statement, and Public Forms

Frontend routes:

- `GET /` -> `FrontendController@index`
- `GET /pricing` -> `FrontendController@pricing`
- `GET /login` and `POST /login` -> public auth.
- `GET /register` and `POST /register` -> public registration.
- `POST /logout` -> public logout.
- `GET /customer/statements` -> authenticated customer statement.
- `GET|POST /form/{type}` -> dynamic form display/submission.

Main views:

- `resources/views/frontend/index.blade.php`
- `resources/views/frontend/pricing.blade.php`
- `resources/views/frontend/login.blade.php`
- `resources/views/frontend/register.blade.php`
- `resources/views/frontend/customer/statement.blade.php`

Controller references:

- `App\Http\Controllers\Frontend\FrontendController`
- `App\Http\Controllers\Frontend\CustomerStatementController`
- `App\Http\Controllers\Frontend\FormController`
- `App\Http\Controllers\Auth\AuthController`

### Dashboard

Backend dashboard route:

- `GET /admin/dashboard` -> `DashboardController@dashboard`, route name `backend.dashboard`.

Main view:

- `resources/views/backend/dashboard.blade.php`

The sidebar always links dashboard through `route('backend.dashboard')`.

### Properties

Primary routes:

- `GET /admin/properties` -> list.
- `GET /admin/properties/create` -> full create form.
- `POST /admin/properties/store` -> full create submit.
- `GET /admin/properties/quick-create` -> quick create form.
- `GET /admin/properties/quick_step/{step}` -> quick form step partial.
- `POST /admin/properties/quick-store` -> quick create submit.
- `GET /admin/properties/view/{id}` -> property detail page.
- `GET /admin/properties/edit/{id}` -> edit form.
- `POST /admin/properties/update/{id}` -> update.
- `POST /admin/properties/delete/{id}` -> delete.
- `GET /admin/properties/deleted` -> soft-deleted properties.
- `POST /admin/properties/restore/{id}` and `/bulk-restore`.
- `GET /admin/properties/load-form` and `POST /admin/properties/save-form` for popup/tab forms.
- `GET /admin/properties/ajax` for select-style AJAX search.
- `GET /admin/properties/search-ajax` for property search.

Controller:

- `App\Http\Controllers\Backend\PropertyController`

Main methods:

- `index`
- `create`
- `quick`
- `store`
- `quickStore`
- `getStepView`
- `getQuickStepView`
- `edit`
- `view`
- `update`
- `destroy`
- `showSoftDeletedProperties`
- `restore`
- `bulkRestore`
- `loadForm`
- `saveForm`
- `ajaxList`

Views:

- `resources/views/backend/properties/index.blade.php`
- `resources/views/backend/properties/create.blade.php`
- `resources/views/backend/properties/quick.blade.php`
- `resources/views/backend/properties/view.blade.php`
- `resources/views/backend/properties/edit.blade.php`
- `resources/views/backend/properties/deleted.blade.php`
- `resources/views/backend/properties/form_components/*`
- `resources/views/backend/properties/quick_form_components/*`
- `resources/views/backend/properties/popup_forms/*`
- `resources/views/backend/properties/tabs/*`

Property detail tabs currently include:

- property
- owners
- tenancy and tenancy2
- teams
- offers
- notes
- documents
- compliance
- appointments
- media
- statement
- aps

Property popup forms currently include:

- `property_info`
- `availability_pricing`
- `property_status`
- `property_features`
- `property_services`
- `property_media`
- `property_accessibility`
- `property_compliance`
- `notes`
- `notes_tab`

Model references:

- `App\Models\Property`
- `App\Models\PropertyResponsibility`
- `App\Models\EstateCharge`
- `App\Models\ComplianceRecord`
- `App\Models\Country`
- `App\Models\LocalAuthority`
- `App\Models\Notes`
- `App\Models\Document`
- `App\Models\Event`

Important behavior:

- `PropertyController@index` supports search/filtering and returns the property list view.
- Full and quick create flows load step partials and validate by step.
- `PropertyController@view` prepares the property detail page and tab data.
- `PropertyController@loadForm` and `saveForm` are used by modal/tab edit interactions.
- The property model has polymorphic notes, documents, and events.
- `Property::getFullAddressAttribute()` and `Property::getDisplayLabelAttribute()` provide display labels.

### Contacts and Users

The UI labels this area as `Contacts` in the active sidebar, while controllers and routes still use `users`.

Primary routes:

- `GET /admin/users` -> list contacts/users.
- `GET /admin/users/create` -> create form.
- `GET /admin/users/user_step/{step}` -> step partial.
- `POST /admin/users/store` -> create submit through `userStore`.
- `POST /admin/users/quick-store-user` -> quick user creation.
- `GET /admin/users/properties/search` -> property search for user forms.
- `GET /admin/users/show/{id}` -> detail page.
- `GET /admin/users/edit/{id}` -> edit form.
- `POST /admin/users/update/{id}` -> update.
- `POST /admin/users/delete/{id}` -> delete.
- `GET /admin/users/load-form` and `POST /admin/users/save-form` -> popup/tab forms.
- `GET /admin/users/ajax` -> user search/select endpoint.
- `GET /admin/users/staff-ajax` -> staff user search/select endpoint.
- Profile routes under `/admin/users/profile*`.

Controller:

- `App\Http\Controllers\Backend\UserController`

Main methods:

- `index`
- `create`
- `userStore`
- `getQuickStepView`
- `quicklyStoreUser`
- `show` path is routed, but the controller currently has tab-focused logic through `getTabContent`.
- `edit`
- `update`
- `delete`
- `loadForm`
- `saveForm`
- `ajaxList`
- `staffAjaxList`
- profile methods
- statement CSV helpers

Views:

- `resources/views/backend/users/index.blade.php`
- `resources/views/backend/users/create.blade.php`
- `resources/views/backend/users/edit.blade.php`
- `resources/views/backend/users/profile/*`
- `resources/views/backend/users/user_form/*`
- `resources/views/backend/users/popup_forms/*`
- `resources/views/backend/users/tabs/*`

User tabs currently include:

- user details
- user owner
- linked
- bank details
- compliance
- notes
- documents
- letters
- appointments
- statement

User popup forms currently include:

- `user_detail`
- `bank_detail`
- `compliance`
- `notes`
- `notes_tab`

Model references:

- `App\Models\User`
- `App\Models\UserCategory`
- `App\Models\UserDetail`
- `App\Models\BankDetails`
- `App\Models\BankAccount`
- `App\Models\Tenancy`
- `App\Models\TenantMember`
- `App\Models\Transaction`
- `App\Models\Notes`
- `App\Models\Document`

Important behavior:

- `User` composes `name` from first/middle/last name in model events.
- `User::roles()` points to `model_has_roles` and `Spatie\Permission\Models\Role`.
- User options and AJAX lists are used by forms throughout the app.
- Users can have polymorphic notes, documents, and events.
- `User::getAvailableCreditAttribute()` is based on open advance receipts.

### Tenancies

Primary routes:

- `GET /admin/tenancies/create`
- `POST /admin/tenancies/store`
- `GET /admin/tenancies/{id}/rent-ledger`
- `GET /admin/tenancies/{id}`
- `GET /admin/tenancies/{id}/edit`
- `POST /admin/tenancies/{id}/update`
- `POST /admin/tenancies/{id}/delete`
- `GET /admin/properties/{propertyId}/tenancies`

Controller:

- `App\Http\Controllers\Backend\TenancyController`

Views:

- `resources/views/backend/tenancies/create.blade.php`
- `resources/views/backend/tenancies/edit.blade.php`
- `resources/views/backend/tenancies/show.blade.php`
- `resources/views/backend/tenancies/rent-ledger.blade.php`
- Property tabs also show tenancy information.

Model references:

- `App\Models\Tenancy`
- `App\Models\TenantMember`
- `App\Models\TenancyType`
- `App\Models\TenancySubStatus`
- `App\Models\Property`
- `App\Models\Offer`
- `App\Models\User`

Important behavior:

- `Tenancy` belongs to a property and offer.
- `Tenancy` has many tenant members.
- `Tenancy` belongs to tenancy type and sub-status.
- `Tenancy` is linked to property managers through `property_manager_tenancy`.

### Offers, Owner Groups, Estate Charges, and Compliance

Offers:

- Routes under `/admin/offers`.
- Controller: `Backend\OfferController`.
- Views: `resources/views/backend/offers/*`.
- Additional actions: `setMainPerson` and `updateStatus`.
- Model: `App\Models\Offer`.
- Property tabs include offers.

Owner groups:

- Routes under `/admin/owner-groups`.
- Controller: `Backend\OwnerGroupController`.
- Views: `resources/views/backend/owner_groups/*`.
- Models: `OwnerGroup`, `OwnerGroupUser`, `User`.
- Supports subgroup create/update/delete and main group update.

Estate charges:

- Routes under `/admin/estate-charges` and `/admin/estate-charges-items`.
- Controllers: `EstateChargeController`, `EstateChargeItemController`.
- Views: `resources/views/backend/estate_charges/*`.
- Models: `EstateCharge`, `EstateChargeItem`.
- Properties can belong to an estate charge through `Property::estateCharge()`.

Compliance:

- Routes under `/admin/compliance`.
- Controller: `Backend\ComplianceController`.
- Views: `resources/views/backend/compliance/*`.
- Models: `ComplianceType`, `ComplianceRecord`, `ComplianceDetail`.
- Form partials exist for EICR, EPC, gas, landlord registration, and common fields.
- Properties and users both have compliance UI surfaces.

### Repairs, Work Orders, and Legacy Invoices

Repair routes:

- `GET /admin/property-repairs/issue-list`
- `GET /admin/property-repairs/raise-repair-issue-create`
- `POST /admin/property-repairs/raise-issue-store`
- `GET /admin/property-repairs/repair-show/{id}`
- `GET /admin/property-repairs/repair-edit/{id}/edit`
- `PUT /admin/property-repairs/repair-update/{id}`
- `DELETE /admin/property-repairs/repair-delete/{id}`
- `POST /admin/property-repairs/repair/check-last-step`
- `GET /admin/property-repairs/repair-category/{categoryId}/subcategories`
- `GET /admin/property-repairs/get-repair-categories`
- `GET /admin/property-repairs/selected-property/tenants`
- `GET /admin/property-repairs/repair/{repair}/workorder-invoice`
- `GET /admin/property-repairs/load-form`
- `POST /admin/property-repairs/save-form`
- `GET /admin/property-repairs/ajax`

Controller:

- `App\Http\Controllers\Backend\PropertyRepairController`

Views:

- `resources/views/backend/repair/index.blade.php`
- `resources/views/backend/repair/create_raise_issue.blade.php`
- `resources/views/backend/repair/edit_raise_issue.blade.php`
- `resources/views/backend/repair/view_raise_issue.blade.php`
- `resources/views/backend/repair/detail/show.blade.php`
- `resources/views/backend/repair/list/*`
- `resources/views/backend/repair/popup_forms/*`
- `resources/views/backend/repair/workorder-invoice.blade.php`

Repair statuses visible in the sidebar:

- Pending
- Reported
- Under Process
- Work Completed
- Invoice Received
- Invoice Paid
- Closed

Model references:

- `RepairIssue`
- `RepairCategory`
- `RepairPhoto`
- `RepairAssignment`
- `RepairHistory`
- `RepairIssuePropertyManager`
- `RepairIssueContractorAssignment`
- `RepairIssueUser`
- `WorkOrder`
- `Invoice`

Important behavior:

- `RepairIssue` belongs to property and category.
- `RepairIssue` has photos, assignments, histories, property-manager assignments, contractor assignments, users, a final contractor, a work order, and an invoice.
- Repair forms support step checking and dynamic category/subcategory loading.
- Repair popup forms handle assignment, final contractor, property issue details, repair history, work order details, and invoice details.

Work orders:

- `POST /admin/work-orders/store` -> create/update work order.
- `GET /admin/work-orders/get/{repairIssueId}` -> fetch by repair issue.
- `GET /admin/work-orders/generate-pdf/{id}` -> PDF generation.
- Controller: `Backend\WorkOrderController`.
- Views: `resources/views/backend/work_orders/*`.

Legacy repair invoices:

- Routes under `/admin/invoices`.
- Controller: `Backend\InvoiceController`.
- Views: `resources/views/backend/invoices/*`.
- Supports generating from work order, viewing, downloading, marking paid, editing/updating, search, and JSON fetch.
- This is separate from the newer accounting sale invoice module under `/admin/accounting/sale/invoices`.

### Calendar and Events

Calendar routes:

- `GET /admin/calendar` -> calendar view.
- `GET /admin/calendar/instances` -> event instance list.
- `POST /admin/calendar/instances/store`
- `POST /admin/calendar/instances/update/{instance}`
- `PUT /admin/calendar/master/update/{event}`
- `POST /admin/calendar/instances/cancel/{id}`
- `POST /admin/calendar/instances/delete/{id}`
- `POST /admin/calendar/instances/change-status/{id}`

Controller:

- `App\Http\Controllers\Backend\EventController`

Views:

- `resources/views/backend/events/calendar.blade.php`
- `resources/views/backend/events/modal.blade.php`
- `resources/views/backend/partials/calendar.blade.php`
- `resources/views/backend/partials/_calendar_modals.blade.php`

Event type routes:

- `Route::resource('event-types', EventTypeController::class)` named `backend.event_types.*`.
- `Route::resource('event-sub-types', EventSubTypeController::class)` named `backend.event_sub_types.*`.
- `GET /admin/api/event-sub-types/{typeId}` returns subtypes by type.

Models:

- `Event`
- `EventInstance`
- `EventInstanceChange`
- `EventReminder`
- `EventType`
- `EventSubType`

Background commands:

- `events:generate-future {days=30}` keeps future instances populated.
- `app:generate-recurring-events` generates recurring event instances using RRULE data.
- `events:send-reminders` sends due event reminders.

### Transactions and Master Data

Transactions:

- `Route::resource('transactions', TransactionController::class)` named `backend.transactions.*`.
- `Route::resource('transaction-categories', TransactionCategoryController::class)` named `backend.transaction_categories.*`.
- Views under `resources/views/backend/transactions/*` and `resources/views/backend/transaction_categories/*`.
- Models: `Transaction`, `TransactionCategory`.

Master data managed through backend routes and sidebar:

- User categories: `Route::resource('user-categories', UserCategoryController::class)`.
- Branches: `/admin/branches`.
- Designations: `/admin/designations`.
- Note types: `/admin/note-types`.
- Document types: `/admin/document-types`.
- Tenancy types: `/admin/tenancy-types`.
- Tenancy sub statuses: `/admin/tenancy-sub-statuses`.
- Event types and event subtypes.
- Job types: `/admin/job-types`.
- Transaction categories.

### Website Setup, System Setup, Staff, Roles, and Email Templates

Website setup:

- Routes named `website.footer`, `website.header`, and `website.appearance`.
- Controller: `Backend\WebsiteController`.
- Views: `resources/views/backend/website_settings/*`.

Business/system setup:

- `POST /admin/business-settings/update` -> `BusinessSettingsController@update`.
- `GET /admin/smtp-settings` -> `BusinessSettingsController@smtp_settings`.
- `POST /admin/env_key_update` -> `BusinessSettingsController@env_key_update`.
- `POST /admin/test/smtp` -> `BusinessSettingsController@testEmail`.
- View: `resources/views/backend/setup_configurations/smtp_settings.blade.php`.

Roles:

- `Route::resource('roles', RoleController::class)`.
- Extra routes for edit, destroy, and `roles/add_permission`.
- Controller: `Backend\RoleController`.
- Views: `resources/views/backend/staff/staff_roles/*`.

Staff:

- `Route::resource('staffs', StaffController::class)`.
- Extra destroy route: `/admin/staffs/destroy/{id}`.
- Controller: `Backend\StaffController`.
- Views: `resources/views/backend/staff/staffs/*`.

Email templates:

- `Route::resource('email-templates', EmailTemplateController::class)`.
- Extra index route: `/admin/email-template/{id}`.
- Status update route: `/admin/email-template/update-status`.
- Controller: `Backend\EmailTemplateController`.
- Views: `resources/views/backend/setup_configurations/email_templates/*`.
- Model: `EmailTemplate`.

## 4. Accounting Flow

The accounting module lives under `/admin/accounting` and uses route names under `backend.accounting.*`.

### Accounting Navigation

The active sidebar exposes:

- Masters.
- Sale invoices and credit notes.
- Receipts.
- Customer statements.
- Account ledger.
- Reports.
- Payments.

The route file also defines routes for:

- General ledger accounts, balances, journals, and journal lines.
- Purchase invoices and debit notes.
- Bank reconciliation.
- Fixed assets.

Some of these route-defined areas are commented out in the visible sidebar, but the backend routes and controllers exist.

### Accounting Masters

Routes:

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

- `GlJournal` has active/reversal relationships and helpers for issue journals.
- `GlJournal` has many lines.
- Posting services create/update/delete journals and maintain balances.
- Default GL account mappings are read from `BusinessSetting` values such as default AR, AP, revenue, and expense account IDs.

### Sale Invoices

Routes:

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

- `App\Http\Controllers\Backend\Accounting\Sale\SaleInvoiceController`

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

Service references:

- `SaleInvoiceLifecycleService`
- `SaleInvoicePenaltyService`
- `PostingService`

Important behavior:

- `SaleInvoiceLifecycleService::nextInvoiceNo()` uses the `sale_invoice_prefix` business setting and the current max invoice ID.
- `SaleInvoiceLifecycleService::persistItems()` recalculates subtotal, tax, total, and balance from submitted items.
- `SaleInvoiceLifecycleService::postInvoiceIfNeeded()` posts or removes the sale invoice issue journal depending on status.
- The sale invoice controller handles recurring invoice information, notification information, PDF generation, Stripe checkout, marking paid, advances, applying credit, undoing credit, and AJAX selection helpers.
- `SysSaleInvoice` has item, receipt, payment, customer, invoice header, GL journal, `linkTo`, and `chargeTo` relationships.

### Credit Notes, Purchase Invoices, Debit Notes, Receipts, and Payments

Credit notes:

- Routes under `/admin/accounting/sale/credit-notes`.
- Controller: `Accounting\Sale\CreditNoteController`.
- Views: `resources/views/backend/accounting/sale/credit_notes/*`.
- Model: `CreditNote`.

Purchase invoices:

- Routes under `/admin/accounting/purchase/invoices`.
- Controller: `Accounting\Purchase\PurchaseInvoiceController`.
- Views: `resources/views/backend/accounting/purchase/invoices/*`.
- Models: `SysPurchaseInvoice`, `SysPurchaseInvoiceItem`.

Debit notes:

- Routes under `/admin/accounting/purchase/debit-notes`.
- Controller: `Accounting\Purchase\DebitNoteController`.
- Views: `resources/views/backend/accounting/purchase/debit_notes/*`.
- Model: `DebitNote`.

Receipts:

- Routes under `/admin/accounting/receipts`.
- PDF route: `/admin/accounting/receipts/{receipt}/pdf`.
- Controller: `Accounting\Receipts\ReceiptController`.
- Views: `resources/views/backend/accounting/receipts/*`.
- Model: `SysReceipt`.

Payments:

- Routes under `/admin/accounting/payments`.
- Extra list modes: `all-transactions`, `incomes`, `expenses`, `general-entry`.
- Controller: `Accounting\Payments\PaymentController`.
- Views: `resources/views/backend/accounting/payments/*`.
- Model: `SysPayment`.

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

Statement service behavior:

- Builds customer/contact statements from GL journal lines.
- Builds account ledgers with opening, running, and closing balances.
- Supports property statements by resolving tenant users and property-linked sale invoices.

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
- Controller: `Accounting\BankReconciliationController`.
- View: `resources/views/backend/accounting/bank_reconciliation/index.blade.php`.
- Models: `BankReconciliation`, `BankReconciliationLine`.

Fixed assets:

- `Route::resource('fixed-assets', FixedAssetController::class)->names('fixed_assets')`.
- Controller: `Accounting\FixedAssetController`.
- Model: `FixedAsset`.

### Accounting Posting Rules

`App\Services\Accounting\PostingService` is the core GL posting service.

Current posting flows include:

- Sale invoice issue: debit AR, credit revenue or penalty income split when penalties are applied.
- Purchase invoice issue: debit expense, credit AP.
- Advance receipt: debit bank/cash, credit advances.
- Applying credit to sale invoice: debit advances, credit AR.
- Stripe payment: debit bank/cash, credit AR.
- Purchase payment: debit AP, credit bank/cash.
- Undo credit: reverse the apply-credit posting.
- Updating sale and purchase issue journals after invoice edits.
- Deleting journals and reversing balances.

Default accounts are resolved through `BusinessSetting` keys and GL account codes.

### Recurring Invoices, Penalties, Reminders, and Notifications

Commands:

- `sale-invoices:generate-recurring` generates future invoices from recurring sale invoice masters.
- `sale-invoices:apply-penalties` applies late payment penalties to eligible sale invoices.
- `sale-invoices:send-reminders` sends due reminders.
- `sale-invoices:send-overdue-reminders` sends overdue reminders.
- `sale-invoices:send-unsent-emails` sends queued/unsent invoice emails.
- `sale-invoices:recurring-smoke-test` smoke-tests recurring invoice scenarios.
- `sale-invoices:penalty-smoke-test` smoke-tests penalty scenarios.

Notification flow:

- `NotificationService::trigger()` looks up active email templates by identifier.
- If the `sms_templates` table exists, it also loads active SMS templates.
- Optional WhatsApp/system channels depend on `config/notification_system.php`.
- For every template/channel, it creates a `NotificationLog`.
- It dispatches `SendNotificationJob` after the database commit.
- `notifications:retry {--limit=500}` retries failed or pending notification logs.

Models:

- `EmailTemplate`
- `NotificationLog`
- optional `sms_templates` table

## 5. Data Model Highlights

This is not a full schema reference. Use migrations in `database/migrations` for column-level detail.

Core entities:

- `User` is the contact/account actor and can represent owner, tenant, property manager, contractor, staff, and other roles through Spatie roles and local category data.
- `Property` is the central property record. It links to compliance records, estate charges, notes, documents, events, and local authority/country data.
- `Tenancy` links properties, tenant members, tenancy type, tenancy sub-status, offers, and property managers.
- `RepairIssue` links a property, tenant, category, photos, assignments, histories, contractor assignments, work order, and invoice.
- `Notes` and `Document` are reusable polymorphic records.
- `Event` and `EventInstance` handle calendar master records and generated/changed occurrences.
- `SysSaleInvoice`, `SysPurchaseInvoice`, `SysReceipt`, `SysPayment`, and GL models handle the newer accounting system.
- `Invoice`, `InvoiceItems`, and `InvoiceStatuses` support the legacy repair/work-order invoice flow.

Seeder highlights:

- `DatabaseSeeder` coordinates seeders.
- Role/permission seeders define baseline access.
- Accounting seeders define GL and system accounting defaults.
- Property, tenancy, repair, event, and lookup seeders provide sample/master data.

## 6. Background and Operational Flows

Registered command classes exist under `app/Console/Commands`. `routes/console.php` currently only defines the default `inspire` closure command, so production scheduling should be checked in the deployment scheduler, Laravel bootstrap configuration, or any external cron/process manager.

Operational commands to know:

- `events:generate-future {days=30}`
- `app:generate-recurring-events`
- `events:send-reminders`
- `sale-invoices:generate-recurring`
- `sale-invoices:apply-penalties`
- `sale-invoices:send-reminders`
- `sale-invoices:send-overdue-reminders`
- `sale-invoices:send-unsent-emails`
- `notifications:retry {--limit=500}`
- `sale-invoices:recurring-smoke-test`
- `sale-invoices:penalty-smoke-test`

Queue-related behavior:

- Notifications dispatch `SendNotificationJob`.
- `composer.json` dev script starts `php artisan queue:listen --tries=1`.
- If notifications are not sending, check queue worker status, `notification_logs`, email templates, and mail/SMS configuration.

## 7. Where to Change Things

### Add or Change a Backend Menu Item

Change:

- `resources/views/backend/partials/aside2.blade.php`

Also check:

- Route name in `routes/backend.php`.
- Permission name in seeders/migrations.
- Active-route checks using `request()->routeIs()` or `areActiveRoutes()`.

### Add a Backend Page

Usually change:

- `routes/backend.php`
- A controller under `app/Http/Controllers/Backend` or `app/Http/Controllers/Backend/Accounting`
- Views under `resources/views/backend`
- Sidebar only if it should be user-visible navigation.

### Add a Property Form Step or Popup Section

Usually change:

- `PropertyController@getStepView`
- `PropertyController@getQuickStepView`
- `PropertyController@getValidationRules`
- `PropertyController@getValidationRulesQuick`
- `PropertyController@loadForm`
- `PropertyController@saveForm`
- Property form/popup partials under `resources/views/backend/properties`

### Add a User/Contact Form Step or Popup Section

Usually change:

- `UserController@getQuickStepView`
- `UserController@getValidationRulesQuick`
- `UserController@loadForm`
- `UserController@saveForm`
- User form/popup partials under `resources/views/backend/users`

### Add a Shared Notes or Documents Surface

Usually change:

- Add `notes-component` or `documents-component` markup with the correct polymorphic type and ID.
- Ensure `common-notes.js` or `common-documents.js` is loaded.
- Ensure modals `#notesModal`, `#documentsModal`, and shared delete modal exist on the page.
- Confirm the target model has the relevant `morphMany` relationship.

### Change Repair Status or Workflow

Usually change:

- `PropertyRepairController`
- Repair views under `resources/views/backend/repair`
- Sidebar status list in `aside2.blade.php`
- `RepairIssue` model and repair migrations if data shape changes.

### Change Work Order or Repair Invoice Output

Usually change:

- `WorkOrderController`
- `InvoiceController`
- Views under `resources/views/backend/work_orders`
- Views under `resources/views/backend/invoices`
- Repair popup forms if the UI is launched from a repair detail page.

### Change Sale Invoice Posting

Usually change:

- `SaleInvoiceController`
- `SaleInvoiceLifecycleService`
- `PostingService`
- `SysSaleInvoice` and `SysSaleInvoiceItem`
- Accounting sale invoice views under `resources/views/backend/accounting/sale/invoices`
- Relevant migrations if fields change.

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
- `EmailTemplateController`
- Email template views and seed data.
- `config/notification_system.php`.
- Notification commands under `app/Console/Commands`.

### Change Roles and Permissions

Usually change:

- `database/seeders/RoleAndPermissionSeeder.php`
- Permission migrations or additional seeders if they exist.
- Sidebar gates in `aside2.blade.php`.
- Controller middleware/policies if introduced later.

### Change Upload Behavior

Usually change:

- `AizUploadController`
- `Upload` model
- Uploader Blade views under `resources/views/uploader`
- Shared scripts under `public/asset/backend/js/aiz-core.js` and uploader assets.

## 8. Verification Checklist for Future Changes

Use this checklist when changing flows described in this document:

- Run `php artisan route:list` and confirm route names used by Blade views still exist.
- Check `resources/views/backend/partials/aside2.blade.php` for permission gates and active route checks.
- Confirm any changed model relationship is used consistently by controllers and views.
- For AJAX modals, confirm the modal ID, form ID, component wrapper, data attributes, CSRF token, and refresh event match the shared JS.
- For accounting changes, verify GL posting creates balanced journal lines and updates balances.
- For recurring invoice or penalty changes, run the smoke commands if database state allows it.
- For notification changes, verify template identifiers, `notification_logs`, and queue worker behavior.
- For PDF changes, test both browser view and download routes.

