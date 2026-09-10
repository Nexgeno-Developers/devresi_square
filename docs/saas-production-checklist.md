# ResiSquare SaaS Production Checklist

Use this before enabling paid SaaS accounts in production.

## Required Environment

Set and verify these keys:

```text
APP_URL=
QUEUE_CONNECTION=
MAIL_MAILER=
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

Production must use HTTPS for `APP_URL` and all Stripe redirect/webhook URLs.

## Stripe Setup

- Configure the Stripe webhook endpoint: `/stripe/webhook`.
- Subscribe to at least:
  - `checkout.session.completed`
  - `customer.subscription.created`
  - `customer.subscription.updated`
  - `customer.subscription.deleted`
  - `invoice.payment_succeeded`
  - `invoice.payment_failed`
- Copy the signing secret into `STRIPE_WEBHOOK_SECRET`.
- Confirm local plan/addon Stripe price IDs match live Stripe prices.
- Do not expose `STRIPE_SECRET` or `STRIPE_WEBHOOK_SECRET` to frontend code.

## Client-money rent (separate Stripe / bank)

US/client-money rules: subscriptions stay on the **operating** Stripe account (`STRIPE_SECRET`). Tenant rent must use a **client/trust** Stripe account whose payouts go to the client bank.

```text
STRIPE_RENT_KEY=
STRIPE_RENT_SECRET=
STRIPE_RENT_WEBHOOK_SECRET=
STRIPE_RENT_CONNECTED_ACCOUNT_ID=
RENT_PAYMENT_FEE_PERCENT=0
RENT_PAYMENT_FEE_FIXED=0
```

- Point `STRIPE_RENT_*` at the client-money Stripe account. Do not reuse live `STRIPE_SECRET`.
- Webhook for that account: `/stripe/rent/webhook` (`checkout.session.completed`, `checkout.session.async_payment_succeeded`).
- Optional Connect: set `STRIPE_RENT_CONNECTED_ACCOUNT_ID` to the client-money account. Checkout then uses the business secret with a direct charge so **rent** lands in the client account and `RENT_PAYMENT_FEE_*` is taken as `application_fee` on the operating account.
- Without Connect, the tenant still pays rent + fee on the client Stripe account; sweep the fee to the operating bank separately.

## Deployment Commands

Run in staging first:

```bash
php artisan migrate
php artisan db:seed --class=SaasPlanSeeder
php artisan saas:backfill-account --owner_user_id=USER_ID --dry-run
php artisan saas:backfill-account --owner_user_id=USER_ID
php artisan route:list
php artisan view:cache
```

## Queue And Scheduler

- Start a queue worker if queued mail, notifications, Stripe follow-up jobs, or repair emails are enabled:

```bash
php artisan queue:work --tries=3 --timeout=90
```

- Configure Laravel scheduler cron:

```text
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

- Document any named queues used by repair notifications or billing jobs.

## Super Admin

- Super Admin remains in `users`.
- Super Admin role remains in Spatie `roles` and `model_has_roles`.
- Super Admin must not be added to `accounts` or `account_users`.
- Verify Super Admin can manage plans, addons, accounts, and subscriptions.
- Verify Super Admin selects an account before creating support records for a customer workspace.

## Plans And Addons

- Confirm active plans exist for each supported account type.
- Confirm inactive plans do not appear on pricing or registration.
- Confirm plan limits: properties, branches, staff, property managers.
- Confirm feature flags: company profile, invoice branding, roles/permissions, contact login.
- Confirm addon grant quantities and Stripe price IDs.

## Data Isolation

- Run the manual data isolation checklist.
- Confirm normal users only see rows with their current `account_id`.
- Confirm direct URL access to another account returns 403.
- Confirm portal users only see assigned `property_participants` data.

## Backfill

- Always run `--dry-run` first.
- Confirm row counts by table before writing.
- Do not choose a Super Admin as the owner.
- The command only fills null `account_id`; it does not overwrite existing non-null values.

## Account Status

- `trialing`: business modules allowed within plan limits.
- `active`: business modules allowed within plan limits.
- `past_due`: viewing remains allowed; new paid resources remain governed by plan limits.
- `suspended`: login and billing access allowed; business modules blocked.
- `cancelled`: login and billing access allowed; business modules blocked.
- Customer data is not deleted automatically.

## Rollback Considerations

- SaaS hardening migrations are additive and non-destructive.
- Do not drop SaaS tables after production launch without a data export.
- Keep database backups before migrations and before backfill.
- Stripe remains the billing source of truth; webhook replay may be needed after rollback.
