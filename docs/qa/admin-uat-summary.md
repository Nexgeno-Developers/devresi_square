# Admin UAT – summary of Parts 1–8

UAT, black-box and white-box testing of the admin actions that hadn't been tested before. Run on 24 Nov 2026 on local `resi/head`. All fixes are **uncommitted** in the working tree.

| Part | Area | Bugs found | Fixed | Report |
|---|---|---|---|---|
| 1 | Contractor document and trade decisions | 5 | 5 | [part 1](admin-uat-part1-contractor-documents-trades.md) |
| 2 | Contractor suspend, reinstate, remove, reject, changes | 5 | 5 | [part 2](admin-uat-part2-contractor-status-actions.md) |
| 3 | Stuck jobs, payouts, contractor settings, old-module invite | 6 | 6 | [part 3](admin-uat-part3-contractor-jobs-payouts-settings.md) |
| 4 | Account suspend, cancel, reactivate | 5 | 5 | [part 4](admin-uat-part4-account-actions.md) |
| 5 | Subscription actions (Extend trial; trials never ending) | 5 | 5 | [part 5](admin-uat-part5-subscription-actions.md) |
| 6 | Plans and add-ons vs Stripe prices | 5 | 5 | [part 6](admin-uat-part6-plans-addons.md) |
| 7 | Repair issue list, tabbed view, offers 403 | 2 | 2 | [part 7](admin-uat-part7-repairs-offers.md) |
| 8 | Repair invoices, transactions, uncharged work orders | 3 | 3 | [part 8](admin-uat-part8-invoices-transactions-uncharged.md) |

The full PHPUnit suite was green after every part (444+ tests by Part 7, on the isolated `resisquare_qa_suite`). Each fix has a regression test that fails on the old code.

## The most serious ones

- **Agents could read and change other accounts' repair invoices**, including marking them paid (Part 8).
- **Other accounts' contractors and managers were listed to every account** (Part 7).
- **Admin "Cancel account" never stopped Stripe billing**, and a renewal brought the account back. It now cancels every Stripe subscription first, with no refund (your decision, Part 4).
- **Admin suspensions were lifted by any billing event** (Part 4).
- **Trials never ended.** A daily job now suspends lapsed local trials (your decision, Part 5).
- **Plans and add-ons could charge a different Stripe price from the one shown** (£29 shown, £15 charged). Deactivating or editing an add-on changed what paying customers had (Part 6).
- **Cancel award on a paid job** left the payout queued (Part 3).
- **One-click contractor approval** saved a half-approved state (Part 1).

## Before deploying to staging and production

1. Run `php artisan migrate`. This part added `2026_11_24_180000_align_repair_invoice_statuses`; earlier parts added none. Both local DBs (3306, 3307) are already migrated.
2. **Trial expiry job**: run `php artisan accounts:expire-trials --dry-run` first. Seeded accounts 1, 2 and 374 (and any real lapsed trials) will be **suspended on the first nightly run** unless their trials are extended first.
3. **Stripe price IDs**: no plan or add-on has live Stripe price IDs. Add-ons cannot be bought until they're set. The plan and add-on forms now refuse IDs that don't match the app price.
4. Make sure `APP_DEBUG=false` (debug mode leaks full stack traces in JSON errors).
5. `LEGACY_CONTRACTOR_MODULE` stays off unless needed. Its "add contractor" path is now safe either way.

## Left as product decisions or notes (not changed)

These are listed under "Minor notes" in each part. Examples:
- Owners aren't emailed when their account is suspended or cancelled.
- "Mark paid" on a repair invoice records no payment.
- Electrical work can be set to need no registration.
- The Accounting module (transactions, uncharged work orders) is Super Admin–only and platform-wide by design.
