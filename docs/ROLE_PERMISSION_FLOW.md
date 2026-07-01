# Role & Permission Flow

## High-Level Flow

```text
Request
  -> web/auth middleware
  -> optional Spatie permission/role middleware
  -> controller
  -> optional Gate/Policy/role checks
  -> model query scoping
  -> Blade view
  -> Blade @can/@canany/hasRole menu or action visibility
```

## Login Flow

### Public login

File: `app/Http/Controllers/Auth/AuthController.php`

1. User submits `/login`.
2. `Auth::attempt()` checks credentials.
3. Authenticated user is redirected by Spatie role:
   - `Tenant` -> `backend.home`
   - `Contractor` -> `backend.home`
   - `Super Admin`, `Owner`, `Property Manager`, `Landlord`, `Estate Agent`, `Agent`, `Staff`, `Test` -> `backend.dashboard`
   - anything else -> `home`

### Backend login

File: `app/Http/Controllers/Backend/AuthenticateController.php`

1. User submits `/admin/login`.
2. `Auth::attempt()` checks credentials.
3. Authenticated user is redirected by role:
   - `Tenant` -> `backend.home`
   - backend role list -> `backend.dashboard`
   - anything else -> `home`

## Role Assignment Flow

### Normal backend user creation/update

File: `app/Http/Controllers/Backend/UserController.php`

```text
role_ids[] from request
  -> Spatie Role::whereIn(...)->pluck('name')
  -> $user->syncRoles($roles)
  -> model_has_roles updated
```

### Staff creation/update

File: `app/Http/Controllers/Backend/StaffController.php`

```text
staff form
  -> create/update users row
  -> syncRoles(['Staff'])
  -> create/update staff row
  -> compare selected custom permissions with designation permissions
  -> if customized, sync direct permissions
  -> if not customized, direct permissions cleared
```

### Registration approval

File: `app/Http/Controllers/Backend/RegistrationController.php`

```text
approved registration
  -> selected role_id
  -> create user
  -> manual insert into model_has_roles
  -> optional direct permissions managed later via syncPermissions()
```

Risk:

- This bypasses Spatie `assignRole()`.

### Contractor creation from repair flow

File: `app/Http/Controllers/Backend/PropertyRepairController.php`

```text
quote contractor form
  -> find/create User
  -> find Contractor role
  -> assignRole(Contractor)
```

## Permission Assignment Flow

### Role permissions

File: `app/Http/Controllers/Backend/RoleController.php`

```text
role create/edit form
  -> permissions[] ids
  -> Permission::whereIn('id', ...)
  -> $role->syncPermissions($permissions)
  -> role_has_permissions updated
```

### Direct user permissions

Files:

- `app/Http/Controllers/Backend/RegistrationController.php`
- `app/Http/Controllers/Backend/StaffController.php`

```text
direct permission selection
  -> $user->syncPermissions(...)
  -> model_has_permissions updated
```

### Designation permissions

File: `app/Http/Controllers/Backend/DesignationController.php`

```text
designation form
  -> permissions[] ids
  -> $designation->permissions()->sync(...)
  -> designation_has_permissions updated
```

## Effective Permission Resolution

File: `app/Models/User.php`

```text
User::hasEffectivePermission(permission)
  -> if staff account and customized:
       check direct user permissions
  -> else if staff account and has designation:
       check designation permissions
  -> else:
       check Spatie role/direct permissions
       or designation permissions
```

Super Admin bypass:

File: `app/Providers/AppServiceProvider.php`

```text
Gate::before
  -> if user has role Super Admin
       return true for every ability
```

Dynamic Gates:

```text
permissions table rows
  -> cached forever as all_permissions
  -> Gate::define(permission_name)
  -> calls user.hasEffectivePermission(permission_name)
```

## Middleware Flow

Active aliases:

File: `bootstrap/app.php`

| Alias | Class |
| --- | --- |
| `auth` | `App\Http\Middleware\Authenticate` |
| `can` | `Illuminate\Auth\Middleware\Authorize` |
| `permission` | `Spatie\Permission\Middleware\PermissionMiddleware` |
| `role` | `Spatie\Permission\Middleware\RoleMiddleware` |

Active permission middleware usage:

| Controller | Methods | Permissions |
| --- | --- | --- |
| `RoleController` | `index`, `create`, `edit`, `destroy` | `view staff roles`, `add staff role`, `edit staff role`, `delete staff role` |
| `StaffController` | `index`, `create`, `edit`, `destroy` | `view all staffs`, `add staff`, `edit staff`, `delete staff` |
| `EmailTemplateController` | `index`, `edit`, `update` | `manage_email_templates` |

Important gap:

- `store` and `update` are not protected in `RoleController`.
- `store` and `update` are not protected in `StaffController`.
- Most admin controllers have no permission middleware.

## Controller Authorization Flow

### Dashboard

```text
GET admin/dashboard
  -> auth
  -> DashboardController@dashboard
  -> Tenant redirected to home
  -> authorize('view dashboard')
  -> hardcoded hasAnyRole allowlist
  -> dashboard view
```

Issue:

- A user can have `view dashboard` but fail the hardcoded role allowlist.

### Properties

