# Staging User-Flow Test Checklist

Use test data from:

```bash
php artisan saas:create-staging-test-data
```

Default staging password for generated users:

```text
Password123!
```

## Test Users

| Role | Email |
| --- | --- |
| Super Admin | admin@resisquare.test |
| Landlord Owner | landlord.owner@resisquare.test |
| Estate Agent Owner | estate.owner@resisquare.test |
| Estate Agent Staff | estate.staff@resisquare.test |
| Landlord Contact | landlord.contact@resisquare.test |
| Tenant | tenant@resisquare.test |
| Contractor | contractor@resisquare.test |
| Property Manager | property.manager@resisquare.test |

## Flow 1: Super Admin SaaS Setup

- [ ] Login as Super Admin.
- [ ] Open SaaS Management.
- [ ] Create, edit, and deactivate a plan.
- [ ] Create, edit, and deactivate an addon.
- [ ] View accounts.
- [ ] View subscriptions.
- [ ] Login as a non-Super Admin and try `/admin/saas/plans`.
- [ ] Expected: Super Admin succeeds; non-Super Admin gets 403.

## Flow 2: Dynamic Pricing

- [ ] Open `/pricing`.
- [ ] Confirm active plans appear.
- [ ] Confirm inactive plans are hidden.
- [ ] Toggle monthly and annual.
- [ ] Click register on Landlord Basic.
- [ ] Click register on Estate Agent Company.
- [ ] Expected: correct `plan_id`, `billing_cycle`, and `account_type` reach the registration page.

## Flow 3: Landlord Registration And Approval

- [ ] Select Landlord Basic.
- [ ] Register.
- [ ] Verify OTP.
- [ ] Approve registration.
- [ ] Confirm user, account, owner `account_users` row, and trial subscription are created.
- [ ] Confirm landlord cannot create company, branch, or staff.

## Flow 4: Estate-Agent Registration And Approval

- [ ] Select Estate Agent Company annual.
- [ ] Register.
- [ ] Verify OTP.
- [ ] Approve registration.
- [ ] Confirm user and account are created.
- [ ] Confirm company is created or linked.
- [ ] Confirm subscription is created.
- [ ] Confirm one branch and one staff user can be created.

## Flow 5: Data Isolation

- [ ] Login as `landlord.owner@resisquare.test`.
- [ ] Confirm only landlord account properties are visible.
- [ ] Try a direct URL to `STG-EST-001`.
- [ ] Confirm 403.
- [ ] Login as `estate.owner@resisquare.test`.
- [ ] Confirm only estate-agent account data is visible.
- [ ] Login as Super Admin.
- [ ] Confirm both accounts are visible.

## Flow 6: Plan Limits

- [ ] Confirm Landlord Basic property limit is 1.
- [ ] Try to create a second landlord property.
- [ ] Confirm blocked.
- [ ] Add Extra Property addon manually or through billing.
- [ ] Try second property again.
- [ ] Confirm allowed.

## Flow 7: Branch And Staff Limits

- [ ] Confirm Estate Agent Company branch limit is 1 and staff limit is 1.
- [ ] Try to create a second branch.
- [ ] Confirm blocked.
- [ ] Try to create a second staff user.
- [ ] Confirm blocked.
- [ ] Add Extra Staff addon.
- [ ] Try second staff user again.
- [ ] Confirm allowed.

## Flow 8: Stripe Subscription

- [ ] Open Billing and Plan.
- [ ] Click Activate Subscription.
- [ ] Complete Stripe checkout in test mode.
- [ ] Trigger webhook.
- [ ] Confirm local subscription status updates.
- [ ] Confirm Stripe subscription id is stored.
- [ ] Simulate payment failed.
- [ ] Confirm account/subscription becomes `past_due`.
- [ ] Simulate cancellation.
- [ ] Confirm account/subscription becomes `cancelled`.

## Flow 9: Addon Purchase

- [ ] Use an account with an active Stripe subscription.
- [ ] Buy Extra Property addon.
- [ ] Confirm Stripe subscription item is created.
- [ ] Confirm local `account_subscription_addons` row is created.
- [ ] Confirm property limit increases.

## Flow 10: Portal Access

- [ ] Login as `landlord.contact@resisquare.test`.
- [ ] Confirm only `STG-LAND-001` is visible.
- [ ] Try direct URL to `STG-EST-001`.
- [ ] Confirm 403.
- [ ] Confirm finance and document flags control visibility.
- [ ] Login as `tenant@resisquare.test`.
- [ ] Confirm only assigned property/tenancy is visible and finance is hidden unless allowed.
- [ ] Login as `contractor@resisquare.test`.
- [ ] Confirm only assigned repair/work order is visible.
- [ ] Login as `property.manager@resisquare.test`.
- [ ] Confirm only assigned properties are visible.

## Flow 11: Accounting Isolation

- [ ] Create sale invoice under Account A.
- [ ] Create sale invoice under Account B.
- [ ] Login as Account A user.
- [ ] Confirm only Account A invoices are visible.
- [ ] Try direct URL to Account B invoice.
- [ ] Confirm 403.

## Flow 12: File And Document Security

- [ ] Upload document under Account A property.
- [ ] Login as Account B user.
- [ ] Try document URL/view.
- [ ] Confirm denied.
- [ ] Login as a portal user.
- [ ] Confirm only portal-visible assigned documents are accessible.

## Route Audit

Run:

```bash
php artisan route:list
```

Review:

- [ ] `/pricing`
- [ ] `/register`
- [ ] `/admin/saas/*`
- [ ] `/admin/billing/*`
- [ ] `/stripe/webhook`
- [ ] `/admin/properties/*`
- [ ] `/admin/users/*`
- [ ] `/admin/tenancies/*`
- [ ] `/admin/property-repairs/*`
- [ ] `/admin/accounting/*`
- [ ] `/contractor/*`

## Backfill Verification

```bash
php artisan saas:backfill-account --owner_user_id=1 --dry-run
php artisan saas:backfill-account --owner_user_id=1
php artisan saas:backfill-account --owner_user_id=1 --dry-run
```

Expected:

- [ ] Dry run prints counts.
- [ ] Real run does not overwrite non-null `account_id`.
- [ ] Second dry run shows no unexpected remaining rows.
