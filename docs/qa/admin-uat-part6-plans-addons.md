# Admin UAT – Part 6 of 8: plans and add-ons

Run on 24 Nov 2026 against local `resi/head`, signed in as the local Super Admin.
Setup is the same as before: XAMPP MySQL 3306, app on http://localhost:8003, PHPUnit on `resisquare_qa_suite`.
Stripe checks used **Stripe test mode** (`sk_test_…`) only. The test products and prices are labelled "QA P6 … (safe to delete)".

Pages covered: SaaS Management › Plans (create, edit, delete) and Add-ons (create, edit, delete, deactivate). For "plan changes against live Stripe prices", the plan form was saved with real Stripe test-mode prices, and test checkout sessions showed what Stripe would charge.

## White-box review (`PlanController`, `AddonController`, `StripeCheckoutService`, `AccountLimitService`)

| # | Finding | Severity | Status |
|---|---|---|---|
| W1 | **Stripe charges a different price from the one the app shows.** When a plan has a Stripe price ID, checkout bills that Stripe price; the price typed into the app is only for display. Nothing checked they match. Reproduced in test mode: a plan showing **£29.00/month** was linked to a £15 price, and checkout charged **£15.00 per month**. The annual box held a USD monthly price, so the plan showing **£290.00/year** checked out as **US$29.00 per month**. Archived, one-off and missing prices were accepted too. | **High** (billing) | **Fixed**: on save, each Stripe price ID is looked up in Stripe. It must exist, be active, bill once a month (monthly box) or once a year (annual box), use the plan's currency, and charge exactly the app price. Otherwise the field shows why and nothing is saved. Applies to plans and add-ons. |
| W2 | **Deactivating an add-on takes it from paying customers.** Limits only counted add-ons still active in the catalogue. The delete page tells admins to "deactivate it instead". Reproduced: deactivating "Extra Property" took P5 Active's paid extra properties from **2 to 0**. | **High** | **Fixed**: deactivating only takes the add-on off sale. Reproduced again after the fix: 2 stays 2. |
| W3 | **Editing an add-on changes what existing buyers get.** Limits are worked out from the catalogue add-on's current grant quantity and type. Reproduced: changing "Extra Property" from 1 to 5 per unit took P5 Active from **2 to 10** extra properties at the same price. | **High** | **Fixed**: grant quantity and type can't change while customers hold the add-on; the admin is told to create a new add-on instead. Price, name and Stripe IDs can still change (and are checked, W1). |
| W4 | The add-on Stripe price fields accepted any text ("not a price"). The plan fields already required `price_…`. | Low | **Fixed** (same format rule as plans, plus the W1 check) |
| W5 | No upper limits: trial days (Stripe allows at most 730), prices, grant quantity. A 5,000-day trial saved. | Low | **Fixed**: trial up to 730 days; monthly price up to £100,000, annual up to £1,000,000; grant up to 1,000 |

### What changed

- `app/Services/Saas/StripePriceCheck.php` (new): looks up a Stripe price and says why it doesn't fit.
- `app/Http/Controllers/Backend/Saas/BaseSaasController.php`: `assertStripePricesMatch()`, used by plan and add-on create and edit.
- `app/Http/Controllers/Backend/Saas/PlanController.php`, `AddonController.php`: the check, the new limits, and the add-on lock on grant and type.
- `app/Services/Saas/AccountLimitService.php`: paid add-ons count whether or not the catalogue add-on is on sale.
- `tests/Feature/SaasBillingAndLimitsHttpTest.php`: 2 regression tests (the Stripe price check on plans and add-ons, with Stripe mocked, plus the 730-day trial limit; paid add-ons keep their limits when the catalogue add-on is deactivated, and the grant can't change while held). Both fail on the old code and pass now. The real Stripe behaviour was checked in test mode (below).

## Black-box and UAT cases

| ID | Case | Expected | Result |
|---|---|---|---|
| P6-01 | Create an add-on with a duplicate code, negative price, non-numeric annual price, 6-letter currency, grant 0 | One message per field; nothing saved | Pass |
| P6-02 | Create an add-on with code "QA Extra Users!" and HTML in the name | Code becomes `qa_extra_users`; name shown as text | Pass |
| P6-03 | Add-on Stripe price "not a price" | Refused | **Fail → fixed** (W4) |
| P6-04 | Delete an unused add-on (confirm dialog) | Deleted | Pass |
| P6-05 | Delete an add-on customers hold (button hidden; forged request) | "Deactivate it instead of deleting it." | Pass |
| P6-06 | Deactivate an add-on customers hold | Taken off sale; customers keep their extra limits | **Fail → fixed** (W2) |
| P6-07 | Change a held add-on from 1 to 5 per unit | Refused | **Fail → fixed** (W3) |
| P6-08 | Plan £29/month linked to a £15 Stripe price | Refused: "charges 15.00, but the monthly price here is 29.00…" | **Fail → fixed** (W1). Before: saved, and checkout charged £15.00 |
| P6-09 | Plan annual box holding a USD price | Refused: "is in USD, not GBP" | **Fail → fixed** (W1). Before: checkout charged US$29.00 per month |
| P6-10 | One-off price; yearly price in the monthly box | Refused: "does not bill once a month" | **Fail → fixed** (W1) |
| P6-11 | Archived Stripe price | Refused: "is archived" | **Fail → fixed** (W1) |
| P6-12 | Price ID that doesn't exist in this Stripe account | Refused: "Stripe has no price …" | **Fail → fixed** (W1) |
| P6-13 | Correct £29/month and £290/year test prices | Saved | Pass |
| P6-14 | Plan with a 5,000-day trial | Refused (max 730) | **Fail → fixed** (W5) |

## Before going live

- **No plan or add-on in the dev DB has Stripe price IDs.** Plans fall back to a price built from the app amount, so they still check out. **Add-ons cannot be bought at all**: add-on checkout requires a Stripe price ID ("Stripe price ID is missing for this addon"). Create the live prices in Stripe and enter their IDs. The form now refuses any that don't match.
- Test-mode price IDs won't exist in live mode (and the reverse). The check reports "Stripe has no price …" if the wrong mode's ID is used.

## Minor notes (not changed)

- Changing a plan's price or Stripe price affects new checkouts only. Existing subscribers stay on the Stripe price they signed up with, which is normal for Stripe. The admin page doesn't say so.
- Currency accepts any 3 letters. The Stripe check catches a mismatch when a price ID is set, but a plan without one could be saved as, say, "XYZ" and fail at checkout.
- When the annual price isn't numeric, the error says "field is required" instead of "must be a number".
