# Human UAT pack (Step 48)

**Done when:** 3–5 external landlords + their tenants run acceptance scripts on staging; zero open S0/S1; scripts signed off.  
**Scripts:** `docs/launch/ACCEPTANCE_SCRIPTS.md`  
**Hosts:** Local `http://127.0.0.1:8000` · commit `fa775c1` · 15 Oct 2026 · operator: browser UAT on staging seed accounts

## Participants

| # | Landlord | Tenant(s) | Device | Operator | Dates |
|---|---|---|---|---|---|
| 1 | landlord.owner@resisquare.test | tenant@resisquare.test | Desktop 960px and phone 390px | Browser UAT | 15 Oct 2026 |
| 2 | | | | | |
| 3 | | | | | |
| 4 | | | | | |
| 5 | | | | | |

## Defect log

Severity: **S0** launch block (data leak, money wrong, auth break) · **S1** launch block (IN path dead) · **S2** ship with owner+date · **S3** later.

| ID | Sev | Script step | Repro | Owner | Status | Target |
|---|---|---|---|---|---|---|
| UAT-01 | S0 | Platform | Signed-out `GET /migrate` returns `INFO Nothing to migrate.` Migrations run with no login. | Fixed 15 Oct 2026. Public URL removed. `php artisan migrate` applies pending schema only and does not seed. | Fixed | Before any public host |
| UAT-02 | S1 | A6 / People | People lists Chris Contact, Tina Tenant, Carl Contractor, and Priya Manager, each with Resend invite and Revoke. Dashboard people count is 2. | Fixed. People and the dashboard count are active tenants. Resend and revoke refuse anyone else. | Fixed | |
| UAT-03 | S2 | A5 / B2 | Tenancy 1 Jan 2026–31 Dec 2026 shows term `0 months, 0 days` and move-out `2026-12-31 00:00:00`. Tenant confirm says Term (months) Not set. | Fixed. Term is taken from the dates when months were not saved. Move-out is a written date. | Fixed | |
| UAT-04 | S2 | A3 / B2 | Property and repair titles prefix the numeric id: `1 Staging Landlord Street`. | Fixed. Repair headings use the property name when one is set. A house number in the street line is left as the address. | Fixed | |
| UAT-05 | S2 | A3 | At 960px the property stat cards clip to `Need your at...` and `LET STATUS`. | Fixed. Stat labels wrap, and the certificate card says Certificates. | Fixed | |
| UAT-06 | S2 | E2 | Contractor job 1 history says `Another offer was accepted` while Send offer is still on the page. Street and lockbox stay hidden. | Fixed. A still-open offer no longer shows that line, and the quote form hides once another contractor is chosen. Street stays hidden until the landlord commits. | Fixed | |
| UAT-07 | S2 | B8 | Tenant profile still shows `Audit Disposable House` and three copies of the STG-REP-001 inspection notice. The invoice notice uses `01/11/2026` and `ResiSquare`. | Fixed. Notices stay on the tenant's own lets, duplicates collapse, dates read 1 Nov 2026, and the name is Resisquare. | Fixed | |
| UAT-08 | S2 | Super admin | Accounts list shows blank names, ISO dates, and a plan filter that repeats Billing Limit and Money Guard. Nav is the agency shell, including Clear Cache. Page title is `Resisquare`. | Fixed. Names fall back, dates are written, plan names are unique, Clear Cache is gone, and the accounts tab title is Accounts. Repair desk is under SaaS Management. | Fixed | |
| UAT-09 | S3 | D | Other-account property and tenancy return a branded 403. Finance says `This action is unauthorized.` A tenant opening another invoice gets 404. Contractor 403 `Back to dashboard` points at `/admin/dashboard`, which 403s again. | Fixed. The generic unauthorized line uses the branded title. Back goes to the contractor jobs list, the tenant home, or the landlord dashboard. | Fixed | |
| UAT-10 | S3 | A10 | Dashboard says 3 certificates are expired or due within 60 days. The certificates page shows Gas, EPC, and EICR as missing, not expired. | Fixed. The banner says missing, expired, or due within 60 days. | Fixed | |
| UAT-11 | S3 | Auth | `GET /admin/logout` opens the old `Login \| Resisquare` page. The account-menu logout posts to `/logout` and returns the branded sign-in. | Fixed. `/admin/logout` now returns the branded sign-in. | Fixed | |

## Script sign-off (per participant)

Copy one block per landlord/tenant pair.

### Participant 1

| Script | Pass? | Notes | Initials | Date |
|---|---|---|---|---|
| A Landlord Happy Path | Partial | Portfolio, tenancy, invoice RENT-0001, repair, billing trial, and certificates render. Term length, people actions, and certificate banner copy fail. Card pay and a new calendar visit were not completed. | | 15 Oct 2026 |
| B Tenant Happy Path | Partial | Home, confirm details, rent with Pay, maintenance, empty documents, calendar empty state, profile, and phone bottom tabs pass. Stale notices remain. | | 15 Oct 2026 |
| C Money Path | Partial | Invoice issued at £1,250.00. Pay button present. Stripe checkout, webhook, void, and past_due were not exercised. Bank details show as missing. | | 15 Oct 2026 |
| D Failure Path | Pass with notes | Cross-account property, tenancy, and finance are refused. See UAT-09 for the 404 and copy differences. | | 15 Oct 2026 |
| E Contractor marketplace | Partial | Approved contractor sees London ST1 only, with no street or lockbox, and is refused the landlord dashboard. History line contradicts the open offer form. | | 15 Oct 2026 |

**Open S0:** ___ · **Open S1:** ___ · **S2 with owners:** ___

## Exit criteria

- [ ] ≥ 3 landlords completed A–D
- [ ] Zero open S0 / S1
- [ ] Every S2 has owner + date
- [ ] Invite + rent emails observed in real inboxes (ties to Step 45)
- [ ] Phone (≤390px) used for at least one tenant Happy Path

**UAT lead sign-off:** _________________ date ________
