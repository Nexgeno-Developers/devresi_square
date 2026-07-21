# Staging QA Report

Date: 2026-07-09

Environment: staging

Commit hash: 3b1da02

Tester: Codex local audit

Go/no-go status: No-go - staging deployment and end-to-end QA blocked by missing staging access and unavailable local DB

## Deployment Verification

- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm install`
- [ ] `npm run build`
- [ ] `php artisan migrate --force`
- [ ] `php artisan db:seed --class=SaasPlanSeeder --force`
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `php artisan storage:link`
- [ ] `php artisan queue:restart`

Local command audit on 2026-07-06:

- `php artisan saas:create-staging-test-data`: passed.
- `php artisan saas:backfill-account --dry-run`: passed.
- `php artisan route:list`: passed, 506 routes registered.
- `php artisan config:cache`: passed.
- `php artisan route:cache`: passed.
- `php artisan view:cache`: passed.
- `php artisan test`: passed, 2 tests / 2 assertions.
- `public/storage`: exists locally.
- `php artisan migrate`: blocked locally by existing `invoices` table schema drift.

Current local audit on 2026-07-09:

- Staging deployment: blocked. No staging URL, SSH/deployment target, CI/CD pipeline, or staging `.env` access is present in this workspace.
- `node -v`: failed. Node.js is not installed/available in the shell, so `npm install` and `npm run build` cannot run here.
- `php artisan test`: after the provider fix below, 1 unit test passed and 1 feature test failed because the homepage view calls `get_setting('header_logo')`, which reads `business_settings` from the unavailable local MySQL database.
- `vendor\bin\phpunit.bat`: initially errored while `AppServiceProvider` loaded `permissions` during app boot; fixed below.
- `vendor\bin\phpunit.bat` after fix: still failed, 1 failed / 2 tests, because the homepage view calls `get_setting('header_logo')`, which reads `business_settings` from the unavailable local MySQL database.
- `php artisan route:list --path=stripe`: blocked by the same local MySQL connection failure.
- `php artisan saas:backfill-account --dry-run`: blocked by the same local MySQL connection failure.
- `php artisan saas:create-staging-test-data`: not run in staging because staging access is unavailable; not run locally because the configured local MySQL service is unavailable.

## Staging Test Data

Command:

```bash
php artisan saas:create-staging-test-data
```

Local verification result: passed on 2026-07-06. The command created/updated the documented staging users and is idempotent.

## Passed Flows

- [ ] Super Admin SaaS setup
- [ ] Dynamic pricing
- [ ] Landlord registration and approval
- [ ] Estate-agent registration and approval
- [ ] Account provisioning
- [ ] Data isolation
- [ ] Plan limits
- [ ] Branch/staff limits
- [ ] Stripe checkout/webhook
- [ ] Addon purchase
- [ ] Portal access
- [ ] Accounting isolation
- [ ] File/document security
- [ ] Backfill verification

## Failed Flows

- Staging deployment: blocked before deployment.
- Test user creation: blocked before seeding.
- Super Admin plan/addon CRUD: not tested.
- Pricing page: not tested.
- Landlord registration: not tested.
- Estate-agent registration: not tested.
- Account creation after approval: not tested.
- Data isolation: not tested.
- Property limit: not tested.
- Branch limit: not tested.
- Staff limit: not tested.
- Addon limit increase: not tested.
- Stripe checkout: not tested.
- Stripe webhook: not tested.
- Portal access: not tested.
- Accounting isolation: not tested.
- Document access: not tested.

## Bugs Fixed During Staging

- Fixed `AppServiceProvider` so dynamic permission gate registration does not crash PHPUnit or `php artisan test` boot when the permissions table/database is unavailable during test execution. Normal runtime still rethrows the database error.
- Added guarded staging data command and idempotent seeder.
- Added staging deployment, flow-test, and QA-report documentation.
- Fixed `saas:backfill-account --dry-run` so it runs non-interactively without requiring `--owner_user_id`.
- Fixed `/admin/accounting/*` route group middleware so accounting routes now include `current.account` and `account.status`.
- Fixed admin property search routes so they include both `current.account` and `account.status`.
- Fixed duplicate route names that blocked `php artisan route:cache`: `uploaded-files.destroy`, `roles.edit`, `roles.destroy`, `staffs.destroy`, and `email-templates.index`.

## Remaining Issues

- Staging host/deployment credentials are not available in this workspace.
- Local MySQL at `127.0.0.1:3306` is unavailable, blocking Laravel commands, local seeding, route audit, and page-level tests.
- Node.js is not available in this shell, blocking frontend build verification.
- `vendor\bin\phpunit.bat` still fails without a database because the homepage view reads `business_settings` through `get_setting()`.
- Local `php artisan migrate` is currently blocked by legacy invoice migration/schema drift: `2025_03_08_170302_create_invoices_table` tries to create an existing `invoices` table.
- Stripe checkout/webhook must be tested with staging test-mode keys and the real staging webhook secret.
- Mail delivery must be verified with staging SMTP credentials.

## Database Audit

Local audit after running staging data command:

```text
accounts: 4
account_users: 9
plans: 2
addons: 4
account_subscriptions: 3
property_participants: 4
properties null account_id: 0
tenancies null account_id: 0
repair_issues null account_id: 0
sys_sale_invoices null account_id: 0
```

Local backfill dry run:

```text
companies: 0 rows would be updated
branches: 0 rows would be updated
staff: 0 rows would be updated
designations: 0 rows would be updated
properties: 0 rows would be updated
property_responsibilities: 0 rows would be updated
tenancies: 0 rows would be updated
tenant_members: 0 rows would be updated
repair_issues: 0 rows would be updated
work_orders: 0 rows would be updated
documents: 0 rows would be updated
notes: 0 rows would be updated
uploads: 0 rows would be updated
events: 0 rows would be updated
registrations: 0 rows would be updated
sys_sale_invoices: 0 rows would be updated
sys_sale_invoice_items: 0 rows would be updated
sys_receipts: 0 rows would be updated
sys_payments: 0 rows would be updated
gl_journals: 0 rows would be updated
gl_journal_lines: 0 rows would be updated
```

Staging SQL to rerun:

```sql
SELECT COUNT(*) FROM accounts;
SELECT COUNT(*) FROM account_users;
SELECT COUNT(*) FROM plans;
SELECT COUNT(*) FROM addons;
SELECT COUNT(*) FROM account_subscriptions;
SELECT COUNT(*) FROM property_participants;
SELECT COUNT(*) FROM properties WHERE account_id IS NULL;
SELECT COUNT(*) FROM tenancies WHERE account_id IS NULL;
SELECT COUNT(*) FROM repair_issues WHERE account_id IS NULL;
SELECT COUNT(*) FROM sys_sale_invoices WHERE account_id IS NULL;
```

## Sign-Off

Name:

Date:

Decision:
