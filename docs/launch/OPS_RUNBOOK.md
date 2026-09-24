# Ops runbook (Step 46)

**Done when:** On-call can detect and recover without guessing.  
**Assert:** `php artisan launch:assert-ops --env=staging` (and `--env=production` before GA).  
**Companion:** `docs/launch/PRODUCTION_MAIL_AND_STRIPE.md`, `docs/saas-production-checklist.md`.

---

## 1. Queue workers

### Config

| Variable | Staging / production |
|---|---|
| `QUEUE_CONNECTION` | `database`, `redis`, or `sqs` — **never** `sync` |
| Workers | Process manager (Supervisor / systemd / Forge / Cloud Run jobs) |
| Restart after deploy | `php artisan queue:restart` |

### Processes to run

```bash
# Default + notifications
php artisan queue:work --tries=3 --timeout=120 --max-time=3600

# Named repair queues (if used in this deploy)
php artisan queue:work --queue=repair-quotes,repair-assignments --tries=3 --timeout=120 --max-time=3600
```

### Health signals

| Signal | How to check | Action |
|---|---|---|
| Queue depth | `SELECT COUNT(*) FROM jobs` or `queue:monitor` | Scale workers / clear poison job |
| Failed jobs | `php artisan queue:failed` | Inspect payload; `queue:retry {id}` or fix then retry |
| OTP / invite delay | ESP + `failed_jobs` + mailer logs | Restart workers; check `MAIL_*` |

Alert when `jobs` depth &gt; 500 for 10+ minutes, or `failed_jobs` grows steadily.

---

## 2. Scheduler (cron)

Host cron (or platform scheduler):

```text
* * * * * cd /var/www/resisquare && php artisan schedule:run >> /dev/null 2>&1
```

### Expected entries (from `bootstrap/app.php`)

| Command | Cadence | Purpose |
|---|---|---|
| `sale-invoices:generate-recurring` | daily 00:05 | Agency sale invoices (OUT of landlord MVP; keep if shared deploy) |
| `rent-invoices:generate-recurring` | daily 00:15 | Landlord rent invoices |
| `crm-notifications:send-due` | every minute | Due CRM emails / in-app |
| `notifications:retry` | every 5 min | Retry failed notification logs |
| `events:send-reminders` | every minute | Calendar reminders |
| `sale-invoices:apply-penalties` | daily 00:10 | Overdue penalties |

Verify: `php artisan schedule:list` and that `schedule:run` is actually invoked by cron (check host logs).

---

## 3. HTTP health

- Laravel health: `GET /up` (framework).
- Synthetic checks (every 1–5 min from uptime provider): `/up`, login page 200, Stripe webhook endpoints reject unsigned posts with 4xx (not 500).

---

## 4. Backups and restore drill

1. **Nightly DB backup** (provider snapshot or `mysqldump` / managed backup) retained ≥ 14 days.
2. **Object storage** (uploads) versioned or separately backed up.
3. **Quarterly restore drill** (and once before GA):
   - Restore backup to a disposable DB.
   - `php artisan migrate --force` if needed.
   - Log in as Super Admin; open one landlord account’s property + rent ledger.
   - Confirm a known upload downloads.
4. Record date, duration, operator, and any gaps in the UAT sheet.

---

## 5. Monitoring and alerts

Minimum alert set:

| Alert | Source | Severity |
|---|---|---|
| HTTP 5xx rate spike | Load balancer / APM | P1 |
| `/up` down | Uptime robot | P1 |
| Stripe webhook 4xx/5xx | Stripe Dashboard + app log `Stripe webhook` | P1 (money) |
| Queue depth / failed_jobs | Cron + DB metric or log shipper | P2 |
| Disk / DB storage | Host | P2 |
| Scheduler silence (no `schedule:run` heartbeats) | Cron log age | P2 |

Log queries for money incidents:

- `Stripe webhook processing failed`
- `Rent Stripe webhook processing failed`
- `Stripe subscription payment failed; account marked past_due`

---

## 6. Super Admin runbook

### Allowed

- Plans / add-ons / platform billing catalogue
- Account list → status change (suspend / reinstate) with reason
- Login-as customer (audited) for support; leave when done
- SMTP / business settings (not landlord-facing)
- UI Lab (`/admin/ui-lab`) — internal only
- `privacy:dsar-delete` for erasure requests (dry-run first)

### Never

- Add Super Admin to `account_users`
- Edit `.env` via the app UI in production (kill switches)
- Manually invent Stripe subscription IDs in DB without Dashboard confirmation
- Promise agency GL / TDS API features (OUT of scope)

### Incident cheat sheet

| Symptom | First 5 minutes |
|---|---|
| Site down | `/up`, host status, last deploy |
| Nobody gets email | Workers up? `QUEUE_*`? ESP? SPF |
| Rent paid in Stripe, unpaid in app | Rent webhook secret / URL mix-up with operating |
| Landlord locked out | Account `suspended`/`cancelled` → Billing still open; reinstate if payment cleared |
| Mass 403 after deploy | Route cache / middleware; known past bug: duplicate `admin/dashboard` without `account.status` |

---

## 7. Pre-GA ops checklist

- [ ] `php artisan launch:assert-ops --env=production`
- [ ] `php artisan launch:assert-money-config --env=production`
- [ ] Supervisor (or equivalent) running both worker lines
- [ ] Cron `schedule:run` verified in last hour
- [ ] Backup job green last 24h; restore drill dated
- [ ] Uptime + 5xx + webhook alerts paging a human
- [ ] Super Admin break-glass credentials in password manager (2 people)
