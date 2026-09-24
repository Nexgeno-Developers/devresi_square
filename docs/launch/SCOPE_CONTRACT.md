# Resisquare Launch Scope Contract

**Status:** Active (Step 1 complete)  
**Product:** Landlord–tenant SaaS (Resisquare)  
**Bar:** Functionality, integrity, and usability are equal launch gates. Nothing IN scope ships incomplete.

## Pillars (all required)

| Pillar | Meaning |
|---|---|
| **Integrity** | Isolation, authz, secrets, money correctness, installs, privacy ops |
| **Functionality** | Every IN workflow works end-to-end with no dead ends |
| **Usability** | One visual system, human titles, mobile tenant shell, empty/error states |

## IN scope (must be complete at GA)

### Landlord workspace
- Signup / OTP / trial / plan billing (Landlord Basic)
- First-home onboarding overlay
- Properties (portfolio, photos, short addresses)
- Tenancies (create, day-2 edit, show page, details confirmation / corrections)
- Finance (invoices, void, manual pay, tenant card pay reflected, recurring via scheduler)
- Bank details / rent pay configuration
- Repairs (raise, triage, photos, landlord-visible status)
- Documents (named uploads, portal visibility, authz downloads)
- Compliance (EPC / Gas / EICR capture, expiry, reminders, served-to-tenant evidence)
- Deposit protection **workflow** (manual: scheme, reference, dates, checklist, docs, reminders)
- People (owners, tenants, portal invite / resend / revoke)
- Calendar / visits + reminders
- Notifications (email + in-app) for IN events
- Settings / Billing & Plan (landlord self-serve only)

### Tenant portal
- Home, My Tenancy (confirm / correction), Rent (list / detail / pay), Maintenance (photo-first), Documents, Calendar, Profile + notification prefs
- Mobile shell (bottom tabs) on phone widths
- No staff / agency chrome or copy

### Platform
- Dual Stripe (operating subscriptions + client-money rent)
- Production mail, queues, scheduler
- Super Admin ops for plans/accounts (not landlord-facing)

## OUT of scope (not marketed, not required for GA)

- Agency double-entry accounting / GL / client-money ledgers as a product
- Sales / offers CRM
- Branches, staff designations, company profile as landlord features
- Contractor marketplace
- Auto-generated AST / How to Rent packs via third-party APIs
- TDS / DPS / MyDeposits **API** integrations (manual deposit workflow remains IN)
- AML / KYC productisation for estate agents
- Public marketing site beyond thin existing pages (can improve later)

## Stop-ship rules

Launch waits if any IN route is broken, leaks accounts, mis-applies money, shows Debugbar/staging copy, or still uses agency Bootstrap as the primary UI.

## Change control

Scope changes require updating this file and the acceptance scripts before work starts.
