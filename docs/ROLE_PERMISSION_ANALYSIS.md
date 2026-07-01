# Role & Permission Technical Analysis

Generated from the current Laravel codebase and local database state.

## Executive Summary

This application uses **Spatie Laravel Permission** (`spatie/laravel-permission` v6.x) as the active role/permission system, but it also contains legacy/custom role code that is still referenced in places.

Key points:

- Spatie is installed in `composer.json`, configured in `config/permission.php`, registered in `bootstrap/app.php`, and enabled on `App\Models\User` through `Spatie\Permission\Traits\HasRoles`.
- Active Spatie tables are `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, and `role_has_permissions`.
- The application adds a custom staff designation permission layer through `designations`, `staff`, and `designation_has_permissions`.
- `AppServiceProvider` dynamically registers a Laravel Gate for every row in `permissions`.
- `Super Admin` bypasses all Gate checks through `Gate::before`.
- A legacy/custom `App\Models\Role`, `App\Models\Permission`, `users.role_id`, `App\Http\Middleware\backendAuthenticate`, and `App\Http\Middleware\RoleMiddleware` still exist or are referenced.
- The live database currently has **no `users.role_id` column**, but code still references `role_id` in login redirects, middleware, and contractor filtering.
- `database/migrations/2024_10_24_115128_create_roles_table.php` and `database/migrations/2025_07_05_180135_create_permission_tables.php` both attempt to create a `roles` table. A fresh migration run can fail unless this has been handled outside the visible migrations.
- Most admin routes are protected only by `auth`; only Staff, Staff Roles, and Email Template routes have active Spatie `permission:` middleware. Many menu items use Blade `@can`, but matching controller or route-level authorization is missing.

## Spatie vs Custom Implementation

### Spatie Evidence

`composer.json`:

```json
"spatie/laravel-permission": "^6.20"
```

`config/permission.php`:

```php
'permission' => Spatie\Permission\Models\Permission::class,
'role' => Spatie\Permission\Models\Role::class,
```

`app/Models/User.php`:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable;
    use HasRoles;
    use TracksUser;
}
```

`bootstrap/app.php`:

```php
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

'permission' => PermissionMiddleware::class,
'role'       => RoleMiddleware::class,
```

### Custom/Legacy Evidence

`app/Models/Role.php` is a custom Eloquent model:

```php
class Role extends Model
{
    protected $fillable = ['name'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
```

`app/Models/Permission.php` is a custom empty model:

```php
class Permission extends Model
{
  //
}
```

`app/Http/Middleware/backendAuthenticate.php` uses a legacy role ID check:

```php
if(isset(auth()->user()->id) && auth()->user()->id && auth()->user()->role_id == 1):
    return $next($request);
```

`app/Http/Middleware/RoleMiddleware.php` now uses Spatie `hasAnyRole`, but still contains commented legacy `role_id` logic.

## Database Structure

The following structure was taken from the local database with `SHOW COLUMNS`.

### `roles`

Spatie role table.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| id | bigint unsigned | NO | PRI |  |
| name | varchar(191) | NO | MUL |  |
| guard_name | varchar(191) | NO |  |  |
| created_at | timestamp | YES |  |  |
| updated_at | timestamp | YES |  |  |

Indexes:

- `PRIMARY(id)`
- `roles_name_guard_name_unique(name, guard_name)`

Migration conflict:

- `database/migrations/2024_10_24_115128_create_roles_table.php` creates `roles` with only `id`, `name`, timestamps.
- `database/migrations/2025_07_05_180135_create_permission_tables.php` also creates `roles` with Spatie columns.

### `permissions`

Spatie permission table with an extra live `section` column.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| id | bigint unsigned | NO | PRI |  |
| name | varchar(191) | NO | MUL |  |
| section | varchar(50) | YES |  |  |
| guard_name | varchar(191) | NO |  |  |
| created_at | timestamp | YES |  |  |
| updated_at | timestamp | YES |  |  |

Indexes:

- `PRIMARY(id)`
- `permissions_name_guard_name_unique(name, guard_name)`

Important drift:

- No migration in `database/migrations` was found that adds `permissions.section`.
- Several views group by `section`, for example `resources/views/backend/staff/staff_roles/create.blade.php`.

### `model_has_roles`

Spatie polymorphic pivot between users/models and roles.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| role_id | bigint unsigned | NO | PRI |  |
| model_type | varchar(255) | NO | PRI | `App\Models\User` |
| model_id | bigint unsigned | NO | PRI |  |

Indexes:

- `PRIMARY(role_id, model_id, model_type)`
- `model_has_roles_model_id_model_type_index(model_id, model_type)`

Relationship:

- `model_has_roles.role_id` -> `roles.id`
- `model_has_roles.model_id` + `model_type` -> `users.id` when `model_type = App\Models\User`

### `model_has_permissions`

Spatie direct permission pivot between users/models and permissions.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| permission_id | bigint unsigned | NO | PRI |  |
| model_type | varchar(191) | NO | PRI |  |
| model_id | bigint unsigned | NO | PRI |  |

Indexes:

- `PRIMARY(permission_id, model_id, model_type)`
- `model_has_permissions_model_id_model_type_index(model_id, model_type)`

Relationship:

- `model_has_permissions.permission_id` -> `permissions.id`
- `model_has_permissions.model_id` + `model_type` -> `users.id` for direct user permissions.

### `role_has_permissions`

Spatie pivot between roles and permissions.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| permission_id | bigint unsigned | NO | PRI |  |
| role_id | bigint unsigned | NO | PRI |  |

Indexes:

- `PRIMARY(permission_id, role_id)`
- `role_has_permissions_role_id_foreign(role_id)`

Relationship:

- `role_has_permissions.role_id` -> `roles.id`
- `role_has_permissions.permission_id` -> `permissions.id`

### `designation_has_permissions`