```text
GET admin/properties
  -> auth
  -> PropertyController@index
  -> hardcoded role scoping:
       Super Admin / Property Manager: all
       Tenant: active tenancy properties
       Landlord / Staff / Test: created_by = user id
       Estate Agent: own and created users
  -> selected property access check
  -> tabs built with user->can(permission)
  -> Blade view
```

### Company Transfer

```text
POST admin/companies/{company}/transfer-owner
  -> auth
  -> CompanyController@transferOwner
  -> allow if can('transfer company owner') or current owner
```

### Staff Roles

```text
GET admin/roles
  -> auth
  -> permission:view staff roles
  -> RoleController@index

POST admin/roles
  -> auth
  -> RoleController@store
  -> no permission middleware
```

### Staff

```text
GET admin/staffs
  -> auth
  -> permission:view all staffs
  -> StaffController@index

POST admin/staffs
  -> auth
  -> StaffController@store
  -> no permission middleware
```

## Blade/Menu Flow

Main file: `resources/views/backend/partials/aside2.blade.php`

```text
authenticated user
  -> aside2.blade.php
  -> @can / @canany use Laravel Gate
  -> Gate::before grants Super Admin
  -> dynamic Gate calls hasEffectivePermission()
  -> menu link visible or hidden
```

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

Risk:

- Menu checks are UI-only.
- Direct URL access is possible unless route/controller authorization also exists.

## Request Flow Diagram

See `role_permission_diagram.mmd` for the Mermaid source.

```mermaid
flowchart LR
    Request[HTTP Request] --> Web[web middleware]
    Web --> Auth{Authenticated?}
    Auth -- no --> Login[Login / Redirect]
    Auth -- yes --> Route[Route Match]
    Route --> MW{Permission or Role Middleware?}
    MW -- permission --> SpatiePermission[Spatie PermissionMiddleware]
    MW -- role --> SpatieRole[Spatie RoleMiddleware]
    MW -- none --> Controller[Controller]
    SpatiePermission --> GateCheck[Gate / hasPermissionTo]
    SpatieRole --> RoleCheck[hasRole / hasAnyRole]
    GateCheck --> Controller
    RoleCheck --> Controller
    Controller --> ControllerAuth{Controller Authorization?}
    ControllerAuth -- Gate --> Gate[Laravel Gate]
    ControllerAuth -- Policy --> Policy[Policy]
    ControllerAuth -- Role Checks --> HardcodedRoles[hasRole / hardcoded list]
    ControllerAuth -- none --> Query[Model Query]
    Gate --> Effective[User::hasEffectivePermission]
    Policy --> Query
    HardcodedRoles --> Query
    Effective --> RolePerms[Role Permissions]
    Effective --> DirectPerms[Direct User Permissions]
    Effective --> DesignationPerms[Designation Permissions]
    RolePerms --> Query
    DirectPerms --> Query
    DesignationPerms --> Query
    Query --> View[Blade View]
    View --> BladeCan[@can / @canany / hasRole]
    BladeCan --> Response[HTTP Response]
```

## Protected Endpoint Inventory

### API Endpoints

None. There is no `routes/api.php`, and no API route file is registered in `RouteServiceProvider`.

### Web Routes Protected by Spatie Permission Middleware

| Method | URI | Name | Permission |
| --- | --- | --- | --- |
| GET/HEAD | `admin/email-template/{id}` | `email-templates.index` | `manage_email_templates` |
| GET/HEAD | `admin/email-templates` | `email-templates.index` | `manage_email_templates` |
| PUT/PATCH | `admin/email-templates/{email_template}` | `email-templates.update` | `manage_email_templates` |
| GET/HEAD | `admin/email-templates/{email_template}/edit` | `email-templates.edit` | `manage_email_templates` |
| GET/HEAD | `admin/roles` | `roles.index` | `view staff roles` |
| GET/HEAD | `admin/roles/create` | `roles.create` | `add staff role` |
| GET/HEAD | `admin/roles/destroy/{id}` | `roles.destroy` | `delete staff role` |
| GET/HEAD | `admin/roles/edit/{id}` | `roles.edit` | `edit staff role` |
| DELETE | `admin/roles/{role}` | `roles.destroy` | `delete staff role` |
| GET/HEAD | `admin/roles/{role}/edit` | `roles.edit` | `edit staff role` |
| GET/HEAD | `admin/staffs` | `staffs.index` | `view all staffs` |
| GET/HEAD | `admin/staffs/create` | `staffs.create` | `add staff` |
| GET/HEAD | `admin/staffs/destroy/{id}` | `staffs.destroy` | `delete staff` |
| DELETE | `admin/staffs/{staff}` | `staffs.destroy` | `delete staff` |
| GET/HEAD | `admin/staffs/{staff}/edit` | `staffs.edit` | `edit staff` |

### Web Routes with Gate/Controller Permission Checks

These do not use `PermissionMiddleware` but call `authorize()` or `can()` in controller code.

| Route | Controller | Check |
| --- | --- | --- |
| `GET admin/dashboard` | `DashboardController@dashboard` | `authorize('view dashboard')` plus hardcoded roles. |
| `POST admin/companies/{company}/transfer-owner` | `CompanyController@transferOwner` | `can('transfer company owner')` or owner user. |
| `GET admin/properties/{property}/brochure` | `PropertyController@brochure` | `canAccessProperty()` role/ownership check. |

