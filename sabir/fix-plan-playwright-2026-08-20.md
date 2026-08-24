# ResiSquare — Comprehensive Fix Plan & Playwright E2E Strategy
**Audit date:** 20 August 2026  
**Branch:** `sabir/audit`  
**Base revision:** `de31c60`  
**Decision:** NO-GO for production or broad client UAT  
**E2E Framework:** Playwright (TypeScript)

---

## Table of Contents
1. [P0 — Security & Data Integrity (Release Blockers)](#p0)
2. [P1 — Broken Modules & HTTP 500/404](#p1)
3. [P2 — User-Facing UI/UX Defects](#p2)
4. [P3 — Code Quality & Architecture](#p3)
5. [P4 — E2E Test Strategy (Playwright)](#p4)
6. [Edge Cases & Negative Testing Matrix](#p5)
7. [Responsive & Accessibility Coverage](#p6)
8. [Infrastructure & DevOps](#p7)
9. [Execution Timeline](#p8)
10. [Release Gates](#p9)

---

<a name="p0"></a>
## 1. P0 — Security & Data Integrity (Release Blockers)

### 1.1 Cross-Account Accounting Access (CRITICAL)
**Files:**
- `app/Http/Controllers/Backend/Accounting/*Controller.php`
- `app/Http/Controllers/Backend/DocumentController.php`

**Problem:** Shared accounting base controllers use `$modelClass::query()` + `findOrFail()` without account scoping middleware. Users in Account A can access Account B records by manipulating IDs.

**Fix:**
```php
// Add account predicate to every accounting/document query
$records = $modelClass::where('account_id', auth()->user()->account_id)
    ->findOrFail($id);
```

**Playwright Test:**
```typescript
test('Account A cannot access Account B accounting records', async ({ page }) => {
  // Login as Account A user
  // Navigate to /admin/accounting/receipts/999 (Account B ID)
  // Assert: 403 or 404, never 200
});
```

**Edge Cases:**
- URL tampering: `/admin/accounting/receipts/{other_account_id}`
- Direct POST to edit endpoint with cross-account ID
- PDF export of another account's receipt
- JSON API responses leaking other account data

---

### 1.2 Global RBAC & Incomplete Authorization (CRITICAL)
**Files:**
- `app/Http/Controllers/Backend/RoleController.php`
- `app/Http/Middleware/backendAuthenticate.php`
- `database/seeders/RoleAndPermissionSeeder.php`
- `resources/views/backend/partials/aside2.blade.php`

**Problem:**
- Spatie teams disabled → roles/permissions are global
- `RoleController` protects `index`, `create`, `edit`, `destroy` but NOT `store`, `update`, `add_permission`
- Blade visibility checks ≠ server-side authorization
- `backendAuthenticate.php` hardcodes `role_id == 1` (latent risk)

**Fix:**
```php
// RoleController.php — add middleware to ALL methods
public function __construct() {
    $this->middleware('permission:view roles|create roles|edit roles|delete roles');
}

// Every mutation endpoint must check:
Gate::authorize('update role');

// Replace backendAuthenticate.php or remove it
```

**Playwright Tests:**
```typescript
test('Non-super-admin cannot access RoleController mutations', async ({ page }) => {
  // Login as Estate Agent Owner
  // POST /admin/roles (without permission)
  // Assert: 403
  // PUT /admin/roles/1 (without permission)
  // Assert: 403
});

test('Hidden buttons still enforce server-side authorization', async ({ page }) => {
  // Login as Staff
  // Directly POST to /admin/users/store (button hidden in UI)
  // Assert: 403 or 419 (CSRF)
});
```

**Edge Cases:**
- CSRF token bypass attempts
- Mass assignment via hidden fields
- Permission escalation via API
- Cross-account role mutation

---

### 1.3 Web-Based Environment Mutation (CRITICAL)
**Files:**
- `routes/web.php`
- `app/Http/Controllers/Backend/EnvKeyUpdateController.php`

**Problem:** `POST /admin/env_key_update` accepts request-provided key names and writes to `.env`. SMTP settings render environment credentials.

**Fix:**
```php
// REMOVE this route entirely OR restrict to local-only
// If absolutely necessary, whitelist only safe keys
$allowed = ['APP_NAME', 'APP_URL'];
foreach ($request->keys() as $key) {
    abort_unless(in_array($key, $allowed), 403);
}
```

**Playwright Test:**
```typescript
test('Environment mutation endpoint is inaccessible', async ({ page }) => {
  // Login as Super Admin
  // POST /admin/env_key_update with malicious keys
  // Assert: 404 or 403
  // Verify .env file unchanged
});
```

---

### 1.4 Database Build & Upgrade Drift (CRITICAL)
**Problem:**
- Clean migration fails: duplicate `users.email` in `2024_10_24_115129_create_users_table`
- 28 pending migrations on current MySQL
- `purchase_invoices` table absent

**Fix:**
```bash
# Audit every migration for duplicate column definitions
grep -r "email" database/migrations/ | grep "unique"

# Fix duplicate in create_users_table
# Run: php artisan migrate:fresh --seed
# Verify: php artisan migrate:status (zero pending)
```

**Playwright Test:**
```typescript
test('Fresh migration produces working application', async ({ page }) => {
  // Spin up fresh SQLite database
  // Run: php artisan migrate:fresh --seed
  // Assert: All routes return expected status codes
});
```

---

<a name="p1"></a>
## 2. P1 — Broken Modules & HTTP 500/404

### 2.1 Route Name Mismatches (HIGH)

**Issues found:**

| File | Lines | Bug | Fix |
|------|-------|-----|-----|
| `resources/views/backend/estate_charges/index.blade.php` | 6, 34, 35 | `route('backend.estate_charges.*')` → should be `admin.estate-charges.*` | Replace all route() calls |
| `resources/views/backend/owner_groups/index.blade.php` | 28, 29 | `route('backend.owner_groups.edit/destroy')` → should be `admin.owner-groups.*` | Replace route names |
| `resources/views/backend/offers/index.blade.php` | 26, 27 | `route('offers.edit/destroy')` → should be `admin.offers.*` | Replace route names |
| `resources/views/backend/users/__create.blade.php` | 6 | `route('users.store')` → should be `admin.users.store` | Fix route name |

**Fix Pattern:**
```blade
{{-- BEFORE --}}
<a href="{{ route('backend.estate_charges.edit', $item) }}">Edit</a>

{{-- AFTER --}}
<a href="{{ route('admin.estate-charges.edit', $item) }}">Edit</a>
```

**Playwright Test:**
```typescript
test('Estate Charges CRUD buttons navigate correctly', async ({ page }) => {
  await page.goto('/admin/estate-charges');
  await expect(page.locator('text=Estate Charges')).toBeVisible();
  
  // Click Edit button
  await page.click('button:has-text("Edit") >> nth=0');
  await expect(page).toHaveURL(/\/admin\/estate-charges\/\d+\/edit/);
  await expect(page.locator('form')).toBeVisible();
});

test('Owner Groups Edit/Delete buttons work', async ({ page }) => {
  await page.goto('/admin/owner-groups');
  await page.click('button:has-text("Edit") >> nth=0');
  await expect(page).toHaveURL(/\/admin\/owner-groups\/\d+\/edit/);
});

test('Offers Edit/Delete buttons work', async ({ page }) => {
  await page.goto('/admin/offers');
  await page.click('button:has-text("Edit") >> nth=0');
  await expect(page).toHaveURL(/\/admin\/offers\/\d+\/edit/);
});
```

---

### 2.2 Missing Controller Methods (HIGH)

**Issues:**

| Endpoint | Root Cause | Fix |
|----------|-----------|-----|
| `/admin/credit-notes/create` | `AccountsNoteApplicationController::createCredit()` missing | Add method OR redirect to correct controller |
| `/admin/debit-notes/create` | `AccountsNoteApplicationController::createDebit()` missing | Add method OR redirect |
| `/admin/job-types/create` | `JobTypeController::create()` missing | Add method |
| `/admin/document-types/show` | Route missing required `{id}` parameter | Fix route definition |
| `/admin/note-types/show` | Route missing required `{id}` parameter | Fix route definition |
| `/admin/email-templates/create` | View `backend.setup_configurations.email_templates.create` missing | Create view file |
| `/admin/purchase_invoices` | Table `purchase_invoices` does not exist | Run migration OR remove routes |
| `/admin/purchase_invoices/create` | View missing | Create view OR remove route |
| `/uploaded-files` | View `modals.delete_modal` missing | Create modal view |
| `/uploaded-files/create` | Layout `backend.layouts.app` missing (typo: `layouts` vs `layout`) | Fix layout reference |
| `/uploaded-files/file-info` | `AizUploadController::show()` missing | Add method |
| `/admin/offers/create` | Undefined `$property_id` in view | Pass variable from controller |

**Playwright Tests:**
```typescript
test('Credit Notes create page loads', async ({ page }) => {
  await page.goto('/admin/credit-notes/create');
  await expect(page.locator('form')).toBeVisible();
  await expect(page.locator('input[name="amount"]')).toBeVisible();
});

test('Debit Notes create page loads', async ({ page }) => {
  await page.goto('/admin/debit-notes/create');
  await expect(page.locator('form')).toBeVisible();
});

test('Job Types create page loads', async ({ page }) => {
  await page.goto('/admin/job-types/create');
  await expect(page.locator('input[name="name"]')).toBeVisible();
});
```

---

### 2.3 Missing HTTP Method Directives (HIGH)

**File:** `resources/views/backend/tenancies/edit.blade.php:34-36`

**Problem:** Form uses `method="POST"` but missing `@method('PUT')`.

**Fix:**
```blade
{{-- BEFORE --}}
<form action="{{ route('admin.tenancies.update', $tenancy) }}" method="POST">

{{-- AFTER --}}
<form action="{{ route('admin.tenancies.update', $tenancy) }}" method="POST">
    @method('PUT')
    @csrf
</form>
```

**Playwright Test:**
```typescript
test('Tenancy edit form submits with PUT method', async ({ page }) => {
  await page.goto('/admin/tenancies/1/edit');
  await page.fill('input[name="tenant_name"]', 'Updated Name');
  await page.click('button:has-text("Update")');
  
  // Assert form submission used PUT
  const response = await page.waitForResponse('/admin/tenancies/1');
  expect(response.request().method()).toBe('PUT');
  await expect(page.locator('text=Tenancy updated successfully')).toBeVisible();
});
```

---

<a name="p2"></a>
## 3. P2 — User-Facing UI/UX Defects

### 3.1 Login Page Defects (HIGH)

**Issues:**

| # | File | Problem | Fix |
|---|------|---------|-----|
| 1 | `resources/views/frontend/login.blade.php:44` | "Sign Up" link uses `route('register.post')` (POST route) | Change to `route('register')` |
| 2 | `resources/views/frontend/login.blade.php:33` | Remember Me has `required` attribute | Remove `required` |
| 3 | `resources/views/frontend/layout/app.blade.php:5` | Page title reads "Backend Dashboard" | Change to "ResiSquare" |
| 4 | `resources/views/frontend/layout/app.blade.php:51` | Uses `data-dismiss="alert"` (Bootstrap 4) | Change to `data-bs-dismiss="alert"` |

**Fix:**
```blade
{{-- login.blade.php --}}
<a href="{{ route('register') }}">Sign Up</a>  {{-- FIX #1 --}}
<input type="checkbox" name="remember" id="remember">  {{-- Remove required --}}

{{-- app.blade.php --}}
<title>ResiSquare | Property Management</title>  {{-- FIX #3 --}}
<button data-bs-dismiss="alert">×</button>  {{-- FIX #4 --}}
```

**Playwright Tests:**
```typescript
test('Login form submits without Remember Me checked', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'user@test.com');
  await page.fill('input[name="password"]', 'password');
  // Do NOT check "Remember Me"
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/\/admin\/dashboard/);
});

test('Sign Up link navigates to registration', async ({ page }) => {
  await page.goto('/login');
  await page.click('text=Sign Up');
  await expect(page).toHaveURL('/register');
  await expect(page.locator('form')).toBeVisible();
});

test('Page title is correct on public pages', async ({ page }) => {
  await page.goto('/login');
  await expect(page).toHaveTitle(/ResiSquare/);
});

test('Alert dismiss button works', async ({ page }) => {
  await page.goto('/login');
  await page.click('button[data-bs-dismiss="alert"]');
  await expect(page.locator('.alert')).not.toBeVisible();
});
```

---

### 3.2 Bootstrap 5 Attribute Mismatches (HIGH)

**Files affected:**
- `resources/views/backend/modals/delete_modal.blade.php:7, 11`
- `resources/views/backend/modals/ai_popup_modal.blade.php:7, 26`
- `resources/views/backend/uploaded_files/index.blade.php:140`
- `resources/views/frontend/layout/app.blade.php:51`

**Problem:** All use `data-dismiss="modal"` / `data-dismiss="alert"` instead of `data-bs-dismiss="modal"` / `data-bs-dismiss="alert"`.

**Fix:**
```bash
# Global search and replace
grep -r 'data-dismiss="modal"' resources/views/ -l
grep -r 'data-dismiss="alert"' resources/views/ -l
```

```blade
{{-- BEFORE --}}
<button data-dismiss="modal">Close</button>

{{-- AFTER --}}
<button data-bs-dismiss="modal">Close</button>
```

**Playwright Tests:**
```typescript
test('Delete modal closes on X button click', async ({ page }) => {
  await page.goto('/admin/properties');
  await page.click('button:has-text("Delete") >> nth=0');
  await expect(page.locator('.modal.show')).toBeVisible();
  await page.click('button[data-bs-dismiss="modal"]');
  await expect(page.locator('.modal.show')).not.toBeVisible();
});

test('Alert dismiss button works', async ({ page }) => {
  await page.goto('/admin/dashboard');
  // Trigger an alert (e.g., form validation error)
  await page.click('button[data-bs-dismiss="alert"]');
  await expect(page.locator('.alert')).not.toBeVisible();
});
```

---

### 3.3 JavaScript Scope Error (HIGH)

**File:** `resources/views/backend/users/index.blade.php:292-294, 526`

**Problem:** `loadTabContent()` references `activeRole` declared with `var` in a separate `$(function() {})` block. `var` is function-scoped, causing `ReferenceError`.

**Fix:**
```javascript
{{-- BEFORE --}}
$(function() {
    var activeRole = $('#role_id').val();
});

function loadTabContent() {
    var role = activeRole;  // ReferenceError!
}

{{-- AFTER --}}
var activeRole;  // Declare in outer scope

$(function() {
    activeRole = $('#role_id').val();
});

function loadTabContent() {
    var role = activeRole;  // Works
}
```

**Playwright Test:**
```typescript
test('Users index AJAX tabs load without console errors', async ({ page }) => {
  const consoleErrors: string[] = [];
  page.on('console', msg => {
    if (msg.type() === 'error') consoleErrors.push(msg.text());
  });
  
  await page.goto('/admin/users');
  await page.click('text=Contacts');
  
  await expect(page.locator('.tab-content')).toBeVisible();
  expect(consoleErrors).not.toContain('ReferenceError: activeRole is not defined');
});
```

---

### 3.4 Contractor Detail View JavaScript (HIGH)

**File:** `resources/views/frontend/contractor/detail/show.blade.php:48`

**Problem:** Scripts pushed to `@push('extra.scripts')` but `backend.layout.app` only renders `@stack('scripts')`.

**Fix:**
```blade
{{-- backend/layout/app.blade.php --}}
@stack('scripts')
@stack('extra.scripts')  {{-- ADD THIS --}}
```

**OR:**

```blade
{{-- contractor/detail/show.blade.php --}}
@push('scripts')  {{-- Change from extra.scripts --}}
```

**Playwright Test:**
```typescript
test('Contractor detail accordion toggle works', async ({ page }) => {
  const consoleErrors: string[] = [];
  page.on('console', msg => {
    if (msg.type() === 'error') consoleErrors.push(msg.text());
  });
  
  await page.goto('/contractor/detail/1');
  await page.click('text=Collapse All');
  
  // All accordion items should be collapsed
  await expect(page.locator('.accordion-item.show')).toHaveCount(0);
  expect(consoleErrors).toEqual([]);
});
```

---

### 3.5 Duplicate DOM IDs (MEDIUM)

**File:** `resources/views/backend/properties/popup_forms/property_media.blade.php`

**Problem:**
- `id="photos"` appears twice on one input
- Two live controls with `id="view_360"` and `name="view_360"`

**Fix:**
```blade
{{-- BEFORE --}}
<input id="photos" name="photos[]" type="file">
<input id="photos" name="photos[]" type="file">  {{-- Duplicate ID! --}}

{{-- AFTER --}}
<input id="photos_1" name="photos[]" type="file">
<input id="photos_2" name="photos[]" type="file">

{{-- BEFORE --}}
<input type="text" id="view_360" name="view_360">
<input type="hidden" id="view_360" name="view_360">  {{-- Duplicate ID! --}}

{{-- AFTER --}}
<input type="text" id="view_360_url" name="view_360_url">
<input type="hidden" id="view_360_hidden" name="view_360">
```

**Playwright Test:**
```typescript
test('Property media form has no duplicate DOM IDs', async ({ page }) => {
  await page.goto('/admin/properties/quick-create');
  await page.click('text=Media');
  
  const ids = await page.evaluate(() => {
    return Array.from(document.querySelectorAll('[id]'))
      .map(el => el.id);
  });
  
  const duplicates = ids.filter((id, index) => ids.indexOf(id) !== index);
  expect(duplicates).toEqual([]);
});
```

---

### 3.6 Empty onclick Attributes (LOW-MEDIUM)

**Files:**
- `resources/views/backend/properties/index.blade.php:18, 65, 77, 107`
- `resources/views/backend/users/index.blade.php:18`
- `resources/views/backend/components/modal.blade.php:88`
- `resources/views/helper.blade.php:166, 193, 221, 249, 276`

**Problem:** Buttons have `onclick=""` or `onClick=''` — appear interactive but do nothing.

**Fix:** Either wire up the click handlers OR remove the attributes and disable the buttons:

```blade
{{-- BEFORE --}}
<button onclick="">Owners</button>

{{-- AFTER --}}
<button onclick="showOwnersModal()">Owners</button>
{{-- OR --}}
<button disabled>Owners</button>
```

**Playwright Test:**
```typescript
test('No interactive element has empty onclick', async ({ page }) => {
  await page.goto('/admin/properties');
  
  const emptyOnclicks = await page.evaluate(() => {
    return Array.from(document.querySelectorAll('[onclick], [onClick]'))
      .filter(el => (el.getAttribute('onclick') || el.getAttribute('onClick') || '').trim() === '')
      .map(el => el.outerHTML.substring(0, 100));
  });
  
  expect(emptyOnclicks).toEqual([]);
});
```

---

### 3.7 Inert Sidebar Links (HIGH)

**File:** `resources/views/backend/partials/aside2.blade.php`

**Problem:** Documents, Users, Settings, Reports links have `href="#"` and no action.

**Fix:**
```blade
{{-- BEFORE --}}
<a href="#">Documents</a>
<a href="#">Users</a>
<a href="#">Settings</a>
<a href="#">Reports</a>

{{-- AFTER --}}
<a href="{{ route('admin.documents.index') }}">Documents</a>
<a href="{{ route('admin.users.index') }}">Users</a>
<a href="{{ route('admin.settings.index') }}">Settings</a>
<a href="{{ route('admin.reports.index') }}">Reports</a>
{{-- OR remove if not implemented --}}
```

**Playwright Test:**
```typescript
test('Sidebar links navigate to valid pages', async ({ page }) => {
  await page.goto('/admin/dashboard');
  
  const sidebarLinks = await page.locator('aside a[href]').all();
  for (const link of sidebarLinks) {
    const href = await link.getAttribute('href');
    if (href && href !== '#') {
      await page.goto(href);
      await expect(page.locator('body')).not.toContainText('Whoops');
    }
  }
});
```

---

### 3.8 Notification Links Visible But Inaccessible (MEDIUM)

**File:** `resources/views/backend/partials/navbar.blade.php`

**Problem:** View all / Preferences links rendered for all authenticated users, but routes require `current.account` and `account.status`.

**Fix:**
```blade
{{-- BEFORE --}}
<a href="{{ route('backend.notifications.index') }}">View all</a>

{{-- AFTER --}}
@if(auth()->user()->current_account && auth()->user()->account->status === 'active')
    <a href="{{ route('backend.notifications.index') }}">View all</a>
@endif
```

**Playwright Test:**
```typescript
test('Notification links hidden when no active account', async ({ page }) => {
  // Login as Super Admin without current_account
  await page.goto('/admin/dashboard');
  await expect(page.locator('text=View all')).not.toBeVisible();
});
```

---

### 3.9 Public Authentication Uses Backend Shell (HIGH)

**Problem:**
- Login page title is "Backend Dashboard"
- Login page includes backend navbar
- Sidebar toggle visible but meaningless
- Clear Cache button visible on login page

**Fix:**
```blade
{{-- resources/views/frontend/login.blade.php --}}
{{-- Change layout from backend.layout.app to frontend.layout.app --}}
@extends('frontend.layout.app')

{{-- Remove backend navbar --}}
{{-- Remove Clear Cache button --}}
```

**Playwright Test:**
```typescript
test('Login page uses public layout', async ({ page }) => {
  await page.goto('/login');
  
  // Assert: No backend navbar
  await expect(page.locator('nav.navbar-dark')).not.toBeVisible();
  
  // Assert: No sidebar toggle
  await expect(page.locator('.sidebar-toggle')).not.toBeVisible();
  
  // Assert: No Clear Cache button
  await expect(page.locator('text=Clear Cache')).not.toBeVisible();
  
  // Assert: Correct page title
  await expect(page).toHaveTitle(/ResiSquare/);
});
```

---

### 3.10 Debug Output Visible in Production (HIGH)

**File:** `resources/views/backend/users/tabs/contact.blade.php` (and Bank, Compliance tabs)

**Problem:** `var_dump($userId)` outputs `User ID: string(3) "157"` in rendered response.

**Fix:**
```php
{{-- BEFORE --}}
<div class="tab-content">
    <?php var_dump($userId); ?>
    <!-- rest of content -->
</div>

{{-- AFTER --}}
<div class="tab-content">
    <!-- rest of content -->
</div>
```

**Playwright Test:**
```typescript
test('No debug output in rendered pages', async ({ page }) => {
  await page.goto('/admin/users?user_id=1&tabname=Contact');
  
  const content = await page.content();
  expect(content).not.toContain('string(');
  expect(content).not.toContain('var_dump');
  expect(content).not.toContain('dd(');
});
```

---

### 3.11 Forgot Password Broken Assets (HIGH)

**Problem:** Forgot Password page loads 3 relative JS assets returning 404:
- `asset/js/jquery.min.js` → resolves to `/password/asset/js/...`
- `asset/js/bootstrap.bundle.min.js`
- `asset/js/toastr.min.js`

**Fix:**
```blade
{{-- BEFORE --}}
<script src="asset/js/jquery.min.js"></script>

{{-- AFTER --}}
<script src="{{ asset('js/jquery.min.js') }}"></script>
```

**Playwright Test:**
```typescript
test('Forgot Password page loads all assets', async ({ page }) => {
  const failedRequests: string[] = [];
  page.on('requestfailed', request => {
    if (request.resourceType() === 'script' || request.resourceType() === 'stylesheet') {
      failedRequests.push(request.url());
    }
  });
  
  await page.goto('/password/forgot');
  
  expect(failedRequests).toEqual([]);
  await expect(page.locator('input[name="email"]')).toBeVisible();
});
```

---

<a name="p3"></a>
## 4. P3 — Code Quality & Architecture

### 4.1 Permission Seeder Alignment (HIGH)

**Problem:** ~20 Blade files check non-existent permissions:
- `manage tenancies` → should be `view tenancies` or similar
- `create property repair` → should be `create repairs`
- `edit important note` → should be `edit notes`
- `view calendar` → not in seeder
- `view deleted properties` → not in seeder

**Fix:**
```php
// database/seeders/RoleAndPermissionSeeder.php
// Add missing permissions:
Permission::create(['name' => 'manage tenancies', 'guard_name' => 'web']);
Permission::create(['name' => 'create property repair', 'guard_name' => 'web']);
// OR update blade files to use existing permissions
```

**Playwright Test:**
```typescript
test('All permission checks reference valid permissions', async ({ page }) => {
  // This is a static analysis test, not a browser test
  // Run: php artisan permissions:validate
  // Assert: Exit code 0, no missing permissions
});
```

---

### 4.2 Unsafe Upload Pipeline (HIGH)

**Problem:**
- Trusts client extensions (`.svg`, `.xml`, `.zip` accepted)
- Stores on public disk
- No MIME/content/size validation
- No malware scanning
- No private authorized download pipeline

**Fix:**
```php
// UploadController.php
public function store(Request $request) {
    $request->validate([
        'file' => 'required|file|max:10240|mimetypes:image/jpeg,image/png,application/pdf'
    ]);
    
    $path = $request->file('file')->store('private/uploads');
    
    // Scan for malware (ClamAV or similar)
    // Store in S3/private disk
}
```

**Playwright Test:**
```typescript
test('Rejects unsafe file uploads', async ({ page }) => {
  await page.goto('/admin/uploaded-files/create');
  
  // Try uploading SVG (should be rejected)
  await page.setInputFiles('input[type="file"]', 'malicious.svg');
  await page.click('button:has-text("Upload")');
  
  await expect(page.locator('text=Invalid file type')).toBeVisible();
});

test('Uploaded files are not publicly accessible', async ({ page }) => {
  await page.goto('/admin/uploaded-files');
  const fileUrl = await page.locator('.file-link >> nth=0').getAttribute('href');
  
  // Try accessing file without authentication
  const response = await page.request.get(fileUrl!);
  expect(response.status()).toBe(403);
});
```

---

### 4.3 Weak Authentication Lifecycle (HIGH)

**Problems:**
- No throttles on login/reset/registration/OTP
- No session regeneration after login
- Logout doesn't invalidate session or regenerate CSRF

**Fix:**
```php
// routes/web.php
Route::post('/login', 'Auth\LoginController@login')
    ->middleware('throttle:5,1');  // 5 attempts per minute

// LoginController.php
public function login(Request $request) {
    // ... validate credentials ...
    $request->session()->regenerate();  // Regenerate session ID
}

// LoginController.php - logout
public function logout(Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();  // Regenerate CSRF
}
```

**Playwright Tests:**
```typescript
test('Brute-force protection on login', async ({ page }) => {
  for (let i = 0; i < 6; i++) {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@resisquare.test');
    await page.fill('input[name="password"]', 'wrong');
    await page.click('button[type="submit"]');
  }
  
  // 6th attempt should be throttled
  await expect(page.locator('text=Too many attempts')).toBeVisible();
});

test('Session regenerated after login', async ({ page }) => {
  const context = await browser.newContext();
  const page2 = await context.newPage();
  
  // Login in first tab
  await page.goto('/login');
  await page.fill('input[name="email"]', 'admin@resisquare.test');
  await page.fill('input[name="password"]', 'Password123!');
  await page.click('button[type="submit"]');
  
  // Try old session cookie in second tab
  await page2.goto('/admin/dashboard');
  
  // Should redirect to login (old session invalid)
  await expect(page2).toHaveURL('/login');
});

test('Logout invalidates session', async ({ page }) => {
  await page.goto('/admin/dashboard');
  await page.click('text=Logout');
  
  // Try going back
  await page.goto('/admin/dashboard');
  await expect(page).toHaveURL('/login');
});
```

---

### 4.4 State-Changing GET Routes (HIGH)

**Problem:**
- `GET /admin/clear-cache` changes server state
- `GET /storage-link` creates symlink
- `GET /command/optimize-clear` runs Artisan command

**Fix:**
```php
// Remove GET routes for state-changing operations
// Use POST with CSRF protection

// routes/web.php
Route::post('/admin/clear-cache', 'Admin\SystemController@clearCache')
    ->middleware('auth');

// Or use Artisan commands via queue, not web routes
```

**Playwright Test:**
```typescript
test('Clear cache is not accessible via GET', async ({ page }) => {
  await page.goto('/admin/clear-cache');
  
  // Should return 405 Method Not Allowed or redirect
  await expect(page).toHaveURL(/\/admin\/dashboard/);
});

test('Maintenance routes are not publicly accessible', async ({ page }) => {
  const routes = ['/storage-link', '/command/optimize-clear', '/helper'];
  
  for (const route of routes) {
    const response = await page.request.get(route);
    expect([404, 405, 403]).toContain(response.status());
  }
});
```

---

### 4.5 Email Template Security Issues (MEDIUM)

**Files:**
- `resources/views/emails/contact.blade.php:4`
- `resources/views/emails/guest_account_opening.blade.php:3`
- `resources/views/emails/newsletter.blade.php:2`
- `resources/views/emails/invoice.blade.php:93`

**Issues:**
1. `contact.blade.php`: `@if ($email != null || $email != '')` — always true
2. `guest_account_opening.blade.php`: Outputs plaintext password
3. `newsletter.blade.php`: Uses `echo` instead of `{{ }}` (XSS risk)
4. `invoice.blade.php`: `date('d-m-Y', $order->date)` — `date()` expects int, not Carbon

**Fix:**
```blade
{{-- contact.blade.php --}}
@if ($email)  {{-- Use truthy check --}}

{{-- guest_account_opening.blade.php --}}
{{-- REMOVE password from email --}}
{{-- Use password reset link instead --}}
<a href="{{ route('password.reset', $token) }}">Set your password</a>

{{-- newsletter.blade.php --}}
{{ $array['content'] }}  {{-- Use Blade escaping --}}

{{-- invoice.blade.php --}}
{{ $order->date->format('d-m-Y') }}  {{-- Use Carbon --}}
```

**Playwright Test:**
```typescript
test('Email templates render without errors', async ({ page }) => {
  // Trigger email sending (e.g., new registration)
  // Check that emails are sent successfully
  // Assert: No TypeError in logs
});

test('Newsletter template escapes HTML', async ({ page }) => {
  // Send newsletter with XSS payload
  // Assert: Payload is escaped in rendered email
});
```

---

<a name="p4"></a>
## 5. P4 — E2E Test Strategy (Playwright)

### 5.1 Playwright Setup

**Directory Structure:**
```
tests/
  e2e/
    playwright.config.ts
    global-setup.ts
    global-teardown.ts
    fixtures/
      auth.ts
      roles.ts
    pages/
      LoginPage.ts
      DashboardPage.ts
      PropertiesPage.ts
      TenanciesPage.ts
      UsersPage.ts
      ContactsPage.ts
      DocumentsPage.ts
      RepairsPage.ts
      InvoicesPage.ts
      AccountingPage.ts
      SettingsPage.ts
    specs/
      auth/
        login.spec.ts
        logout.spec.ts
        forgot-password.spec.ts
        registration.spec.ts
        otp.spec.ts
      super-admin/
        dashboard.spec.ts
        properties.spec.ts
        tenancies.spec.ts
        users.spec.ts
        contacts.spec.ts
        documents.spec.ts
        accounting.spec.ts
        notifications.spec.ts
      landlord/
        dashboard.spec.ts
        properties.spec.ts
        tenancies.spec.ts
        repairs.spec.ts
      estate-agent/
        dashboard.spec.ts
        properties.spec.ts
        staff.spec.ts
        offers.spec.ts
      tenant/
        dashboard.spec.ts
        repairs.spec.ts
        documents.spec.ts
      contractor/
        dashboard.spec.ts
        repairs.spec.ts
        quotes.spec.ts
      cross-cutting/
        role-isolation.spec.ts
        direct-url-access.spec.ts
        responsive.spec.ts
        accessibility.spec.ts
        javascript-errors.spec.ts
```

**playwright.config.ts:**
```typescript
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './specs',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [
    ['html'],
    ['json', { outputFile: 'test-results/results.json' }],
    ['junit', { outputFile: 'test-results/junit.xml' }]
  ],
  use: {
    baseURL: 'http://127.0.0.1:8000',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    { name: 'super-admin', testMatch: '**/super-admin/**' },
    { name: 'landlord', testMatch: '**/landlord/**' },
    { name: 'estate-agent', testMatch: '**/estate-agent/**' },
    { name: 'tenant', testMatch: '**/tenant/**' },
    { name: 'contractor', testMatch: '**/contractor/**' },
    { name: 'cross-cutting', testMatch: '**/cross-cutting/**' },
  ],
});
```

**fixtures/auth.ts:**
```typescript
import { test as base } from '@playwright/test';

type AuthFixtures = {
  authenticatedPage: Page;
};

export const test = base.extend<AuthFixtures>({
  authenticatedPage: async ({ page }, use) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@resisquare.test');
    await page.fill('input[name="password"]', 'Password123!');
    await page.click('button[type="submit"]');
    await page.waitForURL('/admin/dashboard');
    await use(page);
  },
});

export { expect } from '@playwright/test';
```

---

### 5.2 Critical Test Suites

#### A. Authentication & Security
```typescript
// auth/login.spec.ts
test.describe('Login', () => {
  test('successful login redirects to dashboard', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@resisquare.test');
    await page.fill('input[name="password"]', 'Password123!');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL('/admin/dashboard');
  });

  test('invalid credentials show error', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'wrong@test.com');
    await page.fill('input[name="password"]', 'wrong');
    await page.click('button[type="submit"]');
    await expect(page.locator('text=Invalid credentials')).toBeVisible();
  });

  test('login without CSRF token fails', async ({ page }) => {
    await page.goto('/login');
    await page.evaluate(() => {
      const token = document.querySelector('input[name="_token"]') as HTMLInputElement;
      token.value = '';
    });
    await page.click('button[type="submit"]');
    await expect(page.locator('text=CSRF token mismatch')).toBeVisible();
  });

  test('brute-force protection after 5 failed attempts', async ({ page }) => {
    for (let i = 0; i < 5; i++) {
      await page.goto('/login');
      await page.fill('input[name="email"]', 'admin@resisquare.test');
      await page.fill('input[name="password"]', 'wrong');
      await page.click('button[type="submit"]');
    }
    await expect(page.locator('text=Too many login attempts')).toBeVisible();
  });
});
```

#### B. Role-Based Access Control
```typescript
// cross-cutting/role-isolation.spec.ts
test.describe('Role Isolation', () => {
  const roles = [
    { name: 'Super Admin', email: 'admin@resisquare.test' },
    { name: 'Landlord Owner', email: 'landlord.owner@resisquare.test' },
    { name: 'Estate Agent Owner', email: 'estate.owner@resisquare.test' },
    // ... etc
  ];

  for (const role of roles) {
    test.describe(`${role.name} role`, () => {
      test.use({ storageState: undefined });  // Fresh state per role

      test('can login', async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[name="email"]', role.email);
        await page.fill('input[name="password"]', 'Password123!');
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL(/\/admin\/dashboard/);
      });

      test('sees correct sidebar items', async ({ page }) => {
        // Login and verify sidebar matches role permissions
      });

      test('cannot access unauthorized pages', async ({ page }) => {
        // Attempt to access each module page
        // Assert 403 or redirect for unauthorized modules
      });
    });
  }
});
```

#### C. CRUD Operations
```typescript
// super-admin/properties.spec.ts
test.describe('Properties CRUD', () => {
  test('create property', async ({ authenticatedPage }) => {
    const page = authenticatedPage;
    await page.goto('/admin/properties/quick-create');
    
    await page.fill('input[name="name"]', 'Test Property');
    await page.fill('input[name="address"]', '123 Test St');
    await page.selectOption('select[name="property_type"]', 'residential');
    await page.click('button:has-text("Save")');
    
    await expect(page.locator('text=Property created successfully')).toBeVisible();
    await expect(page).toHaveURL(/\/admin\/properties\/\d+/);
  });

  test('edit property', async ({ authenticatedPage }) => {
    const page = authenticatedPage;
    await page.goto('/admin/properties/1/edit');
    
    await page.fill('input[name="name"]', 'Updated Property');
    await page.click('button:has-text("Update")');
    
    await expect(page.locator('text=Property updated')).toBeVisible();
  });

  test('delete property', async ({ authenticatedPage }) => {
    const page = authenticatedPage;
    await page.goto('/admin/properties');
    
    await page.click('button:has-text("Delete") >> nth=0');
    await page.click('button:has-text("Confirm")');
    
    await expect(page.locator('text=Property deleted')).toBeVisible();
  });
});
```

#### D. Accounting Cross-Account Isolation
```typescript
// cross-cutting/role-isolation.spec.ts
test.describe('Cross-Account Isolation', () => {
  test('Account A cannot view Account B receipts', async ({ page }) => {
    // Setup: Create two accounts with separate users
    // Login as Account A user
    // Try to access Account B receipt: /admin/accounting/receipts/{account_b_id}
    // Assert: 403 or 404
  });

  test('Account A cannot edit Account B invoices', async ({ page }) => {
    // Login as Account A user
    // PUT /admin/accounting/sale/invoices/{account_b_id}
    // Assert: 403 or 404
  });

  test('Account A cannot export Account B statements', async ({ page }) => {
    // Login as Account A user
    // GET /admin/accounting/statements/customers?account_id={account_b_id}
    // Assert: Empty results or 403
  });
});
```

---

<a name="p5"></a>
## 6. Edge Cases & Negative Testing Matrix

### 6.1 Input Validation Edge Cases

| Test Case | Input | Expected Result |
|-----------|-------|----------------|
| Empty email on login | `""` | Validation error |
| SQL injection in email | `admin' OR '1'='1` | Validation error or safe handling |
| XSS in property name | `<script>alert('xss')</script>` | Escaped in output |
| Negative number in invoice amount | `-100` | Validation error |
| Future date in tenancy start | `2099-01-01` | Validation error or accepted |
| Oversized file upload | `100MB file` | `max:10240` validation error |
| Wrong file type | `.php` file | MIME type rejection |
| Duplicate email registration | Existing email | Validation error |
| Special characters in password | `!@#$%^&*()` | Accepted (if allowed) |
| CSRF token missing | POST without token | 419 error |

**Playwright Implementation:**
```typescript
test.describe('Input Validation', () => {
  test('rejects SQL injection in login', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', "admin' OR '1'='1");
    await page.fill('input[name="password"]', 'Password123!');
    await page.click('button[type="submit"]');
    await expect(page.locator('text=Invalid credentials')).toBeVisible();
  });

  test('escapes XSS in property name', async ({ page }) => {
    await page.goto('/admin/properties/quick-create');
    await page.fill('input[name="name"]', '<script>alert("xss")</script>');
    await page.fill('input[name="address"]', 'Test');
    await page.click('button:has-text("Save")');
    
    await expect(page.locator('text=alert("xss")')).not.toBeVisible();
  });

  test('rejects oversized file upload', async ({ page }) => {
    await page.goto('/admin/uploaded-files/create');
    
    // Create 11MB file
    const largeFile = Buffer.alloc(11 * 1024 * 1024, 'x');
    
    await page.setInputFiles('input[type="file"]', {
      name: 'large.pdf',
      mimeType: 'application/pdf',
      buffer: largeFile,
    });
    
    await page.click('button:has-text("Upload")');
    await expect(page.locator('text=File size exceeds')).toBeVisible();
  });
});
```

---

### 6.2 URL Tampering & Direct Access

| Test Case | URL | Expected Result |
|-----------|-----|-----------------|
| Access other account's property | `/admin/properties/999` (Account B) | 403 or 404 |
| Access other account's receipt | `/admin/accounting/receipts/999` | 403 or 404 |
| Access non-existent user | `/admin/users/99999` | 404 |
| Access deleted record | `/admin/tenancies/1` (deleted) | 404 |
| Access with invalid UUID | `/admin/properties/not-a-number` | 404 or 400 |
| Access PDF export without permission | Direct URL to PDF | 403 |

**Playwright Implementation:**
```typescript
test.describe('Direct URL Access Control', () => {
  test('cannot access another account property by ID', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'landlord.owner@resisquare.test');
    await page.fill('input[name="password"]', 'Password123!');
    await page.click('button[type="submit"]');
    
    // Try to access property from different account
    await page.goto('/admin/properties/999');
    await expect(page).toHaveURL(/\/admin\/properties/);
    await expect(page.locator('text=Unauthorized')).toBeVisible();
  });

  test('cannot access deleted record', async ({ page }) => {
    await page.goto('/admin/tenancies/99999');
    await expect(page.locator('text=Not Found')).toBeVisible();
  });
});
```

---

### 6.3 Concurrent Operations

| Test Case | Scenario | Expected Result |
|-----------|----------|----------------|
| Simultaneous form submissions | Submit same form twice | One succeeds, one fails gracefully |
| Race condition on inventory | Two users edit same record | Last write wins or optimistic locking |
| Session expiration during form fill | Wait 2 hours, submit form | Redirect to login, data preserved or warning |
| Network timeout during upload | Upload large file, disconnect | Retry mechanism or error message |

---

<a name="p6"></a>
## 7. Responsive & Accessibility Coverage

### 7.1 Responsive Breakpoints

```typescript
// responsive.spec.ts
const breakpoints = [
  { name: 'Mobile', width: 375, height: 667 },
  { name: 'Tablet', width: 768, height: 1024 },
  { name: 'Laptop', width: 1366, height: 768 },
  { name: 'Desktop', width: 1920, height: 1080 },
];

for (const bp of breakpoints) {
  test.describe(`${bp.name} (${bp.width}x${bp.height})`, () => {
    test.use({ viewport: { width: bp.width, height: bp.height } });

    test('dashboard layout is usable', async ({ page }) => {
      await page.goto('/admin/dashboard');
      
      // Sidebar should collapse on mobile
      if (bp.width < 768) {
        await expect(page.locator('.sidebar')).not.toBeVisible();
      }
      
      // All critical buttons visible and clickable
      const buttons = await page.locator('button, a').all();
      for (const btn of buttons.slice(0, 10)) {
        const box = await btn.boundingBox();
        expect(box!.width).toBeGreaterThan(0);
        expect(box!.height).toBeGreaterThan(0);
      }
    });

    test('tables are scrollable', async ({ page }) => {
      await page.goto('/admin/properties');
      
      // Table should have horizontal scroll on small screens
      if (bp.width < 768) {
        const table = page.locator('table');
        const scrollWidth = await table.evaluate(el => el.scrollWidth);
        const clientWidth = await table.evaluate(el => el.clientWidth);
        expect(scrollWidth).toBeGreaterThan(clientWidth);
      }
    });
  });
}
```

### 7.2 Accessibility

```typescript
// accessibility.spec.ts
test.describe('Accessibility', () => {
  test('all images have alt text', async ({ page }) => {
    await page.goto('/admin/properties');
    
    const imagesWithoutAlt = await page.evaluate(() => {
      return Array.from(document.querySelectorAll('img'))
        .filter(img => !img.hasAttribute('alt'))
        .length;
    });
    
    expect(imagesWithoutAlt).toBe(0);
  });

  test('all form inputs have labels', async ({ page }) => {
    await page.goto('/admin/properties/quick-create');
    
    const inputsWithoutLabels = await page.evaluate(() => {
      return Array.from(document.querySelectorAll('input, select, textarea'))
        .filter(input => {
          const id = input.id;
          return !id || !document.querySelector(`label[for="${id}"]`);
        })
        .length;
    });
    
    expect(inputsWithoutLabels).toBe(0);
  });

  test('page has proper heading hierarchy', async ({ page }) => {
    await page.goto('/admin/dashboard');
    
    const headings = await page.evaluate(() => {
      return Array.from(document.querySelectorAll('h1, h2, h3'))
        .map(h => ({ tag: h.tagName, text: h.textContent?.substring(0, 50) }));
    });
    
    // Should have exactly one h1
    const h1Count = headings.filter(h => h.tag === 'H1').length;
    expect(h1Count).toBe(1);
  });

  test('keyboard navigation works', async ({ page }) => {
    await page.goto('/login');
    
    // Tab through form
    await page.keyboard.press('Tab');
    await page.keyboard.press('Tab');
    await page.keyboard.press('Tab');
    
    // Should be able to submit with Enter
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/\/admin\/dashboard/);
  });

  test('color contrast meets WCAG AA', async ({ page }) => {
    // Use axe-core or similar library
    await page.goto('/admin/dashboard');
    
    const accessibilityIssues = await page.evaluate(async () => {
      // Integrate axe-core here
      return [];
    });
    
    expect(accessibilityIssues).toEqual([]);
  });
});
```

---

<a name="p7"></a>
## 8. Infrastructure & DevOps

### 8.1 Composer Dependency Audit

**Current Status:** 34 advisories across 12 packages, 1 abandoned package

**Fix:**
```bash
# Update dependencies
composer update --with-all-dependencies

# Review and fix advisories
composer audit

# Remove abandoned package
composer remove abandoned/package

# Add frontend lockfile
npm install
npm run build
git commit package-lock.json
```

**Playwright Test:**
```typescript
test('no JavaScript console errors on any page', async ({ page }) => {
  const consoleErrors: string[] = [];
  page.on('console', msg => {
    if (msg.type() === 'error') consoleErrors.push(msg.text());
  });
  
  const pages = [
    '/admin/dashboard',
    '/admin/properties',
    '/admin/tenancies',
    '/admin/invoices',
    '/admin/accounting/receipts',
  ];
  
  for (const url of pages) {
    await page.goto(url);
    await page.waitForTimeout(2000);  // Wait for JS to execute
  }
  
  expect(consoleErrors).toEqual([]);
});
```

### 8.2 Node/npm Verification

**Problem:** Node/npm unavailable, frontend build blocked

**Fix:**
```bash
# Install Node.js LTS
# Add to PATH
npm install
npm run build
```

---

<a name="p8"></a>
## 9. Execution Timeline

### First 24–72 Hours (P0 + P1 Critical)

**Day 1:**
- [ ] Fix all 16 HTTP 500/404 routes (Section 2)
- [ ] Fix tenancy edit `@method('PUT')` (Section 2.3)
- [ ] Fix login Sign Up link (Section 3.1)
- [ ] Remove `required` from Remember Me (Section 3.1)
- [ ] Fix Forgot Password asset paths (Section 3.11)
- [ ] Remove `var_dump` from user tabs (Section 3.10)
- [ ] Replace all `data-dismiss` with `data-bs-dismiss` (Section 3.2)
- [ ] Fix `activeRole` JavaScript scope error (Section 3.3)
- [ ] Fix contractor detail script push (Section 3.4)
- [ ] Add Playwright configuration and first test suite

**Day 2:**
- [ ] Fix cross-account accounting access (Section 1.1)
- [ ] Add server-side authorization to RoleController (Section 1.2)
- [ ] Remove or protect environment mutation route (Section 1.3)
- [ ] Fix route name mismatches (Section 2.1)
- [ ] Replace inert sidebar links (Section 3.7)
- [ ] Hide notification links when no active account (Section 3.8)
- [ ] Add route smoke regression test

**Day 3:**
- [ ] Fix duplicate DOM IDs (Section 3.5)
- [ ] Fix empty onclick attributes (Section 3.6)
- [ ] Fix public authentication layout (Section 3.9)
- [ ] Fix missing controller methods (Section 2.2)
- [ ] Run full Playwright suite for Super Admin
- [ ] Fix database migration issues (Section 1.4)

### Days 4–7 (P2 + P3)

- [ ] Fix permission seeder alignment (Section 4.1)
- [ ] Implement upload validation and private storage (Section 4.2)
- [ ] Add authentication throttles and session rotation (Section 4.3)
- [ ] Remove state-changing GET routes (Section 4.4)
- [ ] Fix email template security issues (Section 4.5)
- [ ] Add Playwright tests for all CRUD operations
- [ ] Run Playwright tests for all available roles

### Days 8–14 (P4 + P5 + P6)

- [ ] Add Playwright tests for cross-cutting concerns
- [ ] Implement responsive test suite
- [ ] Implement accessibility test suite
- [ ] Add edge case and negative tests
- [ ] Set up CI/CD integration for Playwright
- [ ] Fix any issues found by automated tests

### Days 15–21 (Final Preparation)

- [ ] Provision all 8 role test users in staging database
- [ ] Run complete Playwright suite across all roles
- [ ] Run responsive and accessibility checks
- [ ] Exercise Stripe test checkout, SMTP, SMS/OTP
- [ ] Rerun full audit and verify zero unexpected 4xx/5xx
- [ ] Final security review

---

<a name="p9"></a>
## 10. Release Gates

All gates must pass before production or client UAT:

- [ ] **Zero** registered client pages return unexpected 4xx/5xx
- [ ] **Zero** visible controls are inert unless explicitly disabled with explanation
- [ ] **Every** documented role can complete primary workflow in automated E2E tests
- [ ] **Every** tenant-owned read/write/export/delete is account-scoped and negative-tested
- [ ] **Both** clean install and representative upgrade pass on MySQL
- [ ] **Zero** unexplained pending migrations in current database
- [ ] **Zero** unaccepted high/critical advisories in Composer/frontend audits
- [ ] **Zero** web routes write `.env`, run Artisan maintenance, or perform mutation through GET
- [ ] **All** login/reset/OTP throttling and session lifecycle controls pass security tests
- [ ] **All** uploads are private, validated, scanned, authorized, and audited
- [ ] **Zero** debug mode/tooling and raw debug output in client environments
- [ ] **All** Playwright tests pass (100% pass rate)
- [ ] **All** JavaScript console error checks pass (zero errors across all pages)
- [ ] **All** responsive breakpoints tested and pass
- [ ] **All** accessibility checks pass (WCAG 2.1 AA)
- [ ] **All** security tests pass (no cross-account access, no CSRF bypass, no injection vulnerabilities)

---

## Appendix A: Playwright Test Execution Commands

```bash
# Install Playwright
npm init playwright@latest

# Run all tests
npx playwright test

# Run specific role
npx playwright test --project=super-admin

# Run specific suite
npx playwright test specs/super-admin/properties.spec.ts

# Run with UI
npx playwright test --ui

# Run with debug
npx playwright test --debug

# Generate test report
npx playwright show-report

# Run in CI
npx playwright test --reporter=junit --output=test-results/
```

## Appendix B: Test Data Requirements

**Required test users (all must exist in database):**

| Email | Role | Account Type |
|-------|------|--------------|
| admin@resisquare.test | Super Admin | Platform |
| landlord.owner@resisquare.test | Landlord Owner | landlord |
| landlord.contact@resisquare.test | Landlord Contact | landlord |
| estate.owner@resisquare.test | Estate Agent Owner | estate_agent_company |
| estate.staff@resisquare.test | Estate Agent Staff | estate_agent_company |
| tenant@resisquare.test | Tenant | tenant |
| contractor@resisquare.test | Contractor | contractor |
| property.manager@resisquare.test | Property Manager | property_manager |

**Required test data per account:**
- 2-3 properties
- 2-3 tenancies
- 1-2 repairs
- 3-5 invoices
- 2-3 contacts
- 1-2 documents
- 1 subscription
- 1 payment method

## Appendix C: CI/CD Integration

```yaml
# .github/workflows/e2e.yml
name: E2E Tests

on:
  push:
    branches: [main, sabir/audit]
  pull_request:
    branches: [main]

jobs:
  e2e:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: resisquare_test
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, bcmath, mysql
          
      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-interaction
        
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '20'
          
      - name: Install npm dependencies
        run: npm ci
        
      - name: Run migrations
        run: php artisan migrate:fresh --seed
        env:
          DB_DATABASE: resisquare_test
          
      - name: Install Playwright browsers
        run: npx playwright install --with-deps
        
      - name: Run Playwright tests
        run: npx playwright test
        env:
          DB_DATABASE: resisquare_test
```

---

**End of Fix Plan**