Custom pivot for staff designation permissions.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| designation_id | bigint unsigned | NO | PRI |  |
| permission_id | bigint unsigned | NO | PRI |  |
| created_at | timestamp | YES |  |  |
| updated_at | timestamp | YES |  |  |

Indexes:

- `PRIMARY(designation_id, permission_id)`
- `designation_has_permissions_permission_id_foreign(permission_id)`

Migration:

- `database/migrations/2026_05_15_000012_create_designation_has_permissions_and_drop_staff_role_id.php`

Relationship:

- `designation_id` -> `designations.id`
- `permission_id` -> `permissions.id`

### `users`

Relevant user access columns in the live database:

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| id | bigint unsigned | NO | PRI |  |
| company_id | bigint unsigned | YES | MUL |  |
| branch_id | bigint unsigned | YES | MUL |  |
| designation_id | bigint unsigned | YES | MUL |  |
| user_type | varchar(20) | YES |  |  |
| email | varchar(191) | YES | UNI |  |
| status | tinyint(1) | NO |  | 1 |
| can_login | tinyint(1) | NO |  | 0 |

Important drift:

- The live `users` table does **not** contain `role_id`.
- Code still references `role_id` in multiple places.

### `staff`

Custom staff access layer.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI |  |
| user_id | int | NO |  |  |
| permissions_customized | tinyint(1) | NO |  | 0 |
| branch_id | bigint unsigned | YES | MUL |  |
| created_at | timestamp | NO |  | current_timestamp() |
| updated_at | timestamp | NO |  | current_timestamp() |

Relationship:

- `staff.user_id` -> intended `users.id`, but no foreign key was shown in the local indexes.
- `staff.branch_id` -> `branches.id`
- `staff.permissions_customized` determines whether direct user permissions override designation permissions.

### `designations`

Custom staff designation table.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| id | bigint unsigned | NO | PRI |  |
| title | varchar(191) | NO | UNI |  |
| created_at | timestamp | YES |  |  |
| updated_at | timestamp | YES |  |  |

## Relationship Model

Primary Spatie flow:

```text
users.id
  -> model_has_roles.model_id
  -> model_has_roles.role_id
  -> roles.id
  -> role_has_permissions.role_id
  -> role_has_permissions.permission_id
  -> permissions.id
```

Direct user permission flow:

```text
users.id
  -> model_has_permissions.model_id
  -> model_has_permissions.permission_id
  -> permissions.id
```

Staff designation flow:

```text
users.designation_id
  -> designations.id
  -> designation_has_permissions.designation_id
  -> designation_has_permissions.permission_id
  -> permissions.id
```

Effective permission resolver:

`app/Models/User.php`:

```php
public function hasEffectivePermission(string $permissionName): bool
{
    if ($this->isStaffAccount()) {
        if ($this->hasCustomizedStaffPermissions()) {
            return $this->getDirectPermissions()->contains('name', $permissionName);
        }

        if ($this->designation_id) {
            return $this->hasDesignationPermission($permissionName);
        }
    }

    return $this->hasPermissionTo($permissionName)
        || $this->hasDesignationPermission($permissionName);
}
```

## Complete Permission Flow

### 1. User Login

Frontend login:

`app/Http/Controllers/Auth/AuthController.php`

```php
if (Auth::attempt($request->only('email', 'password'), $remember)) {
    $user = Auth::user();

    if ($user->hasRole('Tenant')) {
        return redirect()->route('backend.home');
    }

    if ($user->hasRole('Contractor')) {
        return redirect()->route('backend.home');
    }

    if ($user->hasAnyRole([
        'Super Admin',
        'Owner',
        'Property Manager',
        'Landlord',
        'Estate Agent',
        'Agent',
        'Staff',
        'Test',
    ])) {
        return redirect()->route('backend.dashboard');
    }
}
```

Backend login:

`app/Http/Controllers/Backend/AuthenticateController.php`

```php
$backendRoles = [
    'Super Admin', 'Owner', 'Property Manager',
    'Landlord', 'Staff', 'Estate Agent', 'Agent', 'Test', 'Contractor',
];

if ($user->hasAnyRole($backendRoles)) {
    return redirect()->route('backend.dashboard');
}
```

Legacy commented login still references `role_id`:

`app/Http/Controllers/Auth/AuthController.php`

```php
if (in_array($user->role_id, [1, 2, 3])) {
    return redirect()->route('backend.dashboard');
}
```

### 2. Role Assignment

Users created from seeders:

`database/seeders/UserSeeder.php`

```php
$user->assignRole($u['role']);
```

Users created/updated from backend:

`app/Http/Controllers/Backend/UserController.php`

```php
$roles = Role::whereIn('id', $request->role_ids)->pluck('name')->toArray();
$user->syncRoles($roles);
```

Staff users:

`app/Http/Controllers/Backend/StaffController.php`

```php
Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);
$user->syncRoles(['Staff']);
```

Registration approval:

`app/Http/Controllers/Backend/RegistrationController.php`

```php
DB::table('model_has_roles')->insert([
    'role_id'    => $role->id,
    'model_type' => get_class($user),
    'model_id'   => $user->id,
]);
```

This bypasses Spatie's `assignRole()` API and can bypass events/cache handling.

Contractor quote creation:

`app/Http/Controllers/Backend/PropertyRepairController.php`

```php
$role = Role::where('name', 'Contractor')->first();
if ($role && ! $contractor->hasRole($role->name)) {
    $contractor->assignRole($role);
}
```

### 3. Permissions Assigned to Roles

Role management:

`app/Http/Controllers/Backend/RoleController.php`

```php
$perms = Permission::whereIn('id', $request->permissions)->get();
$role->syncPermissions($perms);
```

Seeder:

`database/seeders/RoleAndPermissionSeeder.php`

```php
$role = Role::firstOrCreate(['name' => $roleName]);
$role->syncPermissions(Permission::all());
```

