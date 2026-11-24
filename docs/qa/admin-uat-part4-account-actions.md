# Admin UAT – Part 4 of 8: account suspend, cancel, reactivate

Run on 24 Nov 2026 against local `resi/head`, signed in as the local Super Admin.
Setup is the same as before: XAMPP MySQL 3306, app on http://localhost:8003 with `MAIL_MAILER=log`, PHPUnit on `resisquare_qa_suite`.
Pages covered: SaaS Management › Accounts › account page › "Account actions" (`POST /admin/saas/accounts/{id}/status`).

## Test data

Landlord accounts created only in the dev DB, owners on `p4-*@resisquare.test`:

| Account | State |
|---|---|
| P4 Trial Lettings | Trialing, trial ends in 5 days, no Stripe subscription |
| P4 Paid Lettings | Active, monthly subscription (fake Stripe ID `sub_P4QAFAKE…`) |
| P4 Lapsed Lettings | Past due: trial ended 20 days ago, subscription past due |

Stripe webhooks were simulated by calling `StripeWebhookService::handle()` with fake events. No Stripe API call succeeded, and nothing was charged.

## White-box review (`Saas/AccountController`, `StripeWebhookService`, `AccountStatusGuard`)

| # | Finding | Severity | Status |
|---|---|---|---|
| W1 | **Admin "Cancel account" never cancels the Stripe subscription.** Nothing in the app calls Stripe to cancel. Cancel only marks our own rows cancelled, so the customer keeps being charged. The next renewal webhook then sets the subscription and account back to **active**. Reproduced: cancelled → one `invoice.payment_succeeded` → active. | **High** (billing) | **Fixed** (your decision: cancel in Stripe now). Cancel first stops every live Stripe subscription on the account's customer (the plan, plus add-ons, which checkout creates as separate subscriptions), with no refund. Only then is the account marked cancelled. If Stripe refuses, nothing changes and the admin is told. |
| W2 | **A suspension is lifted by any billing event.** A renewal, a subscription update or a new checkout by the owner sets the account back to active. Reproduced: suspended → one renewal webhook → active. The owner was also told to "visit Billing & Plan to restore access", which invites paying their way out. | **High** | **Fixed**: while an admin suspension is in place, billing events update the subscription but leave the account suspended. The owner now sees "suspended by Resisquare. Please contact support." Trial accounts suspended without an admin (`status_changed_by` empty) still unlock on payment, as before. |
| W3 | **Reactivate ignored billing.** It always set "active" (or "trialing" during a trial). A past-due account that was suspended and reactivated came back "Active", wiping the payment failure. An account whose trial had ended with no subscription got free access. | Medium | **Fixed**: reactivate returns the account to its live subscription's status (trialing, active or past due). With no live subscription and no trial left, it is refused with an explanation. |
| W4 | **No history of status changes.** Only the latest reason and time are kept; each change overwrote the one before. | Medium | **Fixed**: every suspend, cancel and reactivate is written to `audit_events` with the before and after status and the reason |
| W5 | **"Cancel account" had no confirmation**, and a cancelled account can't be reactivated from the admin panel. | Low–Medium (UX) | **Fixed**: confirmation dialog |

### Decision (W1)

On 24 Nov 2026 you chose **cancel in Stripe immediately**, with no automatic refund. Verified against Stripe **test mode**: a test customer with a plan subscription and a second (add-on) subscription. Cancelling from the admin page set both to `canceled` in Stripe at the same moment, marked the account and subscription cancelled, and showed "Stripe billing was cancelled (2 subscriptions); no refund was issued." The audit entry lists both subscription IDs. The test product, prices and customer were left in the Stripe test account, labelled "QA P4 … (safe to delete)".

### What changed

- `app/Http/Controllers/Backend/Saas/AccountController.php`: cancel stops Stripe billing first (and marks add-ons cancelled); reactivate follows the live subscription and refuses free access; audit record on every change, including the Stripe subscriptions cancelled.
- `app/Services/Saas/StripeCheckoutService.php`: `cancelAllBillingNow()` and `cancelSubscriptionNow()`. A subscription already cancelled or missing in Stripe counts as done.
- `app/Services/Saas/StripeWebhookService.php`: `syncAccountStatus()` keeps an admin suspension in place.
- `app/Http/Middleware/AccountStatusGuard.php`: owner message for an admin suspension.
- `resources/views/backend/saas/accounts/show.blade.php`: confirm on "Cancel account", and help and confirm text saying Stripe billing stops with no refund. This file already had your uncommitted one-line change.
- `tests/Feature/SaasBillingAndLimitsHttpTest.php`: 2 regression tests (a webhook does not lift an admin suspension, plus the audit entry and owner redirect; reactivate follows the subscription and refuses free access). Both fail on the old code and pass now. Plus 2 for cancel, with Stripe mocked: Stripe failing changes nothing; Stripe succeeding cancels and audits; a trial with no Stripe billing never calls Stripe. The existing "checkout return unlocks a suspended trial" test still passes.

## Black-box and UAT cases

| ID | Case | Expected | Result |
|---|---|---|---|
| P4-01 | Suspend with a reason under 8 characters | "The reason field must be at least 8 characters." | Pass (server; the browser's minlength only fires on typed input) |
| P4-02 | Suspend an active paid account | Suspended; reason and time shown; Reactivate offered | Pass |
| P4-03 | Suspended owner opens the app | Every page redirects to Billing & Plan with a suspension message | Pass |
| P4-04 | Renewal webhook while suspended | Stays suspended | **Fail → fixed** (W2) |
| P4-05 | Message the suspended owner sees | Contact support (paying won't lift it) | **Fail → fixed** (W2) |
| P4-06 | Reactivate a suspended active account | Active | Pass |
| P4-07 | Suspend, then reactivate, a past-due account | Past due | **Fail → fixed** (W3). Before: "Active" |
| P4-08 | Cancel a trial account | Cancelled; subscriptions cancelled; no further actions offered | Pass |
| P4-09 | Forged reactivate, cancel or suspend on a cancelled account; unknown action | Refused; status unchanged | Pass |
| P4-10 | Cancel a paid account, then a renewal webhook arrives | Stays cancelled, and Stripe stops billing | **Fail → fixed** (W1). Verified in Stripe test mode: both subscriptions `canceled` |
| P4-11 | Cancel button | Asks to confirm | **Fail → fixed** (W5) |
| P4-12 | Status changes leave a history | Audit entries with before, after and reason | **Fail → fixed** (W4) |

## Minor notes (not changed)

- The account owner gets no email when their account is suspended or cancelled. They find out at next login.
- A cancelled account can't be reactivated from the admin panel, even by mistake. The owner can only come back through a new checkout.
- Tenants keep portal access while the account is suspended or cancelled (except paying rent by card). This looks intended.
