# Retention & deletion (engineering defaults)

**Status:** Operational stub for Launch Step 15. Legal must confirm before GA.  
**Command:** `php artisan privacy:dsar-delete {userId} --account={id} --dry-run`

| Record class | Default retention after account/tenancy end | Deletion / anonymisation |
|---|---|---|
| Auth sessions / OTP | 30 days | Auto-expire |
| Support notes | 24 months | Soft-delete then purge job |
| Rent invoices / payments | 7 years (accounting) | Archive; do not hard-delete casually |
| Identity documents | End of tenancy + 12 months (unless dispute) | Secure delete via DSAR job |
| Soft-deleted properties | 90 days then eligible for force-delete | Admin force-delete after review |
| Backups | Provider window (document in runbook) | Follow backup expiry |

## DSAR deletion path (implemented stub)

1. Super Admin / privacy operator runs `privacy:dsar-delete`.
2. Dry-run lists affected tables.
3. Live run anonymises PII on `users` (name/email/phone) and marks `status` inactive; detaches portal login flags on `tenant_members` / `account_users`.
4. Financial rows are **retained** with user FK nulled or anonymised reference where schema allows — never silently destroy ledgers.
5. Outcomes append to `privacy_breach_logs` only for incidents; DSAR actions log to `storage/logs/privacy-dsar.log`.

## Breach log

Table `privacy_breach_logs` stores incident stubs (discovered_at, summary, severity, notified_at). Not a full IR platform — evidence scaffold for Step 15.