Tenant extra permissions:

`database/seeders/TenantPermissionsSeeder.php`

```php
$role = Role::findByName('Tenant');
$role->givePermissionTo($permissions);
```

### 4. Direct User Permissions

Registration custom permissions:

`app/Http/Controllers/Backend/RegistrationController.php`

```php
$registration->user->syncPermissions($request->permissions ?? []);
```

Staff customized permissions:

`app/Http/Controllers/Backend/StaffController.php`

```php
$user->syncPermissions(
    $permissionsCustomized
        ? Permission::whereIn('id', $selectedPermissionIds)->get()
        : []
);
```

### 5. Gate Registration and Checks

`app/Providers/AppServiceProvider.php`

```php
Gate::before(function ($user, $ability) {
    return $user->isSuperAdmin() ? true : null;
});

$permissions = cache()->rememberForever('all_permissions', fn() => Permission::all());

foreach ($permissions as $permission) {
    Gate::define($permission->name, function ($user) use ($permission) {
        return $user->hasEffectivePermission($permission->name);
    });
}
```

Implications:

- `Super Admin` can pass any Gate, even if the role currently has no rows in `role_has_permissions`.
- All permission names are cached forever under `all_permissions`.
- Some migrations clear `all_permissions`, but `RoleController::add_permission()` does not clear it. Newly created permissions may not become Gates until cache is cleared.

### 6. Middleware Checks

Active aliases:

`bootstrap/app.php`

```php
'can' => \Illuminate\Auth\Middleware\Authorize::class,
'permission' => PermissionMiddleware::class,
'role'       => RoleMiddleware::class,
```

Active controller middleware:

`app/Http/Controllers/Backend/StaffController.php`

```php
$this->middleware(['permission:view all staffs'])->only('index');
$this->middleware(['permission:add staff'])->only('create');
$this->middleware(['permission:edit staff'])->only('edit');
$this->middleware(['permission:delete staff'])->only('destroy');
```

`app/Http/Controllers/Backend/RoleController.php`

```php
$this->middleware(['permission:view staff roles'])->only('index');
$this->middleware(['permission:add staff role'])->only('create');
$this->middleware(['permission:edit staff role'])->only('edit');
$this->middleware(['permission:delete staff role'])->only('destroy');
```

`app/Http/Controllers/Backend/EmailTemplateController.php`

```php
$this->middleware(['permission:manage_email_templates'])->only('index', 'edit', 'update');
```

Important mismatch:

- The database permission is `manage email templates`.
- The controller middleware checks `manage_email_templates`.
- The menu checks `manage email templates`.
- Non-Super Admin users with the visible menu permission will not satisfy the controller middleware unless the underscore permission also exists.

### 7. Policy Checks

Policies exist:

- `app/Policies/PropertyPolicy.php`
- `app/Policies/TenancyPolicy.php`
- `app/Policies/UserPolicy.php`

They use role checks like:

```php
return $user->hasAnyRole(['Super Admin','Property Manager','Landlord']);
```

No `AuthServiceProvider` exists in this Laravel 11 project. Policy auto-discovery may apply if naming conventions match, but most controller code does not use these policies directly.

### 8. Controller Checks

Dashboard:

`app/Http/Controllers/Backend/DashboardController.php`

```php
$this->authorize('view dashboard');

if (!$user->hasAnyRole(['Super Admin', 'Landlord', 'Staff', 'Property Manager', 'Estate Agent', 'Test'])) {
    abort(403);
}
```

Company owner transfer:

`app/Http/Controllers/Backend/CompanyController.php`

```php
if (! $authUser || (! $authUser->can('transfer company owner') && (int) $company->owner_user_id !== (int) $authUser->id)) {
    abort(403, 'Unauthorized to transfer this company.');
}
```

Property listing/access:

`app/Http/Controllers/Backend/PropertyController.php`

```php
if ($user->hasRole('Property Manager') || $user->hasRole('Super Admin')) {
    $properties = $propertiesQuery->orderBy('id', 'desc')->paginate(15);
} elseif ($user->hasRole('Tenant')) {
    // active tenancy properties only
} elseif ($user->hasRole('Landlord') || $user->hasRole('Staff') || $user->hasRole('Test')) {
    $properties = $propertiesQuery->where('created_by', $user->id)->paginate(15);
}
```

Property tabs:

```php
foreach ($availableTabs as $permission => $name) {
    if ($user->can($permission)) {
        $tabs[] = ['name' => $name];
    }
}
```

### 9. Blade Checks

Main menu:

`resources/views/backend/partials/aside2.blade.php`

Examples:

```blade
@canany(['view properties', 'edit properties', 'create properties'])
```

```blade
@can('view invoices')
```

```blade
@unless(auth()->user()->hasRole('Tenant') || auth()->user()->hasRole('Contractor'))
```

Other Blade checks:

- `resources/views/backend/properties/index.blade.php`: `@can('create properties')`
- `resources/views/backend/properties/tabs/property.blade.php`: `@can('delete properties')`, `@can('edit properties')`
- `resources/views/backend/staff/staff_roles/index.blade.php`: `@can('add staff role')`, `auth()->user()->can('delete staff role')`
- `resources/views/backend/staff/staffs/index.blade.php`: `@can('add staff')`, `@can('edit staff')`, `@can('delete staff')`
- `resources/views/backend/dashboard.blade.php`: `@canany(['view properties', 'edit properties'])`, `@can('view invoices')`

## File-by-File Analysis

### Configuration and Bootstrap

| File | Purpose |
| --- | --- |
| `composer.json` | Installs `spatie/laravel-permission`. |
| `config/permission.php` | Spatie models, table names, cache settings. |
| `bootstrap/app.php` | Registers `permission`, `role`, and `can` middleware aliases. |
| `app/Providers/AppServiceProvider.php` | Registers `Gate::before` for Super Admin and dynamic Gates from `permissions`. |
| `app/Providers/RouteServiceProvider.php` | Loads `routes/web.php` and `routes/backend.php` with `web` middleware. |

