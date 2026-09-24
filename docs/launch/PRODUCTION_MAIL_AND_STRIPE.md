# Production mail + dual Stripe (Step 45)

**Done when:** Money Path and invite emails proven on production-like staging three times.  
**Assert:** `php artisan launch:assert-money-config --env=staging` (and `--env=production` before GA).

---

## 1. Production mail (SPF / DKIM / DMARC)

### App config

| Variable | Production value |
|---|---|
| `MAIL_MAILER` / `MAIL_DRIVER` | `smtp`, `ses`, `postmark`, or `mailgun` — never `log` |
| `MAIL_FROM_ADDRESS` | Real mailbox on your sending domain (e.g. `noreply@mail.resisquare.com`) |
| `MAIL_FROM_NAME` | `Resisquare` (or brand) |
| Queue | Prefer queued mail (`QUEUE_CONNECTION=redis` or `database`) so OTP/invite survive spikes |

SMTP Settings UI is Super-Admin only (kill switch). Landlords never edit `.env` mail secrets.

### DNS checklist (sending domain)

1. **SPF** — TXT on the sending domain / subdomain listing your ESP (`include:` for SES/Postmark/Mailgun).
2. **DKIM** — ESP-provided CNAME/TXT; verify in the ESP dashboard.
3. **DMARC** — start `p=none` with rua mailbox, then tighten to `quarantine`/`reject` once clean.
4. **Alignment** — From domain must align with SPF/DKIM identities (prefer a dedicated `mail.` subdomain).

### Prove mail (do this three times on staging)

| # | Message | Trigger | Pass |
|---|---|---|---|
| 1 | Signup OTP | New registration | Code arrives &lt; 60s; not in spam |
| 2 | Tenant portal invite | People → invite | Link works; lands on tenant shell |
| 3 | Rent / finance notice | Issue invoice or payment received CRM event | Delivered; From shows brand |

Record message-IDs and ESP delivery logs in the UAT sheet (Step 48).

---

## 2. Dual Stripe (operating + client-money)

Resisquare uses **two** Stripe surfaces:

| Account | Env keys | Webhook URL | Purpose |
|---|---|---|---|
| **Operating** | `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` | `POST /stripe/webhook` | Landlord subscriptions, Customer Portal, plan add-ons |
| **Client-money / rent** | `STRIPE_RENT_KEY`, `STRIPE_RENT_SECRET`, `STRIPE_RENT_WEBHOOK_SECRET` | `POST /stripe/rent/webhook` | Tenant rent Checkout; payouts to client/trust bank |

Staging may reuse test keys for both. **Production must use two different Stripe accounts** (or Connect with `STRIPE_RENT_CONNECTED_ACCOUNT_ID` so rent lands on the client-money account). The assert command fails GA if secrets are identical.

### Webhook events to enable

**Operating** (`/stripe/webhook`):

- `checkout.session.completed`
- `customer.subscription.created|updated|deleted`
- `invoice.paid` / `invoice.payment_failed`
- `customer.subscription.trial_will_end` (optional reminders)

**Rent** (`/stripe/rent/webhook`):

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`

Both endpoints are CSRF-exempt; signatures are verified with the matching webhook secret.

### Plan limits

Enforced in-app via `AccountLimitService` + `EnforcesSaasPlanLimits` (properties, portal users, seats, feature flags). Covered by `SaasBillingAndLimitsHttpTest`.

### Account status guards

| Account status | Product access | Billing |
|---|---|---|
| `trialing` / `active` | Full | Normal |
| `past_due` | **Still allowed** (grace) | Banner: update card via Customer Portal |
| `suspended` / `cancelled` | Blocked by `AccountStatusGuard` | Billing, logout, profile still reachable |

`invoice.payment_failed` → subscription + account → `past_due` (see `StripeWebhookService`).

---

## 3. Failed-payment recovery runbook

### Landlord subscription (operating Stripe)

1. Stripe Dashboard → Customers → open failed invoice → confirm `invoice.payment_failed` webhook delivered (200).
2. App: account `status=past_due`; Billing & Plan shows “Payment failed”.
3. Landlord: **Manage billing** (Customer Portal) → update card → pay open invoice.
4. Confirm `invoice.paid` / `customer.subscription.updated` → local status `active`.
5. If stuck: Super Admin may extend trial / note reason; never manually invent Stripe IDs in DB without Dashboard match.

### Tenant rent (client-money Stripe)

1. Failed Checkout → invoice stays open; tenant sees clear error (no false “paid”).
2. Success path: rent webhook → `RentStripeCheckoutService::fulfillSession` once (idempotent; covered by `TenantRentPaymentHttpTest`).
3. Double-apply check: same `checkout.session` twice → one payment row.
4. Mismatch vs Dashboard: log `event_id`; do **not** mark paid from success URL alone.

### Escalation

| Symptom | First check | Owner |
|---|---|---|
| OTP never arrives | ESP logs + SPF/DKIM + `MAIL_*` | Ops |
| Invite link 403 | Portal access + account status | Support |
| Sub stays past_due after pay | Webhook secret / endpoint URL / replay cache | Eng |
| Rent paid in Stripe, unpaid in app | Rent webhook secret vs operating mix-up | Eng (P0) |
| Rent webhook 500 | App log `Rent Stripe webhook processing failed` | Eng |

---

## 4. Staging Money Path dress-rehearsal (×3)

Use `docs/launch/ACCEPTANCE_SCRIPTS.md` §C. For each run:

1. `php artisan launch:assert-money-config --env=staging`
2. Fresh landlord trial → Checkout (test card) → active
3. Invite tenant → OTP/invite email received
4. Issue rent invoice → tenant card pay → ledger + Stripe rent Dashboard agree
5. Force failed subscription card (test clock or `4000 0000 0000 0341`) → `past_due` + banner; fix card → `active`
6. Tick sign-off row with date + operator initials

Three green runs → Step 45 closed for launch tracking (human UAT still Step 48).
