# Admin UAT – Part 5 of 8: subscription detail actions

Run on 24 Nov 2026 against local `resi/head`, signed in as the local Super Admin.
Setup is the same as before: XAMPP MySQL 3306, app on http://localhost:8003 with `MAIL_MAILER=log`, PHPUnit on `resisquare_qa_suite`.
Stripe checks used **Stripe test mode** (`sk_test_…`) only.

Pages covered: SaaS Management › Subscriptions (list, filters, search) and the subscription page. The page's only write action is **Extend trial** (`POST /admin/saas/subscriptions/{id}/extend-trial`). Plan changes and add-ons are Part 6.

## Test data

| Subscription | Account | State |
|---|---|---|
| 785 | P5 Stripe Trial | Trialing in Stripe test mode (`sub_1UfI83…`), trial ending in 3 days |
| 786 | P5 Local Trial | Local-only trial that **ended 6 days ago** (no Stripe subscription) |
| 787 | P5 Active | Active, with a QA add-on. Later linked to a Stripe test subscription that is active while our copy says "trialing" |
| 788 | P5 Suspended Trial | Trialing, account suspended by an admin |

The Stripe test-mode products, prices and customers are labelled "QA P5 … (safe to delete)".

## White-box review (`SubscriptionController`, `TrialExtensionService`)

| # | Finding | Severity | Status |
|---|---|---|---|
| W1 | **Trials never end.** Sign-up creates a local trial (no Stripe subscription) for the plan's trial days. Nothing moves an account on when `trial_ends_at` passes; only a warning email goes out 2 days before. Reproduced: the owner of a trial that ended 6 days ago still had full access to the dashboard and properties. The dev DB has 5 more such accounts. | **High** (revenue) | **Fixed** (your decision: a daily job) |
| W2 | **Extending a trial left no record.** It gives free access, but there was no audit entry for who extended it or by how long. | Medium | **Fixed**: audit entry with the old and new end, the days added, and whether Stripe was changed |
| W3 | Extend trial trusted our copy of the status. If Stripe had already moved the subscription to **active** (paying), extending would put a paying customer back on a free trial in Stripe. | Medium | **Fixed**: Stripe is checked first. Verified in test mode: refused with "Stripe shows this subscription as active…", and Stripe was left unchanged |
| W4 | Extending an **older** trial subscription set the account back to "trialing", even when the account had since moved to a newer paid subscription. | Low | **Fixed**: only the account's latest subscription changes the account status (the same rule the Stripe webhook uses) |
| W5 | The message said "extended in Stripe and the application" even when there is no Stripe subscription. | Low (UX) | **Fixed**: the message gives the new end date and says when only the application was changed |

### Decision (W1): daily trial-expiry job

On 24 Nov 2026 you chose a daily job. What it does:

- `php artisan accounts:expire-trials` is scheduled daily at 00:30. It suspends any account still "trialing" after its trial end, unless it has a live Stripe subscription (Stripe ends those itself).
- The suspension is a system one: reason "Trial ended", with no admin recorded. Each one is written to the audit log.
- The local subscription stays "trialing", because checkout needs it. Checkout only carries over trial time still left, so an ended trial gets no free days.
- The owner sees "Your free trial has ended. Choose a plan in Billing & Plan to carry on." Paying unlocks the account through the existing checkout and webhook path, which has a test.
- An admin can unlock it by **extending the trial**, which now lifts the "Trial ended" lock. **Reactivate** is refused for an ended local trial, because the job would lock it again that night.
- Options: `--dry-run` lists the accounts without changing them; `--account=ID` (repeatable) limits the run.

**Before deploying:** a dry run on the dev DB lists 5 accounts. Among them are the seeded **Staging Landlord Account (1)**, **Staging Estate Agent Account (2)** and **Friday Portfolio (374)**, whose trials ended in October. On staging or production, the first nightly run will lock every lapsed trial like these. Run `php artisan accounts:expire-trials --dry-run` there first. Then extend the trials of any accounts that should stay open (test, demo or goodwill accounts), or tell the owners. On the dev DB I ran it for one test account only, so the seeded accounts are unchanged.

### What changed

- `app/Console/Commands/ExpireEndedTrials.php` (new) and `bootstrap/app.php` (schedule): the daily job.
- `app/Services/Saas/TrialExtensionService.php`: checks Stripe's own status before extending; only the latest subscription changes the account; extending lifts a "Trial ended" lock.
- `app/Http/Controllers/Backend/Saas/AccountController.php`: Reactivate is refused for an ended local trial.
- `app/Http/Middleware/AccountStatusGuard.php`: the owner's "trial has ended" message.
- `app/Http/Controllers/Backend/Saas/SubscriptionController.php`: audit entry; clearer success message.
- `tests/Feature/SaasBillingAndLimitsHttpTest.php`: 1 regression test (extension audited; an older subscription doesn't reset the account). It fails on the old code and passes now. A second test covers the trial job: an ended local trial is suspended; a running trial and a Stripe trial are left alone; a re-run changes nothing; the owner is sent to Billing; Reactivate is refused; extending unlocks. The Stripe-status check was verified against Stripe test mode, not mocked.

## Black-box and UAT cases

| ID | Case | Expected | Result |
|---|---|---|---|
| P5-01 | Subscriptions list, search "P5" | The 4 fixtures with plan, cycle, status, trial and period ends, and Stripe ID | Pass |
| P5-02 | Search "%"; nonsense filters (status, cycle, plan, type, page −1) | No error; "No subscriptions found." for the nonsense filters | Pass ("%" matches everything; see notes) |
| P5-03 | Subscription page with an add-on | Subscription, account and plan info; add-on row | Pass |
| P5-04 | Extend a Stripe-backed trial by 5 days | Trial end moves from 27 Nov to 2 Dec in **both** Stripe and the app (both 2 Dec 17:13) | Pass |
| P5-05 | Extend by 0, 731, or "abc" | Validation errors | Pass |
| P5-06 | Extend so the trial would end more than 2 years out | "Stripe allows a trial end no more than two years in the future." | Pass |
| P5-07 | Forged extend on an active subscription | "Only a currently trialing subscription can be extended." | Pass |
| P5-08 | Extend a trial whose account is suspended by an admin | Trial extended; account stays suspended | Pass |
| P5-09 | Extend a local trial that already ended | New end counted from today; message says only the app changed | Pass (message **fixed**, W5) |
| P5-10 | Extend where Stripe says active but our copy says trialing | Refused; Stripe untouched | **Fail → fixed** (W3), verified in Stripe test mode |
| P5-11 | Extension leaves a record | Audit entry | **Fail → fixed** (W2) |
| P5-12 | Unknown subscription ID | 404 | Pass |
| P5-13 | Owner of a trial that ended 6 days ago | Blocked until they choose a plan | **Fail → fixed** (W1). After the job ran: every page redirects to Billing & Plan with "Your free trial has ended…" |
| P5-14 | Admin Reactivate on an ended trial | Refused with guidance | Pass (new) |
| P5-15 | Admin extends an ended trial by 7 days | Account back to Trialing straight away | Pass (new) |

## Minor notes (not changed)

- Search doesn't escape `%` or `_`, so "%" lists everything. Harmless on an admin-only page.
- The add-ons table shows the add-on's current catalogue name and no price. If an add-on is renamed or repriced, the page no longer shows what the customer bought (`addon_name_at_purchase` and `price_at_purchase_minor` are stored but not shown).
- Trial extension asks for no reason. The audit entry records who and how long, but not why.