### Models

| File | Purpose |
| --- | --- |
| `app/Models/User.php` | Primary access model. Uses Spatie `HasRoles`, overrides `roles()`, implements `hasEffectivePermission()`, designation fallback, and Super Admin helper. |
| `app/Models/Role.php` | Legacy/custom role model; not the active Spatie role model in controllers that import Spatie. |
| `app/Models/Permission.php` | Legacy/custom permission model; used by some Blade views for grouping. |
| `app/Models/Designation.php` | Staff designation with many-to-many Spatie permissions through `designation_has_permissions`. |
| `app/Models/Staff.php` | Staff profile and `permissions_customized` flag. |
| `app/Models/RepairIssueContractorAssignment.php` | Contains contractor role filtering with fallback to `users.role_id` if the column exists. |

### Controllers

| File | Role/permission behavior |
| --- | --- |
| `app/Http/Controllers/Auth/AuthController.php` | Login redirects by Spatie roles; old commented `role_id` login remains. |
| `app/Http/Controllers/Backend/AuthenticateController.php` | Backend login redirects by Spatie roles. |
| `app/Http/Controllers/Backend/DashboardController.php` | Uses `authorize('view dashboard')` plus hardcoded role allowlist. |
| `app/Http/Controllers/Backend/RoleController.php` | Staff role CRUD; permission middleware on index/create/edit/destroy; syncs role permissions. Store/update are not protected by permission middleware. |
| `app/Http/Controllers/Backend/StaffController.php` | Staff CRUD; permission middleware on index/create/edit/destroy; assigns Staff role and direct permission overrides. Store/update are not protected by permission middleware. |
| `app/Http/Controllers/Backend/RegistrationController.php` | Registration approval creates users and manually inserts `model_has_roles`; direct user permissions can be synced. No permission middleware. |
| `app/Http/Controllers/Backend/DesignationController.php` | Manages designation-permission pivot. No permission middleware. |
| `app/Http/Controllers/Backend/PropertyController.php` | Many hardcoded role checks; uses `can()` for tabs; route group only requires auth. |
| `app/Http/Controllers/Backend/PropertyRepairController.php` | Tenant/contractor role checks; contractor role assignment. |
| `app/Http/Controllers/Backend/CompanyController.php` | Uses `can('transfer company owner')` or ownership check. |
| `app/Http/Controllers/Backend/BranchController.php` | Hardcoded Super Admin and company ownership scoping. |
| `app/Http/Controllers/Backend/EmailTemplateController.php` | Permission middleware uses `manage_email_templates`, which mismatches seeded/menu permission naming. |
| `app/Http/Controllers/Frontend/FrontendController.php` | Still checks legacy `role_id` values. |

### Middleware

| File | Status |
| --- | --- |
| `app/Http/Middleware/Authenticate.php` | Active auth alias. |
| `app/Http/Middleware/RoleMiddleware.php` | Custom middleware now uses Spatie `hasAnyRole`, but alias `role` points to Spatie middleware, not this class. |
| `app/Http/Middleware/backendAuthenticate.php` | Legacy `role_id == 1` middleware. Not registered as an active alias in `bootstrap/app.php`. |
| `Spatie\Permission\Middleware\PermissionMiddleware` | Active `permission` alias. |
| `Spatie\Permission\Middleware\RoleMiddleware` | Active `role` alias. |
| `Illuminate\Auth\Middleware\Authorize` | Active `can` alias. |

### Policies

| File | Behavior |
| --- | --- |
| `app/Policies/PropertyPolicy.php` | Role-based policy for property view/create/update/delete. |
| `app/Policies/TenancyPolicy.php` | Role-based policy for tenancy access. |
| `app/Policies/UserPolicy.php` | Role-based user access policy. |

### Migrations

| File | Purpose / issue |
| --- | --- |
| `database/migrations/2024_10_24_115128_create_roles_table.php` | Legacy `roles` table. Conflicts with Spatie roles migration. |
| `database/migrations/2024_10_24_115130_add_role_id_to_users_table.php` | Adds legacy `users.role_id`. Live DB currently lacks this column. |
| `database/migrations/2025_07_05_180135_create_permission_tables.php` | Spatie permission tables. |
| `database/migrations/2025_07_15_000005_create_staff_table.php` | Staff table originally had `role_id`. |
| `database/migrations/2026_05_15_000012_create_designation_has_permissions_and_drop_staff_role_id.php` | Creates `designation_has_permissions`; migrates staff role permissions to designations; drops `staff.role_id`. |
| `database/migrations/2026_05_15_000013_add_permissions_customized_to_staff_table.php` | Adds `staff.permissions_customized`. |
| `database/migrations/2026_03_02_100600_add_accounting_permissions.php` | Adds accounting permissions. |
| `database/migrations/2026_05_18_000001_extend_companies_branches_and_staff_for_agent_ownership.php` | Adds `manage own company`, `transfer company owner`, `download property brochure`. |

### Seeders

| File | Purpose / issue |
| --- | --- |
| `database/seeders/RoleSeeder.php` | Legacy role names such as `super_admin`, `landlord`. Uses `App\Models\Role`; conflicts conceptually with Spatie role names. |
| `database/seeders/RoleAndPermissionSeeder.php` | Main Spatie roles and permissions. Not called from current `DatabaseSeeder.php`. |
| `database/seeders/TenantPermissionsSeeder.php` | Adds tenant property/repair permissions. Not called from current `DatabaseSeeder.php`. |
| `database/seeders/UserSeeder.php` | Creates users and assigns Spatie roles with `assignRole()`. |
| `database/seeders/DatabaseSeeder.php` | Calls `RoleSeeder`, not `RoleAndPermissionSeeder` or `TenantPermissionsSeeder`. |

