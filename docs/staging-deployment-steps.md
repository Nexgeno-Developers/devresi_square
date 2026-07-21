# Staging Deployment Steps

Use this checklist for staging only. Do not seed staging fake data in production.

## Environment

Required staging `.env` keys:

```text
APP_URL=https://staging.example.com
APP_ENV=staging
APP_DEBUG=false
DB_CONNECTION=
DB_HOST=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
MAIL_MAILER=
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
QUEUE_CONNECTION=
SESSION_DRIVER=
CACHE_STORE=
```

Checks:

- `APP_URL` must be HTTPS.
- Stripe keys must be test-mode keys in staging.
- Stripe webhook endpoint must be `https://<staging-host>/stripe/webhook`.
- Mail credentials must send to real or sandbox staging mail.
- Queue driver must be configured. This app uses queued mail, notifications, `repair-quotes`, and `repair-assignments`.
- Scheduler/cron must be configured because recurring invoices, reminders, notifications retry, and penalties are scheduled in `bootstrap/app.php`.
- Storage link must exist for uploads: `public/storage -> storage/app/public`.
- `.env.example` currently does not include Stripe keys and still shows local/debug defaults; staging must override them.

## Deploy

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan migrate --force
php artisan db:seed --class=SaasPlanSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
php artisan queue:restart
```

If staging needs fake QA data:

```bash
php artisan saas:create-staging-test-data
```

## Workers

Run a default worker:

```bash
php artisan queue:work --tries=3 --timeout=120
```

If repair queues are separated, also run:

```bash
php artisan queue:work --queue=repair-quotes,repair-assignments --tries=3 --timeout=120
```

## Scheduler

Add cron:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Post-Deploy Verification

```bash
php artisan about
php artisan route:list
php artisan route:list --path=stripe
php artisan test
php artisan saas:backfill-account --dry-run
```

Database audit SQL:

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

If legacy rows need account assignment, run a dry run first:

```bash
php artisan saas:backfill-account --owner_user_id=1 --dry-run
php artisan saas:backfill-account --owner_user_id=1
```

## Known Local Migration Blocker

The current local database fails `php artisan migrate` before new SaaS migrations run because `2025_03_08_170302_create_invoices_table` attempts to create an `invoices` table that already exists. Fix the migration table/schema drift in staging before relying on `migrate --force`.
