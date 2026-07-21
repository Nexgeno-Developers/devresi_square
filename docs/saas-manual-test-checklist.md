# ResiSquare SaaS Manual Test Checklist

Run these in staging with at least two customer accounts and one Super Admin.

## Scenario 1: Super Admin

- Login as Super Admin.
- Create a Landlord plan.
- Create an Estate Agent plan.
- Create property, branch, staff, and property-manager addons.
- View all accounts.
- View all subscriptions.
- Confirm Super Admin is not present in `account_users`.

## Scenario 2: Landlord Signup

- Select a landlord plan from pricing.
- Register.
- Verify OTP.
- Approve registration.
- Confirm `accounts`, owner `account_users`, and `account_subscriptions` rows are created once.
- Confirm company, branch, and staff setup are hidden or blocked when the plan disallows them.

## Scenario 3: Estate Agent Signup

- Select an estate-agent company plan.
- Register.
- Verify OTP.
- Approve registration.
- Confirm the company is created or linked.
- Confirm branch and staff creation follow plan limits.
- Confirm duplicate approval is blocked.

## Scenario 4: Data Isolation

- Create Account A and Account B.
- Create one property in each.
- Login as Account A owner.
- Confirm Account B property is not listed.
- Try `/admin/properties/view/{account_b_property_id}`.
- Confirm 403.
- Repeat for users, tenancies, repairs, work orders, documents, notes, invoices, receipts, and events.

## Scenario 5: Plan Limits

- Set property limit to 1.
- Create the first property.
- Try creating a second property.
- Confirm creation is blocked.
- Buy or activate an extra property addon.
- Try creating the second property again.
- Confirm creation is allowed.
- Confirm existing records above a later reduced limit remain visible/editable.

## Scenario 6: Portal Access

- Create landlord contact.
- Enable login and assign only property A.
- Set `can_view_finance=true`, `can_view_documents=true`, `can_upload_documents=false`.
- Login as contact.
- Confirm only property A is visible.
- Try direct URL to property B and confirm 403.
- Confirm statements/invoices are visible for property A only.
- Confirm documents are visible but upload is blocked.
- Confirm private notes are not visible.

## Scenario 7: Tenant Portal

- Create tenant user.
- Assign tenant to property A.
- Set documents allowed and finance denied.
- Confirm property A is visible.
- Confirm finance tab and statement routes return 403.
- Confirm property B direct URL returns 403.

## Scenario 8: Contractor Portal

- Create contractor user.
- Assign contractor to property A or repair A.
- Create repairs for property A and property B.
- Login as contractor.
- Confirm only assigned repairs/work orders are visible.
- Confirm direct URL to property B repair returns 403.

## Scenario 9: Stripe

- Start checkout for a plan.
- Complete checkout.
- Trigger `checkout.session.completed`.
- Confirm subscription is linked and active/trialing.
- Trigger `invoice.payment_failed`.
- Confirm subscription/account becomes `past_due`.
- Trigger `customer.subscription.deleted`.
- Confirm subscription/account becomes `cancelled` and addons are cancelled.
- Replay the same webhook and confirm no duplicate addon rows are created.

## Scenario 10: Account Status

- Mark an account `suspended`.
- Login as owner.
- Confirm billing page is reachable.
- Confirm business module routes redirect to billing or return 403 for JSON.
- Mark an account `cancelled`.
- Confirm business modules remain blocked and data is not deleted.

## Scenario 11: Backfill

- Run `php artisan saas:backfill-account --owner_user_id=USER_ID --dry-run`.
- Confirm counts look correct.
- Run without `--dry-run`.
- Run it again.
- Confirm second run updates 0 rows and does not overwrite non-null `account_id`.