### Routes

| File | Purpose |
| --- | --- |
| `routes/web.php` | Public, auth, contractor, customer statement, upload routes. No `permission:` routes. |
| `routes/backend.php` | Admin routes. Wrapped in `auth`; most routes lack explicit permission middleware. |
| `routes/api.php` | Not present. No API routes are registered. |

### Main Blade Files

| File | Permission behavior |
| --- | --- |
| `resources/views/backend/partials/aside2.blade.php` | Main menu gating with `@can`, `@canany`, and hardcoded role checks. |
| `resources/views/backend/dashboard.blade.php` | Dashboard cards gated by `@can`. |
| `resources/views/backend/properties/index.blade.php` | Create property button gated by `@can('create properties')`; tenant role condition. |
| `resources/views/backend/properties/tabs/property.blade.php` | Edit/delete property UI gated by permissions. |
| `resources/views/backend/properties/tabs/owners.blade.php` | Hides some UI from tenants. |
| `resources/views/backend/properties/tabs/tenancy2.blade.php` | Hides some UI from tenants. |
| `resources/views/backend/staff/staff_roles/index.blade.php` | Role action buttons gated by staff role permissions. |
| `resources/views/backend/staff/staff_roles/create.blade.php` | Permission checkbox UI. Uses `App\Models\Permission`. |
| `resources/views/backend/staff/staff_roles/edit.blade.php` | Permission checkbox UI. Uses `$role->hasPermissionTo()`. |
| `resources/views/backend/staff/staffs/index.blade.php` | Staff action buttons gated by staff permissions. |
| `resources/views/backend/staff/staffs/create.blade.php` | Designation/direct permission UI. |
| `resources/views/backend/staff/staffs/edit.blade.php` | Designation/direct permission UI. |
| `resources/views/backend/designations/create.blade.php` | Designation permission assignment UI. |
| `resources/views/backend/designations/edit.blade.php` | Designation permission assignment UI. |
| `resources/views/backend/registrations/show.blade.php` | Approval role dropdown and direct user permission toggles. |
| `resources/views/backend/users/index.blade.php` | Contact create button checks `@can('Create Contacts')`, which case-mismatches `create contacts`. |
| `resources/views/backend/users/tabs/user_details.blade.php` | Uses `@can('Edit Contacts')`, which case-mismatches `edit contacts`. |

## Permission Matrix

This matrix reflects the current local database role-permission rows and the custom Super Admin Gate bypass.

### Role Matrix Summary

| Role | Assigned Permission Summary | Modules Accessible | CRUD Summary |
| --- | --- | --- | --- |
| Super Admin | Role has no assigned permissions in current DB, but `Gate::before` grants all abilities. | All Gate-checked modules; many role-gated controllers also allow Super Admin. | Full by Gate, subject to hardcoded role checks. |
| Owner | `view dashboard`, `view own profile`, `view properties`, `view tenants`, `view rent payments`, `view reports` | Dashboard, Properties, Tenants, Rent/Reports. | Read-heavy only. Dashboard controller currently hard-denies Owner because Owner is not in its hardcoded allowlist. |
| Property Manager | Many property permissions, contacts view, documents, finance create/edit, reports, communication. | Dashboard, Properties, Contacts, Documents, Rent/Finance, Reports, Property tabs. | Properties C/R/U/D; Contacts read; Documents C/R/U/D; Finance partial C/U. |
| Tenant | Dashboard/profile, view properties, own lease, maintenance, contacts view, documents upload/view, property repair create/view, property owners/tenancy view. | Tenant home, linked properties, repairs, documents, contacts. | Repair create/read; documents upload/read; property read only. Dashboard redirects to home. |
| Landlord | Dashboard, properties C/R/U/D, tenants C/R/U/D, maintenance read, documents read, rent read, communication read. | Dashboard, own created properties/tenancies, maintenance, documents. | Seeded C/R/U/D broad, but controller scopes to owned/created records. |
| Estate Agent | Dashboard, manage users, office profiles, properties C/R/U/D, tenants C/R/U/D, maintenance create/read, documents read, communication read. | Dashboard, own/team-created properties, users, tenants, maintenance. | C/R/U/D for property/tenant by permission, controller scopes by creator tree. |
| Agent | No assigned permissions. | Role appears in login/dashboard allowlists, but lacks permissions. | None unless direct permissions. |
| Contractor | `view own lease info`, `view maintenance requests`, `update maintenance status`, `complete maintenance tasks`. | Contractor repairs portal and maintenance work. | Maintenance read/update/complete. |
| Maintenance | No assigned permissions. | None unless direct permissions. | None. |
| Service Provider | No assigned permissions. | None unless direct permissions. | None. |
| User | No assigned permissions. | Public/basic auth only unless direct permissions. | None. |
| Letting Applicant | No assigned permissions. | Applicant-specific views only where hardcoded role checks exist. | None. |
| Sales Applicant | No assigned permissions. | Applicant-specific views only where hardcoded role checks exist. | None. |
| Solicitor | No assigned permissions. | None unless direct permissions. | None. |
| Other | No assigned permissions. | None unless direct permissions. | None. |
| Staff | `view dashboard`, `view properties`. Also may inherit designation permissions. | Dashboard, properties; more through designation. | Read property by base role, plus designation/direct overrides. |
| Test | Property read/create/edit/delete plus many property tab view permissions. | Dashboard, property module, property tabs. | Property C/R/U/D plus tab read. |

### Current Role Permissions

#### Super Admin

- Direct role permissions: none in current DB.
- Effective permissions: all Gate abilities through `Gate::before`.

#### Owner

- `view dashboard`
- `view own profile`
- `view properties`
- `view tenants`
- `view rent payments`
- `view reports`

#### Property Manager

- `view dashboard`
- `manage users`
- `view all profiles`
- `view properties`
- `create properties`
- `edit properties`
- `delete properties`
- `assign properties to landlord`
- `view tenants`
- `create tenants`
- `edit tenants`
- `delete tenants`
- `assign tenants to property`
- `view maintenance requests`
- `create maintenance requests`
- `assign maintenance tasks`
- `update maintenance status`
- `complete maintenance tasks`
- `view contacts`
- `upload documents`
- `view documents`
- `download documents`
- `delete documents`
- `view rent payments`
- `create invoices`
- `edit invoices`
- `mark invoice paid`
- `view reports`
- `send notifications`
- `view communication log`
- `view deleted properties`
- `recover deleted properties`
- property tab permissions for important note, availability/pricing, information, features, service, owners, compliance, media, offers, tenancy, APS, teams, documents, appointments.

#### Tenant

- `view dashboard`
- `view own profile`
- `view properties`
- `view own lease info`
- `view maintenance requests`
- `create maintenance requests`
- `view contacts`
- `upload documents`
- `view documents`
- `view communication log`
- `view property repair`
- `view property owners`
- `view property tenancy`
- `create property repair`

#### Landlord

- `view dashboard`
- `view properties`
- `create properties`
- `edit properties`
- `delete properties`
- `assign properties to landlord`
- `view tenants`
- `create tenants`
- `edit tenants`
- `delete tenants`
- `assign tenants to property`
- `view own lease info`
- `view maintenance requests`
- `view documents`
- `view rent payments`
- `view communication log`

#### Estate Agent

- `view dashboard`
- `manage users`
- `view office profiles`
- `view properties`
- `create properties`
- `edit properties`
- `delete properties`
- `assign properties to landlord`
- `view tenants`
- `create tenants`
- `edit tenants`
- `delete tenants`
- `assign tenants to property`
- `view maintenance requests`
- `create maintenance requests`
- `view documents`
- `view communication log`

#### Contractor

- `view own lease info`
- `view maintenance requests`
- `update maintenance status`
- `complete maintenance tasks`

#### Staff

- `view dashboard`
- `view properties`
- May inherit `designation_has_permissions`.

#### Test

- `view dashboard`
- `view properties`
- `create properties`
- `edit properties`
- `delete properties`
- property tab view permissions for availability/pricing, information, service, owners, compliance, offers, tenancy, APS, teams, documents, appointments.

## Staff Designation Matrix

Current local database:

| Designation | Assigned Permissions |
| --- | --- |
| Manager | Dashboard/settings/profile/property permissions, calendar, property owners/compliance/media/offers/tenancy/APS/teams/documents/appointments. |
| Negotiator | None |
| Lister | Tenant-like permissions: dashboard, own profile, view properties, own lease, maintenance create/view, contacts view, documents upload/view, communication, property repair create/view, property owners/tenancy view. |
| Assistant | None |

Staff effective behavior:

- If `staff.permissions_customized = false`, `User::hasEffectivePermission()` uses the user's designation permissions.
- If `staff.permissions_customized = true`, direct Spatie permissions on `model_has_permissions` are used instead.

## Permission Middleware and Protected Web Routes

No `routes/api.php` file exists, so there are no registered API endpoints protected by permissions.

The following web routes are protected by active Spatie `PermissionMiddleware`:

| Method | URI | Route Name | Controller Action | Required Permission |
| --- | --- | --- | --- | --- |
| GET/HEAD | `admin/email-template/{id}` | `email-templates.index` | `EmailTemplateController@index` | `manage_email_templates` |
| GET/HEAD | `admin/email-templates` | `email-templates.index` | `EmailTemplateController@index` | `manage_email_templates` |
| PUT/PATCH | `admin/email-templates/{email_template}` | `email-templates.update` | `EmailTemplateController@update` | `manage_email_templates` |
| GET/HEAD | `admin/email-templates/{email_template}/edit` | `email-templates.edit` | `EmailTemplateController@edit` | `manage_email_templates` |
| GET/HEAD | `admin/roles` | `roles.index` | `RoleController@index` | `view staff roles` |
| GET/HEAD | `admin/roles/create` | `roles.create` | `RoleController@create` | `add staff role` |
| GET/HEAD | `admin/roles/destroy/{id}` | `roles.destroy` | `RoleController@destroy` | `delete staff role` |
| GET/HEAD | `admin/roles/edit/{id}` | `roles.edit` | `RoleController@edit` | `edit staff role` |
| DELETE | `admin/roles/{role}` | `roles.destroy` | `RoleController@destroy` | `delete staff role` |
| GET/HEAD | `admin/roles/{role}/edit` | `roles.edit` | `RoleController@edit` | `edit staff role` |
| GET/HEAD | `admin/staffs` | `staffs.index` | `StaffController@index` | `view all staffs` |
| GET/HEAD | `admin/staffs/create` | `staffs.create` | `StaffController@create` | `add staff` |
| GET/HEAD | `admin/staffs/destroy/{id}` | `staffs.destroy` | `StaffController@destroy` | `delete staff` |
| DELETE | `admin/staffs/{staff}` | `staffs.destroy` | `StaffController@destroy` | `delete staff` |
| GET/HEAD | `admin/staffs/{staff}/edit` | `staffs.edit` | `StaffController@edit` | `edit staff` |

Routes with controller/Gate checks but no `permission:` middleware include:

| Route | Check |
| --- | --- |
| `admin/dashboard` | `$this->authorize('view dashboard')` plus hardcoded role allowlist. |
| `admin/companies/{company}/transfer-owner` | `$authUser->can('transfer company owner')` or owner check. |
| `admin/properties/*` | Hardcoded role/ownership checks in `PropertyController`, plus tab-level `can()`. |
| `admin/properties/{property}/brochure` | `canAccessProperty()` role/ownership check; does not use `download property brochure`. |

## Menu Items and Visibility

Main menu file: `resources/views/backend/partials/aside2.blade.php`.

