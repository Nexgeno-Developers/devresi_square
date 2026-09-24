# Automated proof (Step 47)

**Status:** Suite green on 23 Sep 2026 (local)  
**Done when:** Feature tests cover Happy Paths + Money Path + IDOR matrix; CI green on main; no known flaky skips on launch-critical tests.

## Launch-critical filter

```bash
php artisan test --filter="InScopeIdorMatrixHttpTest|TenantPortalHttpTest|TenantRentPaymentHttpTest|LandlordFinanceHttpTest|SaasBillingAndLimitsHttpTest|LaunchKillSwitchesHttpTest|LaunchMoneyConfigHttpTest|LaunchOpsConfigHttpTest|WorkspaceIsolationHttpTest|StoredXssHttpTest|SecureUploadHttpTest|RoleRbacHttpTest|TenancyDetailsConfirmationHttpTest|SignupTrialFirstHomeHttpTest"
```

**Last run:** 75 passed (365 assertions), ~20s.

## Coverage map

| Acceptance area | Primary tests |
|---|---|
| A Landlord Happy Path (signup → trial → home) | `SignupTrialFirstHomeHttpTest` |
| B Tenant Happy Path | `TenantPortalHttpTest`, `TenancyDetailsConfirmationHttpTest` |
| C Money Path | `LandlordFinanceHttpTest`, `TenantRentPaymentHttpTest`, `SaasBillingAndLimitsHttpTest`, `LaunchMoneyConfigHttpTest` |
| D Failure / isolation | `InScopeIdorMatrixHttpTest`, `WorkspaceIsolationHttpTest`, `StoredXssHttpTest`, `SecureUploadHttpTest`, `RoleRbacHttpTest`, `LaunchKillSwitchesHttpTest` |
| Ops readiness | `LaunchOpsConfigHttpTest` |

## CI

See `docs/launch/CI.md` and `.github/workflows/ci.yml`.  
**Freeze rule:** Do not merge to main with failing launch-critical filter. Prefer fixing or quarantining with an owner + date over skip.

## Flaky policy

| Rule | Action |
|---|---|
| Flake reproduced twice | File issue; stabilize before GA |
| Skip without owner | Not allowed on launch-critical list |
| New IN feature | Add Feature test before GA |

## Sign-off

| Check | Date | Result |
|---|---|---|
| Launch-critical filter green locally | 23 Sep 2026 | PASS 75/75 |
| CI green on main | | |
| No open flaky skips on above list | 23 Sep 2026 | None known |