Roles below are based on current DB role permissions plus the Super Admin Gate bypass. Users can also see items via direct permissions or staff designation permissions.

| Menu Item | Blade Condition | Roles/Users Who Can See |
| --- | --- | --- |
| Dashboard | Not Tenant and not Contractor | All non-Tenant/non-Contractor authenticated users may see the link. Controller later only allows Super Admin, Landlord, Staff, Property Manager, Estate Agent, Test with `view dashboard`. |
| Calendar | `@can('view calendar')` | Super Admin; staff/direct users with `view calendar`; current Manager designation has it. |
| Properties | `@canany(['view properties','edit properties','create properties'])` | Super Admin, Owner, Property Manager, Tenant, Landlord, Estate Agent, Staff, Test, and users with designation/direct property permissions. |
| Property List | `@can('view properties')` | Same as roles with `view properties`. |
| Add Property | `@can('create properties')` | Super Admin, Property Manager, Landlord, Estate Agent, Test; also direct/designation permission users. |
| Deleted Properties | `@can('view deleted properties')` | Super Admin, Property Manager; also direct/designation permission users. |
| Contacts | `@canany(['view contacts','create contacts','edit contacts','delete contacts'])` | Super Admin, Property Manager, Tenant, and direct/designation users. |
| Registrations | `@unless(Tenant or Contractor)` | All non-Tenant/non-Contractor users see it; no permission middleware protects these routes. |
| Tenancies | `@can('manage tenancies')` | Super Admin or direct/designation users. Current role rows do not assign this broadly. |
| Repairs | `@canany(['view property repair','edit property repair','create property repair'])` | Super Admin, Tenant, staff with Lister designation, and direct users. |
| Contractor Repairs | `hasRole('Contractor')` | Contractor role only. |
| Invoices | `@can('view invoices')` | Super Admin and direct users. Current Property Manager role has create/edit invoice but not `view invoices`. |
| Document Types | `@can('Manage Document Types')` | Super Admin only by Gate bypass unless a case-matched permission exists. DB has `manage document types`, lower-case. |
| Transactions | `@canany(['view transactions'])` | Super Admin or direct users. |
| Website Setup | `@canany(['manage website setup','manage header','manage footer','manage appearance'])` | Super Admin or direct users with one of these permissions. `manage website setup` was not found in current permissions. |
| Master Manage | large `@canany([...])` list | Super Admin or direct users with matching master permissions. |
| User Categories | `@can('manage categories')` | Super Admin or direct users. Permission not found in current DB list. |
| Branches | `@can('manage branches')` | Super Admin or direct users. |
| Designations | `@can('manage designations')` | Super Admin or direct users. |
| Note Types | `@canany(['manage note types'])` | Super Admin or direct users. |
| Document Types Setup | `@can('manage document types')` | Super Admin or direct users. |
| Tenancy Types | `@can('manage tenancy types')` | Super Admin or direct users. |
| Tenancy Sub Status | `@can('manage tenancy sub status')` | Super Admin or direct users. |
| Event Types | `@can('manage event types')` | Super Admin or direct users. |
| Event Sub Types | `@can('manage event sub types')` | Super Admin or direct users. |
| Job Types | `@can('manage job types')` | Super Admin or direct users. |
| Transaction Categories | `@can('manage transaction categories')` | Super Admin or direct users. |
| Staffs | `@canany(['view all staffs','manage designations'])` | Super Admin or direct users. |
| SMTP Settings | `@canany(['view smtp settings'])` | Super Admin only unless permission is created/assigned; current DB list did not show `view smtp settings`. |
| Accounting | Not Tenant and not Contractor | All non-Tenant/non-Contractor users see it. This is not tied to accounting permissions in the menu. |
| Email Templates | `@canany(['manage email templates'])` | Super Admin or direct users with spaced permission. Controller middleware uses underscore permission and may deny those users. |

## Hardcoded Role and Permission Checks

### Legacy `role_id`

Found in:

- `database/seeders/UserSeeder.php`
- `database/migrations/2024_10_24_115130_add_role_id_to_users_table.php`
- `app/Http/Middleware/backendAuthenticate.php`
- `app/Http/Middleware/RoleMiddleware.php` commented legacy block
- `app/Http/Controllers/Frontend/FrontendController.php`
- `app/Http/Controllers/Auth/AuthController.php` commented blocks
- `app/Http/Controllers/Backend/RegistrationController.php`
- `app/Http/Controllers/Backend/PropertyRepairController.php`
- `app/Models/RepairIssueContractorAssignment.php`
- `app/helpers.php`
- several user form Blade files

Examples:

```php
auth()->user()->role_id == 1
```

```php
if (in_array($user->role_id, [1, 2, 3])) {
```

### `hasRole()` / `hasAnyRole()`

Major files:

- `app/Http/Controllers/Auth/AuthController.php`
- `app/Http/Controllers/Backend/AuthenticateController.php`
- `app/Http/Controllers/Backend/DashboardController.php`
- `app/Http/Controllers/Backend/PropertyController.php`
- `app/Http/Controllers/Backend/PropertyRepairController.php`
- `app/Http/Controllers/Backend/UserController.php`
- `app/Http/Controllers/Backend/BranchController.php`
- `app/Http/Controllers/Backend/CompanyController.php`
- `app/Policies/*.php`
- `resources/views/backend/partials/aside2.blade.php`
- `resources/views/backend/repair/popup_forms/property_issue_details.blade.php`

### `can()` / `@can` / Gates

Major files:

- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/Backend/DashboardController.php`
- `app/Http/Controllers/Backend/CompanyController.php`
- `app/Http/Controllers/Backend/PropertyController.php`
- `resources/views/backend/partials/aside2.blade.php`
- `resources/views/backend/dashboard.blade.php`
- `resources/views/backend/properties/index.blade.php`
- `resources/views/backend/properties/tabs/property.blade.php`
- `resources/views/backend/staff/staff_roles/index.blade.php`
- `resources/views/backend/staff/staffs/index.blade.php`
- `resources/views/backend/users/index.blade.php`

No active `Gate::allows()` or `Gate::denies()` usages were found. Dynamic Gates are created with `Gate::define()`.

## Security Findings

### 1. Most admin routes have only `auth`, not permissions

The route list has 420 admin routes without `PermissionMiddleware`, `Authorize`, or `RoleMiddleware`.

Examples of modules under only `auth`:

- Properties
- Users/contacts
- Registrations
- Designations
- Branches
- Tenancies
- Notes/Documents
- Repairs
- Accounting
- Website setup
- OTP/SMS setup

Impact:

- Hiding menu items with `@can` does not prevent direct URL access.
- Any authenticated user may hit many endpoints unless each controller method performs its own authorization.

### 2. Role and staff role write actions are partially unprotected

`RoleController` middleware protects `index`, `create`, `edit`, and `destroy`, but not `store` or `update`.

`StaffController` middleware protects `index`, `create`, `edit`, and `destroy`, but not `store` or `update`.

Impact:

- Users who can guess POST/PUT endpoints may create or update roles/staff if only `auth` applies.

### 3. Email template permission name mismatch

Controller:

```php
permission:manage_email_templates
```

Menu/DB:

```text
manage email templates
```

Impact:

- Normal users with the menu permission can be denied by the controller.
- Users with underscore permission may access routes while menu logic does not reflect it.

### 4. Case-sensitive permission mismatches

Examples:

- `@can('Create Contacts')` vs DB `create contacts`
- `@can('Edit Contacts')` vs DB `edit contacts`
- `@can('Manage Document Types')` vs DB `manage document types`

Impact:

- Non-Super Admin users may not see expected UI.
- Super Admin masks the problem because `Gate::before` returns true for any ability.

### 5. Legacy `role_id` code remains while live DB has no `users.role_id`

Impact:

- Some checks always evaluate incorrectly.
- Queries may fail if they select or filter `role_id` without `Schema::hasColumn`.
- Fresh environments may diverge depending on migrations.

### 6. Migration conflict on `roles`

Two migrations create the `roles` table.

Impact:

- Fresh migration runs can fail.
- Schema history is ambiguous.

### 7. `permissions.section` exists in DB but no migration was found

Impact:

- Fresh environments may lack the column.
- Role/staff/designation permission UIs that group by `section` can break or behave differently.

### 8. Manual insert into `model_has_roles`

`RegistrationController` inserts directly into the pivot table instead of calling `$user->assignRole($role)`.

Impact:

- Spatie cache/events/model consistency can be bypassed.
- Duplicate handling depends on DB constraints.

### 9. Custom `User::roles()` overrides Spatie's trait relationship

`User.php` manually declares `roles()`.

Impact:

- It may diverge from Spatie internals, especially if teams, guards, or package updates change relationship behavior.

### 10. Custom `all_permissions` cache can become stale

`AppServiceProvider` uses:

```php
cache()->rememberForever('all_permissions', fn() => Permission::all());
```

Impact:

- New permissions added through `RoleController::add_permission()` do not clear this cache.
- New permissions may not be registered as Gates until cache is cleared.

### 11. Accounting menu is role-hidden but not permission-gated

Menu condition:

```blade
@unless(auth()->user()->hasRole('Tenant') || auth()->user()->hasRole('Contractor'))
```

Impact:

- Many non-accounting roles can see accounting menus.
- Accounting routes appear protected only by `auth`.
- Seeded accounting permissions are not consistently enforced.

### 12. Dashboard has duplicate authorization logic

It checks both:

```php
$this->authorize('view dashboard');
```

and a hardcoded role list.

Impact:

- A role can have `view dashboard` but still be denied. Current `Owner` has `view dashboard` but is not in the hardcoded allowlist.

## Refactoring Recommendations

1. Standardize fully on Spatie.
   - Remove or quarantine `App\Models\Role`, `App\Models\Permission`, `users.role_id`, and legacy middleware after migration.
   - Use `Spatie\Permission\Models\Role` and `Spatie\Permission\Models\Permission` everywhere.

2. Fix migrations.
   - Remove/replace the duplicate legacy `roles` migration.
   - Add a migration for `permissions.section` or remove code that depends on it.
   - Add a cleanup migration for legacy `users.role_id` references only after code no longer uses it.

3. Use middleware or policies on every admin route.
   - Move CRUD permissions to route groups or controller constructors.
   - Protect write endpoints (`store`, `update`, `destroy`) first.

4. Replace menu-only checks with server-side authorization.
   - Keep `@can` for UI visibility.
   - Add matching controller `authorize()` or route `can:`/`permission:` checks.

5. Normalize permission names.
   - Use one naming convention, for example lower-case spaces (`manage email templates`) or dot notation (`email-templates.manage`).
   - Fix case mismatches in Blade.
   - Fix underscore vs spaced permissions.

6. Remove manual pivot inserts.
   - Replace direct `DB::table('model_has_roles')->insert()` with `$user->assignRole($role)`.

7. Use Spatie cache management only.
   - Avoid a separate forever cache for permissions or clear it consistently whenever permissions change.
   - Prefer Spatie's `PermissionRegistrar::forgetCachedPermissions()`.

8. Introduce policies for domain objects.
   - Use `PropertyPolicy`, `TenancyPolicy`, and `UserPolicy` consistently.
   - Register or rely on Laravel policy auto-discovery, then call `$this->authorize()` in controllers.

9. Define a canonical permission matrix.
   - Seed all permissions and assignments from one seeder that is actually called by `DatabaseSeeder`.
   - Include accounting, setup, repair, property tabs, staff, and email templates.

10. Add authorization tests.
   - Test direct URL access for every module.
   - Test that menu visibility and route access match.
   - Test Super Admin bypass separately from normal role permissions.

